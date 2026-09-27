<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AcademicSessionController extends Controller
{
    public function index(): View
    {
        $sessions = AcademicSession::withCount('terms')->orderByDesc('start_date')->get();
        $institution = Auth::user()->institution;

        return view('ict-admin.sessions.index', compact('sessions', 'institution'));
    }

    public function create(): View
    {
        $this->ensureCanManage();

        $institution = Auth::user()->institution;

        return view('ict-admin.sessions.create', compact('institution'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        AcademicSession::create($validated + ['status' => 'upcoming']);

        return redirect()->route('ict-admin.sessions.index')->with('success', 'Session created.');
    }

    public function edit(AcademicSession $session): View
    {
        $this->ensureCanManage();

        $institution = Auth::user()->institution;

        return view('ict-admin.sessions.edit', compact('session', 'institution'));
    }

    public function update(Request $request, AcademicSession $session): RedirectResponse
    {
        $this->ensureCanManage();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:upcoming,active,closed'],
        ]);

        $session->update($validated);

        return redirect()->route('ict-admin.sessions.index')->with('success', 'Session updated.');
    }

    public function destroy(AcademicSession $session): RedirectResponse
    {
        $this->ensureCanManage();

        abort_if($session->terms()->exists(), 422, 'Remove this session\'s terms/semesters first.');

        $session->delete();

        return redirect()->route('ict-admin.sessions.index')->with('success', 'Session deleted.');
    }

    private function ensureCanManage(): void
    {
        abort_unless(Auth::user()->hasPermission('institution.setup'), 403);
    }
}
