"""Check a Brainsnacc practice exam JSON: python3 database/content/mock-exams/check_exam.py [files]"""
import json, sys, glob, os, re
SPEC = {'chemistry': (30, 90), 'biology': (40, 80)}
def check(path):
    p = []
    try: e = json.load(open(path))
    except Exception as x: return [f'bad JSON: {x}']
    for k in ('subject', 'title', 'slug', 'section_a', 'section_b'):
        if k not in e: p.append('missing ' + k)
    if p: return p
    if e['subject'] not in SPEC: return ['subject must be chemistry or biology']
    na, nb = SPEC[e['subject']]
    if os.path.splitext(os.path.basename(path))[0] != e['slug']: p.append('file name must equal slug')
    if len(e['section_a']) != na: p.append(f"section_a has {len(e['section_a'])} questions (need {na})")
    letters = {}
    for i, q in enumerate(e['section_a'], 1):
        if set(q.get('options', {})) != {'A', 'B', 'C', 'D'}: p.append(f'A{i}: options must be A-D')
        if q.get('answer') not in ('A', 'B', 'C', 'D'): p.append(f'A{i}: bad answer')
        letters[q.get('answer')] = letters.get(q.get('answer'), 0) + 1
        for k in ('prompt', 'explanation'):
            if not q.get(k): p.append(f'A{i}: missing {k}')
    if letters and max(letters.values()) > len(e['section_a']) * 0.4: p.append(f'answers not spread: {letters}')
    total = 0
    for q in e['section_b']:
        if not q.get('parts'): p.append(f"B{q.get('number')}: no parts")
        for part in q.get('parts', []):
            m = part.get('marks', 0); total += m
            if not isinstance(m, int) or m < 1: p.append(f"B{q.get('number')}{part.get('label')}: marks must be a positive integer")
            if not part.get('prompt') or not part.get('answer'): p.append(f"B{q.get('number')}{part.get('label')}: missing prompt or answer")
            mk = part.get('marking', [])
            if len(mk) != m: p.append(f"B{q.get('number')}{part.get('label')}: {len(mk)} marking points for {m} marks")
    if total != nb: p.append(f'section_b totals {total} marks (need {nb})')
    blob = json.dumps(e, ensure_ascii=False)
    if re.search(r'<[a-zA-Z/]', blob): p.append('looks like HTML tags in text')
    return p
files = sys.argv[1:] or sorted(glob.glob(os.path.join(os.path.dirname(os.path.abspath(__file__)), '*', '*.json')))
bad = 0
for f in files:
    pr = check(f)
    print(('BAD ' if pr else 'OK  ') + f + (' | ' + '; '.join(pr) if pr else '')); bad += bool(pr)
print(f'{len(files)} exams, {bad} with problems')
