<?php

namespace App\Http\Controllers;

use App\Models\HelpRequest;
use App\Models\Question;
use App\Models\TutorSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "Ask my tutor": a student flags a question to go through with their tutor next session. */
class AskTutorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'question_id' => ['nullable', 'integer', 'exists:questions,id', 'required_without:tutor_session_id'],
            'tutor_session_id' => ['nullable', 'integer', 'exists:tutor_sessions,id'],
            'message' => ['nullable', 'string', 'max:1000'],
            'back' => ['nullable', 'string', 'max:500'],
        ]);

        if (! HelpRequest::canAsk($user)) {
            return back()->withErrors(['message' => 'Ask a parent or teacher to add you to their Brainsnacc account first.']);
        }

        if (! empty($data['tutor_session_id'])) {
            $session = TutorSession::findOrFail($data['tutor_session_id']);
            abort_unless($session->user_id === $user->id, 404);
            $data['question_id'] = null;
        } else {
            $question = Question::with('lesson')->findOrFail($data['question_id']);
            abort_unless($question->lesson && ($question->lesson->isPublished() || $user->isAdmin()), 404);
        }

        // One open request per question or problem: asking again just updates the message.
        HelpRequest::updateOrCreate(
            [
                'student_id' => $user->id,
                'question_id' => $data['question_id'] ?? null,
                'tutor_session_id' => $data['tutor_session_id'] ?? null,
                'resolved_at' => null,
            ],
            ['message' => $data['message'] ?? null],
        );

        // Go back to where they asked from (only to this site).
        $back = $data['back'] ?? '';
        $to = str_starts_with($back, '/') && ! str_starts_with($back, '//') ? $back : url()->previous();

        return redirect()->to($to)->with('status', 'Sent to your tutor. You’ll go through it together next session.');
    }
}
