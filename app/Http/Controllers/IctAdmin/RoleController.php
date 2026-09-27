<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Permissions an ICT Admin is allowed to hand out to their own institution's roles.
     * Platform-level permissions (institutions.manage, billing.manage) are deliberately
     * excluded — those stay Super Admin only, no matter what an ICT Admin configures.
     */
    private const ASSIGNABLE_PERMISSION_SLUGS = [
        'institution.setup', 'institution.view',
        'users.manage', 'users.view', 'users.delete', 'roles.manage',
        'students.manage', 'courses.manage', 'registrations.assign',
        'results.enter', 'results.approve', 'results.publish', 'results.correct',
        'reports.generate', 'audit.view',
        'notes.create', 'notes.view',
        'billing.request_change', 'billing.view',
    ];

    public function index(): View
    {
        $this->ensureCanManageRoles();

        $roles = Role::where('institution_id', Auth::user()->institution_id)
            ->withCount('users')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return view('ict-admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->ensureCanManageRoles();

        $permissions = $this->assignablePermissions();

        return view('ict-admin.roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManageRoles();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $slug = \Illuminate\Support\Str::slug($validated['name'], '_');

        $role = Role::create([
            'institution_id' => Auth::user()->institution_id,
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        $this->syncAssignablePermissions($role, $validated['permissions'] ?? []);

        return redirect()->route('ict-admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $this->ensureCanManageRoles();
        $this->ensureBelongsToInstitution($role);
        $this->ensureNotIctAdminRole($role);

        $permissions = $this->assignablePermissions();
        $assignedIds = $role->permissions->pluck('id')->toArray();

        return view('ict-admin.roles.edit', compact('role', 'permissions', 'assignedIds'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->ensureCanManageRoles();
        $this->ensureBelongsToInstitution($role);
        $this->ensureNotIctAdminRole($role);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update(['name' => $validated['name']]);

        $this->syncAssignablePermissions($role, $validated['permissions'] ?? []);

        return redirect()->route('ict-admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->ensureCanManageRoles();
        $this->ensureBelongsToInstitution($role);
        $this->ensureNotIctAdminRole($role);

        abort_if($role->users()->exists(), 422, 'Cannot delete a role that still has users assigned to it.');

        $role->delete();

        return redirect()->route('ict-admin.roles.index')->with('success', 'Role deleted.');
    }

    private function ensureCanManageRoles(): void
    {
        abort_unless(Auth::user()->hasPermission('roles.manage'), 403);
    }

    private function ensureBelongsToInstitution(Role $role): void
    {
        abort_if($role->institution_id !== Auth::user()->institution_id, 404);
    }

    /**
     * The ICT Admin role — its name AND its permissions — can only be changed by
     * Super Admin. This is what stops an ICT Admin from editing their own role's
     * permissions and accidentally (or otherwise) locking themselves out.
     */
    private function ensureNotIctAdminRole(Role $role): void
    {
        abort_if($role->slug === 'ict_admin', 403, 'The ICT Admin role can only be managed by the platform Administrator.');
    }

    private function assignablePermissions()
    {
        return Permission::whereIn('slug', self::ASSIGNABLE_PERMISSION_SLUGS)->orderBy('group')->orderBy('name')->get()->groupBy('group');
    }

    /**
     * Only ever sync permissions from the assignable whitelist — even if someone tampered
     * with the request to include a platform-level permission ID, it gets filtered out here.
     */
    private function syncAssignablePermissions(Role $role, array $requestedIds): void
    {
        $allowedIds = Permission::whereIn('slug', self::ASSIGNABLE_PERMISSION_SLUGS)->pluck('id')->toArray();
        $safeIds = array_intersect($requestedIds, $allowedIds);

        $role->permissions()->sync($safeIds);
    }
}
