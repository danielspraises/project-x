<?php
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InstitutionController;

use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\IctAdmin\RoleController;
use App\Http\Controllers\IctAdmin\UserController;
use App\Http\Controllers\IctAdmin\NoteController;
use App\Http\Controllers\IctAdmin\StudentImportController;
use App\Http\Controllers\IctAdmin\InstitutionSettingsController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\IctAdmin\AcademicSessionController;
use App\Http\Controllers\IctAdmin\TermController;
use App\Http\Controllers\IctAdmin\CourseController;
use App\Http\Controllers\IctAdmin\CourseOfferingController;
use App\Http\Controllers\IctAdmin\CourseRegistrationController;
use App\Http\Controllers\IctAdmin\BillingController;

use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\BillingOverviewController;

use App\Http\Controllers\IctAdmin\FacultyController;
use App\Http\Controllers\IctAdmin\DepartmentController;
use App\Http\Controllers\IctAdmin\ProgrammeController;
use App\Http\Controllers\IctAdmin\ClassController;
use App\Http\Controllers\IctAdmin\ArmController;
use App\Http\Controllers\IctAdmin\StudentController;

use App\Http\Controllers\IctAdmin\SubjectController;
use App\Http\Controllers\IctAdmin\SubjectOfferingController;
use App\Http\Controllers\IctAdmin\ClassTeacherAssignmentController;
use App\Http\Controllers\IctAdmin\ResultController;
use App\Http\Controllers\IctAdmin\GpaController;


use App\Http\Controllers\Hod\HodController;

use App\Http\Controllers\Lecturer\LecturerResultController;

use App\Http\Controllers\IctAdmin\SubjectRegistrationController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

// Routes any logged-in user can reach (their own profile only — nothing institution-sensitive belongs here)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Super Admin only — platform-wide management
Route::middleware(['auth', 'super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/institutions', [InstitutionController::class, 'index'])->name('institutions.index');
    Route::get('/institutions/create', [InstitutionController::class, 'create'])->name('institutions.create');
    Route::post('/institutions', [InstitutionController::class, 'store'])->name('institutions.store');

    Route::get('/institutions/{institution}/edit', [InstitutionController::class, 'edit'])->name('institutions.edit');
    Route::put('/institutions/{institution}', [InstitutionController::class, 'update'])->name('institutions.update');
    Route::delete('/institutions/{institution}', [InstitutionController::class, 'destroy'])->name('institutions.destroy');

    Route::post('/institutions/{institution}/users/{user}/reset-password', [InstitutionController::class, 'resetUserPassword'])->name('institutions.reset-password');
    Route::delete('/institutions/{institution}/users/{user}', [InstitutionController::class, 'destroyUser'])->name('institutions.users.destroy');

    Route::get('/settings', [PlatformSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [PlatformSettingsController::class, 'update'])->name('settings.update');
    Route::post('/institutions/{institution}/ict-admins', [InstitutionController::class, 'addIctAdmin'])->name('institutions.ict-admins.store');

    Route::post('/institutions/{institution}/billing-requests/{billingRequest}/approve', [InstitutionController::class, 'approveBillingRequest'])->name('institutions.billing-requests.approve');
    Route::post('/institutions/{institution}/billing-requests/{billingRequest}/reject', [InstitutionController::class, 'rejectBillingRequest'])->name('institutions.billing-requests.reject');

    
    Route::get('/billing', [BillingOverviewController::class, 'index'])->name('billing.index');
    Route::post('/billing/{billingRequest}/approve', [BillingOverviewController::class, 'approve'])->name('billing.approve');
    Route::post('/billing/{billingRequest}/reject', [BillingOverviewController::class, 'reject'])->name('billing.reject');

    Route::put('/institutions/{institution}/features', [InstitutionController::class, 'updateFeatures'])->name('institutions.features.update');

});

// ICT Admin routes

Route::middleware(['auth', 'permission:institution.setup,institution.view'])->prefix('ict-admin')->name('ict-admin.')->group(function () {
    // 'faculties' is included here. This is the module from earlier that I am wiring in now too
    Route::middleware('feature:institution_structure')->group(function () {
        Route::resource('faculties', FacultyController::class)->except(['show']);
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('programmes', ProgrammeController::class)->except(['show']);
        Route::resource('classes', ClassController::class)->except(['show']);
        Route::resource('arms', ArmController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::resource('sessions', AcademicSessionController::class)->except(['show']);
        Route::resource('terms', TermController::class)->except(['show']);
        Route::resource('course-offerings', CourseOfferingController::class)->except(['show']);
        Route::get('/subject-offerings/subjects/search', [SubjectOfferingController::class, 'subjectSearch'])->name('subject-offerings.subjects.search');
        Route::get('/subject-offerings/teachers/search', [SubjectOfferingController::class, 'teacherSearch'])->name('subject-offerings.teachers.search');
        Route::get('/subject-offerings/class/{schoolClass}', [SubjectOfferingController::class, 'classView'])->name('subject-offerings.class');
        Route::post('/subject-offerings/bulk', [SubjectOfferingController::class, 'bulkStore'])->name('subject-offerings.bulk');
        Route::resource('subject-offerings', SubjectOfferingController::class)->except(['show']);

        Route::get('/subject-offerings/{subjectOffering}/registrations', [SubjectRegistrationController::class, 'edit'])->name('subject-offerings.registrations.edit');
        Route::put('/subject-offerings/{subjectOffering}/registrations', [SubjectRegistrationController::class, 'update'])->name('subject-offerings.registrations.update');

        // Route::get('/subject-registrations/{subjectOffering}', [SubjectRegistrationController::class, 'edit'])->name('subject-registrations.edit');
        // Route::put('/subject-registrations/{subjectOffering}', [SubjectRegistrationController::class, 'update'])->name('subject-registrations.update');

        Route::get('/subjects/catalogue', [SubjectController::class, 'catalogue'])->name('subjects.catalogue');


        Route::resource('subjects', SubjectController::class)->except(['show']);
    });

    Route::middleware('feature:courses')->group(function () {
        Route::resource('courses', CourseController::class)->except(['show']);
    });

    Route::middleware('feature:students')->group(function () {
        Route::get('/students/import', [StudentImportController::class, 'create'])->name('students.import.create');
        Route::get('/students/import/template', [StudentImportController::class, 'template'])->name('students.import.template');
        Route::post('/students/import', [StudentImportController::class, 'store'])->name('students.import.store');
        Route::resource('students', StudentController::class)->except(['show']);
    });

    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);

    Route::post('/notes/{type}/{id}', [NoteController::class, 'store'])->name('notes.store');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    Route::get('/settings', [InstitutionSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [InstitutionSettingsController::class, 'update'])->name('settings.update');

    Route::get('/course-offerings/{courseOffering}/registrations', [CourseRegistrationController::class, 'edit'])->name('course-offerings.registrations.edit');
    Route::put('/course-offerings/{courseOffering}/registrations', [CourseRegistrationController::class, 'update'])->name('course-offerings.registrations.update');

    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/request-change', [BillingController::class, 'requestChange'])->name('billing.request-change');

    Route::put('/users/{user}/course-offerings', [UserController::class, 'updateCourseOfferings'])->name('users.course-offerings.update');


});

// Class teacher management is separate from institution setup/view access.
// Class teachers are institution/session/term/class/arm scoped and require subjects.manage.
Route::middleware(['auth', 'permission:subjects.manage', 'feature:institution_structure'])
    ->prefix('ict-admin')
    ->name('ict-admin.')
    ->group(function () {
        Route::post('/class-teachers/bulk', [ClassTeacherAssignmentController::class, 'bulkStore'])->name('class-teachers.bulk');
        Route::resource('class-teachers', ClassTeacherAssignmentController::class)->only(['index', 'create', 'store', 'destroy']);
    });


// Result Engine — viewing is separate from entering/updating results.
Route::middleware(['auth', 'permission:results.view'])
    ->prefix('ict-admin/results')
    ->name('ict-admin.results.')
    ->group(function () {
        Route::get('/', [ResultController::class, 'index'])->name('index');
    });

// Result Engine — result entry/write actions.
Route::middleware(['auth', 'permission:results.enter'])
    ->prefix('ict-admin/results')
    ->name('ict-admin.results.')
    ->group(function () {
        Route::post('/', [ResultController::class, 'store'])->name('store');
        Route::put('/{result}', [ResultController::class, 'update'])->name('update');
        Route::post('/{result}/transition', [ResultController::class, 'transition'])->name('transition');
    });
    

// GPA calculation routes are separate from result entry access.
Route::middleware(['auth', 'permission:results.calculate'])
    ->prefix('ict-admin/results/gpa')
    ->name('ict-admin.results.gpa.')
    ->group(function () {
        Route::post('/student', [GpaController::class, 'calculateStudent'])->name('student');
        Route::post('/term', [GpaController::class, 'calculateTerm'])->name('term');
    });



// HOD result review and verification.
Route::middleware(['auth', 'permission:results.approve'])
    ->prefix('hod/results')
    ->name('hod.results.')
    ->group(function () {
        Route::get('/', [HodController::class, 'index'])
            ->name('index');

        Route::post('/{submission}/verify', [HodController::class, 'verify'])
            ->name('verify');
    });


// Lecturer and Teachers routes


Route::middleware(['auth', 'permission:results.enter'])
    ->prefix('lecturer/results')
    ->name('lecturer.results.')
    ->group(function () {
        Route::get('/', [LecturerResultController::class, 'index'])->name('index');
        Route::post('/{offering}/save', [LecturerResultController::class, 'saveScores'])->name('save');
        Route::post('/{offering}/submit', [LecturerResultController::class, 'submit'])->name('submit');
        Route::get('/{offering}/students', [LecturerResultController::class, 'students'])->name('students.index');
        Route::get('/{offering}/students/{registration}', [LecturerResultController::class, 'studentShow'])->name('students.show');
        Route::post('/{offering}/students/{registration}', [LecturerResultController::class, 'studentUpdate'])->name('students.update');
        Route::get('/{offering}/bulk', [LecturerResultController::class, 'bulk'])->name('bulk');
        Route::get('/{offering}/import', [LecturerResultController::class, 'importForm'])->name('import');
        Route::get('/{offering}/import/template', [LecturerResultController::class, 'importTemplate'])->name('import.template');
        Route::post('/{offering}/import', [LecturerResultController::class, 'importUpload'])->name('import.upload');

        Route::get('/{offering}/import/preview', [LecturerResultController::class, 'importPreview'])->name('import.preview');
        Route::post('/{offering}/import/apply', [LecturerResultController::class, 'importApply'])->name('import.apply');
        Route::post('/{offering}/import/discard', [LecturerResultController::class, 'importDiscard'])->name('import.discard');
    });



// Global notification routes.
Route::middleware('auth')->group(function () {

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('notifications.unread-count');

    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])
        ->name('notifications.open');

    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])
        ->name('notifications.mark-all-read');
});



// Subject/class teacher routes
use App\Http\Controllers\SubjectTeacher\SubjectTeacherResultController;
use App\Http\Controllers\ClassTeacher\ClassTeacherController;

Route::middleware(['auth', 'permission:results.enter'])
    ->prefix('subject-teacher/results')
    ->name('subject-teacher.results.')
    ->group(function () {
        Route::get('/', [SubjectTeacherResultController::class, 'index'])->name('index');
        Route::post('/{offering}/save', [SubjectTeacherResultController::class, 'saveScores'])->name('save');
        Route::post('/{offering}/submit', [SubjectTeacherResultController::class, 'submit'])->name('submit');
        Route::get('/{offering}/students', [SubjectTeacherResultController::class, 'students'])->name('students.index');
        Route::get('/{offering}/students/{student}', [SubjectTeacherResultController::class, 'studentShow'])->name('students.show');
        Route::post('/{offering}/students/{student}', [SubjectTeacherResultController::class, 'studentUpdate'])->name('students.update');
        Route::get('/{offering}/bulk', [SubjectTeacherResultController::class, 'bulk'])->name('bulk');

        Route::get('/{offering}/import', [SubjectTeacherResultController::class, 'importForm'])->name('import');
        Route::get('/{offering}/import/template', [SubjectTeacherResultController::class, 'importTemplate'])->name('import.template');
        Route::post('/{offering}/import', [SubjectTeacherResultController::class, 'importUpload'])->name('import.upload');
        Route::get('/{offering}/import/preview', [SubjectTeacherResultController::class, 'importPreview'])->name('import.preview');
        Route::post('/{offering}/import/apply', [SubjectTeacherResultController::class, 'importApply'])->name('import.apply');
        Route::post('/{offering}/import/discard', [SubjectTeacherResultController::class, 'importDiscard'])->name('import.discard');

    });

Route::middleware(['auth', 'permission:results.approve'])
    ->prefix('class-teacher/results')
    ->name('class-teacher.results.')
    ->group(function () {
        Route::get('/', [ClassTeacherController::class, 'index'])->name('index');
        Route::post('/{submission}/verify', [ClassTeacherController::class, 'verify'])->name('verify');
    });




// ==============================================================================================
// LEARNING CONTENT ROUTES
// ==============================================================================================

use App\Http\Controllers\LearningContentController;

Route::middleware(['auth'])->group(function () {
    Route::get('/learning', [LearningContentController::class, 'index'])
        ->name('learning.index');

    Route::get('/learning/create', [LearningContentController::class, 'create'])
        ->name('learning.create');

    Route::post('/learning', [LearningContentController::class, 'store'])
        ->name('learning.store');

    Route::get('/learning/{learningContent}/edit', [LearningContentController::class, 'edit'])
        ->name('learning.edit');

    Route::put('/learning/{learningContent}', [LearningContentController::class, 'update'])
        ->name('learning.update');

    Route::post('/learning/{learningContent}/publish', [LearningContentController::class, 'publish'])
        ->name('learning.publish');

    Route::post('/learning/{learningContent}/archive', [LearningContentController::class, 'archive'])
        ->name('learning.archive');


    Route::post('/learning/{learningContent}/submit', [LearningContentController::class, 'submit'])
        ->name('learning.submit');

    Route::post('/learning/{learningContent}/approve', [LearningContentController::class, 'approve'])
        ->name('learning.approve');

    Route::post('/learning/{learningContent}/return', [LearningContentController::class, 'returnForRevision'])
        ->name('learning.return');
});



require __DIR__.'/auth.php';