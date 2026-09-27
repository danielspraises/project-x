<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Traits\EnforcesWriteAccess;
use App\Models\Arm;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArmController extends Controller
{
    use EnforcesWriteAccess;

    public function index(): View
    {
        $arms = Arm::with('schoolClass')->latest()->get();

        return view('ict-admin.arms.index', compact('arms'));
    }

    public function create(): View
    {
        $this->ensureCanManage();

        $classes = SchoolClass::orderBy('order')->get();

        return view('ict-admin.arms.create', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        Arm::create($validated);

        return redirect()->route('ict-admin.arms.index')->with('success', 'Arm created.');
    }

    public function destroy(Arm $arm): RedirectResponse
    {
        $this->ensureCanManage();

        $arm->delete();

        return redirect()->route('ict-admin.arms.index')->with('success', 'Arm deleted.');
    }
}
