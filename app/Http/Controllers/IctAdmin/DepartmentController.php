<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Traits\EnforcesWriteAccess;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    use EnforcesWriteAccess;

    public function index(): View
    {
        $departments = Department::with('faculty')->latest()->get();

        return view('ict-admin.departments.index', compact('departments'));
    }

    public function create(): View
    {
        $this->ensureCanManage();

        // Only this institution's own faculties show up here — the trait scopes this too.
        $faculties = Faculty::orderBy('name')->get();

        return view('ict-admin.departments.create', compact('faculties'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'faculty_id' => ['required', 'exists:faculties,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        Department::create($validated);

        return redirect()->route('ict-admin.departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department): View
    {
        $this->ensureCanManage();

        $faculties = Faculty::orderBy('name')->get();

        return view('ict-admin.departments.edit', compact('department', 'faculties'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'faculty_id' => ['required', 'exists:faculties,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        $department->update($validated);

        return redirect()->route('ict-admin.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->ensureCanManage();

        $department->delete();

        return redirect()->route('ict-admin.departments.index')->with('success', 'Department deleted.');
    }
}
