<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Programme;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\SubjectRegistrationAutoEnroller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        private readonly SubjectRegistrationAutoEnroller $autoEnroller,
    ) {
    }

    public function index():View
    {
        $query = Student::with(['department', 'programme', 'schoolClass', 'arm']);

        // HOD / Department Officer: only see their own department's students.
        // ICT Admin, Faculty Officer, Auditor (department_id null) see everything.
        if (Auth::user()->isDepartmentScoped()) {
            $query->where('department_id', Auth::user()->department_id);
        }

        $students = $query->latest()->paginate(20);

        return view('ict-admin.students.index', compact('students'));
    }

    public function create(): View
    {
        $this->ensureCanManageStudents();

        $institution = Auth::user()->institution;
        $scopedDepartmentId = Auth::user()->department_id;

        $departments = $scopedDepartmentId
            ? Department::where('id', $scopedDepartmentId)->get()
            : Department::orderBy('name')->get();

        $programmes = $scopedDepartmentId
            ? Programme::where('department_id', $scopedDepartmentId)->orderBy('name')->get()
            : Programme::orderBy('name')->get();

        $classes = SchoolClass::with('arms')->orderBy('order')->get();

        return view('ict-admin.students.create', compact('institution', 'departments','programmes', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManageStudents();

        $institution = Auth::user()->institution;
        $isTertiary = $institution->education_level === 'tertiary';

        $validated = $request->validate([
            'admission_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'admission_number')->where(fn ($query) => $query->where('institution_id', Auth::user()->institution_id)),
            ],
            'matric_number' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'other_names' =>['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],

            'department_id' => [Rule::requiredIf($isTertiary), 'nullable', 'exists:departments,id'],
            'programme_id' => [Rule::requiredIf($isTertiary), 'nullable', 'exists:programmes,id'],
            'level' => [Rule::requiredIf($isTertiary), 'nullable', 'string', 'max:20'],

            'class_id' => [Rule::requiredIf(! $isTertiary), 'nullable', 'exists:classes,id'],
            'arm_id' => [Rule::requiredIf(! $isTertiary),'nullable', 'exists:arms,id'],
        ]);

        if ($isTertiary) {
            $validated['class_id'] = null;
            $validated['arm_id'] = null;
        } else {
            $validated['department_id'] = null;
            $validated['programme_id'] = null;
            $validated['level'] = null;
        }

        // Defense in depth:a department-scoped user (HOD, Department Officer) can only ever
        // create a student in their own department, regardless of what the form submitted.
        if (Auth::user()->isDepartmentScoped()) {
            abort_unless($isTertiary, 403, 'Department-scoped access only applies to tertiary institutions.');
            $validated['department_id'] = Auth::user()->department_id;
        }

        $validated['status']= 'active';

        try {
            $student = Student::create($validated);
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000' && str_contains($e->getMessage(), 'admission_number')) {
                throw ValidationException::withMessages([
                    'admission_number' => 'That student ID is already in use. Please enter a different student ID.',
                ]);
            }

            throw $e;
        }

        if (! $isTertiary) {
            $this->autoEnroller->enrollForStudent($student);
        }

        return redirect()->route('ict-admin.students.index')->with('success', 'Studentadded.');
    }

    public function edit(Student $student): View
    {
        $this->ensureCanManageStudents();
        $this->ensureCanAccessStudent($student);

        $institution = Auth::user()->institution;
        $scopedDepartmentId = Auth::user()->department_id;

        $departments = $scopedDepartmentId
            ? Department::where('id', $scopedDepartmentId)->get()
            : Department::orderBy('name')->get();

        $programmes = $scopedDepartmentId
            ? Programme::where('department_id', $scopedDepartmentId)->orderBy('name')->get()
            : Programme::orderBy('name')->get();

        $classes = SchoolClass::with('arms')->orderBy('order')->get();

        return view('ict-admin.students.edit', compact('student', 'institution', 'departments', 'programmes', 'classes'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->ensureCanManageStudents();
        $this->ensureCanAccessStudent($student);

        $institution = Auth::user()->institution;
        $isTertiary = $institution->education_level === 'tertiary';

        $validated = $request->validate([
            'admission_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'admission_number')
                    ->where(fn ($query) => $query->where('institution_id', Auth::user()->institution_id))
                    ->ignore($student->id),
            ],
            'matric_number' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'other_names' =>['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,graduated,withdrawn,suspended'],

            'department_id' => [Rule::requiredIf($isTertiary), 'nullable', 'exists:departments,id'],
            'programme_id' => [Rule::requiredIf($isTertiary), 'nullable', 'exists:programmes,id'],
            'level' => [Rule::requiredIf($isTertiary), 'nullable', 'string', 'max:20'],

            'class_id' => [Rule::requiredIf(! $isTertiary), 'nullable', 'exists:classes,id'],
            'arm_id' => [Rule::requiredIf(! $isTertiary),'nullable', 'exists:arms,id'],
        ]);

        if ($isTertiary) {
            $validated['class_id'] = null;
            $validated['arm_id'] = null;
        } else {
            $validated['department_id'] = null;
            $validated['programme_id'] = null;
            $validated['level'] = null;
        }

        if (Auth::user()->isDepartmentScoped()) {
            $validated['department_id'] = Auth::user()->department_id;
        }

        $classOrArmChanged = ! $isTertiary
            && ((int) $student->class_id !== (int) ($validated['class_id'] ?? 0)
                || (int) $student->arm_id !== (int) ($validated['arm_id'] ?? 0));

        try {
            $student->update($validated);
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000' && str_contains($e->getMessage(), 'admission_number')) {
                throw ValidationException::withMessages([
                    'admission_number' => 'That student ID is already in use. Please enter a different student ID.',
                ]);
            }

            throw $e;
        }

        // NOTE: this only ADDS registrations for the student's new class/arm.
        // It does not remove registrations tied to their previous class/arm —
        // a student transferred between classes keeps their old subject
        // registrations too. Flagged as a known gap, not fixed in this batch.
        if ($classOrArmChanged) {
            $this->autoEnroller->enrollForStudent($student->fresh());
        }

        return redirect()->route('ict-admin.students.index')->with('success', 'Studentupdated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->ensureCanManageStudents();
        $this->ensureCanAccessStudent($student);

        $student->delete();

        return redirect()->route('ict-admin.students.index')->with('success', 'Studentremoved.');
    }

    private function ensureCanManageStudents(): void
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);
    }

    private function ensureCanAccessStudent(Student $student): void
    {
        if (Auth::user()->isDepartmentScoped()) {
            abort_if($student->department_id !== Auth::user()->department_id, 403, 'This student is outside your department.');
        }
    }
}
