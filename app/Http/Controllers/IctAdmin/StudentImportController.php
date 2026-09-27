<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\Arm;
use App\Models\Department;
use App\Models\Programme;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\SubjectRegistrationAutoEnroller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportController extends Controller
{
    public function __construct(
        private readonly SubjectRegistrationAutoEnroller $autoEnroller,
    ) {
    }

    public function create(): View
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);

        $institution = Auth::user()->institution;

        return view('ict-admin.students.import', compact('institution'));
    }

    /**
     * Downloadable CSV template — its columns differ depending on whether this
     * institution is tertiary (department/programme/level) or basic education (class/arm).
     */
    public function template(): StreamedResponse
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);

        $isTertiary = Auth::user()->institution->education_level === 'tertiary';

        $headers = $isTertiary
            ? ['admission_number', 'matric_number', 'first_name', 'last_name', 'other_names', 'gender', 'date_of_birth', 'phone', 'email', 'department_code', 'programme_code', 'level']
            : ['admission_number', 'first_name', 'last_name', 'other_names', 'gender','date_of_birth', 'phone', 'email', 'class_code', 'arm_name'];

        $callback = function() use ($headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle,$headers);
            fclose($handle);
        };

        return response()->streamDownload($callback, 'student_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('students.manage'), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $institution = Auth::user()->institution;
        $isTertiary = $institution->education_level === 'tertiary';
        $scopedDepartmentId = Auth::user()->department_id;

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $created = 0;
        $errors = [];
        $rowNumber = 1; // header is row 1

        // Pre-load lookup maps so each row doesn't hit the database repeatedly.
        $departments = Department::pluck('id', 'code');
        $programmes = Programme::pluck('id', 'code');
        $classes = SchoolClass::pluck('id', 'code');
        $arms = Arm::with('schoolClass')->get();

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row)) === 0) {
                continue; //skip blank rows
            }

            $data = array_combine($header, $row);

            try {
                $newStudent = DB::transaction(function () use ($data, $isTertiary, $institution, $scopedDepartmentId, $departments, $programmes, $classes, $arms) {
                    $payload= [
                        'institution_id' => $institution->id,
                        'admission_number' => trim($data['admission_number'] ?? ''),
                        'matric_number' => trim($data['matric_number'] ?? '') ?: null,
                        'first_name' => trim($data['first_name'] ?? ''),
                        'last_name' => trim($data['last_name'] ?? ''),
                        'other_names' => trim($data['other_names'] ?? '') ?: null,
                        'gender' => in_array(strtolower($data['gender'] ?? ''), ['male', 'female']) ? strtolower($data['gender']) : null,
                        'date_of_birth' => ! empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
                        'phone' => trim($data['phone'] ??'') ?: null,
                        'email' => trim($data['email'] ??'') ?: null,
                        'status' => 'active',
                    ];

                    if (empty($payload['admission_number'])) {
                        throw new \Exception('Missing admission_number.');
                    }
                    if (empty($payload['first_name']) || empty($payload['last_name'])){
                        throw new \Exception('Missing first_name or last_name.');
                    }

                    if ($isTertiary) {
                        $departmentCode = trim($data['department_code'] ?? '');
                        $programmeCode = trim($data['programme_code'] ?? '');

                        if (! isset($departments[$departmentCode])) {
                            throw new \Exception("Unknowndepartment_code '{$departmentCode}'.");
                        }
                        if (! isset($programmes[$programmeCode])) {
                            throw new \Exception("Unknownprogramme_code '{$programmeCode}'.");
                        }

                        $departmentId = $departments[$departmentCode];

                        // Department-scoped importer (HOD) can only import into theirown department.
                        if ($scopedDepartmentId && $departmentId !== $scopedDepartmentId) {
                            throw new \Exception('Row is outside your department — skipped.');
                        }

                        $payload['department_id'] = $departmentId;
                        $payload['programme_id'] = $programmes[$programmeCode];
                        $payload['level'] = trim($data['level'] ?? '');

                        if (empty($payload['level'])) {
                            throw new \Exception('Missinglevel.');
                        }
                    } else {
                        $classCode = trim($data['class_code'] ?? '');
                        $armName = trim($data['arm_name']?? '');

                        if (! isset($classes[$classCode])) {
                            throw new \Exception("Unknownclass_code '{$classCode}'.");
                        }

                        $classId = $classes[$classCode];
                        $arm= $arms->first(fn ($a) => $a->class_id === $classId && strcasecmp($a->name, $armName) === 0);

                        if (! $arm) {
                            throw new \Exception("Unknownarm '{$armName}' for class '{$classCode}'.");
                        }

                        $payload['class_id'] = $classId;
                        $payload['arm_id'] = $arm->id;
                    }

                    return Student::create($payload);
                });

                if (! $isTertiary && $newStudent) {
                    $this->autoEnroller->enrollForStudent($newStudent);
                }

                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNumber}: {$e->getMessage()}";
            }
        }

        fclose($handle);

        return redirect()
            ->route('ict-admin.students.import.create')
            ->with('success', "{$created} student(s) imported successfully.")
            ->with('import_errors', $errors);
    }
}
