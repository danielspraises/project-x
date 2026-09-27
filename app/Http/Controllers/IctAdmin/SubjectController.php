<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        $this->ensureCanManage();
        $classes = SchoolClass::with('arms')->orderBy('order')->orderBy('name')->get();

        return view('ict-admin.subjects.index', compact('classes'));
    }

    public function catalogue(): View
    {
        $this->ensureCanManage();
        $subjects = Subject::orderBy('name')->get();

        return view('ict-admin.subjects.catalogue', compact('subjects'));
    }

    public function create(): View
    {
        $this->ensureCanManage();

        return view('ict-admin.subjects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $institutionId = Auth::user()->institution_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('subjects', 'code')->where(
                    fn ($query) => $query->where('institution_id', $institutionId)
                ),
            ],
            'category' => ['nullable', 'string', 'max:100'],
        ], [
            'code.unique' => 'That subject code is already in use in this institution.',
        ]);

        Auth::user()->institution->subjects()->create($validated);

        return redirect()
            ->route('ict-admin.subjects.catalogue')
            ->with('success', 'Subject created successfully.');
    }

    public function edit(Subject $subject): View
    {
        $this->ensureCanManage();

        return view('ict-admin.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureCanManage();

        $institutionId = Auth::user()->institution_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('subjects', 'code')
                    ->where(fn ($query) => $query->where('institution_id', $institutionId))
                    ->ignore($subject->id),
            ],
            'category' => ['nullable', 'string', 'max:100'],
        ], [
            'code.unique' => 'That subject code is already in use in this institution.',
        ]);

        $subject->update($validated);

        return redirect()
            ->route('ict-admin.subjects.catalogue')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $this->ensureCanManage();

        abort_if(
            $subject->offerings()->exists(),
            422,
            'This subject is used by one or more offerings. Remove those offerings first.'
        );

        $subject->delete();

        return redirect()
            ->route('ict-admin.subjects.catalogue')
            ->with('success', 'Subject deleted.');
    }

    private function ensureCanManage(): void
    {
        abort_unless(Auth::user()->hasPermission('subjects.manage'), 403);
    }
}
