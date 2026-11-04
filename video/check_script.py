"""Check video scripts: python3 video/check_script.py [files...]  (default: all scripts). Prints OK/BAD per file."""
import json, sys, glob, os
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
LIMITS = {'points': ('points', 5, 64), 'recap': ('points', 5, 60), 'example': ('lines', 5, 58), 'steps': ('steps', 5, 44)}
TYPES = {'title', 'points', 'analogy', 'equation', 'example', 'compare', 'steps', 'recap'}
def check(path):
    p = []
    try: s = json.load(open(path))
    except Exception as e: return [f'not valid JSON: {e}']
    for k in ('lesson', 'level', 'subject', 'title', 'scenes'):
        if k not in s: p.append(f'missing {k}')
    if p: return p
    if not os.path.exists(os.path.join(ROOT, 'database', 'content', s['lesson'])): p.append('lesson file not found: ' + s['lesson'])
    sc = s['scenes']
    if not 5 <= len(sc) <= 10: p.append(f'{len(sc)} scenes (want 5-10)')
    types = [x.get('type') for x in sc]
    if types[:1] != ['title']: p.append('first scene must be title')
    if types[-1:] != ['recap']: p.append('last scene must be recap')
    if 'analogy' not in types: p.append('needs at least one analogy scene')
    words = 0
    for i, x in enumerate(sc):
        t = x.get('type')
        if t not in TYPES: p.append(f'scene {i}: unknown type {t}'); continue
        if not x.get('say'): p.append(f'scene {i}: missing say')
        words += len(x.get('say', '').split())
        if len(x.get('say', '').split()) > 75: p.append(f'scene {i}: say too long ({len(x["say"].split())} words, max 75)')
        if t in LIMITS:
            key, n, chars = LIMITS[t]
            items = x.get(key, [])
            if not items or len(items) > n: p.append(f'scene {i}: {key} needs 1-{n} items')
            for it in items:
                if len(it) > chars: p.append(f'scene {i}: item too long ({len(it)}>{chars}): {it[:40]}')
        if t == 'analogy':
            for k in ('everyday', 'science'):
                if not x.get(k) or len(x[k]) > 80: p.append(f'scene {i}: analogy {k} missing or over 80 chars')
            if x.get('breaks') and len(x['breaks']) > 150: p.append(f'scene {i}: breaks over 150 chars')
        if t == 'equation':
            if not x.get('equation') or len(x['equation']) > 34: p.append(f'scene {i}: equation missing or over 34 chars')
            if len(x.get('parts', [])) > 4: p.append(f'scene {i}: max 4 parts')
        if t == 'compare':
            for side in ('left', 'right'):
                sd = x.get(side, {})
                if not sd.get('title') or len(sd['title']) > 26: p.append(f'scene {i}: {side} title missing or over 26 chars')
                for pt in sd.get('points', []):
                    if len(pt) > 46: p.append(f'scene {i}: {side} point over 46 chars')
                if len(sd.get('points', [])) > 4: p.append(f'scene {i}: {side} max 4 points')
        if t == 'title' and len(x.get('heading', s['title'])) > 60: p.append('title heading over 60 chars')
        if t == 'example' and len(x.get('question', '')) > 130: p.append(f'scene {i}: question over 130 chars')
        cap = x.get('caption', x.get('say', ''))
        if len(cap) > 330: p.append(f'scene {i}: caption over 330 chars')
    if not 150 <= words <= 380: p.append(f'{words} narration words (want 150-380)')
    return p
files = sys.argv[1:] or sorted(glob.glob(os.path.join(ROOT, 'video', 'scripts', '**', '*.json'), recursive=True))
bad = 0
for f in files:
    pr = check(f)
    if pr: bad += 1; print('BAD', os.path.relpath(f, ROOT), '|', '; '.join(pr))
    else: print('OK ', os.path.relpath(f, ROOT))
print(f'{len(files)} scripts, {bad} with problems')
