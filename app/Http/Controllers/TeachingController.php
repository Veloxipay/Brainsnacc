<?php

namespace App\Http\Controllers;

use App\Models\HelpRequest;
use App\Models\Homework;
use App\Models\Lesson;
use App\Models\SessionNote;
use App\Models\User;
use App\Support\Teaching;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** A tutor or parent teaching a student: set homework, write session notes, go through the student's questions. */
class TeachingController extends Controller
{
    private function student(Request $request, User $student): User
    {
        abort_unless(Teaching::canSupervise($request->user(), $student), 404);

        return $student;
    }

    public function storeHomework(Request $request, User $student): RedirectResponse
    {
        $student = $this->student($request, $student);
        $data = $request->validate([
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'due_on' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'lesson_id.required' => 'Pick a lesson or test to set.',
            'due_on.after_or_equal' => 'The due date can’t be in the past.',
        ]);
        $lesson = Lesson::findOrFail($data['lesson_id']);
        if (! $lesson->isPublished()) {
            return back()->withErrors(['lesson_id' => 'That lesson isn’t published yet, so the student can’t open it.'])->withInput();
        }

        Homework::create($data + ['student_id' => $student->id, 'set_by' => $request->user()->id]);

        return redirect()->to(route('family.students.show', $student).'#homework')
            ->with('status', "Homework set: {$lesson->title}. {$student->name} will see it on their dashboard.");
    }

    public function destroyHomework(Request $request, Homework $homework): RedirectResponse
    {
        $student = $this->student($request, $homework->student);
        $homework->delete();

        return redirect()->to(route('family.students.show', $student).'#homework')->with('status', 'Homework removed.');
    }

    public function storeNote(Request $request, User $student): RedirectResponse
    {
        $student = $this->student($request, $student);
        $data = $request->validate([
            'taught_on' => ['required', 'date', 'before_or_equal:today'],
            'body' => ['required', 'string', 'max:3000'],
        ], [
            'body.required' => 'Write a short note about the session.',
            'taught_on.before_or_equal' => 'The session date can’t be in the future.',
        ]);

        SessionNote::create($data + ['student_id' => $student->id, 'author_id' => $request->user()->id]);

        return redirect()->to(route('family.students.show', $student).'#notes')->with('status', 'Session note saved.');
    }

    public function destroyNote(Request $request, SessionNote $note): RedirectResponse
    {
        $student = $this->student($request, $note->student);
        abort_unless($request->user()->isAdmin() || $note->author_id === $request->user()->id, 403);
        $note->delete();

        return redirect()->to(route('family.students.show', $student).'#notes')->with('status', 'Note deleted.');
    }

    public function resolveHelp(Request $request, HelpRequest $helpRequest): RedirectResponse
    {
        $student = $this->student($request, $helpRequest->student);
        $helpRequest->update(['resolved_at' => now(), 'resolved_by' => $request->user()->id]);

        return redirect()->to(route('family.students.show', $student).'#questions')->with('status', 'Marked as gone through.');
    }
}
