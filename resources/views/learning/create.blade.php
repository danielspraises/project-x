<x-app-layout>
<x-slot name="header">
<div>
    <div class="cx-kicker">Academic command center</div>
    <div class="font-display text-base font-semibold" style="color:var(--ink)">
        {{ $type === 'lecture' ? 'Lectures' : 'Lessons' }}
    </div>
</div>
</x-slot>

@php
$isLecture = $type === 'lecture';
$contentLabel = $isLecture ? 'Lecture' : 'Lesson';
$contextLabel = $isLecture ? 'Course / Programme' : 'Subject / Class';
@endphp

<div class="px-4 py-5 sm:px-6 lg:px-8">
<div class="mx-auto max-w-[1200px] space-y-5">

@if($errors->any())

<div class="rounded-2xl border px-4 py-3 text-sm"
     style="border-color:rgba(239,68,68,.25);background:rgba(239,68,68,.08);color:#ef4444">
    <ul class="list-disc space-y-1 pl-5">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- INTRO CARD --}}

<section class="cx-stage cx-reveal" data-cx-tilt>
<div class="cx-panel cx-grid cx-glow relative overflow-hidden rounded-[32px] p-6 sm:p-8">


<div class="absolute right-8 top-8 h-24 w-24 rounded-full border border-white/10"></div>
<div class="absolute right-14 top-14 h-10 w-10 rounded-full border border-white/10"></div>

<div class="relative max-w-3xl">

    <div class="cx-kicker">
        {{ $isLecture ? 'Tertiary learning' : 'School learning' }}
    </div>

    <h1 class="cx-title mt-3">
        Create a {{ strtolower($contentLabel) }}<br>
        <span style="color:var(--brand-primary)">
            for your learners.
        </span>
    </h1>

    <p class="cx-subtitle mt-3 max-w-2xl">
        Add the academic context, title, learning material and optional media.
    </p>

    <div class="mt-5 flex flex-wrap gap-3">
        <div class="cx-stat">
            <div class="text-lg font-display font-bold">01</div>
            <div class="mt-0.5 text-xs text-slate-500">Context</div>
        </div>

        <div class="cx-stat">
            <div class="text-lg font-display font-bold">02</div>
            <div class="mt-0.5 text-xs text-slate-500">Content</div>
        </div>

        <div class="cx-stat">
            <div class="text-lg font-display font-bold">03</div>
            <div class="mt-0.5 text-xs text-slate-500">Media</div>
        </div>
    </div>

</div>


</div>
</section>

<form
    method="POST"
    action="{{ route('learning.store') }}"
    enctype="multipart/form-data"
    id="learning-content-form"
    class="space-y-4"
>
@csrf

<input type="hidden" name="content_type" value="{{ $type }}">

{{-- ACADEMIC CONTEXT --}}

<section class="ui-panel p-5 sm:p-6">


<div class="mb-4">
    <div class="cx-kicker">Academic context</div>
    <h2 class="mt-1 font-display text-xl font-semibold" style="color:var(--ink)">
        {{ $contextLabel }}
    </h2>
</div>

@if($isLecture)

<div class="grid gap-4 sm:grid-cols-2">

    <div>
        <label for="course_offering_id" class="ui-label mb-1.5">
            Course
        </label>

        <select
            id="course_offering_id"
            name="course_offering_id"
            class="cx-input w-full rounded-xl px-3.5 py-2.5 text-sm"
        >
            <option value="">Select course</option>

            @foreach($courseOfferings as $offering)
                <option
                    value="{{ $offering->id }}"
                    @selected(old('course_offering_id') == $offering->id)
                >
                    {{ $offering->course?->code ? $offering->course->code . ' — ' : '' }}
                    {{ $offering->course?->title }}
                    @if($offering->programme)
                        — {{ $offering->programme->name }}
                    @endif
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="ui-label mb-1.5">
            Programme
        </label>

        <div
            class="cx-input flex min-h-[43px] items-center rounded-xl px-3.5 py-2.5 text-sm"
            style="color:var(--muted)"
        >
            Selected with course
        </div>
    </div>

</div>

@else

<div>
    <label for="subject_offering_id" class="ui-label mb-1.5">
        Subject / Class
    </label>

    <select
        id="subject_offering_id"
        name="subject_offering_id"
        class="cx-input w-full rounded-xl px-3.5 py-2.5 text-sm"
    >
        <option value="">Select subject / class</option>

        @foreach($subjectOfferings as $offering)
            <option
                value="{{ $offering->id }}"
                @selected(old('subject_offering_id') == $offering->id)
            >
                {{ $offering->subject?->code ? $offering->subject->code . ' — ' : '' }}
                {{ $offering->subject?->name }}
                @if($offering->schoolClass)
                    — {{ $offering->schoolClass->name }}
                @endif
                @if($offering->arm)
                    {{ ' ' . $offering->arm->name }}
                @endif
            </option>
        @endforeach
    </select>
</div>

@endif


</section>

{{-- CONTENT --}}

<section class="ui-panel p-5 sm:p-6">


<div class="mb-4">
    <div class="cx-kicker">{{ $contentLabel }}</div>
    <h2 class="mt-1 font-display text-xl font-semibold" style="color:var(--ink)">
        Learning material
    </h2>
</div>

<div class="grid gap-4 sm:grid-cols-2">

    <div>
        <label for="content_kind" class="ui-label mb-1.5">
            Type
        </label>

        <select
            id="content_kind"
            name="content_kind"
            class="cx-input w-full rounded-xl px-3.5 py-2.5 text-sm"
            required
        >
            <option value="">Select type</option>

            @foreach($contentKinds as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(old('content_kind') === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="title" class="ui-label mb-1.5">
            Title
        </label>

        <input
            id="title"
            name="title"
            type="text"
            value="{{ old('title') }}"
            class="cx-input w-full rounded-xl px-3.5 py-2.5 text-sm"
            placeholder="Enter title"
            required
        >
    </div>

</div>

<div class="mt-4">

    <label for="content-editor" class="ui-label mb-1.5">
        Content
    </label>

    <div
        class="rich-editor overflow-hidden rounded-2xl border"
        style="border-color:var(--line-strong);background:var(--input)"
    >

        <div
            id="content-toolbar"
            class="flex flex-wrap items-center gap-1 border-b p-2"
            style="border-color:var(--line);background:var(--control)"
        >

            <button type="button" data-command="bold" class="editor-tool" title="Bold">
                <strong>B</strong>
            </button>

            <button type="button" data-command="italic" class="editor-tool" title="Italic">
                <em>I</em>
            </button>

            <span class="mx-1 h-5 w-px" style="background:var(--line-strong)"></span>

            <button type="button" data-command="heading2" class="editor-tool" title="Heading 2">
                H2
            </button>

            <button type="button" data-command="heading3" class="editor-tool" title="Heading 3">
                H3
            </button>

            <button type="button" data-command="bulletList" class="editor-tool" title="Bullet list">
                •
            </button>

            <button type="button" data-command="orderedList" class="editor-tool" title="Numbered list">
                1.
            </button>

            <button type="button" data-command="blockquote" class="editor-tool" title="Quote">
                “”
            </button>

            <button type="button" data-command="codeBlock" class="editor-tool" title="Code block">
                &lt;/&gt;
            </button>

            <span class="mx-1 h-5 w-px" style="background:var(--line-strong)"></span>

            <button type="button" data-command="undo" class="editor-tool" title="Undo">
                ↶
            </button>

            <button type="button" data-command="redo" class="editor-tool" title="Redo">
                ↷
            </button>

        </div>

        <div id="content-editor" class="rich-editor-content"></div>

    </div>

    <input
        type="hidden"
        name="content"
        id="content"
        value="{{ old('content') }}"
    >

</div>


</section>

{{-- MEDIA --}}

<section class="ui-panel p-5 sm:p-6">


<div class="mb-4">
    <div class="cx-kicker">Optional</div>
    <h2 class="mt-1 font-display text-xl font-semibold" style="color:var(--ink)">
        Media
    </h2>
</div>

<div class="grid gap-4 lg:grid-cols-2">

    {{-- AUDIO --}}
    <div
        class="rounded-2xl border p-4"
        style="border-color:var(--line);background:var(--control)"
    >

        <div class="mb-3 flex items-center gap-3">

            <div
                class="flex h-9 w-9 items-center justify-center rounded-xl"
                style="background:rgba(139,92,246,.12);color:#8b5cf6"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M9 18V5l10-2v13"/>
                    <circle cx="6" cy="18" r="3"/>
                    <circle cx="16" cy="16" r="3"/>
                </svg>
            </div>

            <div>
                <div class="text-sm font-bold" style="color:var(--ink)">
                    Audio
                </div>
                <div class="text-[11px]" style="color:var(--muted)">
                    Max 10 MB
                </div>
            </div>

        </div>

        <input
            type="file"
            name="audio"
            id="audio"
            accept="audio/mpeg,audio/wav,audio/x-wav,audio/ogg,audio/webm"
            class="cx-input w-full rounded-xl px-3 py-2 text-sm"
        >

        <div class="my-3 flex items-center gap-2 text-[10px] uppercase tracking-wider"
             style="color:var(--muted)">
            <span class="h-px flex-1" style="background:var(--line)"></span>
            or link
            <span class="h-px flex-1" style="background:var(--line)"></span>
        </div>

        <input
            type="url"
            name="audio_url"
            id="audio_url"
            value="{{ old('audio_url') }}"
            class="cx-input w-full rounded-xl px-3.5 py-2.5 text-sm"
            placeholder="https://..."
        >

    </div>

    {{-- VIDEO --}}
    <div
        class="rounded-2xl border p-4"
        style="border-color:var(--line);background:var(--control)"
    >

        <div class="mb-3 flex items-center gap-3">

            <div
                class="flex h-9 w-9 items-center justify-center rounded-xl"
                style="background:rgba(6,182,212,.12);color:#06b6d4"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="5" width="13" height="14" rx="2"/>
                    <path d="m16 10 5-3v10l-5-3"/>
                </svg>
            </div>

            <div>
                <div class="text-sm font-bold" style="color:var(--ink)">
                    Video
                </div>
                <div class="text-[11px]" style="color:var(--muted)">
                    Max 20 MB
                </div>
            </div>

        </div>

        <input
            type="file"
            name="video"
            id="video"
            accept="video/mp4,video/webm,video/ogg"
            class="cx-input w-full rounded-xl px-3 py-2 text-sm"
        >

        <div class="my-3 flex items-center gap-2 text-[10px] uppercase tracking-wider"
             style="color:var(--muted)">
            <span class="h-px flex-1" style="background:var(--line)"></span>
            or link
            <span class="h-px flex-1" style="background:var(--line)"></span>
        </div>

        <input
            type="url"
            name="video_url"
            id="video_url"
            value="{{ old('video_url') }}"
            class="cx-input w-full rounded-xl px-3.5 py-2.5 text-sm"
            placeholder="https://..."
        >

    </div>

</div>


</section>

{{-- ACTIONS --}}

<div class="flex items-center justify-end gap-3">


<a
    href="{{ route('learning.index') }}"
    class="rounded-xl border px-4 py-2.5 text-sm font-semibold"
    style="border-color:var(--line-strong);color:var(--muted);background:var(--control)"
>
    Cancel
</a>

<button
    type="submit"
    class="rounded-xl px-5 py-2.5 text-sm font-bold text-white"
    style="background:var(--brand-primary)"
>
    Create {{ $contentLabel }}
</button>


</div>

</form>

</div>
</div>

<style>
.editor-tool {
    display:inline-flex;
    min-width:32px;
    height:30px;
    align-items:center;
    justify-content:center;
    border-radius:8px;
    padding:0 8px;
    color:var(--ink);
    font-size:12px;
    font-weight:700;
    transition:background .15s ease,color .15s ease;
}

.editor-tool:hover,
.editor-tool.is-active {
    background:color-mix(in srgb,var(--brand-primary) 14%,transparent);
    color:var(--brand-primary);
}

.rich-editor-content {
    min-height:260px;
    padding:18px;
    outline:none;
    color:var(--ink);
    font-size:.94rem;
    line-height:1.7;
}

.rich-editor-content p {
    margin:0 0 .75rem;
}

.rich-editor-content h2 {
    margin:1rem 0 .5rem;
    font-size:1.25rem;
    font-weight:800;
}

.rich-editor-content h3 {
    margin:.9rem 0 .45rem;
    font-size:1.05rem;
    font-weight:800;
}

.rich-editor-content ul,
.rich-editor-content ol {
    margin:.5rem 0 .8rem;
    padding-left:1.5rem;
}

.rich-editor-content ul {
    list-style:disc;
}

.rich-editor-content ol {
    list-style:decimal;
}

.rich-editor-content blockquote {
    margin:.8rem 0;
    border-left:3px solid var(--brand-primary);
    padding-left:1rem;
    color:var(--muted);
}

.rich-editor-content pre {
    margin:.8rem 0;
    overflow-x:auto;
    border-radius:10px;
    padding:.8rem 1rem;
    background:var(--canvas-2);
    font-size:.82rem;
}

.rich-editor-content code {
    border-radius:5px;
    padding:.1rem .3rem;
    background:var(--canvas-2);
    font-size:.88em;
}

.rich-editor-content pre code {
    padding:0;
    background:transparent;
}

.rich-editor-content > p:last-child {
    margin-bottom:0;
}

.rich-editor-content:focus {
    box-shadow:inset 0 0 0 2px color-mix(in srgb,var(--brand-primary) 22%,transparent);
}

.rich-editor-content:empty::before {
    content:'Write the learning material here...';
    color:var(--muted);
    pointer-events:none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editorElement = document.getElementById('content-editor');
    const hiddenInput = document.getElementById('content');
    const form = document.getElementById('learning-content-form');

    if (!editorElement || !hiddenInput || !form) {
        return;
    }

    const initialContent = @json(old('content', ''));

    const editor = new window.TiptapEditor({
        element: editorElement,
        extensions: [
            window.TiptapStarterKit,
        ],
        content: initialContent,

        onUpdate: ({ editor }) => {
            hiddenInput.value = editor.getHTML();
        },

        onSelectionUpdate: ({ editor }) => {
            updateToolbar(editor);
        },

        onTransaction: ({ editor }) => {
            updateToolbar(editor);
        },
    });

    function updateToolbar(editor) {
        document.querySelectorAll('.editor-tool').forEach(button => {
            const command = button.dataset.command;
            let active = false;

            if (command === 'bold') {
                active = editor.isActive('bold');
            } else if (command === 'italic') {
                active = editor.isActive('italic');
            } else if (command === 'heading2') {
                active = editor.isActive('heading', { level: 2 });
            } else if (command === 'heading3') {
                active = editor.isActive('heading', { level: 3 });
            } else if (command === 'bulletList') {
                active = editor.isActive('bulletList');
            } else if (command === 'orderedList') {
                active = editor.isActive('orderedList');
            } else if (command === 'blockquote') {
                active = editor.isActive('blockquote');
            } else if (command === 'codeBlock') {
                active = editor.isActive('codeBlock');
            }

            button.classList.toggle('is-active', active);
        });
    }

    document.querySelectorAll('.editor-tool').forEach(button => {
        button.addEventListener('click', () => {
            const command = button.dataset.command;

            if (command === 'bold') {
                editor.chain().focus().toggleBold().run();
            } else if (command === 'italic') {
                editor.chain().focus().toggleItalic().run();
            } else if (command === 'heading2') {
                editor.chain().focus().toggleHeading({ level: 2 }).run();
            } else if (command === 'heading3') {
                editor.chain().focus().toggleHeading({ level: 3 }).run();
            } else if (command === 'bulletList') {
                editor.chain().focus().toggleBulletList().run();
            } else if (command === 'orderedList') {
                editor.chain().focus().toggleOrderedList().run();
            } else if (command === 'blockquote') {
                editor.chain().focus().toggleBlockquote().run();
            } else if (command === 'codeBlock') {
                editor.chain().focus().toggleCodeBlock().run();
            } else if (command === 'undo') {
                editor.chain().focus().undo().run();
            } else if (command === 'redo') {
                editor.chain().focus().redo().run();
            }

            updateToolbar(editor);
        });
    });

    form.addEventListener('submit', () => {
        hiddenInput.value = editor.getHTML();
    });

    hiddenInput.value = editor.getHTML();
    updateToolbar(editor);
});
</script>

</x-app-layout>
