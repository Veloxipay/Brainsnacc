<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Past VCE exams (links to the official VCAA files) with a simple "done" tick and optional score. */
class PastPaperController extends Controller
{
    public static function key(string $subject, array $paper): string
    {
        return $subject.'-'.$paper['year'].($paper['nht'] ? '-nht' : '');
    }

    public function index(Request $request): View
    {
        $done = DB::table('past_paper_progress')->where('user_id', $request->user()->id)->get()->keyBy('paper');

        return view('student.past-papers', [
            'papers' => config('past_papers'),
            'subjects' => Subject::whereIn('slug', array_keys(config('past_papers')))->orderBy('name')->get()->keyBy('slug'),
            'done' => $done,
            'mockExams' => \App\Support\MockExams::all(),
            'mockAttempts' => \App\Models\MockExamAttempt::where('user_id', $request->user()->id)->whereNotNull('submitted_at')->get()->groupBy('exam'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $valid = collect(config('past_papers'))->flatMap(fn ($s, $slug) => collect($s['papers'])->map(fn ($p) => self::key($slug, $p)))->all();
        $data = $request->validate([
            'paper' => ['required', 'string', 'in:'.implode(',', $valid)],
            'done' => ['required', 'boolean'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $where = ['user_id' => $request->user()->id, 'paper' => $data['paper']];
        if ($data['done']) {
            DB::table('past_paper_progress')->updateOrInsert($where, ['score' => $data['score'] ?? null, 'updated_at' => now(), 'created_at' => now()]);
        } else {
            DB::table('past_paper_progress')->where($where)->delete();
        }

        return redirect()->to(route('past-papers').'#'.$data['paper'])->with('status', $data['done'] ? 'Saved — nice work finishing that paper!' : 'Unticked.');
    }
}
