<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Programme;
use App\Models\Role;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseOfferingController extends Controller
{
    public function index(): View
    {
        $query = CourseOffering::with(['course', 'term.academicSession', 'programme', 'lecturer']);

        if (Auth::user()->isDepartmentScoped()) {
            $query->whereHas('course', fn ($q) => $q->where('department_id', Auth::user()->department_id));
        }

        $offerings = $query->latest()->get();

        return view('ict-admin.course-offerings.index', compact('offerings'));
    }

    public function create(): View
    {
        $this->ensureCanManage();

        $courses = $this->scopedCourses()->orderBy('code')->get();
        $terms = Term::with('academicSession')->orderByDesc('start_date')->get();
        $programmes = $this->scopedProgrammes()->orderBy('name')->get();
        $lecturers = $this->lecturersList();

        return view('ict-admin.course-offerings.create', compact('courses', 'terms', 'programmes', 'lecturers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'programme_id' => ['required', 'exists:programmes,id'],
            'level' => ['required', 'string', 'max:20'],
            'lecturer_id' => ['nullable', 'exists:users,id'],
            'requirement_type' => ['required', 'in:compulsory,elective'],
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $this->ensureCanAccessCourse($course);

        CourseOffering::create($validated);

        return redirect()->route('ict-admin.course-offerings.index')->with('success', 'Course offering created.');
    }

    public function edit(CourseOffering $courseOffering): View
    {
        $this->ensureCanManage();
        $this->ensureCanAccessCourse($courseOffering->course);

        $courses = $this->scopedCourses()->orderBy('code')->get();
        $terms = Term::with('academicSession')->orderByDesc('start_date')->get();
        $programmes = $this->scopedProgrammes()->orderBy('name')->get();
        $lecturers = $this->lecturersList();

        return view('ict-admin.course-offerings.edit', compact('courseOffering', 'courses', 'terms', 'programmes', 'lecturers'));
    }

    public function update(Request $request, CourseOffering $courseOffering): RedirectResponse
    {
        $this->ensureCanManage();
        $this->ensureCanAccessCourse($courseOffering->course);

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'programme_id' => ['required', 'exists:programmes,id'],
            'level' => ['required', 'string', 'max:20'],
            'lecturer_id' => ['nullable', 'exists:users,id'],
            'requirement_type' => ['required', 'in:compulsory,elective'],
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $this->ensureCanAccessCourse($course);

        $courseOffering->update($validated);

        return redirect()->route('ict-admin.course-offerings.index')->with('success', 'Course offering updated.');
    }

    public function destroy(CourseOffering $courseOffering): RedirectResponse
    {
        $this->ensureCanManage();
        $this->ensureCanAccessCourse($courseOffering->course);

        abort_if($courseOffering->registrations()->exists(), 422, 'Students are already registered on this offering — remove those registrations first.');

        $courseOffering->delete();

        return redirect()->route('ict-admin.course-offerings.index')->with('success', 'Course offering deleted.');
    }

    private function ensureCanManage(): void
    {
        abort_unless(Auth::user()->hasPermission('courses.manage'), 403);
    }

    private function ensureCanAccessCourse(Course $course): void
    {
        if (Auth::user()->isDepartmentScoped()) {
            abort_if($course->department_id !== Auth::user()->department_id, 403);
        }
    }

    private function scopedCourses()
    {
        return Auth::user()->isDepartmentScoped()
            ? Course::where('department_id', Auth::user()->department_id)
            : Course::query();
    }

    private function scopedProgrammes()
    {
        return Auth::user()->isDepartmentScoped()
            ? Programme::where('department_id', Auth::user()->department_id)
            : Programme::query();
    }

    /**
     * Anyone holding results.enter is a candidate lecturer for a course offering —
     * covers the Lecturer role and any custom role an institution has set up similarly.
     */
    private function lecturersList()
    {
        $lecturerRoleIds = Role::where('institution_id', Auth::user()->institution_id)
            ->whereHas('permissions', fn ($q) => $q->where('slug', 'results.enter'))
            ->pluck('id');

        return User::where('institution_id', Auth::user()->institution_id)
            ->whereIn('role_id', $lecturerRoleIds)
            ->orderBy('name')
            ->get();
    }
}
