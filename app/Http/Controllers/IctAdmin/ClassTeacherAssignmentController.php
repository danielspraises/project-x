<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Arm;
use App\Models\ClassTeacherAssignment;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClassTeacherAssignmentController extends Controller
{
    public function index(): View
    {
        $this->ensureCanManage();
        $assignments = ClassTeacherAssignment::with(['academicSession','term','schoolClass','arm','teacher'])->latest()->get();
        return view('ict-admin.class-teachers.index', compact('assignments'));
    }

    public function create(): View { $this->ensureCanManage(); return view('ict-admin.class-teachers.create', $this->formData()); }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage(); $data = $this->validated($request); $this->ensureScope($data);
        $key = $data['scope'] === 'class' ? 'class:' . $data['class_id'] : 'arm:' . $data['arm_id'];
        ClassTeacherAssignment::updateOrCreate(
            ['institution_id'=>Auth::user()->institution_id,'academic_session_id'=>$data['academic_session_id'],'term_id'=>$data['term_id'],'scope_key'=>$key],
            ['class_id'=>$data['class_id'],'arm_id'=>$data['arm_id'],'teacher_id'=>$data['teacher_id'],'scope'=>$data['scope']]
        );
        return redirect()->route('ict-admin.class-teachers.index')->with('success','Default class teacher saved.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $this->ensureCanManage();
        $data = $request->validate([
            'academic_session_id'=>['required','exists:academic_sessions,id'],'term_id'=>['required','exists:terms,id'],'class_id'=>['required','exists:classes,id'],
            'scope'=>['required','in:class,arm'],'arm_ids'=>['nullable','array'],'arm_ids.*'=>['integer','exists:arms,id'],'teacher_id'=>['required','exists:users,id'],
        ]);
        if ($data['scope']==='arm' && empty($data['arm_ids'])) abort(422,'Select at least one arm.');
        $this->ensureScope(array_merge($data,['arm_id'=>$data['arm_ids'][0] ?? null]));
        if ($data['scope'] === 'arm') {
            $validArms = Arm::whereIn('id', array_unique(array_map('intval', $data['arm_ids'])))->where('institution_id', Auth::user()->institution_id)->where('class_id', $data['class_id'])->count();
            abort_if($validArms !== count(array_unique(array_map('intval', $data['arm_ids']))), 422, 'One or more selected arms do not belong to this class.');
        }
        $targets = $data['scope']==='class' ? [null] : array_values(array_unique(array_map('intval',$data['arm_ids'])));
        foreach ($targets as $armId) {
            $key = $data['scope']==='class' ? 'class:'.$data['class_id'] : 'arm:'.$armId;
            ClassTeacherAssignment::updateOrCreate(
                ['institution_id'=>Auth::user()->institution_id,'academic_session_id'=>$data['academic_session_id'],'term_id'=>$data['term_id'],'scope_key'=>$key],
                ['class_id'=>$data['class_id'],'arm_id'=>$armId,'teacher_id'=>$data['teacher_id'],'scope'=>$data['scope']]
            );
        }
        return redirect()->route('ict-admin.class-teachers.index')->with('success','Default class teacher assignments updated in bulk.');
    }

    public function destroy(ClassTeacherAssignment $classTeacher): RedirectResponse { $this->ensureCanManage(); $classTeacher->delete(); return redirect()->route('ict-admin.class-teachers.index')->with('success','Default class teacher removed.'); }
    private function ensureCanManage(): void { abort_unless(Auth::user()->hasPermission('subjects.manage'),403); }
    private function validated(Request $request): array
    {
        $data=$request->validate(['academic_session_id'=>['required','exists:academic_sessions,id'],'term_id'=>['required','exists:terms,id'],'class_id'=>['required','exists:classes,id'],'scope'=>['required','in:class,arm'],'arm_id'=>['nullable','exists:arms,id'],'teacher_id'=>['required','exists:users,id']]);
        if($data['scope']==='arm' && empty($data['arm_id'])) abort(422,'An arm is required for an arm-level default teacher.');
        if($data['scope']==='class') $data['arm_id']=null; return $data;
    }
    private function ensureScope(array $data): void
    {
        $institutionId=Auth::user()->institution_id; $session=AcademicSession::findOrFail($data['academic_session_id']); $term=Term::findOrFail($data['term_id']); $class=SchoolClass::findOrFail($data['class_id']); $teacher=User::findOrFail($data['teacher_id']);
        abort_if($session->institution_id!==$institutionId,403); abort_if($term->institution_id!==$institutionId||$term->academic_session_id!==$session->id,422); abort_if($class->institution_id!==$institutionId,403); abort_if($teacher->institution_id!==$institutionId,403);
        if(($data['scope']??'arm')==='arm') { $arm=Arm::findOrFail($data['arm_id']); abort_if($arm->institution_id!==$institutionId||$arm->class_id!==$class->id,422,'Selected arm does not belong to this class.'); }
        abort_unless($teacher->role?->slug==='class_teacher'||$teacher->hasPermission('results.enter'),422,'Selected user cannot be assigned as a teacher.');
    }
    private function formData(): array { return ['sessions'=>AcademicSession::with('terms')->orderByDesc('start_date')->get(),'classes'=>SchoolClass::with('arms')->orderBy('order')->get(),'teachers'=>User::where('institution_id',Auth::user()->institution_id)->where(fn($q)=>$q->whereHas('role',fn($r)=>$r->where('slug','class_teacher'))->orWhereHas('role.permissions',fn($p)=>$p->where('slug','results.enter')))->orderBy('name')->get()]; }
}
