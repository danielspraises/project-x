<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Traits\EnforcesWriteAccess;
use App\Models\Department;
use App\Models\Programme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgrammeController extends Controller
{
    use EnforcesWriteAccess;

    public function index(): View
    {
        $programmes = Programme::with('department')->latest()->get();

        return view('ict-admin.programmes.index', compact('programmes'));
    }

    public function create(): View
    {
        $this->ensureCanManage();

        $departments = Department::orderBy('name')->get();

        return view('ict-admin.programmes.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'duration_years' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        Programme::create($validated);

        return redirect()->route('ict-admin.programmes.index')->with('success', 'Programme created.');
    }

    public function edit(Programme $programme): View
    {
        $this->ensureCanManage();

        $departments = Department::orderBy('name')->get();

        return view('ict-admin.programmes.edit', compact('programme', 'departments'));
    }

    public function update(Request $request, Programme $programme): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'duration_years' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $programme->update($validated);

        return redirect()->route('ict-admin.programmes.index')->with('success', 'Programme updated.');
    }

    public function destroy(Programme $programme): RedirectResponse
    {
        $this->ensureCanManage();

        $programme->delete();

        return redirect()->route('ict-admin.programmes.index')->with('success', 'Programme deleted.');
    }
}
