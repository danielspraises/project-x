<?php

namespace App\Http\Controllers;

use App\Models\Arm;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Institution;
use App\Models\Programme;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return view('dashboard', [
                'view' => 'super_admin',
                'stats' => [
                    'total_institutions' => Institution::count(),
                    'trial_institutions' => Institution::where('status', 'trial')->count(),
                    'active_institutions' => Institution::where('status', 'active')->count(),
                    'suspended_institutions' => Institution::whereIn('status', ['suspended', 'cancelled'])->count(),
                ],
                'recent_institutions' => Institution::latest()->take(5)->get(),
            ]);
        }

        // Institution-level user (ICT Admin, HOD, Lecturer, etc.)
        $institution = $user->institution;
        $isTertiary = $institution?->education_level === 'tertiary';

        $stats = $isTertiary ? [
            'faculties' => Faculty::count(),
            'departments' => Department::count(),
            'programmes' => Programme::count(),
            'students' => Student::count(),
        ] : [
            'classes' => SchoolClass::count(),
            'arms' => Arm::count(),
            'students' => Student::count(),
        ];

        return view('dashboard', [
            'view' => 'institution_user',
            'institution' => $institution,
            'isTertiary' => $isTertiary,
            'stats' => $stats,
        ]);
    }
}
