"""Single-line (pen-stroke) fonts, so writing can appear stroke by stroke like a real pen.

Fonts: EMS SVG fonts (SIL Open Font License, see fonts/single-line/OFL.txt). Maths symbols the font lacks
(× ÷ − √ ✓ ✗ → ≈ ≤ ≥ ∠ π …) are drawn from simple stroke shapes below; anything else falls back to a normal
font that fades in when the pen reaches it.
"""
import math, os, re
from xml.etree import ElementTree as ET

HERE = os.path.dirname(os.path.abspath(__file__))

# Extra glyphs as strokes in a 0..1000 box (x right, y up, baseline 0, x-height ≈ 300, cap ≈ 500).
EXTRA = {
    '×': (360, [[(90, 120), (270, 300)], [(90, 300), (270, 120)]]),
    '÷': (440, [[(60, 180), (380, 180)], [(210, 300), (220, 310)], [(210, 55), (220, 65)]]),
    '−': (440, [[(60, 180), (380, 180)]]),
    '–': (440, [[(60, 180), (380, 180)]]),
    '—': (640, [[(40, 180), (600, 180)]]),
    '√': (520, [[(30, 220), (110, 260), (210, -20), (330, 560), (520, 560)]]),
    '✓': (440, [[(40, 200), (150, 60), (420, 460)]]),
    '✗': (520, [[(60, 0), (200, 200), (460, 560)], [(80, 520), (260, 300), (440, 20)]]),
    '→': (560, [[(40, 180), (500, 180)], [(380, 280), (500, 180), (380, 80)]]),
    '←': (560, [[(520, 180), (60, 180)], [(180, 280), (60, 180), (180, 80)]]),
    '≈': (460, [[(50, 230), (130, 270), (230, 230), (330, 190), (410, 230)], [(50, 110), (130, 150), (230, 110), (330, 70), (410, 110)]]),
    '≤': (440, [[(380, 400), (60, 260), (380, 120)], [(60, 40), (380, 40)]]),
    '≥': (440, [[(60, 400), (380, 260), (60, 120)], [(60, 40), (380, 40)]]),
    '≠': (440, [[(60, 240), (380, 240)], [(60, 120), (380, 120)], [(320, 360), (120, 0)]]),
    '∠': (480, [[(420, 460), (40, 30), (440, 30)]]),
    'π': (480, [[(40, 300), (440, 300)], [(150, 300), (120, 0)], [(330, 300), (340, 40), (400, 0)]]),
    '∥': (300, [[(110, 500), (110, 0)], [(210, 500), (210, 0)]]),
    '△': (520, [[(40, 20), (260, 440), (480, 20), (40, 20)]]),
    '∞': (600, [[(300, 180), (220, 280), (90, 260), (60, 180), (90, 100), (220, 80), (380, 280), (510, 260), (540, 180), (510, 100), (380, 80), (300, 180)]]),
    '·': (200, [[(95, 180), (105, 190)]]),
}


def _parse(d):
    """SVG path (M, L, C, Q, Z; absolute) → list of strokes (lists of points)."""
    toks = re.findall(r'[MLCQZmlcqz]|-?\d*\.?\d+(?:e[-+]?\d+)?', d)
    strokes, cur, i, cmd, pos, start = [], None, 0, None, (0, 0), (0, 0)
    def num():
        nonlocal i
        v = float(toks[i]); i += 1; return v
    while i < len(toks):
        if toks[i].isalpha():
            cmd = toks[i]; i += 1
            if cmd in 'Zz':
                if cur: cur.append(start)
                continue
        if cmd in 'Mm':
            pos = (num(), num()); start = pos; cur = [pos]; strokes.append(cur); cmd = 'L' if cmd == 'M' else 'l'
        elif cmd in 'Ll':
            p = (num(), num()); pos = p if cmd == 'L' else (pos[0] + p[0], pos[1] + p[1]); cur.append(pos)
        elif cmd in 'Cc':
            c1 = (num(), num()); c2 = (num(), num()); p = (num(), num())
            if cmd == 'c': c1 = (pos[0] + c1[0], pos[1] + c1[1]); c2 = (pos[0] + c2[0], pos[1] + c2[1]); p = (pos[0] + p[0], pos[1] + p[1])
            for k in range(1, 9):
                t = k / 8; a = (1 - t) ** 3; b = 3 * (1 - t) ** 2 * t; c = 3 * (1 - t) * t * t; e = t ** 3
                cur.append((a * pos[0] + b * c1[0] + c * c2[0] + e * p[0], a * pos[1] + b * c1[1] + c * c2[1] + e * p[1]))
            pos = p
        elif cmd in 'Qq':
            c1 = (num(), num()); p = (num(), num())
            if cmd == 'q': c1 = (pos[0] + c1[0], pos[1] + c1[1]); p = (pos[0] + p[0], pos[1] + p[1])
            for k in range(1, 7):
                t = k / 6
                cur.append(((1 - t) ** 2 * pos[0] + 2 * (1 - t) * t * c1[0] + t * t * p[0], (1 - t) ** 2 * pos[1] + 2 * (1 - t) * t * c1[1] + t * t * p[1]))
            pos = p
        else:
            i += 1
    return [s for s in strokes if len(s) > 1 or True]


class StrokeFont:
    def __init__(self, name='EMSCasualHand'):
        root = ET.parse(os.path.join(HERE, 'fonts', 'single-line', name + '.svg')).getroot()
        ns = {'s': 'http://www.w3.org/2000/svg'}
        font = root.find('.//s:font', ns)
        self.adv = float(font.get('horiz-adv-x', 500))
        face = root.find('.//s:font-face', ns)
        self.em = float(face.get('units-per-em', 1000))
        self.glyphs = {}
        for g in root.findall('.//s:glyph', ns):
            u = g.get('unicode')
            if u is None or len(u) != 1: continue
            self.glyphs[u] = (float(g.get('horiz-adv-x', self.adv)), _parse(g.get('d', '')))
        # Size the extra symbols to this font's digits (EXTRA is drawn for a cap height of 500).
        zero = self.glyphs.get('0', (0, [[(0, 0), (0, 500)]]))[1]
        self.cap = max(p[1] for st in zero for p in st) or 500
        f = self.cap / 500
        for k, v in EXTRA.items():
            if k not in self.glyphs:
                self.glyphs[k] = (v[0] * f, [[(x * f, y * f) for x, y in st] for st in v[1]])
        # Superscripts and the degree sign: small raised digits and a small circle, drawn by us so they sit right.
        for sup, digit in zip('⁰¹²³⁴⁵⁶⁷⁸⁹', '0123456789'):
            if digit in self.glyphs:
                adv, ss = self.glyphs[digit]
                self.glyphs[sup] = (adv * 0.55, [[(x * 0.55, y * 0.55 + self.cap * 0.55) for x, y in st] for st in ss])
        r = self.cap * 0.13
        self.glyphs['°'] = (self.cap * 0.4, [[(self.cap * 0.16 + r * math.cos(a / 12 * 2 * math.pi), self.cap * 0.95 + r * math.sin(a / 12 * 2 * math.pi)) for a in range(13)]])

    def has(self, ch):
        return ch in self.glyphs

    def layout(self, text, size):
        """Strokes in pixels (origin at the left of the baseline, y down), the width, and characters to fall back on."""
        k = size / self.em * 1.12
        x = 0.0; strokes = []; fallback = []
        for ch in text:
            if ch in self.glyphs:
                adv, ss = self.glyphs[ch]
                for s in ss:
                    strokes.append([(x + px * k, -py * k) for px, py in s])
                x += adv * k
            else:
                fallback.append((x, ch, len(strokes)))
                x += size * 0.62
        return strokes, x, fallback


def length(stroke):
    return sum(math.dist(stroke[i], stroke[i + 1]) for i in range(len(stroke) - 1)) or 1.0


def draw(d, origin, laid, frac, color, width, fallback_font=None, size=40):
    """Draw the first `frac` of the strokes (by pen distance). Returns the pen tip, or None when finished."""
    strokes, _, fallback = laid
    lens = [length(s) for s in strokes]
    total = sum(lens) + 1e-9
    budget = total * max(0.0, min(1.0, frac))
    ox, oy = origin; tip = None; done_strokes = 0
    for s, L in zip(strokes, lens):
        if budget <= 0: break
        pts = [(ox + p[0], oy + p[1]) for p in s]
        if budget >= L:
            seg = pts; budget -= L; done_strokes += 1
        else:
            seg = [pts[0]]; left = budget
            for a, b in zip(pts, pts[1:]):
                dd = math.dist(a, b)
                if dd >= left:
                    t = left / dd if dd else 1
                    seg.append((a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t)); break
                seg.append(b); left -= dd
            budget = 0
        if len(seg) == 1: seg = [seg[0], (seg[0][0] + 0.5, seg[0][1])]
        d.line(seg, fill=color, width=width, joint='curve')
        r = width / 2
        d.ellipse([seg[0][0] - r, seg[0][1] - r, seg[0][0] + r, seg[0][1] + r], fill=color)
        d.ellipse([seg[-1][0] - r, seg[-1][1] - r, seg[-1][0] + r, seg[-1][1] + r], fill=color)
        tip = seg[-1]
    if fallback_font is not None:
        for x, ch, idx in fallback:
            if done_strokes >= idx or frac >= 1:
                d.text((ox + x, oy), ch, font=fallback_font, fill=color, anchor='ls')
    return None if frac >= 1 else tip
