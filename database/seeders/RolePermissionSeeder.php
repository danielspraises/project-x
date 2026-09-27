<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Global Super Admin role — institution_id is null, sits outside all tenant scoping.
        Role::create([
            'institution_id' => null,
            'name' => 'Super Admin',
            'slug' => 'super_admin',
        ]);

        // Base permission catalog. Institution-level roles (ICT Admin, HOD, Lecturer, etc.)
        // get created per-institution during onboarding, with permissions assigned from this list.
        $permissions = [
            ['name' => 'Manage institutions', 'slug' => 'institutions.manage', 'group' => 'platform'],
            ['name' => 'Manage billing config', 'slug' => 'billing.manage', 'group' => 'billing'],
            ['name' => 'Request billing change', 'slug' => 'billing.request_change', 'group' => 'billing'],
            ['name' => 'View invoices', 'slug' => 'billing.view', 'group' => 'billing'],
            ['name' => 'Manage users', 'slug' => 'users.manage', 'group' => 'users'],
            ['name' => 'Manage institution setup', 'slug' => 'institution.setup', 'group' => 'institution'],
            ['name' => 'Manage students', 'slug' => 'students.manage', 'group' => 'students'],
            ['name' => 'Manage courses', 'slug' => 'courses.manage', 'group' => 'courses'],
            ['name' => 'Assign course registrations', 'slug' => 'registrations.assign', 'group' => 'courses'],
            ['name' => 'Manage subjects and subject offerings', 'slug' => 'subjects.manage', 'group' => 'subjects'],
            ['name' => 'Assign student subject registrations', 'slug' => 'subject_registrations.assign', 'group' => 'subjects'],
            ['name' => 'Enter results', 'slug' => 'results.enter', 'group' => 'results'],
            ['name' => 'Approve results', 'slug' => 'results.approve', 'group' => 'results'],
            ['name' => 'Publish results', 'slug' => 'results.publish', 'group' => 'results'],
            ['name' => 'Correct published results', 'slug' => 'results.correct', 'group' => 'results'],
            ['name' => 'Generate reports', 'slug' => 'reports.generate', 'group' => 'reports'],
            ['name' => 'View audit logs', 'slug' => 'audit.view', 'group' => 'audit'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
