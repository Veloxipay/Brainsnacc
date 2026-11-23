"""Brainsnacc "teacher at the board" video: turns a teaching script into a narrated MP4.

    python3 video/board_video.py database/content/teach/year-7/maths/solving-two-step-equations.json [--force]

For every line of the script: the chalk line is written stroke by stroke, the drawing beside it changes
(cubes crossed off and faded, the scale tipping, cubes shared out…), and the voice reads the short sentence,
shown as a subtitle. Then it waits long enough for the student to copy the line, and goes on.

Output: public/videos/teach/<level>/<subject>/<lesson>.mp4 (the same path as the script under content/teach).
Needs: Pillow, numpy, soundfile, ffmpeg, rsvg-convert, php, and the Kokoro voice
(pip install kokoro-onnx; KOKORO_DIR with kokoro-v1.0.onnx and voices-v1.0.bin).
"""
import hashlib, json, os, re, subprocess, sys, tempfile
import numpy as np
import soundfile as sf
from PIL import Image, ImageDraw

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
sys.path.insert(0, HERE)
import render as R  # fonts, handwriting (write_line) and text helpers from the slide-video maker

W, H, FPS = 1280, 720, 15
KOKORO_DIR = os.environ.get('KOKORO_DIR', '/tmp/tts')
VOICE = os.environ.get('BOARD_VOICE', 'bf_emma')
SPEED = float(os.environ.get('BOARD_SPEED', '0.95'))
CACHE = os.path.join(tempfile.gettempdir(), 'brainsnacc-board-cache')
os.makedirs(CACHE, exist_ok=True)

GREEN = (31, 59, 47); WOOD = (107, 74, 46); CHALK = (244, 241, 230); SOFT = (207, 227, 214); DARK = (21, 41, 31)
ACCENT = {'maths': (123, 92, 240), 'chemistry': (47, 107, 234), 'biology': (224, 122, 18), 'selective': (20, 184, 166)}

# ---------- voice ----------
_kokoro = None
def voice():
    global _kokoro
    if _kokoro is None:
        from kokoro_onnx import Kokoro
        _kokoro = Kokoro(os.path.join(KOKORO_DIR, 'kokoro-v1.0.onnx'), os.path.join(KOKORO_DIR, 'voices-v1.0.bin'))
    return _kokoro

def spoken(s):
    """Maths as it is said out loud."""
    s = str(s)
    s = re.sub(r'(\d)\s*/\s*(\d)', r'\1 over \2', s)
    for a, b in (('−', ' minus '), ('–', ' minus '), ('×', ' times '), ('÷', ' divided by '), ('=', ' equals '), ('²', ' squared '),
                 ('³', ' cubed '), ('√', ' the square root of '), ('π', ' pi '), ('≈', ' is about '), ('°', ' degrees'), ('%', ' percent'),
                 ('✓', ''), ('✗', ''), ('→', ' then '), ('±', ' plus or minus '), ('+', ' plus ')):
        s = s.replace(a, b)
    s = re.sub(r'(\d)([a-z])\b', r'\1 \2', s)  # 4x → 4 x
    return re.sub(r'\s+', ' ', s).strip()

def tts(text):
    text = spoken(text)
    key = hashlib.sha1(f'{VOICE}|{SPEED}|{text}'.encode()).hexdigest()[:20]
    path = os.path.join(CACHE, key + '.wav')
    if not os.path.exists(path):
        audio, sr = voice().create(text, voice=VOICE, speed=SPEED, lang='en-gb' if VOICE.startswith('b') else 'en-us')
        sf.write(path + '.tmp.wav', audio, sr); os.replace(path + '.tmp.wav', path)
    audio, sr = sf.read(path, dtype='float32')
    return audio, sr

# ---------- pictures ----------
def svg_png(svg_text, width):
    key = hashlib.sha1((svg_text + str(width)).encode()).hexdigest()[:20]
    png = os.path.join(CACHE, key + '.png')
    if not os.path.exists(png):
        src = png + '.svg'
        open(src, 'w').write(svg_text)
        subprocess.run(['rsvg-convert', '-w', str(width), '-b', 'white', src, '-o', png + '.tmp'], check=True)
        os.replace(png + '.tmp', png)
    return Image.open(png).convert('RGB')

def drawing(spec, phase, width):
    svg = subprocess.run(['php', os.path.join(HERE, 'draw.php'), json.dumps(spec), phase], capture_output=True, text=True, check=True).stdout
    return svg_png(svg, width) if svg.strip() else None

def diagram(path, width):
    full = os.path.join(ROOT, 'public', path.lstrip('/'))
    return svg_png(open(full).read(), width) if os.path.exists(full) else None

def fit_card(img, box):
    """Scale a picture to fit inside a white card."""
    x0, y0, x1, y1 = box
    k = min((x1 - x0 - 24) / img.width, (y1 - y0 - 24) / img.height, 1.0)
    im = img.resize((max(1, int(img.width * k)), max(1, int(img.height * k))), Image.LANCZOS)
    return im, (x0 + (x1 - x0 - im.width) // 2, y0 + (y1 - y0 - im.height) // 2)

# ---------- timeline ----------
def has_change(spec):
    return any(k in spec for k in ('remove', 'add', 'tip', 'share', 'jump'))

def plan(script):
    """Every line gets: when it starts, how long the chalk takes, when the drawing changes, how long to wait."""
    events, audio_parts, t = [], [], 0.6
    for fi, frame in enumerate(script['frames']):
        t += 0.4 if fi else 0.0
        frame_start = t
        t += 0.7
        for li, line in enumerate(frame['lines']):
            say = line.get('say') or line.get('write') or ''
            audio, sr = tts(say) if say else (np.zeros(1, dtype='float32'), 24000)
            dur = len(audio) / sr
            write = line.get('write') or ''
            wdur = max(0.8, min(2.6, len(write) / 14)) if write else 0
            spec = line.get('draw')
            pics = []
            if spec:
                if has_change(spec):
                    pics.append((t, ('draw', spec, 'before')))
                    if spec.get('remove'): pics.append((t + 1.0, ('draw', spec, 'during')))
                    pics.append((t + 2.0, ('draw', spec, 'after')))
                else:
                    pics.append((t, ('draw', spec, 'after')))
            elif line.get('diagram'):
                pics.append((t, ('diagram', line['diagram'])))
            # Long enough to hear it, see the drawing change and copy the chalk line.
            copy = min(2.6, 1.0 + len(write) * 0.035) if write else 0.5
            length = max(dur + 0.6, wdur + 0.8, 2.6 if spec and has_change(spec) else 0) + copy
            events.append({'frame': fi, 'line': li, 'start': t, 'write': write, 'wdur': wdur, 'say': line.get('say') or '', 'pics': pics,
                           'frame_start': frame_start, 'end': t + length})
            audio_parts.append((t + 0.15, audio, sr))
            t += length
    return events, audio_parts, t + 1.2

def build_audio(parts, total, path):
    sr = parts[0][2] if parts else 24000
    out = np.zeros(int(total * sr) + sr, dtype='float32')
    for start, audio, _ in parts:
        i = int(start * sr)
        out[i:i + len(audio)] += audio[: len(out) - i]
    sf.write(path, np.clip(out, -1, 1), sr)

# ---------- drawing a moment ----------
def picture(key, cache):
    ck = json.dumps(key, sort_keys=True)
    if ck not in cache:
        cache[ck] = drawing(key[1], key[2], 560) if key[0] == 'draw' else diagram(key[1], 560)
    return cache[ck]

def render(script_path, force=False):
    script = json.load(open(script_path))
    rel = os.path.relpath(os.path.abspath(script_path), os.path.join(ROOT, 'database', 'content', 'teach'))
    out = os.path.join(ROOT, 'public', 'videos', 'teach', os.path.splitext(rel)[0] + '.mp4')
    src_hash = hashlib.sha1((open(script_path).read() + VOICE + str(SPEED) + open(__file__).read() + open(os.path.join(ROOT, 'app/Support/Drawings.php')).read()).encode()).hexdigest()[:12]
    stamp = os.path.join(HERE, 'stamps', os.path.splitext(rel)[0] + '.hash')
    os.makedirs(os.path.dirname(stamp), exist_ok=True)
    if not force and os.path.exists(out) and os.path.exists(stamp) and open(stamp).read().strip() == src_hash:
        print('up to date', out); return out
    os.makedirs(os.path.dirname(out), exist_ok=True)

    parts = rel.split(os.sep)
    subject = parts[1] if len(parts) > 2 else 'maths'
    acc = ACCENT.get(subject, ACCENT['maths'])
    label = f"{script.get('level', '')} {subject.title()} · {script.get('title', '')}"
    frames = script['frames']
    frame_has_pic = [any(l.get('draw') or l.get('diagram') for l in f['lines']) for f in frames]

    events, audio_parts, total = plan(script)
    work = tempfile.mkdtemp(prefix='board-')
    wav = os.path.join(work, 'voice.wav')
    build_audio(audio_parts, total, wav)

    cache = {}
    n = int(total * FPS)
    tmp = out + '.tmp.mp4'
    p = subprocess.Popen(['ffmpeg', '-y', '-loglevel', 'error', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                          '-i', wav, '-c:v', 'libx264', '-crf', '28', '-preset', 'veryfast', '-tune', 'stillimage', '-pix_fmt', 'yuv420p',
                          '-c:a', 'aac', '-b:a', '64k', '-ac', '1', '-shortest', '-movflags', '+faststart', tmp], stdin=subprocess.PIPE)

    hand_size = 44
    for k in range(n):
        t = k / FPS
        cur = [e for e in events if e['start'] <= t]
        frame_i = cur[-1]['frame'] if cur else 0
        im = Image.new('RGB', (W, H), WOOD)
        d = ImageDraw.Draw(im)
        d.rounded_rectangle([14, 14, W - 14, H - 14], 14, fill=GREEN)
        d.text((48, 52), frames[frame_i].get('heading', ''), font=R.font('Bold', 34), fill=CHALK, anchor='lm')
        d.text((W - 48, 52), label, font=R.font('Medium', 17), fill=SOFT, anchor='rm')
        pic = frame_has_pic[frame_i]
        text_right = 640 if pic else W - 60

        # Chalk lines written so far on this screen (scroll up if the board is full).
        mine = [e for e in cur if e['frame'] == frame_i and e['write']]
        gap = 66
        top, bottom = 108, 580
        visible = mine[-max(1, (bottom - top) // gap):]
        for i, e in enumerate(visible):
            frac = (t - e['start'] - 0.2) / e['wdur'] if e['wdur'] else 1
            size = hand_size
            while size > 26 and R.hand_width(d, e['write'], size) > text_right - 70:
                size -= 2
            R.write_line(im, (56, top + i * gap), e['write'], size, CHALK, min(1.0, frac) if frac < 1 else 1.0)
        d = ImageDraw.Draw(im)

        # The picture: the latest one on this screen, changing as the step plays (cross-fade between stages).
        if pic:
            box = (680, 100, W - 50, 580)
            d.rounded_rectangle(box, 16, fill=(255, 255, 255))
            stages = [(st, key) for e in cur if e['frame'] == frame_i for (st, key) in e['pics'] if st <= t]
            if stages:
                st, key = stages[-1]
                img = picture(key, cache)
                if img is not None:
                    a = min(1.0, (t - st) / 0.45)
                    if a < 1 and len(stages) > 1:
                        prev = picture(stages[-2][1], cache)
                        if prev is not None and prev.size == img.size:
                            img = Image.blend(prev, img, a)
                    fitted, pos = fit_card(img, box)
                    im.paste(fitted, pos)

        # Subtitle: what the teacher is saying.
        speaking = [e for e in cur if e['say']]
        if speaking and t < speaking[-1]['end']:
            f, lines = R.fit(d, speaking[-1]['say'], 'Medium', 27, W - 200, 2, 20)
            lh = int(f.size * 1.35)
            y = H - 40 - lh * len(lines)
            d.rounded_rectangle([60, y - 14, W - 60, H - 28], 14, fill=DARK)
            for i, ln in enumerate(lines):
                d.text((W // 2, y + i * lh), ln, font=f, fill=(255, 255, 255), anchor='ma')
        d.rectangle([14, H - 20, 14 + int((W - 28) * t / total), H - 14], fill=acc)
        p.stdin.write(im.tobytes())
    p.stdin.close()
    if p.wait() != 0:
        raise SystemExit('ffmpeg failed: ' + script_path)
    os.replace(tmp, out)
    open(stamp, 'w').write(src_hash)
    print(out, f'{total:.0f}s')
    return out

if __name__ == '__main__':
    for path in [a for a in sys.argv[1:] if not a.startswith('--')]:
        render(path, '--force' in sys.argv)
