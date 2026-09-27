<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Traits\EnforcesWriteAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacultyController extends Controller
{
    use EnforcesWriteAccess;

    // Note: no institution_id filtering anywhere below — the BelongsToInstitution
    // trait on the Faculty model handles that automatically for every query here.

    public function index(): View
    {
        $faculties = Faculty::withCount('departments')->latest()->get();

        return view('ict-admin.faculties.index', compact('faculties'));
    }

    public function create(): View
    {
        $this->ensureCanManage();

        return view('ict-admin.faculties.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        Faculty::create($validated); // institution_id is auto-stamped by the trait

        return redirect()->route('ict-admin.faculties.index')->with('success', 'Faculty created.');
    }

    public function edit(Faculty $faculty): View
    {
        $this->ensureCanManage();

        return view('ict-admin.faculties.edit', compact('faculty'));
    }

    public function update(Request $request, Faculty $faculty): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        $faculty->update($validated);

        return redirect()->route('ict-admin.faculties.index')->with('success', 'Faculty updated.');
    }

    public function destroy(Faculty $faculty): RedirectResponse
    {
        $this->ensureCanManage();

        $faculty->delete();

        return redirect()->route('ict-admin.faculties.index')->with('success', 'Faculty deleted.');
    }
}
