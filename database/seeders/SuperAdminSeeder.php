<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('slug', 'super_admin')->whereNull('institution_id')->firstOrFail();

        User::create([
            'institution_id' => null,
            'role_id' => $superAdminRole->id,
            'name' => 'Platform Owner',
            'email' => 'admin@test.com', // change this before running in production
            'password' => 'password123', // hashed automatically via the User model cast
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
