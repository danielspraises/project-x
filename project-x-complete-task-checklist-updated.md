# Project-X — Complete Task Checklist

*Multi-tenant school management SaaS — Laravel / Blade / Alpine.js / MySQL*

Legend: `[x]` done and delivered · `[ ]` not yet built · `[~]` in progress / needs validation

---

## 1. Platform / Super Admin

- [x] Institution creation (with education level, type, ownership, initial ICT Admin auto-created)
- [x] Institution edit (details, branding, billing config)
- [x] Institution deletion (correct FK-ordered teardown in a transaction)
- [x] Institution list/index page
- [x] Super Admin can add additional/replacement ICT Admins to an institution
- [x] Single-ICT-Admin-per-institution rule (only Super Admin can create/edit/remove that role)
- [x] Platform-wide settings (Super Admin's own branding)
- [x] Duplicate-email validation on ICT Admin creation (scoped per institution)
- [x] Institution appearance defaults for Light/Dark mode colours
- [ ] Platform admin panel enhancements (subscriptions, plans, usage/storage monitoring, system health) — long-term

## 2. Feature Access System

- [x] `features` / `institution_features` tables, seeded catalog
- [x] `Institution::hasFeature()` helper
- [x] `feature:` middleware
- [x] Super Admin toggle UI (institution edit page)
- [x] Middleware applied to real routes (Courses, Bulk Import, Custom Roles, Notes)
- [x] Sidebar links hide themselves per disabled feature
- [ ] Route-level gating for `institution_structure` and `students` features (currently sidebar-only, not route-enforced)
- [ ] Module-based pricing tiers tied to feature sets — long-term

## 3. Institution Structure — Tertiary

- [x] Faculties (CRUD)
- [x] Departments (CRUD + optional HOD assignment)
- [x] Programmes (CRUD)
- [x] Academic Sessions (CRUD)
- [x] Terms/Semesters (CRUD, institution-configurable label independent of education level)
- [x] Courses (CRUD + optional multi-lecturer assignment)
- [x] Course Offerings (CRUD — course tied to term/programme/level/lecturer)
- [x] Course Registration (bulk-assign students to an offering)

## 4. Institution Structure — Basic Education (Secondary/Primary)

- [x] Classes (CRUD)
- [x] Arms (CRUD)
- [x] Shared Sessions/Terms with tertiary (same tables)
- [x] Subjects module (reusable institution-level Subject catalogue)
- [x] Class-first Subject management UI
- [x] Subject Offerings (Subject tied to Class + Term, with class-wide or arm-specific scope)
- [x] General/class-wide subject offering shared across all arms
- [x] Arm-specific subject offerings
- [x] Multiple teachers per subject offering (lead + supporting teachers)
- [x] Class Teacher assignments (class-wide and arm-level)
- [x] Default/class teacher fallback when explicit subject teachers are not assigned
- [x] Subject teacher fallback setting
- [x] Subject registration
- [x] Bulk subject offering setup
- [x] Bulk class-teacher setup
- [x] Subject registration bulk selection controls
- [x] Subject offering/session/term isolation
- [x] Protection against conflicting class-wide vs arm-specific offerings for the same subject/context

## 5. Students

- [x] Student CRUD
- [x] Live field validation for student name and email fields
- [x] Bulk CSV import (students only)
- [ ] Student academic enrolment/history by academic session and class/arm — preserve the same student record while tracking progression, repetition, transfer and historical placement
- [ ] Bulk CSV import for Faculties/Departments/Programmes/Classes
- [ ] Student lifecycle states (applicant → admitted → enrolled → graduated → alumni; withdrawal, suspension, deferral, transfer) — long-term

## 6. Users, Roles & Access

- [x] User CRUD
- [x] Custom role creation (ICT Admin, whitelisted permission set)
- [x] Department-scoping for HOD/Department Officer
- [x] Self-edit lockout (can't change own role/status/department)
- [x] "Administrator" wording pass (replacing "Super Admin" in ICT-facing text)
- [ ] Notify existing ICT Admin(s) when Super Admin adds a new one (needs in-house messaging module)
- [ ] Person/multi-role architecture (one person, multiple simultaneous roles) — long-term, currently one role per user

## 7. Notes

- [x] Polymorphic notes system (Faculty, Department, Programme, Class, Student, Course)
- [ ] Extend to other entities as they're built (e.g. Subjects, Results)

## 8. Billing

- [x] Institution billing config (Super-Admin-owned: cycle, rate, currency, dates, status)
- [x] ICT Admin: read-only billing status view
- [x] ICT Admin: structured change-request form (cycle, commencement date, reason)
- [x] Super Admin: per-institution approve/reject
- [x] Super Admin: unified cross-institution billing overview page
- [ ] Subscription/plan engine (tiered pricing, free trial automation) — long-term
- [ ] Payment gateway integration — long-term
- [ ] Invoices/receipts generation — long-term

## 9. Notifications

- [x] Database notifications infrastructure (Laravel notifications table)
- [x] Billing-change-request notification to Super Admin, with link to the request
- [x] Notification bell (unread count, mark-as-read, mark-all-read)
- [ ] Extend notification types beyond billing (e.g. new institution created, feature toggled, results submitted) as those events are built
- [ ] Email/SMS delivery channels (currently database-only) — long-term
- [ ] In-house messaging module (internal chat/announcements) — long-term

## 10. UI/UX Infrastructure

- [x] Sidebar + topbar shell (collapsible, mobile hamburger overlay)
- [x] Institution/platform branding applied via CSS custom properties
- [x] Light/Dark application theme toggle
- [x] Persistent Light/Dark theme preference
- [x] Institution-specific Light/Dark colour configuration
- [x] Super Admin institution colour fallback
- [x] ICT Admin institution colour override
- [x] Theme fallback chain: ICT Admin custom → Super Admin institution default → application default
- [x] Coloured solid sidebar using institution branding
- [x] Theme-aware topbar and profile/avatar styling
- [x] Theme-aware buttons with readable hover/focus/active states
- [x] Toasts, modals, animated delete-confirm, spinner buttons, skeleton loaders
- [x] Searchable single-select component (`x-searchable-select`)
- [x] Searchable multi-select component (`x-searchable-multi-select`)
- [x] Dropdown "Actions" menus (replacing cramped inline links)
- [x] Sidebar scrollbar styling
- [x] Layout scroll fix (reverted to proven natural full-page scroll after a regression)
- [x] Subject Offerings page centred content layout
- [x] "How it works" modal centred in the viewport
- [x] Profile page visual refresh
- [ ] Apply searchable-select to any remaining plain `<select>` elements as new forms are built (standing philosophy, ongoing)
- [ ] Full application-wide UI consistency pass (remaining legacy screens) — next UI batch

## 11. Security & Account Management

- [x] Email verification infrastructure (`MustVerifyEmail`, not enforced — admin-created accounts auto-verified)
- [ ] Email verification enforcement (needs real mail/SMTP setup, e.g. Mailtrap for local testing) — deferred, relevant once self-registration exists
- [ ] Two-Factor Authentication (Fortify or similar) — deliberately deferred as its own dedicated task
- [ ] Login history / audit trail for auth events — long-term
- [ ] Rate limiting on auth endpoints — long-term

## 12. Result Management (Core Version 1 Product) — Not Started

### Assessment & Grading
- [ ] Assessment structure configuration (institution-defined CA/Exam weighting, e.g. CA 30% / Exam 70%)
- [ ] Grading Engine (institution-configurable score → grade → grade-point scale)

### Result Entry
- [ ] Result entry screen (lecturer/teacher selects session/term/course/class, enters scores, saves draft)
- [ ] Draft save/edit before submission
- [ ] Bulk result entry / spreadsheet-style score entry
- [ ] Validation and score boundaries
- [ ] Teacher/lecturer scope enforcement on result entry

### Calculation
- [ ] GPA calculation (per term/semester)
- [ ] CGPA calculation (cumulative)
- [ ] Carryover/repeat tracking
- [ ] Academic standing calculation (probation, good standing, etc. — tertiary)
- [ ] Promotion/repeat determination (basic education)

### Approval & Publication
- [ ] Result Approval Workflow (built on the `workflow_templates`/`workflow_stages` shape: Lecturer → HOD → Registry/ICT → Publish)
- [ ] Result publication + locking (post-publish edits require authorization)
- [ ] Result change versioning/audit (previous value, new value, who, when, why)

### Outputs
- [ ] Result sheets / report card generation
- [ ] Basic transcript generation
- [ ] PDF export for result sheets/transcripts
- [ ] Result verification / QR verification foundation

## 13. Data Import & Operational Tools

- [ ] Bulk import Faculties
- [ ] Bulk import Departments
- [ ] Bulk import Programmes
- [ ] Bulk import Classes
- [ ] Bulk import Arms
- [ ] Bulk import Subjects
- [ ] Bulk import Subject Offerings
- [ ] Import validation preview + error report
- [ ] Import duplicate detection and safe retry handling

## 14. Academic Operations — Next Modules

- [ ] Attendance foundation (student attendance by class/course offering)
- [ ] Attendance registers and bulk marking
- [ ] Teacher/Lecturer workload view
- [ ] Class timetable foundation
- [ ] Examination timetable foundation
- [ ] Student promotion workflow for Primary/Secondary
- [ ] Bulk student promotion / batch progression for Primary/Secondary
- [ ] Academic calendar / institution events

## 15. Long-Term Platform Vision (Deferred by Design)

- [ ] Generic configurable academic-structure engine (replacing the hardcoded Faculty/Department vs Class/Arm branches)
- [ ] REST API layer (prerequisite for any mobile/Flutter client)
- [ ] AI service layer: AI Assistant (natural-language queries, permission-aware)
- [ ] AI Analytics (performance/attendance/enrollment analysis)
- [ ] AI Report Generation (draft department/faculty/management reports)
- [ ] AI Anomaly Detection (unusual grades, duplicate records, suspicious patterns)
- [ ] AI Predictive Analytics (at-risk students, graduation likelihood, enrollment forecasting)
- [ ] Platform-wide generic audit log (currently narrow — only a few specific actions tracked)
- [ ] Generic reusable Workflow Engine (currently one-off flows shaped to be extractable later, not yet generic)
- [ ] Universal search engine (students, staff, courses, results, documents)
- [ ] Admissions module (online application, screening, offers, applicant→student conversion)
- [ ] Student Portal (view results/GPA, register courses, view timetable/attendance/fees)
- [ ] Parent/Guardian Portal (view results, attendance, fees, announcements)
- [ ] Teacher/Lecturer Portal (beyond result entry — assignments, class management)
- [ ] Timetable Engine (class/teacher/exam timetables, conflict prevention)
- [ ] Communication Engine (announcements, email, SMS, push notifications)
- [ ] Examination Management (exam timetable, venues, invigilators, seating, malpractice records)
- [ ] Fees & Finance module (tuition, invoices, payments, receipts, scholarships)
- [ ] Accounting module (income, expenses, budgets, vendor management)
- [ ] Library Management (books, borrowing, returns, fines)
- [ ] Hostel Management (rooms, beds, allocation, occupancy)
- [ ] Transport Management (vehicles, routes, student allocation)
- [ ] HR module (staff attendance, leave, appraisal, payroll integration)
- [ ] Inventory & Asset Management
- [ ] Facilities Management (buildings, maintenance requests)
- [ ] Health & Counseling module (with restricted-permission sensitive data)
- [ ] Discipline & Welfare module (incidents, disciplinary cases)
- [ ] General Document Management (versioning, access control, expiration)
- [ ] Certificate/Diploma generation with QR verification portal
- [ ] Reporting & Business Intelligence dashboard (cross-module, filterable)
- [ ] Data backup & disaster recovery automation
- [ ] Public marketing website (separate domain from the app)
- [ ] Verification portal (separate subdomain for result/certificate verification)

---

## Standing Rules (apply to all future work)

- Small, testable batches — a large batch previously caused a sandbox crash and lost work.
- Every feature introduced with a singular create/assign/update workflow must also provide a corresponding bulk/multiple workflow where the domain permits it.
- Always implement proper user-facing error handling for expected validation, duplicate, constraint, and business-rule failures; raw database/framework exceptions must not be the normal user experience.
- "Make everything interactive" — searchable-select/multi-select over plain `<select>` wherever a dropdown could grow long.
- ICT-Admin-facing text says "Administrator," never "Super Admin."
- Windows/XAMPP local dev — every fix needs explicit, step-by-step wiring instructions.
- When a shared layout/component needs a fix, prefer the simplest reliable option over clever CSS — a past over-engineered scroll fix caused a real visual regression and had to be reverted.
- Always deliver incremental update-only packages unless explicitly asked for the full project.
- Preserve Laravel folder structure inside update ZIPs; never flatten paths.
- Verify build (`npm run build`) and clear Laravel caches after UI changes.
