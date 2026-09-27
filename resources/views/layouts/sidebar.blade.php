{{-- Positioned to start exactly below the fixed top bar (top-16 = 4rem = the top bar's height), so the two can never overlap. --}}

<aside
    class="ui-sidebar fixed left-0 top-[72px] bottom-0 z-40 flex flex-col overflow-hidden transition-all duration-200 sm:translate-x-0"
    :class="{
        'w-64': !collapsed,
        'w-[72px]': collapsed,
        '-translate-x-full': !mobileOpen,
        'translate-x-0': mobileOpen,
    }"
>
    <nav class="sidebar-scroll flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
               {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span x-show="!collapsed" x-transition.opacity class="truncate">Dashboard</span>
        </a>

        {{-- Lecturer/HOD result links live here, outside the institution.setup/institution.view
             gate below, because those roles hold neither of those permissions — they were
             previously invisible to anyone but the ICT Admin. The ICT Admin "Results" (Control
             Room) link itself is positioned further down, next to Course Offerings/Subjects,
             since results.view is ICT-Admin-only anyway. --}}
        @if (! auth()->user()->isSuperAdmin())
            @php($resultEducationLevel = auth()->user()->institution->education_level ?? 'tertiary')

            {{-- Lecturer result entry — only for tertiary users who can enter but
                 aren't the ICT Admin (who already has the Control Room above). --}}
            @if ($resultEducationLevel === 'tertiary' && auth()->user()->hasPermission('results.enter') && ! auth()->user()->hasPermission('results.view'))
                <a href="{{ route('lecturer.results.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('lecturer.results.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Result Entry</span>
                </a>
            @endif

            {{-- HOD department approval queue — tertiary only. --}}
            @if ($resultEducationLevel === 'tertiary' && auth()->user()->hasPermission('results.approve'))
                <a href="{{ route('hod.results.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('hod.results.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Department Approvals</span>
                </a>
            @endif

            {{-- Subject Teacher result entry — basic-ed only, same shape as Lecturer above. --}}
            @if ($resultEducationLevel !== 'tertiary' && auth()->user()->hasPermission('results.enter') && ! auth()->user()->hasPermission('results.view'))
                <a href="{{ route('subject-teacher.results.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('subject-teacher.results.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Result Entry</span>
                </a>
            @endif

            {{-- Class Teacher approval queue — basic-ed only, same shape as HOD above. --}}
            @if ($resultEducationLevel !== 'tertiary' && auth()->user()->hasPermission('results.approve'))
                <a href="{{ route('class-teacher.results.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('class-teacher.results.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Class Approvals</span>
                </a>
            @endif
        @endif

        @if (auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.institutions.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                   {{ request()->routeIs('admin.institutions.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21h2m0 0h10M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 8v-4a1 1 0 011-1h0a1 1 0 011 1v4" />
                </svg>
                <span x-show="!collapsed" x-transition.opacity class="truncate">Institutions</span>
            </a>
            <a href="{{ route('admin.settings.edit') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                   {{ request()->routeIs('admin.settings.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"></circle>
                </svg>
                <span x-show="!collapsed" x-transition.opacity class="truncate">Settings</span>
            </a>
        @endif

        @if ((auth()->user()->hasPermission('institution.setup') || auth()->user()->hasPermission('institution.view')) && ! auth()->user()->isSuperAdmin())
            @php($educationLevel = auth()->user()->institution->education_level ?? 'tertiary')
            @php($periodLabel = auth()->user()->institution->periodLabel() ?? 'Term')

            <a href="{{ route('ict-admin.sessions.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                   {{ request()->routeIs('ict-admin.sessions.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span x-show="!collapsed" x-transition.opacity class="truncate">Sessions</span>
            </a>
            <a href="{{ route('ict-admin.terms.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                   {{ request()->routeIs('ict-admin.terms.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span x-show="!collapsed" x-transition.opacity class="truncate">{{ $periodLabel }}s</span>
            </a>

            @if (auth()->user()->institution->hasFeature('institution_structure'))
                @if ($educationLevel === 'tertiary')
                <a href="{{ route('ict-admin.faculties.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('ict-admin.faculties.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.42A12.02 12.02 0 0122 9v3a12 12 0 01-20 8.94V12a12 12 0 013.84-4.42L12 14z" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Faculties</span>
                </a>
                <a href="{{ route('ict-admin.departments.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('ict-admin.departments.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Departments</span>
                </a>
                <a href="{{ route('ict-admin.programmes.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('ict-admin.programmes.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Programmes</span>
                </a>
                @if (auth()->user()->institution->hasFeature('courses'))
                    <a href="{{ route('ict-admin.courses.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                           {{ request()->routeIs('ict-admin.courses.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                        <span x-show="!collapsed" x-transition.opacity class="truncate">Courses</span>
                    </a>
                    <a href="{{ route('ict-admin.course-offerings.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                           {{ request()->routeIs('ict-admin.course-offerings.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span x-show="!collapsed" x-transition.opacity class="truncate">Course Offerings</span>
                    </a>

                    {{-- ICT Admin's own Results (Control Room) link — placed right after
                         Course Offerings since that's its natural place in the hierarchy.
                         results.view is ICT-Admin-only, so no gating concern moving it here. --}}
                    @if (auth()->user()->hasPermission('results.view'))
                        <a href="{{ route('ict-admin.results.index') }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                               {{ request()->routeIs('ict-admin.results.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5h6m-5 4h4m-4 4h4m-4 4h4" />
                            </svg>
                            <span x-show="!collapsed" x-transition.opacity class="truncate">Results</span>
                        </a>
                    @endif
                @endif

                {{-- Fix: this link previously only existed in the basic-ed branch below,
                     leaving tertiary institutions with no way to reach user management
                     at all (HOD/Lecturer/Department Officer accounts were unreachable). --}}
                @if (auth()->user()->hasPermission('users.manage'))
                    <a href="{{ route('ict-admin.users.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                           {{ request()->routeIs('ict-admin.users.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />
                        </svg>
                        <span x-show="!collapsed" x-transition.opacity class="truncate">Staff</span>
                    </a>
                @endif

            @else
                <a href="{{ route('ict-admin.classes.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                    {{ request()->routeIs('ict-admin.classes.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Classes</span>
                </a>
                <a href="{{ route('ict-admin.arms.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                    {{ request()->routeIs('ict-admin.arms.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Arms</span>
                </a>
                <a href="{{ route('ict-admin.subjects.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                    {{ request()->routeIs('ict-admin.subjects.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Subjects</span>
                </a>

                {{-- ICT Admin's own Results (Control Room) link — placed right after
                     Subjects, the basic-ed equivalent of Course Offerings. --}}
                @if (auth()->user()->hasPermission('results.view'))
                    <a href="{{ route('ict-admin.results.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                           {{ request()->routeIs('ict-admin.results.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5h6m-5 4h4m-4 4h4m-4 4h4" />
                        </svg>
                        <span x-show="!collapsed" x-transition.opacity class="truncate">Results</span>
                    </a>
                @endif

                @if (auth()->user()->hasPermission('users.manage'))
                <a href="{{ route('ict-admin.users.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('ict-admin.users.*') || request()->routeIs('ict-admin.class-teachers.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Teachers</span>
                </a>
                @endif
            @endif
            @endif

            @if (auth()->user()->institution->hasFeature('students'))
                <a href="{{ route('ict-admin.students.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                   {{ request()->routeIs('ict-admin.students.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span x-show="!collapsed" x-transition.opacity class="truncate">Students</span>
            </a>
            @endif

            @if (auth()->user()->hasPermission('roles.manage') && auth()->user()->institution->hasFeature('custom_roles'))
                <a href="{{ route('ict-admin.roles.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('ict-admin.roles.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Roles</span>
                </a>
            @endif

            @if (auth()->user()->hasPermission('billing.view'))
                <a href="{{ route('ict-admin.billing.show') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('ict-admin.billing.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Billing</span>
                </a>
            @endif

            @if (auth()->user()->hasPermission('institution.setup'))
                <a href="{{ route('ict-admin.settings.edit') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                       {{ request()->routeIs('ict-admin.settings.*') ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"></circle>
                    </svg>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">Settings</span>
                </a>
            @endif
        @endif
    </nav>
</aside>
