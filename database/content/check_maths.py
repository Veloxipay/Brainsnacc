"""Check Maths lesson and chapter-test files before they go live.

    python3 database/content/check_maths.py database/content/y8/maths/ratios.json [more files...]

Prints OK <file> or a list of problems. Exit code 1 if any file has problems.
Checks structure only; every number must still be worked out (see WRITING_BRIEF.md, Maths section).
"""
import json, re, sys, os, glob

LEVELS = ['easy', 'medium', 'hard', 'challenge']
LESSON_HEADINGS = ['## Learning objective', '## Before you start', '## Think of it like this', '## Key ideas',
                   '## Worked examples', '## Common mistakes', '## Tips to remember', '## Key terms', '## Summary']
REVIEW_HEADINGS = ['## How to use this', '## Topics covered', '## Short-answer questions', '## Worked solutions']


def check_question(q, i, need_topic, topics):
    p = []
    tag = f'question {i + 1}'
    for k in ('difficulty', 'prompt', 'options', 'answer', 'explanation', 'steps', 'mistakes'):
        if k not in q:
            p.append(f'{tag}: missing "{k}"')
    if p:
        return p
    if q['difficulty'] not in LEVELS:
        p.append(f'{tag}: difficulty must be one of {LEVELS}')
    opts = q['options']
    if not isinstance(opts, dict) or sorted(opts) != ['A', 'B', 'C', 'D']:
        p.append(f'{tag}: options must be exactly A, B, C, D')
    elif len({str(v).strip() for v in opts.values()}) != 4 or any(not str(v).strip() for v in opts.values()):
        p.append(f'{tag}: the four options must all be different and non-empty')
    if q['answer'] not in ('A', 'B', 'C', 'D'):
        p.append(f'{tag}: answer must be A, B, C or D')
    if not isinstance(q['steps'], list) or len(q['steps']) < 2 or any(not str(s).strip() for s in q['steps']):
        p.append(f'{tag}: steps must be a list of at least 2 steps')
    wrong = sorted(set('ABCD') - {q['answer']})
    if not isinstance(q['mistakes'], dict) or sorted(q['mistakes']) != wrong:
        p.append(f'{tag}: mistakes must have one note for each wrong option {wrong}')
    elif any(len(str(v).strip()) < 15 for v in q['mistakes'].values()):
        p.append(f'{tag}: each mistake note must say which step goes wrong and how to fix it')
    if len(q['prompt']) > 600:
        p.append(f'{tag}: prompt too long')
    if need_topic:
        if q.get('topic') not in topics:
            p.append(f'{tag}: "topic" must be one of this chapter\'s lesson titles, copied exactly: {topics}')
    return p


def check(path, manifest):
    p = []
    try:
        d = json.load(open(path))
    except Exception as e:
        return [f'not valid JSON: {e}']
    rel = os.path.relpath(path, os.getcwd())
    entry = next((m for m in manifest if m['path'] == rel or path.endswith(m['path'])), None)
    if not entry:
        return [f'this path is not in the manifest — check the file name ({rel})']
    for k, v in (('level', entry['year']), ('subject', 'maths'), ('unit', entry['year']), ('title', entry['title'])):
        if d.get(k) != v:
            p.append(f'"{k}" must be exactly {v!r} (got {d.get(k)!r})')
    if not d.get('summary') or len(d['summary']) > 250:
        p.append('"summary" must be 1 sentence, under 250 characters')
    if not isinstance(d.get('minutes'), int):
        p.append('"minutes" must be a whole number')
    body = d.get('body', '')
    qs = d.get('questions', [])
    is_test = entry['kind'] == 'test'
    title = entry['title']
    review = title.endswith('Semester 1 review') or title.endswith('Maths challenge')
    summary_lesson = title.endswith('summary and memory tips')

    if is_test:
        if d.get('kind') != 'test':
            p.append('"kind" must be "test"')
        counts = {'easy': 4, 'medium': 5, 'hard': 4, 'challenge': 2}
        need = 15
        topics = entry['chapter_lessons']
    else:
        if d.get('kind', 'lesson') != 'lesson':
            p.append('"kind" must be "lesson" or left out')
        counts = {'easy': 5, 'medium': 5, 'hard': 5, 'challenge': 5} if review else {'easy': 3, 'medium': 3, 'hard': 3, 'challenge': 3}
        need = 20 if review else 12
        topics = []
        words = len(body.split())
        heads = REVIEW_HEADINGS if review else (['## Tips to remember', '## Summary'] if summary_lesson else LESSON_HEADINGS)
        for h in heads:
            if not re.search('^' + re.escape(h), body, re.M):
                p.append(f'body is missing the section "{h}"')
        lo, hi = (500, 2600) if review else ((1200, 2800) if summary_lesson else (700, 1800))
        if not lo <= words <= hi:
            p.append(f'body has {words} words; aim for {lo}–{hi}')
        if not review and not summary_lesson and len(re.findall(r'^\*\*Example \d', body, re.M)) + len(re.findall(r'^### Example', body, re.M)) < 3:
            p.append('Worked examples: write at least 3, each starting "### Example 1", "### Example 2"…')
    if '<' in body.replace('<-', '') and re.search(r'<[a-zA-Z/]', body):
        p.append('body contains HTML tags — use Markdown only')

    if len(qs) != need:
        p.append(f'needs exactly {need} questions (has {len(qs)})')
    have = {lvl: sum(1 for q in qs if q.get('difficulty') == lvl) for lvl in LEVELS}
    if have != counts:
        p.append(f'question levels must be {counts} (have {have})')
    for i, q in enumerate(qs):
        p += check_question(q, i, is_test, topics)
    letters = [q.get('answer') for q in qs]
    for L in 'ABCD':
        if letters.count(L) < max(1, need // 6):
            p.append(f'spread the correct answers: only {letters.count(L)} are "{L}"')
            break
    if len({q.get('prompt') for q in qs}) != len(qs):
        p.append('two questions have the same prompt')
    if is_test:
        missing = [t for t in topics if t not in {q.get('topic') for q in qs}]
        if missing:
            p.append(f'every lesson in the chapter must be tested at least once; missing: {missing}')
        # The 4 tests of a chapter must use different questions.
        siblings = [s for s in glob.glob(os.path.join(os.path.dirname(path), re.sub(r'test-\d\.json$', 'test-*.json', os.path.basename(path)))) if s != path]
        mine = {re.sub(r'\s+', ' ', q.get('prompt', '')).strip().lower() for q in qs}
        for s in siblings:
            try:
                other = {re.sub(r'\s+', ' ', q.get('prompt', '')).strip().lower() for q in json.load(open(s)).get('questions', [])}
            except Exception:
                continue
            if mine & other:
                p.append(f'shares {len(mine & other)} question(s) with {os.path.basename(s)} — each test needs its own questions')
    return p


if __name__ == '__main__':
    here = os.path.dirname(os.path.abspath(__file__))
    manifest = json.load(open(os.environ.get('MATHS_MANIFEST', os.path.join(here, 'maths-manifest.json'))))
    bad = 0
    for f in sys.argv[1:]:
        probs = check(f, manifest)
        if probs:
            bad += 1
            print(f'PROBLEMS {f}')
            for x in probs:
                print('  -', x)
        else:
            print(f'OK {f}')
    sys.exit(1 if bad else 0)
