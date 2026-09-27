<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $query = Course::with('department')->withCount('offerings');

        if (Auth::user()->isDepartmentScoped()) {
            $query->where('department_id', Auth::user()->department_id);
        }

        $courses = $query->orderBy('code')->get();

        return view('ict-admin.courses.index', compact('courses'));
    }

    public function create(): View
    {
        $this->ensureCanManage();

        $departments = Auth::user()->department_id
            ? Department::where('id', Auth::user()->department_id)->get()
            : Department::orderBy('name')->get();

        return view('ict-admin.courses.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:255'],
            'credit_unit' => ['required', 'integer', 'min:1', 'max:10'],
            'level' => ['required', 'string', 'max:20'],
        ]);

        if (Auth::user()->isDepartmentScoped()) {
            abort_if((int) $validated['department_id'] !== Auth::user()->department_id, 403);
        }

        Course::create($validated);

        return redirect()->route('ict-admin.courses.index')->with('success', 'Course created.');
    }

    public function edit(Course $course): View
    {
        $this->ensureCanManage();
        $this->ensureCanAccess($course);

        $departments = Auth::user()->department_id
            ? Department::where('id', Auth::user()->department_id)->get()
            : Department::orderBy('name')->get();

        return view('ict-admin.courses.edit', compact('course', 'departments'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCanManage();
        $this->ensureCanAccess($course);

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:255'],
            'credit_unit' => ['required', 'integer', 'min:1', 'max:10'],
            'level' => ['required', 'string', 'max:20'],
        ]);

        if (Auth::user()->isDepartmentScoped()) {
            abort_if((int) $validated['department_id'] !== Auth::user()->department_id, 403);
        }

        $course->update($validated);

        return redirect()->route('ict-admin.courses.index')->with('success', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->ensureCanManage();
        $this->ensureCanAccess($course);

        abort_if($course->offerings()->exists(), 422, 'This course has offerings tied to it — remove those first.');

        $course->delete();

        return redirect()->route('ict-admin.courses.index')->with('success', 'Course deleted.');
    }

    private function ensureCanManage(): void
    {
        abort_unless(Auth::user()->hasPermission('courses.manage'), 403);
    }

    private function ensureCanAccess(Course $course): void
    {
        if (Auth::user()->isDepartmentScoped()) {
            abort_if($course->department_id !== Auth::user()->department_id, 403);
        }
    }
}
