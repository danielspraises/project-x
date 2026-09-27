<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Role;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Role slugs that only make sense for basic-education institutions.
     */
    private const BASIC_ED_ONLY_ROLES = ['class_teacher', 'subject_teacher'];

    /**
     * Tertiary role slugs that are scoped to a single department and therefore
     * require one to be selected. Lecturer is deliberately excluded — that
     * role is scoped by course offering assignment, not department.
     */
    private const DEPARTMENT_SCOPED_ROLES = ['hod', 'department_officer'];

    public function index(): View
    {
        $this->ensureCanManageUsers();

        $isTertiary = (Auth::user()->institution->education_level ?? 'tertiary') === 'tertiary';

        $query = User::where('institution_id', Auth::user()->institution_id)
            ->with('role', 'department');

        if (! $isTertiary) {
            $query->whereHas('role', fn ($role) => $role->whereIn('slug', self::BASIC_ED_ONLY_ROLES));
        }

        $users = $query->latest()->get();

        return view('ict-admin.users.index', compact('users', 'isTertiary'));
    }

    public function create(): View
    {
        $this->ensureCanManageUsers();

        $isTertiary = (Auth::user()->institution->education_level ?? 'tertiary') === 'tertiary';

        // ICT Admin role is deliberately excluded — this institution's one ICT Admin
        // account was created by Super Admin at setup and can never be duplicated
        // or reassigned from here.
        $roles = Role::where('institution_id', Auth::user()->institution_id)
            ->where('slug', '!=', 'ict_admin')
            ->when($isTertiary, fn ($q) => $q->whereNotIn('slug', self::BASIC_ED_ONLY_ROLES))
            ->when(!$isTertiary, fn ($q) => $q->whereIn('slug', self::BASIC_ED_ONLY_ROLES))
            ->orderBy('name')
            ->get();

        $departments = $isTertiary
            ? Department::where('institution_id', Auth::user()->institution_id)->orderBy('name')->get()
            : collect();

        $departmentScopedRoleSlugs = self::DEPARTMENT_SCOPED_ROLES;

        return view('ict-admin.users.create', compact('roles', 'departments', 'isTertiary', 'departmentScopedRoleSlugs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManageUsers();

        $isTertiary = (Auth::user()->institution->education_level ?? 'tertiary') === 'tertiary';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['required', 'exists:roles,id'],
            'department_id' => $isTertiary
                ? ['nullable', 'exists:departments,id']
                : ['nullable'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        abort_if($role->institution_id !== Auth::user()->institution_id, 403);

        if (!$isTertiary) {
            abort_unless(
                in_array($role->slug, self::BASIC_ED_ONLY_ROLES, true),
                422,
                'Only teacher roles can be created here.'
            );
            $validated['department_id'] = null;
        } else {
            abort_if(
                in_array($role->slug, self::BASIC_ED_ONLY_ROLES, true),
                422,
                'Class Teacher and Subject Teacher roles are only available for basic education institutions.'
            );

            if (in_array($role->slug, self::DEPARTMENT_SCOPED_ROLES, true)) {
                if (empty($validated['department_id'])) {
                    throw ValidationException::withMessages([
                        'department_id' => 'A department is required for this role.',
                    ]);
                }
            } else {
                $validated['department_id'] = null;
            }
        }

        abort_if(
            $role->slug === 'ict_admin',
            403,
            'Only the platform Administrator can create an ICT Admin account.'
        );

        if ($isTertiary && !empty($validated['department_id'])) {
            $department = Department::findOrFail($validated['department_id']);
            abort_if($department->institution_id !== Auth::user()->institution_id, 403);
        }

        $generatedPassword = Str::password(12);

        $user = User::create([
            'institution_id' => Auth::user()->institution_id,
            'role_id' => $role->id,
            'department_id' => $validated['department_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($generatedPassword),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Lecturers have no course assigned yet at creation time — course
        // offerings are term-scoped, so that happens on the Edit page instead.
        if ($isTertiary && $user->hasPermission('results.enter')) {
            return redirect()
                ->route('ict-admin.users.edit', $user)
                ->with('generated_password', $generatedPassword)
                ->with('success', 'User created. Assign their courses below.');
        }

        return redirect()
            ->route('ict-admin.users.index')
            ->with('generated_password', $generatedPassword)
            ->with('success', 'User created.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureCanManageUsers();
        $this->ensureBelongsToInstitution($user);
        $this->ensureNotIctAdmin($user);

        $isTertiary = (Auth::user()->institution->education_level ?? 'tertiary') === 'tertiary';

        $roles = Role::where('institution_id', Auth::user()->institution_id)
            ->where('slug', '!=', 'ict_admin')
            ->when($isTertiary, fn ($q) => $q->whereNotIn('slug', self::BASIC_ED_ONLY_ROLES))
            ->when(!$isTertiary, fn ($q) => $q->whereIn('slug', self::BASIC_ED_ONLY_ROLES))
            ->orderBy('name')
            ->get();

        $departments = $isTertiary
            ? Department::where('institution_id', Auth::user()->institution_id)->orderBy('name')->get()
            : collect();

        $departmentScopedRoleSlugs = self::DEPARTMENT_SCOPED_ROLES;

        $courseAssignment = null;

        if ($isTertiary && $user->hasPermission('results.enter')) {
            $terms = Term::where('institution_id', Auth::user()->institution_id)
                ->orderByDesc('start_date')
                ->get();

            $selectedTermId = $request->integer('term_id') ?: $terms->first()?->id;

            $courseOfferingOptions = CourseOffering::where('institution_id', Auth::user()->institution_id)
                ->where('term_id', $selectedTermId)
                ->with('course')
                ->get()
                ->map(fn (CourseOffering $offering) => [
                    'value' => $offering->id,
                    'label' => trim((optional($offering->course)->code ?? 'Unknown').' — '.(optional($offering->course)->title ?? '')),
                ])
                ->values();

            $assignedOfferingIds = CourseOffering::where('institution_id', Auth::user()->institution_id)
                ->where('term_id', $selectedTermId)
                ->where('lecturer_id', $user->id)
                ->pluck('id');

            $courseAssignment = [
                'terms' => $terms,
                'selectedTermId' => $selectedTermId,
                'options' => $courseOfferingOptions,
                'assignedIds' => $assignedOfferingIds,
            ];
        }

        return view('ict-admin.users.edit', compact(
            'user',
            'roles',
            'departments',
            'isTertiary',
            'departmentScopedRoleSlugs',
            'courseAssignment'
        ));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManageUsers();
        $this->ensureBelongsToInstitution($user);
        $this->ensureNotIctAdmin($user);

        $isTertiary = (Auth::user()->institution->education_level ?? 'tertiary') === 'tertiary';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['required', 'exists:roles,id'],
            'department_id' => $isTertiary
                ? ['nullable', 'exists:departments,id']
                : ['nullable'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        if ($user->id === Auth::id()) {
            $validated['role_id'] = $user->role_id;
            $validated['status'] = $user->status;
            $validated['department_id'] = $user->department_id;
        }

        $role = Role::findOrFail($validated['role_id']);
        abort_if($role->institution_id !== Auth::user()->institution_id, 403);

        if (!$isTertiary) {
            abort_unless(
                in_array($role->slug, self::BASIC_ED_ONLY_ROLES, true),
                422,
                'Only teacher roles can be assigned here.'
            );
            $validated['department_id'] = null;
        } else {
            abort_if(
                in_array($role->slug, self::BASIC_ED_ONLY_ROLES, true),
                422,
                'Class Teacher and Subject Teacher roles are only available for basic education institutions.'
            );

            if (in_array($role->slug, self::DEPARTMENT_SCOPED_ROLES, true)) {
                if (empty($validated['department_id'])) {
                    throw ValidationException::withMessages([
                        'department_id' => 'A department is required for this role.',
                    ]);
                }
            } else {
                $validated['department_id'] = null;
            }
        }

        abort_if(
            $role->slug === 'ict_admin',
            403,
            'Only the platform Administrator can assign the ICT Admin role.'
        );

        if ($isTertiary && !empty($validated['department_id'])) {
            $department = Department::findOrFail($validated['department_id']);
            abort_if($department->institution_id !== Auth::user()->institution_id, 403);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $role->id,
            'department_id' => $validated['department_id'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()->route('ict-admin.users.index')->with('success', 'User updated.');
    }

    /**
     * Assign/unassign this lecturer to course offerings for one term.
     * Kept as a separate action from update() since it operates on a
     * different table (course_offerings.lecturer_id) and is term-scoped.
     */
    public function updateCourseOfferings(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManageUsers();
        $this->ensureBelongsToInstitution($user);
        $this->ensureNotIctAdmin($user);

        abort_unless(
            $user->hasPermission('results.enter'),
            422,
            'Course assignment only applies to lecturer-type roles.'
        );

        $validated = $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'course_offering_ids' => ['nullable', 'array'],
            'course_offering_ids.*' => ['integer', 'exists:course_offerings,id'],
        ]);

        $term = Term::findOrFail($validated['term_id']);
        abort_if($term->institution_id !== Auth::user()->institution_id, 403);

        $selectedIds = collect($validated['course_offering_ids'] ?? [])->map(fn ($id) => (int) $id);

        DB::transaction(function () use ($user, $term, $selectedIds) {
            // Unassign anything in this term currently pointing at this user
            // that is no longer in the selected list.
            CourseOffering::where('institution_id', $user->institution_id)
                ->where('term_id', $term->id)
                ->where('lecturer_id', $user->id)
                ->whereNotIn('id', $selectedIds)
                ->update(['lecturer_id' => null]);

            // Assign the selected offerings to this user. This reassigns them
            // away from whoever else may currently hold them — deliberate,
            // since an ICT Admin doing this is a trusted correction action.
            if ($selectedIds->isNotEmpty()) {
                CourseOffering::where('institution_id', $user->institution_id)
                    ->where('term_id', $term->id)
                    ->whereIn('id', $selectedIds)
                    ->update(['lecturer_id' => $user->id]);
            }
        });

        return redirect()
            ->route('ict-admin.users.edit', ['user' => $user->id, 'term_id' => $term->id])
            ->with('success', 'Course assignments updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('users.delete'), 403);
        $this->ensureBelongsToInstitution($user);
        $this->ensureNotIctAdmin($user);

        abort_if($user->id === Auth::id(), 422, "You can't delete your own account.");

        $user->delete();

        return redirect()->route('ict-admin.users.index')->with('success', "{$user->name}'s account was deleted.");
    }

    private function ensureCanManageUsers(): void
    {
        abort_unless(Auth::user()->hasPermission('users.manage'), 403);
    }

    private function ensureBelongsToInstitution(User $user): void
    {
        abort_if($user->institution_id !== Auth::user()->institution_id, 404);
    }

    private function ensureNotIctAdmin(User $user): void
    {
        abort_if(
            $user->role?->slug === 'ict_admin',
            403,
            'The ICT Admin account can only be managed by the platform Administrator.'
        );
    }
}
