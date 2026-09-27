<x-app-layout>
    <div
        class="cx-stage min-h-screen overflow-hidden bg-slate-950 text-slate-100"
        x-data="learningControlRoom()"
    >
        {{-- Ambient background --}}
        <div class="pointer-events-none fixed inset-0 opacity-60">
            <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-indigo-600/10 blur-3xl"></div>
            <div class="absolute right-0 top-1/3 h-96 w-96 rounded-full bg-violet-600/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-indigo-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                        Academic Learning
                    </div>

                    <h1 class="text-2xl font-semibold tracking-tight text-white">
                        Learning Control Room
                    </h1>

                    <p class="mt-1 max-w-2xl text-sm text-slate-400">
                        Manage lessons and lectures across your assigned academic offerings.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if(auth()->user()->institution?->hasFeature('lessons')
                        && auth()->user()->hasPermission('lessons.create'))
                        <a
                            href="{{ route('learning.create', ['type' => 'lesson']) }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-950/30 transition hover:bg-indigo-400"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            New Lesson
                        </a>
                    @endif

                    @if(auth()->user()->institution?->hasFeature('lectures')
                        && auth()->user()->hasPermission('lectures.create'))
                        <a
                            href="{{ route('learning.create', ['type' => 'lecture']) }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-600 hover:bg-slate-800"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            New Lecture
                        </a>
                    @endif
                </div>
            </div>

            {{-- Summary cards --}}
            <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-2xl border border-slate-800 bg-slate-900 px-5 py-4 shadow-xl shadow-black/10">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-400">Total Content</span>
                        <span class="rounded-lg bg-indigo-500/10 p-2 text-indigo-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                        </span>
                    </div>

                    <div class="mt-2 text-2xl font-semibold text-white">
                        {{ $contents->total() }}
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900 px-5 py-4 shadow-xl shadow-black/10">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-400">Lessons</span>
                        <span class="rounded-lg bg-sky-500/10 p-2 text-sky-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                        </span>
                    </div>

                    <div class="mt-2 text-2xl font-semibold text-white">
                        {{ $contents->getCollection()->where('content_type', 'lesson')->count() }}
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900 px-5 py-4 shadow-xl shadow-black/10">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-400">Lectures</span>
                        <span class="rounded-lg bg-violet-500/10 p-2 text-violet-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                        </span>
                    </div>

                    <div class="mt-2 text-2xl font-semibold text-white">
                        {{ $contents->getCollection()->where('content_type', 'lecture')->count() }}
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900 px-5 py-4 shadow-xl shadow-black/10">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-400">Published</span>
                        <span class="rounded-lg bg-emerald-500/10 p-2 text-emerald-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m5 12 4 4L19 6"/>
                            </svg>
                        </span>
                    </div>

                    <div class="mt-2 text-2xl font-semibold text-white">
                        {{ $contents->getCollection()->where('workflow_status', 'published')->count() }}
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="mb-5 rounded-2xl border border-slate-800 bg-slate-900 p-4 shadow-xl shadow-black/10">
                <form
                    method="GET"
                    action="{{ route('learning.index') }}"
                    class="grid gap-3 md:grid-cols-[1fr_auto_auto]"
                >
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m20 20-3.5-3.5"/>
                        </svg>

                        <input
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search lessons or lectures..."
                            class="w-full rounded-xl border border-slate-700 bg-slate-950 py-2.5 pl-10 pr-4 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <select
                        name="type"
                        class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All content</option>
                        <option value="lesson" @selected($type === 'lesson')>Lessons</option>
                        <option value="lecture" @selected($type === 'lecture')>Lectures</option>
                    </select>

                    <button
                        type="submit"
                        class="rounded-xl border border-slate-700 bg-slate-800 px-5 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-700"
                    >
                        Search
                    </button>
                </form>
            </div>

            {{-- Content --}}
            @if($contents->count())

                <div class="space-y-3">
                    @foreach($contents as $content)

                        @php
                            $feature = $content->content_type === 'lesson'
                                ? 'lessons'
                                : 'lectures';

                            $managePermission = $content->content_type === 'lesson'
                                ? 'lessons.manage'
                                : 'lectures.manage';

                            $reviewPermission = $content->content_type === 'lesson'
                                ? 'lessons.review'
                                : 'lectures.review';

                            $publishPermission = $content->content_type === 'lesson'
                                ? 'lessons.publish'
                                : 'lectures.publish';

                            $workflowStatus = $content->workflow_status;

                            $isTertiary = auth()->user()->institution?->education_level === 'tertiary';

                            $canManage = auth()->user()->institution?->hasFeature($feature)
                                && auth()->user()->hasPermission($managePermission);

                            $canReview = auth()->user()->institution?->hasFeature($feature)
                                && auth()->user()->hasPermission($reviewPermission)
                                && $content->created_by !== auth()->id();

                            $canPublish = auth()->user()->institution?->hasFeature($feature)
                                && auth()->user()->hasPermission($publishPermission)
                                && (
                                    ($isTertiary && $workflowStatus === 'draft')
                                    || (! $isTertiary && $workflowStatus === 'approved')
                                );

                            $statusClasses = match ($workflowStatus) {
                                'published' => 'bg-emerald-500/10 text-emerald-300',
                                'approved' => 'bg-sky-500/10 text-sky-300',
                                'submitted' => 'bg-amber-500/10 text-amber-300',
                                'returned' => 'bg-rose-500/10 text-rose-300',
                                'archived' => 'bg-slate-700 text-slate-400',
                                default => 'bg-slate-800 text-slate-300',
                            };
                        @endphp

                        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 shadow-xl shadow-black/10 transition hover:border-slate-700">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                                <div class="min-w-0 flex-1">

                                    <div class="mb-2 flex flex-wrap items-center gap-2">

                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide
                                            {{ $content->content_type === 'lesson'
                                                ? 'bg-sky-500/10 text-sky-300'
                                                : 'bg-violet-500/10 text-violet-300' }}">
                                            {{ $content->content_type }}
                                        </span>

                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses }}">
                                            {{ ucfirst($workflowStatus) }}
                                        </span>

                                    </div>

                                    <h2 class="truncate text-base font-semibold text-white">
                                        {{ $content->title }}
                                    </h2>

                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-400">

                                        @if($content->subjectOffering)
                                            <span>
                                                {{ $content->subjectOffering->subject?->name }}

                                                @if($content->subjectOffering->subject?->code)
                                                    ({{ $content->subjectOffering->subject->code }})
                                                @endif
                                            </span>

                                            <span>
                                                {{ $content->subjectOffering->schoolClass?->name }}

                                                @if($content->subjectOffering->arm)
                                                    · {{ $content->subjectOffering->arm->name }}
                                                @endif
                                            </span>
                                        @endif

                                        @if($content->courseOffering)
                                            <span>
                                                {{ $content->courseOffering->course?->title }}

                                                @if($content->courseOffering->course?->code)
                                                    ({{ $content->courseOffering->course->code }})
                                                @endif
                                            </span>

                                            @if($content->courseOffering->programme)
                                                <span>
                                                    {{ $content->courseOffering->programme->name }}
                                                </span>
                                            @endif
                                        @endif

                                        <span>
                                            By {{ $content->creator?->name ?? 'Unknown' }}
                                        </span>
                                    </div>

                                    @if($content->description)
                                        <p class="mt-3 line-clamp-2 max-w-3xl text-sm leading-6 text-slate-400">
                                            {{ $content->description }}
                                        </p>
                                    @endif

                                    @if($workflowStatus === 'returned' && $content->review_remarks)
                                        <div class="mt-3 rounded-xl border border-rose-500/20 bg-rose-500/5 px-4 py-3">
                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-rose-300">
                                                Reviewer Remarks
                                            </div>

                                            <p class="mt-1 text-sm leading-6 text-slate-300">
                                                {{ $content->review_remarks }}
                                            </p>
                                        </div>
                                    @endif

                                    @if($workflowStatus === 'submitted' && $canReview)
                                        <div class="mt-3 inline-flex items-center gap-2 rounded-xl border border-amber-500/20 bg-amber-500/5 px-3 py-2 text-xs text-amber-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                            Awaiting your review
                                        </div>
                                    @endif

                                </div>

                                <div class="flex shrink-0 flex-wrap items-center gap-2">

                                    @if($canManage)
                                        <a
                                            href="{{ route('learning.edit', $content) }}"
                                            class="rounded-xl border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:bg-slate-800"
                                        >
                                            Edit
                                        </a>
                                    @endif

                                    {{-- Submit --}}
                                    @if($canManage && in_array($workflowStatus, ['draft', 'returned'], true) && ! $isTertiary)
                                        <form method="POST" action="{{ route('learning.submit', $content) }}">
                                            @csrf

                                            <button
                                                type="submit"
                                                class="rounded-xl bg-amber-500/10 px-3 py-2 text-xs font-semibold text-amber-300 transition hover:bg-amber-500/20"
                                            >
                                                Submit
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Review --}}
                                    @if($canReview && $workflowStatus === 'submitted')

                                        <form method="POST" action="{{ route('learning.approve', $content) }}">
                                            @csrf

                                            <button
                                                type="submit"
                                                class="rounded-xl bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-300 transition hover:bg-emerald-500/20"
                                            >
                                                Approve
                                            </button>
                                        </form>

                                        <button
                                            type="button"
                                            @click="openReturnModal(
                                                {{ $content->id }},
                                                @js($content->title)
                                            )"
                                            class="rounded-xl bg-rose-500/10 px-3 py-2 text-xs font-semibold text-rose-300 transition hover:bg-rose-500/20"
                                        >
                                            Return
                                        </button>

                                    @endif

                                    {{-- Publish --}}
                                    @if($canPublish)
                                        <form method="POST" action="{{ route('learning.publish', $content) }}">
                                            @csrf

                                            <button
                                                type="submit"
                                                class="rounded-xl bg-indigo-500/10 px-3 py-2 text-xs font-semibold text-indigo-300 transition hover:bg-indigo-500/20"
                                            >
                                                Publish
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Archive --}}
                                    @if($canManage && $workflowStatus !== 'archived')
                                        <form
                                            method="POST"
                                            action="{{ route('learning.archive', $content) }}"
                                            onsubmit="return confirm('Archive this learning content?');"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="rounded-xl border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-400 transition hover:bg-slate-800 hover:text-slate-300"
                                            >
                                                Archive
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </div>
                        </div>

                    @endforeach
                </div>

                <div class="mt-5">
                    {{ $contents->links() }}
                </div>

            @else

                <div class="rounded-2xl border border-dashed border-slate-800 bg-slate-900 px-6 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-800 text-slate-400">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        </svg>
                    </div>

                    <h2 class="mt-4 text-sm font-semibold text-white">
                        No learning content found
                    </h2>

                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                        Lessons and lectures created for your academic offerings will appear here.
                    </p>
                </div>

            @endif
        </div>
    </div>

    {{-- Return modal --}}
    <div
        x-show="returnModalOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 backdrop-blur-sm"
    >
        <div
            x-show="returnModalOpen"
            x-transition
            @click.outside="closeReturnModal()"
            class="w-full max-w-lg rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl shadow-black/40"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-300">
                        Return for Revision
                    </div>

                    <h2 class="mt-2 text-lg font-semibold text-white">
                        Return learning content
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-slate-400">
                        Add clear remarks so the creator knows what needs to be revised.
                    </p>
                </div>

                <button
                    type="button"
                    @click="closeReturnModal()"
                    class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-800 hover:text-slate-300"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 6l12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>

            <div class="mt-5 rounded-xl border border-slate-800 bg-slate-950 px-4 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    Content
                </div>

                <div
                    class="mt-1 truncate text-sm font-medium text-slate-200"
                    x-text="returnTitle"
                ></div>
            </div>

            <form
                method="POST"
                :action="returnUrl"
                class="mt-5"
            >
                @csrf

                <label
                    for="review_remarks"
                    class="text-sm font-medium text-slate-300"
                >
                    Reviewer remarks
                </label>

                <textarea
                    id="review_remarks"
                    name="review_remarks"
                    rows="5"
                    required
                    maxlength="5000"
                    placeholder="Explain what should be corrected or improved..."
                    class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm leading-6 text-slate-100 placeholder:text-slate-500 focus:border-rose-500 focus:ring-rose-500"
                ></textarea>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="closeReturnModal()"
                        class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-slate-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-rose-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-400"
                    >
                        Return for Revision
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function learningControlRoom() {
            return {
                returnModalOpen: false,
                returnUrl: '',
                returnTitle: '',

                openReturnModal(id, title) {
                    this.returnUrl = @js(url('/learning')) + '/' + id + '/return';
                    this.returnTitle = title;
                    this.returnModalOpen = true;

                    this.$nextTick(() => {
                        document.getElementById('review_remarks')?.focus();
                    });
                },

                closeReturnModal() {
                    this.returnModalOpen = false;
                    this.returnUrl = '';
                    this.returnTitle = '';
                }
            }
        }
    </script>
</x-app-layout>