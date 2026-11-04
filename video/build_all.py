"""Render every video script whose MP4 is missing or older than the script, then link each video to its lesson.
    python3 video/build_all.py [--jobs 4]"""
import glob, json, os, subprocess, sys
from concurrent.futures import ThreadPoolExecutor
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
jobs = int(sys.argv[sys.argv.index('--jobs') + 1]) if '--jobs' in sys.argv else 4

def target(script):
    rel = os.path.relpath(script, os.path.join(ROOT, 'video', 'scripts'))
    return os.path.join(ROOT, 'public', 'videos', os.path.splitext(rel)[0] + '.mp4'), 'videos/' + os.path.splitext(rel)[0] + '.mp4'

def ok(script):
    r = subprocess.run([sys.executable, os.path.join(ROOT, 'video', 'check_script.py'), script], capture_output=True, text=True)
    return r.stdout.startswith('OK')

def build(script):
    mp4, _ = target(script)
    if os.path.exists(mp4) and os.path.getmtime(mp4) >= os.path.getmtime(script): return script, 'skip'
    if not ok(script): return script, 'bad script'
    r = subprocess.run([sys.executable, os.path.join(ROOT, 'video', 'render.py'), script], capture_output=True, text=True)
    return script, 'ok' if r.returncode == 0 else 'FAILED ' + r.stderr[-300:]

def link(script):
    s = json.load(open(script)); mp4, web = target(script)
    if not os.path.exists(mp4): return False
    lp = os.path.join(ROOT, 'database', 'content', s['lesson'])
    d = json.load(open(lp))
    if d.get('video') == web: return False
    out = {}
    for k, v in d.items():
        if k == 'video': continue
        out[k] = v
        if k == 'minutes': out['video'] = web
    if 'video' not in out: out['video'] = web
    json.dump(out, open(lp, 'w'), ensure_ascii=False, indent=2)
    return True

scripts = sorted(glob.glob(os.path.join(ROOT, 'video', 'scripts', '**', '*.json'), recursive=True))
with ThreadPoolExecutor(jobs) as ex:
    for script, status in ex.map(build, scripts):
        if status != 'skip': print(status, os.path.relpath(script, ROOT), flush=True)
linked = sum(link(s) for s in scripts)
print(f'{len(scripts)} scripts; {linked} lessons newly linked to a video')
