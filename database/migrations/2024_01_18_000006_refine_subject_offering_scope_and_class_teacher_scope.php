<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    public function up(): void
    {
        // This migration is intentionally resumable. The first implementation may already
        // have added columns/data before failing on MySQL's identifier-length limit.
        if (!Schema::hasColumn('subject_offerings', 'scope')) {
            Schema::table('subject_offerings', function (Blueprint $table) {
                $table->string('scope', 20)->default('arm')->after('subject_id');
            });
        }

        if (!Schema::hasColumn('subject_offerings', 'scope_key')) {
            Schema::table('subject_offerings', function (Blueprint $table) {
                $table->string('scope_key', 120)->nullable()->after('scope');
            });
        }

        if (!Schema::hasColumn('class_teacher_assignments', 'scope')) {
            Schema::table('class_teacher_assignments', function (Blueprint $table) {
                $table->string('scope', 20)->default('arm')->after('teacher_id');
            });
        }

        if (!Schema::hasColumn('class_teacher_assignments', 'scope_key')) {
            Schema::table('class_teacher_assignments', function (Blueprint $table) {
                $table->string('scope_key', 120)->nullable()->after('scope');
            });
        }

        DB::table('subject_offerings')
            ->whereNull('scope_key')
            ->orderBy('id')
            ->eachById(function ($row) {
                DB::table('subject_offerings')->where('id', $row->id)->update([
                    'scope' => 'arm',
                    'scope_key' => 'arm:' . $row->arm_id . ':subject:' . $row->subject_id,
                ]);
            });

        DB::table('class_teacher_assignments')
            ->whereNull('scope_key')
            ->orderBy('id')
            ->eachById(function ($row) {
                DB::table('class_teacher_assignments')->where('id', $row->id)->update([
                    'scope' => 'arm',
                    'scope_key' => 'arm:' . $row->arm_id,
                ]);
            });

        // Existing records from the first implementation are arm-scoped. Make arm_id
        // nullable so a class-wide offering/assignment can legitimately have no arm.
        DB::statement('ALTER TABLE subject_offerings MODIFY arm_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE class_teacher_assignments MODIFY arm_id BIGINT UNSIGNED NULL');

        if (!$this->indexExists('subject_offerings', 'subject_offering_scope_unique')) {
            Schema::table('subject_offerings', function (Blueprint $table) {
                $table->unique(
                    ['institution_id', 'academic_session_id', 'term_id', 'scope_key'],
                    'subject_offering_scope_unique'
                );
            });
        }

        // Explicit short names avoid MySQL's 64-character identifier limit.
        if (!$this->indexExists('subject_offerings', 'subj_offering_context_idx')) {
            Schema::table('subject_offerings', function (Blueprint $table) {
                $table->index(
                    ['class_id', 'academic_session_id', 'term_id', 'scope'],
                    'subj_offering_context_idx'
                );
            });
        }

        if (!$this->indexExists('class_teacher_assignments', 'class_teacher_scope_unique')) {
            Schema::table('class_teacher_assignments', function (Blueprint $table) {
                $table->unique(
                    ['institution_id', 'academic_session_id', 'term_id', 'scope_key'],
                    'class_teacher_scope_unique'
                );
            });
        }

        if (!$this->indexExists('class_teacher_assignments', 'class_teacher_context_idx')) {
            Schema::table('class_teacher_assignments', function (Blueprint $table) {
                $table->index(
                    ['class_id', 'academic_session_id', 'term_id', 'scope'],
                    'class_teacher_context_idx'
                );
            });
        }

        if ($this->indexExists('subject_offerings', 'subject_offering_unique')) {
            Schema::table('subject_offerings', function (Blueprint $table) {
                $table->dropUnique('subject_offering_unique');
            });
        }

        if ($this->indexExists('class_teacher_assignments', 'class_teacher_assignment_unique')) {
            Schema::table('class_teacher_assignments', function (Blueprint $table) {
                $table->dropUnique('class_teacher_assignment_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subject_offerings')) {
            return;
        }

        if (!$this->indexExists('subject_offerings', 'subject_offering_unique')) {
            Schema::table('subject_offerings', function (Blueprint $table) {
                $table->unique(
                    ['institution_id', 'academic_session_id', 'term_id', 'class_id', 'arm_id', 'subject_id'],
                    'subject_offering_unique'
                );
            });
        }

        if ($this->indexExists('subject_offerings', 'subject_offering_scope_unique')) {
            Schema::table('subject_offerings', function (Blueprint $table) {
                $table->dropUnique('subject_offering_scope_unique');
            });
        }

        if ($this->indexExists('subject_offerings', 'subj_offering_context_idx')) {
            Schema::table('subject_offerings', function (Blueprint $table) {
                $table->dropIndex('subj_offering_context_idx');
            });
        }

        Schema::table('subject_offerings', function (Blueprint $table) {
            if (Schema::hasColumn('subject_offerings', 'scope_key')) {
                $table->dropColumn('scope_key');
            }
            if (Schema::hasColumn('subject_offerings', 'scope')) {
                $table->dropColumn('scope');
            }
        });

        if (Schema::hasTable('class_teacher_assignments')) {
            if (!$this->indexExists('class_teacher_assignments', 'class_teacher_assignment_unique')) {
                Schema::table('class_teacher_assignments', function (Blueprint $table) {
                    $table->unique(
                        ['institution_id', 'academic_session_id', 'term_id', 'class_id', 'arm_id'],
                        'class_teacher_assignment_unique'
                    );
                });
            }

            if ($this->indexExists('class_teacher_assignments', 'class_teacher_scope_unique')) {
                Schema::table('class_teacher_assignments', function (Blueprint $table) {
                    $table->dropUnique('class_teacher_scope_unique');
                });
            }

            if ($this->indexExists('class_teacher_assignments', 'class_teacher_context_idx')) {
                Schema::table('class_teacher_assignments', function (Blueprint $table) {
                    $table->dropIndex('class_teacher_context_idx');
                });
            }

            Schema::table('class_teacher_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('class_teacher_assignments', 'scope_key')) {
                    $table->dropColumn('scope_key');
                }
                if (Schema::hasColumn('class_teacher_assignments', 'scope')) {
                    $table->dropColumn('scope');
                }
            });
        }
    }
};
