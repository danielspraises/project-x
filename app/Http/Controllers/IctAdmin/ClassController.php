<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Traits\EnforcesWriteAccess;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClassController extends Controller
{
    use EnforcesWriteAccess;

    public function index(): View
    {
        $classes = SchoolClass::withCount('arms')->orderBy('order')->get();
        $institution = Auth::user()->institution;

        return view('ict-admin.classes.index', compact('classes', 'institution'));
    }

    public function create(): View
    {
        $this->ensureCanManage();

        $institution = Auth::user()->institution;

        return view('ict-admin.classes.create', compact('institution'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'order' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        SchoolClass::create($validated);

        return redirect()->route('ict-admin.classes.index')->with('success', 'Class created.');
    }

    public function edit(SchoolClass $class): View
    {
        $this->ensureCanManage();

        $institution = Auth::user()->institution;

        return view('ict-admin.classes.edit', ['schoolClass' => $class, 'institution' => $institution]);
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'order' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $class->update($validated);

        return redirect()->route('ict-admin.classes.index')->with('success', 'Class updated.');
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        $this->ensureCanManage();

        $class->delete();

        return redirect()->route('ict-admin.classes.index')->with('success', 'Class deleted.');
    }
}
