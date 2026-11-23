"""Check a teaching script (database/content/teach/<level>/<subject>/<lesson>.json). Prints OK or the problems.

    python3 video/check_teach.py database/content/teach/year-7/maths/solving-two-step-equations.json [...]
"""
import json, os, re, subprocess, sys, unicodedata

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

def slug(s):
    s = unicodedata.normalize('NFKD', str(s)).encode('ascii', 'ignore').decode().lower().replace('_', '-').replace('@', '-at-')
    s = re.sub(r'[^a-z0-9\s-]', '', s)
    return re.sub(r'[\s-]+', '-', s).strip('-')

def lessons():
    out = {}
    for dp, _, fs in os.walk(os.path.join(ROOT, 'database', 'content')):
        if '/teach' in dp or 'mock-exams' in dp: continue
        for f in fs:
            if not f.endswith('.json') or f == 'maths-manifest.json': continue
            try: d = json.load(open(os.path.join(dp, f)))
            except Exception: continue
            if isinstance(d, dict) and d.get('title'):
                out[(slug(d.get('level', '')), d.get('subject'), slug(d['title']))] = d
    return out

def words(s): return len(re.findall(r"[\w'’−-]+", s or ''))

def check(path, known):
    errs = []
    try: d = json.load(open(path))
    except Exception as e: return [f'not valid JSON: {e}']
    rel = os.path.relpath(os.path.abspath(path), os.path.join(ROOT, 'database', 'content', 'teach')).split(os.sep)
    if len(rel) != 3: errs.append('must be at database/content/teach/<level>/<subject>/<lesson>.json')
    else:
        lvl, subj, name = rel[0], rel[1], rel[2][:-5]
        if (lvl, subj, name) not in known: errs.append(f'no lesson matches {lvl}/{subj}/{name} (file name must be the slug of the lesson title)')
        if slug(d.get('level', '')) != lvl or d.get('subject') != subj or slug(d.get('title', '')) != name:
            errs.append('level / subject / title must match the lesson and the file path')
    frames = d.get('frames')
    if not isinstance(frames, list) or not 4 <= len(frames) <= 10: return errs + ['frames: 4 to 10 screens']
    total, pics, changes = 0, 0, 0
    for i, fr in enumerate(frames, 1):
        h = fr.get('heading', '')
        if not h or len(h) > 48: errs.append(f'screen {i}: heading 1–48 characters')
        lines = fr.get('lines', [])
        if not 2 <= len(lines) <= 8: errs.append(f'screen {i}: 2 to 8 lines')
        for j, ln in enumerate(lines, 1):
            at = f'screen {i} line {j}'
            say, write = ln.get('say', ''), ln.get('write', '')
            extra = set(ln) - {'say', 'write', 'draw', 'diagram', 'alt'}
            if extra: errs.append(f'{at}: unknown keys {sorted(extra)}')
            if not say and not write: errs.append(f'{at}: needs say or write')
            if words(say) > 22: errs.append(f'{at}: say is {words(say)} words (max 22, aim for 8–16)')
            if len(write) > 36: errs.append(f'{at}: write is {len(write)} characters (max 36)')
            if '\n' in write: errs.append(f'{at}: write must be one line')
            total += words(say)
            if 'diagram' in ln:
                pics += 1
                if not re.match(r'^diagrams/[a-z0-9/_-]+\.svg$', ln['diagram']) or not os.path.exists(os.path.join(ROOT, 'public', ln['diagram'])):
                    errs.append(f'{at}: diagram file not found in public/: {ln["diagram"]}')
            if 'draw' in ln:
                pics += 1
                spec = ln['draw']
                if not isinstance(spec, dict) or spec.get('type') not in ('balance', 'numberline', 'shapes'):
                    errs.append(f'{at}: draw.type must be balance, numberline or shapes'); continue
                if any(k in spec for k in ('remove', 'add', 'tip', 'share', 'jump')) or any(isinstance(it, dict) and it.get('change') for it in spec.get('items', [])):
                    changes += 1
                if spec['type'] == 'shapes':
                    W, H = spec.get('width', 560), spec.get('height', 320)
                    for k, it in enumerate(spec.get('items', []), 1):
                        if not isinstance(it, dict) or it.get('kind') not in ('rect', 'circle', 'line', 'arrow', 'polygon', 'text'):
                            errs.append(f'{at}: item {k} kind must be rect, circle, line, arrow, polygon or text'); continue
                        xs = [it.get('x', 0), it.get('x2', it.get('x', 0))] + [p[0] for p in it.get('points', []) if isinstance(p, list)]
                        ys = [it.get('y', 0), it.get('y2', it.get('y', 0))] + [p[1] for p in it.get('points', []) if isinstance(p, list)]
                        if it.get('kind') == 'rect': xs.append(it.get('x', 0) + it.get('w', 40)); ys.append(it.get('y', 0) + it.get('h', 40))
                        if any(not isinstance(v, (int, float)) or v < -2 or v > W + 2 for v in xs) or any(not isinstance(v, (int, float)) or v < -2 or v > H + 2 for v in ys):
                            errs.append(f'{at}: item {k} is outside the {W}×{H} drawing')
                        if it.get('kind') == 'text' and it.get('size', 20) < 14: errs.append(f'{at}: item {k} text size at least 14')
                for phase in ('before', 'after'):
                    r = subprocess.run(['php', os.path.join(ROOT, 'video', 'draw.php'), json.dumps(spec), phase], capture_output=True, text=True)
                    if r.returncode != 0 or '<svg' not in r.stdout: errs.append(f'{at}: drawing does not render ({phase})')
    if total < 120 or total > 520: errs.append(f'spoken words {total} (aim 180–400)')
    if pics < max(2, len(frames) // 2): errs.append(f'only {pics} pictures: at least one picture on half the screens')
    if changes < 2: errs.append('at least 2 drawings must show a change (remove / add / highlight / tip / share / jump)')
    return errs

if __name__ == '__main__':
    known = lessons()
    bad = 0
    for p in sys.argv[1:]:
        e = check(p, known)
        if e:
            bad += 1
            print(p); [print('  -', x) for x in e]
    print('OK' if not bad else f'{bad} file(s) with problems')
    sys.exit(1 if bad else 0)
