"""Brainsnacc explainer-video maker.

Turns a short scene script (video/scripts/<level>/<subject>/.../<slug>.json) into a narrated,
captioned 1280x720 MP4 at public/videos/<same path>.mp4.

    python3 video/render.py video/scripts/y10/chemistry/atomic-structure.json [--stills]

Needs: Pillow, ffmpeg, piper-tts and a piper voice (PIPER_VOICE env var or the default path below).
Script format: see video/SCRIPT_BRIEF.md.
"""
import json, math, os, re, subprocess, sys, wave, hashlib, tempfile
from PIL import Image, ImageDraw, ImageFont

W, H, FPS = 1280, 720, 30
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
VOICE = os.environ.get('PIPER_VOICE', '/tmp/claude-0/-home-claude/f0099c76-9e7c-5ee5-b95d-1b7646ef1e8c/scratchpad/tts/en-us-lessac-medium.onnx')
CACHE = os.environ.get('VIDEO_CACHE', os.path.join(tempfile.gettempdir(), 'brainsnacc-video-cache'))

FONT = '/usr/share/fonts/opentype/inter/Inter-%s.otf'
EMOJI = '/usr/share/fonts/truetype/noto/NotoColorEmoji.ttf'
SYMBOL = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
_fonts = {}
def font(weight, size):
    k = (weight, size)
    if k not in _fonts: _fonts[k] = ImageFont.truetype(FONT % weight, size)
    return _fonts[k]

BG = (247, 248, 251); INK = (20, 27, 45); MUTED = (95, 106, 125); NAVY = (17, 24, 39)
WHITE = (255, 255, 255); LINE = (222, 227, 236); RED = (200, 60, 52); GREEN = (40, 140, 80)
SUBJECT = {'chemistry': ((36, 86, 199), (232, 238, 251)), 'biology': ((180, 84, 10), (251, 238, 227)), 'maths': ((91, 62, 214), (240, 236, 254)), 'selective': ((15, 138, 126), (227, 245, 242))}

def ease(t): t = max(0.0, min(1.0, t)); return t * t * (3 - 2 * t)

# ---------- text helpers ----------
def wrap(d, s, f, width):
    out = []
    for para in str(s).split('\n'):
        cur = ''
        for w in para.split(' '):
            t = (cur + ' ' + w).strip()
            if d.textlength(t, font=f) > width and cur: out.append(cur); cur = w
            else: cur = t
        out.append(cur)
    return out

def fit(d, s, weight, size, width, max_lines, min_size=18):
    while size > min_size:
        f = font(weight, size); lines = wrap(d, s, f, width)
        if len(lines) <= max_lines: return f, lines
        size -= 2
    f = font(weight, min_size); return f, wrap(d, s, f, width)[:max_lines]

def para(d, xy, s, weight, size, width, fill=INK, max_lines=4, gap=1.25, anchor='l'):
    f, lines = fit(d, s, weight, size, width, max_lines)
    x, y = xy; lh = int(f.size * gap)
    for i, ln in enumerate(lines):
        if anchor == 'm': d.text((x, y + i * lh), ln, font=f, fill=fill, anchor='ma')
        else: d.text((x, y + i * lh), ln, font=f, fill=fill)
    return y + len(lines) * lh

_emoji_cache = {}
def emoji(im, s, cx, cy, size, alpha=1.0):
    if not s: return
    key = (s, size)
    if key not in _emoji_cache:
        f = ImageFont.truetype(EMOJI, 109)
        tmp = Image.new('RGBA', (160 * max(1, len(s)), 140), (0, 0, 0, 0))
        ImageDraw.Draw(tmp).text((0, 0), s, font=f, embedded_color=True)
        bbox = tmp.getbbox()
        if not bbox: _emoji_cache[key] = None
        else:
            tmp = tmp.crop(bbox); r = size / max(tmp.size)
            _emoji_cache[key] = tmp.resize((max(1, int(tmp.width * r)), max(1, int(tmp.height * r))), Image.LANCZOS)
    e = _emoji_cache[key]
    if e is None: return
    if alpha < 1:
        e = e.copy(); a = e.getchannel('A').point(lambda v: int(v * alpha)); e.putalpha(a)
    im.paste(e, (int(cx - e.width / 2), int(cy - e.height / 2)), e)

def card(d, box, fill=WHITE, outline=LINE, r=18, width=2):
    d.rounded_rectangle(box, r, fill=fill, outline=outline, width=width)

def appear(t, start, dur=0.45):
    return ease((t - start) / dur)

def slide(box, a, dy=24):
    x0, y0, x1, y1 = box; o = int((1 - a) * dy); return (x0, y0 + o, x1, y1 + o)

# ---------- scenes ----------
TOP = 150  # content starts below the heading
BOTTOM = 560  # content ends above the caption

def heading(d, s, accent):
    d.rectangle([64, 92, 72, 132], fill=accent)
    para(d, (88, 90), s, 'Bold', 38, W - 200, INK, max_lines=1)

def times(n, T, lead=0.4, tail=1.2):
    """Reveal times for n items spread across the narration."""
    span = max(0.5, T - lead - tail)
    return [lead + span * i / max(1, n) for i in range(n)]

def s_title(im, d, t, T, sc, ctx):
    acc, tint = ctx['colors']
    a = appear(t, 0)
    d.ellipse([W // 2 - 95, 120, W // 2 + 95, 310], fill=tint)
    emoji(im, sc.get('icon', '🔬'), W // 2, 215, int(110 * (0.8 + 0.2 * a)))
    para(d, (W // 2, 340), sc.get('heading', ctx['title']), 'Bold', 54, W - 240, INK, 2, anchor='m')
    if sc.get('sub'): para(d, (W // 2, 470), sc['sub'], 'Medium', 28, W - 300, MUTED, 2, anchor='m')

def s_points(im, d, t, T, sc, ctx):
    acc, tint = ctx['colors']
    heading(d, sc['heading'], acc)
    pts = sc.get('points', [])[:5]
    icon = sc.get('icon')
    x0 = 360 if icon else 90
    if icon:
        d.ellipse([90, 210, 290, 410], fill=tint); emoji(im, icon, 190, 310, 120)
    ts = times(len(pts), T)
    gap = min(84, (BOTTOM - TOP - 10) // max(1, len(pts)))
    for i, p in enumerate(pts):
        a = appear(t, ts[i])
        if a <= 0: continue
        y = TOP + 10 + i * gap
        box = slide((x0, y, W - 80, y + gap - 14), a, 16)
        card(d, box, WHITE, LINE)
        d.ellipse([box[0] + 18, box[1] + (gap - 14) // 2 - 7, box[0] + 32, box[1] + (gap - 14) // 2 + 7], fill=acc)
        f, lines = fit(d, p, 'SemiBold', 27, box[2] - box[0] - 70, 2)
        lh = int(f.size * 1.2); ty = box[1] + ((gap - 14) - lh * len(lines)) // 2
        for j, ln in enumerate(lines): d.text((box[0] + 48, ty + j * lh), ln, font=f, fill=INK)

def s_analogy(im, d, t, T, sc, ctx):
    acc, tint = ctx['colors']
    heading(d, sc.get('heading', 'Think of it like this'), acc)
    ts = times(3 if sc.get('breaks') else 2, T)
    # left: everyday thing
    a = appear(t, 0.2)
    if a > 0:
        box = slide((80, TOP + 10, 590, 440), a)
        card(d, box, WHITE, LINE)
        emoji(im, sc.get('icon', '💡'), (box[0] + box[2]) // 2, box[1] + 85, 110)
        d.text(((box[0] + box[2]) // 2, box[1] + 158), 'EVERYDAY LIFE', font=font('SemiBold', 18), fill=MUTED, anchor='ma')
        para(d, ((box[0] + box[2]) // 2, box[1] + 192), sc.get('everyday', ''), 'SemiBold', 28, 440, INK, 3, anchor='m')
    a = appear(t, ts[1] if len(ts) > 1 else 1.5)
    if a > 0:
        d.text((640, 300), '=', font=font('Bold', 64), fill=acc, anchor='mm')
        box = slide((690, TOP + 10, W - 80, 440), a)
        card(d, box, tint, tint)
        emoji(im, sc.get('icon2', ctx.get('icon', '🔬')), (box[0] + box[2]) // 2, box[1] + 85, 110)
        d.text(((box[0] + box[2]) // 2, box[1] + 158), 'THE SCIENCE', font=font('SemiBold', 18), fill=acc, anchor='ma')
        para(d, ((box[0] + box[2]) // 2, box[1] + 192), sc.get('science', ''), 'SemiBold', 28, 440, INK, 3, anchor='m')
    if sc.get('breaks'):
        a = appear(t, ts[2])
        if a > 0:
            box = slide((80, 460, W - 80, 540), a, 12)
            card(d, box, (255, 246, 230), (245, 200, 120))
            d.text((104, 500), '⚠', font=ImageFont.truetype(SYMBOL, 30), fill=(190, 120, 0), anchor='lm')
            para(d, (146, 482), 'Where it breaks down: ' + sc['breaks'], 'Medium', 22, W - 260, INK, 2)

def s_equation(im, d, t, T, sc, ctx):
    acc, tint = ctx['colors']
    heading(d, sc['heading'], acc)
    a = appear(t, 0.2)
    box = slide((140, TOP + 10, W - 140, 300), a)
    card(d, box, tint, tint)
    f, lines = fit(d, sc['equation'], 'Bold', 54, box[2] - box[0] - 60, 1, 26)
    d.text(((box[0] + box[2]) // 2, (box[1] + box[3]) // 2), lines[0], font=f, fill=acc, anchor='mm')
    parts = sc.get('parts', [])[:4]
    ts = times(len(parts) + 1, T)[1:]
    for i, (sym, meaning) in enumerate(parts):
        aa = appear(t, ts[i])
        if aa <= 0: continue
        y = 330 + i * 56
        b = slide((240, y, W - 240, y + 46), aa, 10)
        card(d, b, WHITE, LINE, 12)
        d.text((b[0] + 24, (b[1] + b[3]) // 2), sym, font=font('Bold', 26), fill=acc, anchor='lm')
        para(d, (b[0] + 150, b[1] + 8), meaning, 'Medium', 24, b[2] - b[0] - 180, INK, 1)

def s_example(im, d, t, T, sc, ctx):
    acc, tint = ctx['colors']
    heading(d, sc.get('heading', 'Worked example'), acc)
    q = sc.get('question', '')
    yq = TOP + 4
    if q:
        card(d, (80, yq, W - 80, yq + 92), tint, tint)
        para(d, (104, yq + 14), q, 'SemiBold', 25, W - 220, INK, 2)
        yq += 108
    lines = sc.get('lines', [])[:5]
    ts = times(len(lines), T, lead=1.2)
    gap = min(64, (BOTTOM - yq) // max(1, len(lines)))
    for i, ln in enumerate(lines):
        a = appear(t, ts[i])
        if a <= 0: continue
        y = yq + i * gap
        d.text((96, y + 6), f'{i + 1}', font=font('Bold', 24), fill=WHITE, anchor='la') if False else None
        d.ellipse([84, y + 4, 120, y + 40], fill=acc)
        d.text((102, y + 22), str(i + 1), font=font('Bold', 20), fill=WHITE, anchor='mm')
        last = i == len(lines) - 1
        para(d, (138, y + 6), ln, 'Bold' if last else 'Medium', 26, W - 240, GREEN if last else INK, 1)

def s_compare(im, d, t, T, sc, ctx):
    acc, tint = ctx['colors']
    heading(d, sc['heading'], acc)
    sides = [sc.get('left', {}), sc.get('right', {})]
    ts = times(2, T)
    for k, side in enumerate(sides):
        a = appear(t, ts[k])
        if a <= 0: continue
        x0 = 80 if k == 0 else 660
        box = slide((x0, TOP + 6, x0 + 540, BOTTOM - 10), a)
        card(d, box, WHITE if k == 0 else tint, LINE if k == 0 else tint)
        emoji(im, side.get('icon', ''), box[0] + 62, box[1] + 58, 70)
        para(d, (box[0] + 112, box[1] + 36), side.get('title', ''), 'Bold', 30, 400, INK, 1)
        y = box[1] + 112
        for p in side.get('points', [])[:4]:
            d.ellipse([box[0] + 30, y + 10, box[0] + 42, y + 22], fill=acc)
            y = para(d, (box[0] + 56, y), p, 'Medium', 24, 450, INK, 2) + 12

def s_steps(im, d, t, T, sc, ctx):
    acc, tint = ctx['colors']
    heading(d, sc['heading'], acc)
    steps = sc.get('steps', [])[:5]
    n = len(steps); ts = times(n, T)
    w = (W - 160 - (n - 1) * 36) // max(1, n)
    for i, s in enumerate(steps):
        a = appear(t, ts[i])
        if a <= 0: continue
        x0 = 80 + i * (w + 36)
        box = slide((x0, 230, x0 + w, 470), a)
        card(d, box, tint if i == n - 1 else WHITE, tint if i == n - 1 else LINE)
        d.ellipse([x0 + w // 2 - 22, 252, x0 + w // 2 + 22, 296], fill=acc)
        d.text((x0 + w // 2, 274), str(i + 1), font=font('Bold', 22), fill=WHITE, anchor='mm')
        para(d, (x0 + w // 2, 316), s, 'SemiBold', 24 if n > 3 else 27, w - 30, INK, 5, anchor='m')
        if i < n - 1:
            ax = x0 + w + 6; d.polygon([(ax, 340), (ax + 24, 350), (ax, 360)], fill=acc)

def s_recap(im, d, t, T, sc, ctx):
    acc, tint = ctx['colors']
    heading(d, sc.get('heading', 'Quick recap'), acc)
    pts = sc.get('points', [])[:5]
    ts = times(len(pts), T, tail=2.0)
    gap = min(70, (BOTTOM - TOP - 60) // max(1, len(pts)))
    for i, p in enumerate(pts):
        a = appear(t, ts[i])
        if a <= 0: continue
        y = TOP + 6 + i * gap
        d.text((96, y + gap // 2 - 6), '✓', font=ImageFont.truetype(SYMBOL, 30), fill=GREEN, anchor='lm')
        para(d, (140, y + gap // 2 - 22), p, 'SemiBold', 27, W - 260, INK, 1)
    a = appear(t, T - 2.2)
    if a > 0:
        para(d, (W // 2, BOTTOM - 46), sc.get('closing', 'Now try the practice questions!'), 'Bold', 32, W - 300, acc, 1, anchor='m')


# ---------- maths: handwriting board and drawn diagrams ----------
HAND = os.path.join(ROOT, 'video', 'fonts', 'Caveat.ttf')
_hand = {}
def hand(size):
    if size not in _hand:
        f = ImageFont.truetype(HAND, size)
        try: f.set_variation_by_axes([600])
        except Exception: pass
        _hand[size] = f
    return _hand[size]

BOARD = (252, 252, 247); CHALK = (31, 41, 55)

_hand_cmap = None
def hand_runs(s):
    """Split text into runs: handwriting font where it has the glyph, a symbol font (✓, √, π, →…) where it doesn't."""
    global _hand_cmap
    if _hand_cmap is None:
        try:
            from fontTools.ttLib import TTFont
            _hand_cmap = set(TTFont(HAND).getBestCmap())
        except Exception:
            _hand_cmap = set(range(32, 127))
    runs = []
    for ch in s:
        ok = ord(ch) in _hand_cmap or ch == ' '
        if runs and runs[-1][1] == ok: runs[-1][0] += ch
        else: runs.append([ch, ok])
    return runs

def hand_width(d, s, size):
    sym = ImageFont.truetype(SYMBOL, int(size * 0.85))
    return sum(d.textlength(t, font=hand(size) if ok else sym) for t, ok in hand_runs(s))

def write_line(im, xy, s, size, color, frac):
    """Draw text as if being written: reveal it left to right, with a pen at the tip."""
    if frac <= 0: return
    f = hand(size); sym = ImageFont.truetype(SYMBOL, int(size * 0.85))
    probe = ImageDraw.Draw(im)
    tmp = Image.new('RGBA', (int(hand_width(probe, s, size)) + 20, int(size * 1.6)), (0, 0, 0, 0))
    td = ImageDraw.Draw(tmp); x = 4
    for t, ok in hand_runs(s):
        if ok: td.text((x, 0), t, font=f, fill=color)
        else: td.text((x, int(size * 0.24)), t, font=sym, fill=color)
        x += td.textlength(t, font=f if ok else sym)
    w = max(1, int(tmp.width * min(1, frac)))
    im.paste(tmp.crop((0, 0, w, tmp.height)), (int(xy[0]), int(xy[1])), tmp.crop((0, 0, w, tmp.height)))
    if frac < 1:
        d = ImageDraw.Draw(im); px, py = xy[0] + w, xy[1] + size * 0.75
        d.line([(px, py), (px + 16, py - 34)], fill=(91, 62, 214), width=6)
        d.ellipse([px - 4, py - 4, px + 4, py + 4], fill=(91, 62, 214))

def s_board(im, d, t, T, sc, ctx):
    """A worked example written on a whiteboard, line by line, as the teacher talks."""
    acc, tint = ctx['colors']
    heading(d, sc.get('heading', 'Worked example'), acc)
    d.rounded_rectangle([70, TOP - 6, W - 70, BOTTOM + 4], 18, fill=BOARD, outline=(214, 211, 199), width=3)
    q = sc.get('question', '')
    y = TOP + 10
    if q:
        y = para(d, (100, y), q, 'SemiBold', 26, W - 220, INK, 2) + 8
        d.line([(100, y), (W - 100, y)], fill=(225, 222, 210), width=2); y += 10
    lines = sc.get('lines', [])[:5]
    n = max(1, len(lines))
    size = 54 if n <= 3 else (48 if n == 4 else 42)
    while size > 30 and max((hand_width(d, ln, size) for ln in lines), default=0) > W - 260:
        size -= 2
    gap = min(int(size * 1.35), (BOTTOM - y) // n)
    ts = times(n, T, lead=1.0, tail=0.8)
    for i, ln in enumerate(lines):
        dur = max(0.9, min(2.2, len(ln) / 18))
        frac = (t - ts[i]) / dur
        last = i == len(lines) - 1
        write_line(im, (110, y + i * gap), ln, size, (21, 128, 61) if last else CHALK, frac)

_layers = {}
def diagram_layers(path, width):
    """The diagram as images, one per drawing step (lines first, then angles and labels)."""
    key = (path, width)
    if key in _layers: return _layers[key]
    import cairosvg, io
    src = open(os.path.join(ROOT, 'public', path)).read()
    steps = sorted({int(x) for x in re.findall(r'data-step="(\d)"', src)}) or [1]
    out = []
    for k in steps:
        layer = re.sub(r'<(\w+)\b[^>]*data-step="(\d)"[^>]*?(?:/>|>.*?</\1>)',
                       lambda m: m.group(0) if int(m.group(2)) == k else '', src, flags=re.S)
        layer = layer.replace('<rect width="100%" height="100%" fill="#FFFFFF"/>', '')
        png = cairosvg.svg2png(bytestring=layer.encode(), output_width=width)
        out.append(Image.open(io.BytesIO(png)).convert('RGBA'))
    _layers[key] = out
    return out

def s_diagram(im, d, t, T, sc, ctx):
    """A diagram drawn in stages (shape, then angles and labels), with up to 3 short notes."""
    acc, tint = ctx['colors']
    heading(d, sc.get('heading', ''), acc)
    notes = sc.get('notes', [])[:3]
    width = 600 if notes else 760
    layers = diagram_layers(sc['diagram'], width)
    h = layers[0].height
    scale = min(1, (BOTTOM - TOP + 4) / h)
    if scale < 1:
        layers = [l.resize((int(l.width * scale), int(l.height * scale)), Image.LANCZOS) for l in layers]
    lw, lh = layers[0].size
    x0 = 80 if notes else (W - lw) // 2
    y0 = TOP - 6 + ((BOTTOM - TOP + 10) - lh) // 2
    d.rounded_rectangle([x0 - 10, y0 - 10, x0 + lw + 10, y0 + lh + 10], 16, fill=WHITE, outline=LINE, width=2)
    k = len(layers)
    ts = times(k + len(notes), T, lead=0.3)
    for i, layer in enumerate(layers):
        frac = (t - ts[i]) / 1.4
        if frac <= 0: continue
        w = max(1, int(lw * min(1, ease(frac))))
        piece = layer.crop((0, 0, w, lh))
        im.paste(piece, (x0, y0), piece)
    for j, note in enumerate(notes):
        a = appear(t, ts[k + j])
        if a <= 0: continue
        yy = TOP + 20 + j * 120
        box = slide((x0 + lw + 40, yy, W - 70, yy + 100), a, 14)
        card(d, box, tint if j == len(notes) - 1 else WHITE, tint if j == len(notes) - 1 else LINE, 14)
        para(d, (box[0] + 20, box[1] + 16), note, 'SemiBold', 25, box[2] - box[0] - 40, INK, 3)

SCENES = {'title': s_title, 'points': s_points, 'analogy': s_analogy, 'equation': s_equation,
          'example': s_example, 'compare': s_compare, 'steps': s_steps, 'recap': s_recap,
          'board': s_board, 'diagram': s_diagram}

# ---------- frame ----------
def base(ctx):
    im = Image.new('RGB', (W, H), BG); d = ImageDraw.Draw(im)
    acc, _ = ctx['colors']
    d.rectangle([0, 0, W, 56], fill=NAVY)
    d.text((32, 28), 'Brainsnacc', font=font('Bold', 22), fill=WHITE, anchor='lm')
    d.ellipse([W - 32 - d.textlength(ctx['label'], font=font('Medium', 18)) - 22, 22, W - 32 - d.textlength(ctx['label'], font=font('Medium', 18)) - 10, 34], fill=acc)
    d.text((W - 32, 28), ctx['label'], font=font('Medium', 18), fill=(185, 195, 210), anchor='rm')
    return im, d

def caption(d, s):
    f, lines = fit(d, s, 'Medium', 24, W - 220, 3, 18)
    lh = int(f.size * 1.4)
    y = H - 34 - lh * len(lines)
    d.rounded_rectangle([80, y - 14, W - 80, H - 24], 16, fill=WHITE, outline=LINE, width=2)
    for i, ln in enumerate(lines): d.text((W // 2, y + i * lh), ln, font=f, fill=INK, anchor='ma')

def progress(d, frac, acc):
    d.rectangle([0, H - 6, int(W * frac), H], fill=acc)

# ---------- audio ----------
SPOKEN = [(r'(\d)\s*×\s*10\^?(\d+)', r'\1 times ten to the \2'), ('⁻¹', ' per '), ('→', ' to '), ('≈', ' about '), ('%', ' percent'),
          ('°C', ' degrees Celsius'), ('−', ' minus '), ('Δ', 'delta ')]
def spoken(s):
    for a, b in SPOKEN: s = re.sub(a, b, s) if a.startswith('(') else s.replace(a, b)
    return s

def tts(text, length_scale=1.3):
    os.makedirs(CACHE, exist_ok=True)
    key = hashlib.sha1((VOICE + str(length_scale) + text).encode()).hexdigest()[:16]
    wav = os.path.join(CACHE, key + '.wav')
    if not os.path.exists(wav):
        tmp = wav + '.tmp.wav'
        subprocess.run([sys.executable, '-m', 'piper', '-m', VOICE, '-f', tmp, '--length-scale', str(length_scale), '--sentence-silence', '0.5'],
                       input=text.encode(), check=True, capture_output=True)
        os.replace(tmp, wav)
    with wave.open(wav) as w: return wav, w.getnframes() / w.getframerate()

# ---------- render ----------
def render(script_path, stills=False):
    sc = json.load(open(script_path))
    rel = os.path.relpath(script_path, os.path.join(ROOT, 'video', 'scripts'))
    out = os.path.join(ROOT, 'public', 'videos', os.path.splitext(rel)[0] + '.mp4')
    os.makedirs(os.path.dirname(out), exist_ok=True)
    subject = sc.get('subject', 'chemistry')
    ctx = {'colors': SUBJECT.get(subject, SUBJECT['chemistry']), 'title': sc['title'], 'icon': sc.get('icon', '🔬'),
           'label': f"{sc.get('level', '')} {subject.title()} · {sc['title']}".strip()}
    work = tempfile.mkdtemp(prefix='bsv-')
    files = []
    total = sum(1 for _ in sc['scenes'])
    for i, scene in enumerate(sc['scenes']):
        fn = SCENES[scene['type']]
        if subject in ('maths', 'selective') and scene['type'] == 'example':
            fn = s_board
        wav, dur = tts(spoken(scene.get('say_as', scene['say'])))
        T = dur + 0.9
        cap = scene.get('caption', scene['say'])
        if stills:
            for frac in (0.98,):
                im, d = base(ctx); fn(im, d, T * frac, T, scene, ctx); caption(d, cap); progress(d, (i + frac) / total, ctx['colors'][0])
                im.save(os.path.join(work, f'{i:02d}_{scene["type"]}.jpg'), quality=85)
            continue
        n = int(round(T * FPS)); part = os.path.join(work, f'{i:02d}.mp4'); tmp = part + '.tmp.mp4'
        p = subprocess.Popen(['ffmpeg', '-y', '-loglevel', 'error', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                              '-i', wav, '-af', 'adelay=350:all=1,apad', '-t', f'{n / FPS:.3f}',
                              '-vf', f'fade=in:0:6,fade=out:{max(0, n - 6)}:6', '-c:v', 'libx264', '-crf', '27', '-preset', 'veryfast', '-tune', 'stillimage',
                              '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '80k', '-ar', '44100', '-ac', '1', tmp], stdin=subprocess.PIPE)
        last = None
        for k in range(n):
            # Draw 10 new pictures a second and repeat each one 3 times: same 30 fps video, a third of the work.
            if k % 3 == 0 or last is None:
                t = k / FPS
                im, d = base(ctx); fn(im, d, t, T, scene, ctx); caption(d, cap); progress(d, (i + t / T) / total, ctx['colors'][0])
                last = im.tobytes()
            p.stdin.write(last)
        p.stdin.close()
        if p.wait() != 0: raise SystemExit('ffmpeg failed on scene %d' % i)
        os.replace(tmp, part); files.append(part)
    if stills:
        print(work); return work
    lst = os.path.join(work, 'list.txt')
    open(lst, 'w').write(''.join(f"file '{f}'\n" for f in files))
    tmp = out + '.tmp.mp4'
    subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-f', 'concat', '-safe', '0', '-i', lst, '-c', 'copy', '-movflags', '+faststart', tmp], check=True)
    os.replace(tmp, out)
    for f in files: os.remove(f)
    print(out)
    return out

if __name__ == '__main__':
    render(sys.argv[1], '--stills' in sys.argv)
