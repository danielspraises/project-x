<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionBillingConfig;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    /**
     * Roles common to every institution, regardless of level.
     */
    private const SHARED_ROLES = [
        'ict_admin' => [
            'name' => 'ICT Admin',
            'permissions' => [
                'users.manage', 'users.view', 'users.delete', 'roles.manage',
                'institution.setup', 'institution.view',
                'billing.request_change', 'billing.view',
                'students.manage', 'courses.manage', 'registrations.assign', 'subjects.manage', 'subject_registrations.assign',
                'results.approve', 'results.publish', 'reports.generate', 'audit.view',
                'subjects.manage', 'subject_registrations.assign',
                'notes.create', 'notes.view',
            ],
        ],
        'auditor' => ['name' => 'Auditor', 'permissions' => ['institution.view', 'audit.view', 'reports.generate', 'notes.create', 'notes.view']],
    ];

    /**
     * Tertiary-only roles — these map to Faculty/Department/Programme structure,
     * which doesn't exist for secondary/primary schools.
     */
    private const TERTIARY_ROLES = [
        'faculty_officer' => ['name' => 'Faculty Officer', 'permissions' => ['results.approve', 'reports.generate']],
        'department_officer' => ['name' => 'Department Officer', 'permissions' => ['students.manage', 'courses.manage', 'registrations.assign', 'reports.generate']],
        'hod' => ['name' => 'HOD', 'permissions' => ['students.manage', 'results.approve', 'reports.generate', 'notes.create', 'notes.view']],
        'lecturer' => ['name' => 'Lecturer', 'permissions' => ['results.enter']],
    ];

    /**
     * Secondary/primary-only roles — scoped to Class/Arm instead of Department.
     */
    private const BASIC_EDUCATION_ROLES = [
        'class_teacher' => ['name' => 'Class Teacher', 'permissions' => ['students.manage', 'results.approve', 'results.enter', 'reports.generate', 'notes.create', 'notes.view', 'subject_registrations.assign']],
        'subject_teacher' => ['name' => 'Subject Teacher', 'permissions' => ['results.enter']],
    ];

    private function defaultRolesFor(string $educationLevel): array
    {
        return $educationLevel === 'tertiary'
            ? array_merge(self::SHARED_ROLES, self::TERTIARY_ROLES)
            : array_merge(self::SHARED_ROLES, self::BASIC_EDUCATION_ROLES);
    }

    public function index(): View
    {
        $institutions = Institution::with('billingConfig')->latest()->get();

        return view('admin.institutions.index', compact('institutions'));
    }

    public function create(): View
    {
        return view('admin.institutions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'education_level' => ['required', 'in:tertiary,secondary,primary'],
            'type' => ['required', Rule::in($this->allowedTypesFor($request->input('education_level')))],
            'ownership' => ['required', 'in:federal,state,private'],

            'ict_admin_name' => ['required', 'string', 'max:255'],
            'ict_admin_email' => ['required', 'email', 'max:255'],

            'billing_type' => ['required', 'in:monthly,annual,per_student'],
            'rate_amount' => ['nullable', 'numeric', 'min:0', 'required_unless:billing_type,per_student'],
            'per_student_rate' => ['nullable', 'numeric', 'min:0', 'required_if:billing_type,per_student'],
            'currency' => ['required', 'string', 'max:10'],
            'billing_anniversary_date' => ['required', 'date'],
            'contract_start_date' => ['required', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],
            'notes' => ['nullable', 'string'],
        ]);

        $generatedPassword = Str::password(12);

        DB::transaction(function () use ($validated, $generatedPassword) {
            $institution = Institution::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
                'type' => $validated['type'],
                'education_level' => $validated['education_level'],
                'ownership' => $validated['ownership'],
                'status' => 'trial',
            ]);

            InstitutionBillingConfig::create([
                'institution_id' => $institution->id,
                'billing_type' => $validated['billing_type'],
                'rate_amount' => $validated['rate_amount'] ?? null,
                'per_student_rate' => $validated['per_student_rate'] ?? null,
                'currency' => $validated['currency'],
                'billing_anniversary_date' => $validated['billing_anniversary_date'],
                'contract_start_date' => $validated['contract_start_date'],
                'contract_end_date' => $validated['contract_end_date'] ?? null,
                'status' => 'trial',
                'notes' => $validated['notes'] ?? null,
            ]);

            $roleIds = [];
            foreach ($this->defaultRolesFor($validated['education_level']) as $slug => $definition) {
                $role = Role::create(['institution_id' => $institution->id, 'name' => $definition['name'], 'slug' => $slug]);
                $role->permissions()->attach(Permission::whereIn('slug', $definition['permissions'])->pluck('id'));
                $roleIds[$slug] = $role->id;
            }

            User::create([
                'institution_id' => $institution->id,
                'role_id' => $roleIds['ict_admin'],
                'name' => $validated['ict_admin_name'],
                'email' => $validated['ict_admin_email'],
                'password' => Hash::make($generatedPassword),
                'status' => 'active',
                'is_primary' => true, // this is THE original ICT Admin — only Super Admin can ever remove them
                'email_verified_at' => now(), // admin-created, not self-registered — no verification email needed
            ]);
        });

        return redirect()
            ->route('admin.institutions.index')
            ->with('generated_password', $generatedPassword)
            ->with('success', 'Institution created successfully.');
    }

    /**
     * The full management screen: details, branding, billing, and users —
     * everything the Super Admin has override rights over, in one place.
     */
    public function edit(Institution $institution): View
    {
        $institution->load('billingConfig', 'users.role');

        $pendingBillingRequests = \App\Models\BillingChangeRequest::where('institution_id', $institution->id)
            ->where('status', 'pending')
            ->with('requestedBy')
            ->latest()
            ->get();

        $features = \App\Models\Feature::orderBy('category')->orderBy('name')->get();
        $enabledFeatureIds = $institution->features()->wherePivot('enabled', true)->pluck('features.id')->toArray();

        return view('admin.institutions.edit', [
            'institution' => $institution,
            'branding' => $institution->branding(),
            'pendingBillingRequests' => $pendingBillingRequests,
            'features' => $features,
            'enabledFeatureIds' => $enabledFeatureIds,
        ]);
    }

    /**
     * Feature toggles are their own form/submit, separate from the main institution
     * details form — keeps a checkbox misclick from accidentally being bundled into an
     * unrelated "Update Institution" submit, and keeps this list of changes self-contained.
     */
    public function updateFeatures(Request $request, Institution $institution): RedirectResponse
    {
        $validated = $request->validate([
            'feature_ids' => ['array'],
            'feature_ids.*' => ['exists:features,id'],
        ]);

        $enabledIds = $validated['feature_ids'] ?? [];
        $allFeatureIds = \App\Models\Feature::pluck('id');

        $syncData = [];
        foreach ($allFeatureIds as $featureId) {
            $syncData[$featureId] = [
                'enabled' => in_array($featureId, $enabledIds),
                'changed_by' => Auth::id(),
                'changed_at' => now(),
            ];
        }

        $institution->features()->sync($syncData);

        return redirect()->route('admin.institutions.edit', $institution)->with('success', 'Feature access updated.');
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($this->allowedTypesFor($institution->education_level))],
            'ownership' => ['required', 'in:federal,state,private'],
            'status' => ['required', 'in:trial,active,suspended,cancelled'],

            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'light_primary' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'light_secondary' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'dark_primary' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'dark_secondary' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'light_primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'light_secondary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'dark_primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'dark_secondary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'billing_type' => ['required', 'in:monthly,annual,per_student'],
            'rate_amount' => ['nullable', 'numeric', 'min:0', 'required_unless:billing_type,per_student'],
            'per_student_rate' => ['nullable', 'numeric', 'min:0', 'required_if:billing_type,per_student'],
            'currency' => ['required', 'string', 'max:10'],
            'billing_anniversary_date' => ['required', 'date'],
            'contract_start_date' => ['required', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],
            'billing_status' => ['required', 'in:trial,active,suspended,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $institution) {
            $institution->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'ownership' => $validated['ownership'],
                'status' => $validated['status'],
            ]);

            $institution->setSetting('branding', [
                'primary_color' => $validated['primary_color'],
                'secondary_color' => $validated['secondary_color'] ?? null,
                'light_primary' => $validated['light_primary'],
                'light_secondary' => $validated['light_secondary'],
                'dark_primary' => $validated['dark_primary'],
                'dark_secondary' => $validated['dark_secondary'],
            ]);

            $institution->billingConfig()->updateOrCreate(
                ['institution_id' => $institution->id],
                [
                    'billing_type' => $validated['billing_type'],
                    'rate_amount' => $validated['rate_amount'] ?? null,
                    'per_student_rate' => $validated['per_student_rate'] ?? null,
                    'currency' => $validated['currency'],
                    'billing_anniversary_date' => $validated['billing_anniversary_date'],
                    'contract_start_date' => $validated['contract_start_date'],
                    'contract_end_date' => $validated['contract_end_date'] ?? null,
                    'status' => $validated['billing_status'],
                    'notes' => $validated['notes'] ?? null,
                ]
            );
        });

        return redirect()
            ->route('admin.institutions.edit', $institution)
            ->with('success', 'Institution updated successfully.');
    }

    /**
     * Reset the password of any user belonging to this institution — a Super Admin
     * support/override capability, separate from that user's own password change.
     */
    public function resetUserPassword(Institution $institution, User $user): RedirectResponse
    {
        abort_if($user->institution_id !== $institution->id, 404);

        $generatedPassword = Str::password(12);

        $user->update(['password' => Hash::make($generatedPassword)]);

        return redirect()
            ->route('admin.institutions.edit', $institution)
            ->with('reset_password_for', $user->name)
            ->with('generated_password', $generatedPassword);
    }

    /**
     * Delete any user belonging to this institution. Super Admin only — this is the ONLY
     * place in the entire platform where a user account can be deleted. ICT Admins can
     * create, edit, and suspend users, but never delete them.
     */
    public function destroyUser(Institution $institution, User $user): RedirectResponse
    {
        abort_if($user->institution_id !== $institution->id, 404);

        $user->delete();

        return redirect()
            ->route('admin.institutions.edit', $institution)
            ->with('success', "{$user->name}'s account was deleted.");
    }

    /**
     * Permanently delete an entire institution — Super Admin only, and deliberately
     * the most destructive action on the whole platform. Every related record (users,
     * roles, faculties, departments, students, billing, everything) cascades away via
     * the foreign key constraints already set up on each of those tables.
     */
    public function destroy(Institution $institution): RedirectResponse
    {
        $name = $institution->name;
        $id = $institution->id;

        DB::transaction(function () use ($institution, $id) {
            // Several tables reference `users` (notes, billing change requests/audit,
            // course registrations/offerings), and `users` itself references `roles` —
            // so those have to be cleared before users, and users before roles, or
            // MySQL blocks the cascade partway through. Everything else (faculties,
            // departments, students, institution_settings, billing config, and roles
            // themselves) is cleaned up automatically by cascadeOnDelete once we
            // finally delete the institution row below.
            foreach (['notes', 'billing_change_requests', 'billing_change_audit', 'course_registrations', 'course_offerings'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->where('institution_id', $id)->delete();
                }
            }

            DB::table('users')->where('institution_id', $id)->delete();

            $institution->delete();
        });

        return redirect()
            ->route('admin.institutions.index')
            ->with('success', "\"{$name}\" and all of its data has been permanently deleted.");
    }

    /**
     * Add an ICT Admin to an institution — Super Admin exclusively. This is the only
     * path by which an ICT Admin account can ever be created or added, anywhere on
     * the platform (the initial one is seeded in store() above; this covers adding a
     * replacement or backup afterward — an ICT Admin can never create one of their own).
     */
    public function addIctAdmin(Request $request, Institution $institution): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users')->where(fn ($query) => $query->where('institution_id', $institution->id)),
            ],
        ]);

        $ictAdminRole = Role::where('institution_id', $institution->id)->where('slug', 'ict_admin')->firstOrFail();

        $generatedPassword = Str::password(12);

        User::create([
            'institution_id' => $institution->id,
            'role_id' => $ictAdminRole->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($generatedPassword),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // TODO: notify the institution's existing ICT Admin(s) that a new one was added —
        // deferred until the in-house messaging module exists, rather than building a
        // one-off notification mechanism that would just get replaced later.

        return redirect()
            ->route('admin.institutions.edit', $institution)
            ->with('generated_password', $generatedPassword)
            ->with('success', 'ICT Admin added.');
    }

    /**
     * Approve a billing change request — applies the change to the actual billing
     * config immediately, then marks the request resolved.
     */
    public function approveBillingRequest(Institution $institution, \App\Models\BillingChangeRequest $billingRequest): RedirectResponse
    {
        abort_if($billingRequest->institution_id !== $institution->id, 404);
        abort_unless($billingRequest->status === 'pending', 422, 'This request has already been resolved.');

        abort_unless(
            in_array($billingRequest->requested_value, ['monthly', 'annual', 'per_student']),
            422,
            'Invalid billing cycle requested.'
        );

        // The requested commencement date becomes the new billing anniversary —
        // that's what actually makes "when this change takes effect" mean something.
        $institution->billingConfig->update([
            'billing_type' => $billingRequest->requested_value,
            'billing_anniversary_date' => $billingRequest->effective_date ?? $institution->billingConfig->billing_anniversary_date,
        ]);

        $billingRequest->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.institutions.edit', $institution)->with('success', 'Billing change approved and applied.');
    }

    public function rejectBillingRequest(Institution $institution, \App\Models\BillingChangeRequest $billingRequest): RedirectResponse
    {
        abort_if($billingRequest->institution_id !== $institution->id, 404);
        abort_unless($billingRequest->status === 'pending', 422, 'This request has already been resolved.');

        $billingRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.institutions.edit', $institution)->with('success', 'Billing change rejected.');
    }

    private function allowedTypesFor(?string $educationLevel): array
    {
        return match ($educationLevel) {
            'tertiary' => ['university', 'polytechnic', 'college_of_education', 'monotechnic'],
            'secondary' => ['secondary_school'],
            'primary' => ['primary_school'],
            default => [],
        };
    }
}
