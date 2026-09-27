<?php

namespace App\Http\Controllers\IctAdmin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Note;
use App\Models\Programme;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    /**
     * Every model type notes can be attached to. Add an entry here (plus a `notes()`
     * relation on the model itself) any time you want notes available on a new screen.
     */
    private const NOTABLE_TYPES = [
        'faculty' => Faculty::class,
        'department' => Department::class,
        'programme' => Programme::class,
        'class' => SchoolClass::class,
        'student' => Student::class,
        'course' => Course::class,
    ];

    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('notes.create'), 403);
        abort_unless(array_key_exists($type, self::NOTABLE_TYPES), 404);

        $modelClass = self::NOTABLE_TYPES[$type];
        // findOrFail is automatically institution-scoped by the BelongsToInstitution trait
        // on every one of these models — this can't leak into another institution's records.
        $notable = $modelClass::findOrFail($id);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Note::create([
            'institution_id' => Auth::user()->institution_id,
            'user_id' => Auth::id(),
            'notable_type' => $modelClass,
            'notable_id' => $notable->id,
            'body' => $validated['body'],
        ]);

        return redirect()->back()->with('success', 'Note added.');
    }

    public function destroy(Note $note): RedirectResponse
    {
        abort_if($note->institution_id !== Auth::user()->institution_id, 404);
        abort_unless(
            $note->user_id === Auth::id() || Auth::user()->hasPermission('institution.setup'),
            403
        );

        $note->delete();

        return redirect()->back()->with('success', 'Note removed.');
    }
}
