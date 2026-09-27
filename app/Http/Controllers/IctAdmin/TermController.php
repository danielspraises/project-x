<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TermController extends Controller
{
    public function index(): View
    {
        $terms = Term::with('academicSession')->orderByDesc('start_date')->get();
        $institution = Auth::user()->institution;

        return view('ict-admin.terms.index', compact('terms', 'institution'));
    }

    public function create(): View
    {
        $this->ensureCanManage();

        $sessions = AcademicSession::orderByDesc('start_date')->get();
        $institution = Auth::user()->institution;

        return view('ict-admin.terms.create', compact('sessions', 'institution'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:1', 'max:5'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        Term::create($validated + ['status' => 'upcoming']);

        return redirect()->route('ict-admin.terms.index')->with('success', 'Term created.');
    }

    public function edit(Term $term): View
    {
        $this->ensureCanManage();

        $sessions = AcademicSession::orderByDesc('start_date')->get();
        $institution = Auth::user()->institution;

        return view('ict-admin.terms.edit', compact('term', 'sessions', 'institution'));
    }

    public function update(Request $request, Term $term): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:1', 'max:5'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:upcoming,active,closed'],
        ]);

        $term->update($validated);

        return redirect()->route('ict-admin.terms.index')->with('success', 'Term updated.');
    }

    public function destroy(Term $term): RedirectResponse
    {
        $this->ensureCanManage();

        $term->delete();

        return redirect()->route('ict-admin.terms.index')->with('success', 'Term deleted.');
    }

    private function ensureCanManage(): void
    {
        abort_unless(Auth::user()->hasPermission('institution.setup'), 403);
    }
}
