<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // True only for the ICT Admin created automatically when Super Admin
            // onboards the institution. Every ICT Admin added afterward is "secondary."
            $table->boolean('is_primary')->default(false)->after('status');
        });

        // Backfill: for every existing institution, mark its oldest ICT Admin account
        // as primary (best guess — since is_primary didn't exist before, we treat
        // "first ICT Admin created" as equivalent to "the one Super Admin set up").
        $institutionIds = DB::table('institutions')->pluck('id');

        foreach ($institutionIds as $institutionId) {
            $oldestIctAdminId = DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->where('users.institution_id', $institutionId)
                ->where('roles.slug', 'ict_admin')
                ->orderBy('users.created_at')
                ->value('users.id');

            if ($oldestIctAdminId) {
                DB::table('users')->where('id', $oldestIctAdminId)->update(['is_primary' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
