<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Use in any IctAdmin controller that has create/store/edit/update/destroy methods.
 * Call ensureCanManage() at the top of each of those — read-only roles (like Auditor,
 * who only has institution.view) get a 403 instead of being able to mutate data,
 * even though the route middleware lets them into the controller for index/show.
 */
trait EnforcesWriteAccess
{
    protected function ensureCanManage(): void
    {
        abort_unless(Auth::user()->hasPermission('institution.setup'), 403, 'You have read-only access.');
    }
}
