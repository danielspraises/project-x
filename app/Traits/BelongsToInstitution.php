<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Apply this trait to every tenant-owned model (Student, Course, Result, etc).
 *
 * It does two things:
 * 1. Automatically scopes every query to the logged-in user's institution_id,
 *    so a query can never accidentally leak data across schools.
 * 2. Automatically stamps institution_id on create, so you never have to
 *    remember to set it manually.
 *
 * Super Admin (institution_id === null on the user) bypasses scoping entirely,
 * since they legitimately need to see/manage data across all institutions.
 */
trait BelongsToInstitution
{
    protected static function bootBelongsToInstitution(): void
    {
        static::addGlobalScope('institution', function (Builder $builder) {
            $user = Auth::user();

            // No authenticated user (e.g. console/seeder context) — don't scope.
            if (! $user) {
                return;
            }

            // Super Admin: institution_id is null on their user record — see everything.
            if ($user->institution_id === null) {
                return;
            }

            $builder->where($builder->getModel()->getTable() . '.institution_id', $user->institution_id);
        });

        static::creating(function (Model $model) {
            $user = Auth::user();

            if ($user && $user->institution_id !== null && empty($model->institution_id)) {
                $model->institution_id = $user->institution_id;
            }
        });
    }
}
