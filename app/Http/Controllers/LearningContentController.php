<?php

namespace App\Http\Controllers;

use App\Models\CourseOffering;
use App\Models\LearningContent;
use App\Models\SubjectOffering;
use App\Services\Learning\LearningContentGovernanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LearningContentController extends Controller
{
    public function __construct(
        private LearningContentGovernanceService $governance
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $institution = $user->institution;

        $hasLessons = $institution?->hasFeature('lessons') ?? false;
        $hasLectures = $institution?->hasFeature('lectures') ?? false;

        abort_unless($hasLessons || $hasLectures, 403);

        $type = $request->string('type')->toString();
        $search = $request->string('search')->trim()->toString();

        $contents = LearningContent::query()
            ->with([
                'creator:id,name',
                'subjectOffering.subject:id,name,code',
                'subjectOffering.schoolClass:id,name,code',
                'subjectOffering.arm:id,name,code',
                'courseOffering.course:id,title,code',
                'courseOffering.programme:id,name,code',
            ])
            ->when(
                $type === 'lesson' && $hasLessons,
                fn ($query) => $query->where('content_type', 'lesson')
            )
            ->when(
                $type === 'lecture' && $hasLectures,
                fn ($query) => $query->where('content_type', 'lecture')
            )
            ->when(
                $type === 'lesson' && ! $hasLessons,
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->when(
                $type === 'lecture' && ! $hasLectures,
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->when(
                $search !== '',
                fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('learning.index', compact(
            'contents',
            'type',
            'hasLessons',
            'hasLectures'
        ));
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        $type = $request->string('type')->toString();

        abort_unless(
            in_array($type, ['lesson', 'lecture'], true),
            404
        );

        $feature = $type === 'lesson' ? 'lessons' : 'lectures';
        $permission = "{$feature}.create";

        abort_unless($user->institution?->hasFeature($feature), 403);
        abort_unless($user->hasPermission($permission), 403);

        return view('learning.create', [
            'type' => $type,
            'subjectOfferings' => $this->subjectOfferingsFor($user),
            'courseOfferings' => $this->courseOfferingsFor($user),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'content_type' => [
                'required',
                Rule::in(['lesson', 'lecture']),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'subject_offering_id' => [
                'nullable',
                'integer',
                'exists:subject_offerings,id',
            ],
            'course_offering_id' => [
                'nullable',
                'integer',
                'exists:course_offerings,id',
            ],
        ]);

        $feature = $validated['content_type'] === 'lesson'
            ? 'lessons'
            : 'lectures';

        abort_unless(
            $user->institution?->hasFeature($feature),
            403
        );

        abort_unless(
            $user->hasPermission("{$feature}.create"),
            403
        );

        $subjectOffering = $validated['subject_offering_id']
            ? SubjectOffering::findOrFail($validated['subject_offering_id'])
            : null;

        $courseOffering = $validated['course_offering_id']
            ? CourseOffering::findOrFail($validated['course_offering_id'])
            : null;

        $this->validateAcademicContext(
            $user,
            $subjectOffering,
            $courseOffering
        );

        $content = LearningContent::create([
            'created_by' => $user->id,
            'subject_offering_id' => $subjectOffering?->id,
            'course_offering_id' => $courseOffering?->id,
            'content_type' => $validated['content_type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'draft',
            'workflow_status' => 'draft',
        ]);

        $this->governance->record(
            content: $content,
            user: $user,
            action: 'created',
            fromStatus: null,
            toStatus: 'draft',
            metadata: [
                'content_type' => $content->content_type,
                'title' => $content->title,
            ],
        );

        return redirect()
            ->route('learning.index')
            ->with(
                'success',
                ucfirst($feature) . ' created successfully.'
            );
    }

    public function edit(LearningContent $learningContent)
    {
        $user = Auth::user();

        $feature = $learningContent->content_type === 'lesson'
            ? 'lessons'
            : 'lectures';

        abort_unless(
            $user->institution?->hasFeature($feature),
            403
        );

        abort_unless(
            $user->hasPermission("{$feature}.manage"),
            403
        );

        return view('learning.edit', [
            'content' => $learningContent,
            'subjectOfferings' => $this->subjectOfferingsFor($user),
            'courseOfferings' => $this->courseOfferingsFor($user),
        ]);
    }

    public function update(
        Request $request,
        LearningContent $learningContent
    ) {
        $user = Auth::user();

        $feature = $learningContent->content_type === 'lesson'
            ? 'lessons'
            : 'lectures';

        abort_unless(
            $user->institution?->hasFeature($feature),
            403
        );

        abort_unless(
            $user->hasPermission("{$feature}.manage"),
            403
        );

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $learningContent->update($validated);

        $this->governance->record(
            content: $learningContent,
            user: $user,
            action: 'updated',
            fromStatus: $learningContent->workflow_status,
            toStatus: $learningContent->workflow_status,
            metadata: [
                'changed_fields' => array_keys($validated),
            ],
        );

        return redirect()
            ->route('learning.index')
            ->with(
                'success',
                ucfirst($feature) . ' updated successfully.'
            );
    }

    public function submit(LearningContent $learningContent)
    {
        $user = Auth::user();

        $feature = $learningContent->content_type === 'lesson'
            ? 'lessons'
            : 'lectures';

        abort_unless(
            $user->institution?->hasFeature($feature),
            403
        );

        abort_unless(
            $user->hasPermission("{$feature}.manage"),
            403
        );

        abort_unless(
            $user->institution?->education_level !== 'tertiary',
            422,
            'Tertiary learning content does not require submission for approval.'
        );

        abort_unless(
            $learningContent->workflow_status === 'draft' ||
            $learningContent->workflow_status === 'returned',
            422,
            'Only draft or returned content can be submitted.'
        );

        $this->governance->transition(
            content: $learningContent,
            user: $user,
            action: 'submitted',
            toStatus: 'submitted',
            additionalUpdates: [
                'status' => 'draft',
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_remarks' => null,
                'approved_at' => null,
            ],
        );

        return back()->with(
            'success',
            ucfirst($feature) . ' submitted for review successfully.'
        );
    }

    public function approve(LearningContent $learningContent)
    {
        $user = Auth::user();

        $feature = $learningContent->content_type === 'lesson'
            ? 'lessons'
            : 'lectures';

        abort_unless(
            $user->institution?->hasFeature($feature),
            403
        );

        abort_unless(
            $user->hasPermission("{$feature}.review"),
            403
        );

        abort_unless(
            $user->institution?->education_level !== 'tertiary',
            422,
            'Tertiary learning content does not use this approval workflow.'
        );

        abort_unless(
            $learningContent->workflow_status === 'submitted',
            422,
            'Only submitted content can be approved.'
        );

        abort_unless(
            $learningContent->created_by !== $user->id,
            403,
            'You cannot approve your own learning content.'
        );

        $this->governance->transition(
            content: $learningContent,
            user: $user,
            action: 'approved',
            toStatus: 'approved',
            additionalUpdates: [
                'status' => 'draft',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'review_remarks' => null,
                'approved_at' => now(),
            ],
        );

        return back()->with(
            'success',
            ucfirst($feature) . ' approved successfully.'
        );
    }

    public function returnForRevision(
        Request $request,
        LearningContent $learningContent
    ) {
        $user = Auth::user();

        $feature = $learningContent->content_type === 'lesson'
            ? 'lessons'
            : 'lectures';

        abort_unless(
            $user->institution?->hasFeature($feature),
            403
        );

        abort_unless(
            $user->hasPermission("{$feature}.review"),
            403
        );

        abort_unless(
            $user->institution?->education_level !== 'tertiary',
            422,
            'Tertiary learning content does not use this approval workflow.'
        );

        abort_unless(
            $learningContent->workflow_status === 'submitted',
            422,
            'Only submitted content can be returned.'
        );

        abort_unless(
            $learningContent->created_by !== $user->id,
            403,
            'You cannot return your own learning content.'
        );

        $validated = $request->validate([
            'review_remarks' => ['required', 'string', 'max:5000'],
        ]);

        $this->governance->transition(
            content: $learningContent,
            user: $user,
            action: 'returned',
            toStatus: 'returned',
            remarks: $validated['review_remarks'],
            additionalUpdates: [
                'status' => 'draft',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'review_remarks' => $validated['review_remarks'],
                'approved_at' => null,
            ],
        );

        return back()->with(
            'success',
            ucfirst($feature) . ' returned to the creator for revision.'
        );
    }

    public function publish(LearningContent $learningContent)
    {
        $user = Auth::user();

        $feature = $learningContent->content_type === 'lesson'
            ? 'lessons'
            : 'lectures';

        abort_unless(
            $user->institution?->hasFeature($feature),
            403
        );

        abort_unless(
            $user->hasPermission("{$feature}.publish"),
            403
        );

        $isTertiary = $user->institution?->education_level === 'tertiary';

        if ($isTertiary) {
            abort_unless(
                $learningContent->workflow_status === 'draft',
                422,
                'Only draft tertiary content can be published.'
            );
        } else {
            abort_unless(
                $learningContent->workflow_status === 'approved',
                422,
                'Only approved content can be published.'
            );
        }

        $this->governance->transition(
            content: $learningContent,
            user: $user,
            action: 'published',
            toStatus: 'published',
            additionalUpdates: [
                'status' => 'published',
                'published_at' => now(),
            ],
        );

        return back()->with(
            'success',
            ucfirst($feature) . ' published successfully.'
        );
    }

    public function archive(LearningContent $learningContent)
    {
        $user = Auth::user();

        $feature = $learningContent->content_type === 'lesson'
            ? 'lessons'
            : 'lectures';

        abort_unless(
            $user->institution?->hasFeature($feature),
            403
        );

        abort_unless(
            $user->hasPermission("{$feature}.manage"),
            403
        );

        abort_unless(
            $learningContent->workflow_status !== 'archived',
            422,
            'This learning content is already archived.'
        );

        $this->governance->transition(
            content: $learningContent,
            user: $user,
            action: 'archived',
            toStatus: 'archived',
            additionalUpdates: [
                'status' => 'archived',
            ],
        );

        return back()->with(
            'success',
            ucfirst($feature) . ' archived successfully.'
        );
    }

    private function subjectOfferingsFor($user)
    {
        return SubjectOffering::query()
            ->with([
                'subject:id,name,code',
                'schoolClass:id,name,code',
                'arm:id,name,code',
                'teachers:id,name',
            ])
            ->get()
            ->filter(function ($offering) use ($user) {
                return $offering->effectiveTeachers()
                    ->contains('id', $user->id);
            })
            ->values();
    }

    private function courseOfferingsFor($user)
    {
        return CourseOffering::query()
            ->with([
                'course:id,title,code',
                'programme:id,name,code',
            ])
            ->where('lecturer_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();
    }

    private function validateAcademicContext(
        $user,
        ?SubjectOffering $subjectOffering,
        ?CourseOffering $courseOffering
    ): void {
        abort_if(
            $subjectOffering && $courseOffering,
            422,
            'Learning content cannot belong to both a subject and course offering.'
        );

        abort_if(
            ! $subjectOffering && ! $courseOffering,
            422,
            'An academic subject or course offering is required.'
        );

        if ($subjectOffering) {
            abort_unless(
                $user->institution?->education_level !== 'tertiary',
                403
            );

            abort_unless(
                $subjectOffering->effectiveTeachers()
                    ->contains('id', $user->id),
                403
            );
        }

        if ($courseOffering) {
            abort_unless(
                $user->institution?->education_level === 'tertiary',
                403
            );

            abort_unless(
                $courseOffering->lecturer_id === $user->id,
                403
            );
        }
    }
}