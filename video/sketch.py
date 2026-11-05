"""Draw a diagram the way a teacher would: by hand, one line after another.

Takes a diagram SVG made with diagrams.py (elements tagged data-step="1|2|3"), gives straight lines a slight
hand-drawn wobble (never enough to change what the diagram shows), and renders any moment of the drawing:
step 1 (the shape) is drawn line by line, then step 2 (angles, marks and labels), then step 3. Fills appear once
their outline is finished; labels appear when the pen reaches them.
"""
import io, math, random, re

ELEM = re.compile(r'<(line|polyline|polygon|path|circle|ellipse|rect|text)\b([^>]*?)(/>|>(.*?)</\1>)', re.S)
NUM = re.compile(r'-?\d*\.?\d+(?:e[-+]?\d+)?')


def _attrs(s):
    return dict(re.findall(r'([\w:-]+)="([^"]*)"', s))


def _f(a, k, d=0.0):
    try:
        return float(a.get(k, d))
    except ValueError:
        return d


def _wobbly(points, closed, rnd):
    """Points → a path with each straight edge bowed very slightly, like a ruler-less pen stroke."""
    pts = list(points) + ([points[0]] if closed else [])
    d = f'M{pts[0][0]:.1f},{pts[0][1]:.1f}'
    total = 0.0
    for a, b in zip(pts, pts[1:]):
        L = math.dist(a, b); total += L
        if L < 12:
            d += f' L{b[0]:.1f},{b[1]:.1f}'; continue
        off = min(2.6, L * 0.012) * rnd.choice((-1, 1)) * (0.5 + rnd.random() * 0.5)
        mx, my = (a[0] + b[0]) / 2, (a[1] + b[1]) / 2
        nx, ny = -(b[1] - a[1]) / L, (b[0] - a[0]) / L
        d += f' Q{mx + nx * off:.1f},{my + ny * off:.1f} {b[0]:.1f},{b[1]:.1f}'
    return d + (' Z' if closed else ''), total


def _path_length(d):
    """Rough length of an SVG path (M, L, Q, C, A, Z), enough to time the pen."""
    total, pos, start = 0.0, None, None
    for cmd, args in re.findall(r'([MLQCAZHVmlqcazhv])([^MLQCAZHVmlqcazhv]*)', d):
        n = [float(x) for x in NUM.findall(args)]
        if cmd == 'M' and len(n) >= 2:
            pos = start = (n[0], n[1])
        elif cmd == 'L':
            for i in range(0, len(n) - 1, 2):
                p = (n[i], n[i + 1]); total += math.dist(pos, p) if pos else 0; pos = p
        elif cmd in 'QC':
            k = 4 if cmd == 'Q' else 6
            for i in range(0, len(n) - k + 1, k):
                p = (n[i + k - 2], n[i + k - 1]); total += math.dist(pos, p) * 1.15 if pos else 0; pos = p
        elif cmd == 'A':
            for i in range(0, len(n) - 6, 7):
                p = (n[i + 5], n[i + 6]); r = n[i]
                chord = math.dist(pos, p) if pos else 0
                ang = 2 * math.asin(min(1, chord / (2 * r))) if r else 0
                if n[i + 3]: ang = 2 * math.pi - ang
                total += r * ang if r else chord; pos = p
        elif cmd in 'Zz' and pos and start:
            total += math.dist(pos, start); pos = start
    return max(total, 1.0)


class Sketch:
    def __init__(self, svg_text, seed=7):
        rnd = random.Random(seed)
        self.items = []          # [step, kind, template, length]; template has {DASH} / {OP} slots
        parts = []; last = 0
        for m in ELEM.finditer(svg_text):
            parts.append(svg_text[last:m.start()]); last = m.end()
            tag, raw, inner = m.group(1), m.group(2), m.group(4)
            a = _attrs(raw)
            step = int(a.get('data-step', 1))
            if tag == 'rect' and a.get('width') == '100%':
                parts.append(m.group(0)); continue
            if tag == 'text':
                raw_t = re.sub(r'\sopacity="[^"]*"', '', raw)
                tpl = f'<text{raw_t} opacity="{{OP}}">{inner}</text>'
                self.items.append([step, 'text', tpl, 0]); parts.append('{ITEM%d}' % (len(self.items) - 1)); continue
            # Shapes: rebuild as a path with a dash we can grow.
            keep = re.sub(r'\s(x1|y1|x2|y2|points|d|cx|cy|r|rx|ry|x|y|width|height)="[^"]*"', '', raw)
            if tag in ('line', 'polyline') and 'fill=' not in keep:
                keep += ' fill="none"'
            if tag == 'line':
                d, L = _wobbly([(_f(a, 'x1'), _f(a, 'y1')), (_f(a, 'x2'), _f(a, 'y2'))], False, rnd)
            elif tag in ('polyline', 'polygon'):
                n = [float(x) for x in NUM.findall(a.get('points', ''))]
                pts = list(zip(n[0::2], n[1::2]))
                if len(pts) < 2:
                    parts.append(m.group(0)); continue
                d, L = _wobbly(pts, tag == 'polygon', rnd)
            elif tag == 'rect':
                x, y, w, h = _f(a, 'x'), _f(a, 'y'), _f(a, 'width'), _f(a, 'height')
                if w <= 0 or h <= 0:
                    parts.append(m.group(0)); continue
                d, L = _wobbly([(x, y), (x + w, y), (x + w, y + h), (x, y + h)], True, rnd)
            elif tag == 'circle':
                cx, cy, r = _f(a, 'cx'), _f(a, 'cy'), _f(a, 'r')
                d = f'M{cx + r:.1f},{cy:.1f} A{r},{r} 0 1 1 {cx - r:.1f},{cy:.1f} A{r},{r} 0 1 1 {cx + r:.1f},{cy:.1f}'
                L = 2 * math.pi * r
            elif tag == 'ellipse':
                cx, cy, rx, ry = _f(a, 'cx'), _f(a, 'cy'), _f(a, 'rx'), _f(a, 'ry')
                d = f'M{cx + rx:.1f},{cy:.1f} A{rx},{ry} 0 1 1 {cx - rx:.1f},{cy:.1f} A{rx},{ry} 0 1 1 {cx + rx:.1f},{cy:.1f}'
                L = math.pi * (rx + ry)
            else:  # path
                d = a.get('d', ''); L = _path_length(d) * 1.1
            dashed = 'stroke-dasharray' in raw
            has_stroke = a.get('stroke', 'none') != 'none' or (tag in ('line', 'polyline'))
            if dashed or not has_stroke:
                # Dashed (hidden) edges and fill-only shapes appear once their turn comes, rather than being traced.
                keep_f = re.sub(r'\sopacity="[^"]*"', '', keep)
                tpl = f'<path d="{d}"{keep_f} opacity="{{OP}}"/>'
                self.items.append([step, 'fade', tpl, max(L, 40)])
            else:
                kept = re.sub(r'\s(fill-opacity|stroke-dasharray|stroke-dashoffset|stroke-linecap|stroke-linejoin)="[^"]*"', '', keep)
                fill_op = ''
                tpl = (f'<path d="{d}"{kept} stroke-dasharray="{L * 1.02:.1f} {L * 1.02 + 10:.1f}" '
                       f'stroke-dashoffset="{{DASH}}" fill-opacity="{{FOP}}" stroke-linecap="round" stroke-linejoin="round"/>')
                self.items.append([step, 'stroke', tpl, L])
            parts.append('{ITEM%d}' % (len(self.items) - 1))
        parts.append(svg_text[last:])
        self.skeleton = ''.join(parts)
        self.steps = sorted({it[0] for it in self.items}) or [1]
        # Within a step, the pen spends time in proportion to how far it travels (labels count as a short stroke).
        self.slots = {}
        for s in self.steps:
            idx = [i for i, it in enumerate(self.items) if it[0] == s]
            w = [max(self.items[i][3], 30) if self.items[i][1] != 'text' else 45 for i in idx]
            tot = sum(w) or 1; acc = 0
            for i, ww in zip(idx, w):
                self.slots[i] = (acc / tot, (acc + ww) / tot); acc += ww

    def svg_at(self, progress):
        """progress: {step: 0..1}. Steps not listed are hidden."""
        out = {}
        for i, (step, kind, tpl, L) in enumerate(self.items):
            p = progress.get(step, 0.0)
            a, b = self.slots[i]
            f = 0.0 if p <= a else (1.0 if p >= b else (p - a) / (b - a))
            if kind == 'text':
                out[i] = tpl.replace('{OP}', f'{min(1, f * 1.6):.2f}') if f > 0 else ''
            elif kind == 'fade':
                out[i] = tpl.replace('{OP}', f'{f:.2f}') if f > 0 else ''
            else:
                if f <= 0:
                    out[i] = ''; continue
                dash = (L * 1.02) * (1 - f)
                out[i] = tpl.replace('{DASH}', f'{dash:.1f}').replace('{FOP}', '1' if f >= 1 else f'{max(0, f - 0.7) / 0.3:.2f}')
        return re.sub(r'\{ITEM(\d+)\}', lambda m: out[int(m.group(1))], self.skeleton)

    def png(self, progress, width):
        import cairosvg
        from PIL import Image
        svg = self.svg_at(progress).replace('<rect width="100%" height="100%" fill="#FFFFFF"/>', '')
        return Image.open(io.BytesIO(cairosvg.svg2png(bytestring=svg.encode(), output_width=width))).convert('RGBA')
