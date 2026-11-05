"""Brainsnacc maths diagrams: accurate SVG drawings for lessons, questions and videos.

Every function returns an SVG string drawn TO SCALE (a 65° angle really is 65°). Save with
save(svg, 'y7/maths/angles-and-parallel-lines-1') → writes public/diagrams/y7/maths/angles-and-parallel-lines-1.svg
and returns the web path 'diagrams/y7/maths/angles-and-parallel-lines-1.svg' to use in lessons, questions and videos.
preview(svg, '/some/path.png') writes a PNG so you can LOOK at the drawing (Read the PNG) before using it.

Elements carry data-step="1|2|3": videos draw step 1 first (lines and shapes), then 2 (angle arcs and labels), then 3.
Labels: pass text like 'x', '65°', '7 cm'. Use None to leave something unlabelled.

Functions (all sizes in degrees / plain numbers):
  angle(deg, label, base=0, vertex=None, rays=(None, None), right=None)
  angles_at_point([(deg, label), ...], start=0)          # sectors around one point (≤ 360)
  angles_on_line([(deg, label), ...])                    # sectors on a straight line (sum 180)
  vertically_opposite(deg, labels={'a':..,'b':..,'c':..,'d':..})
  parallel_lines(deg, labels={('top','tr'): 'x', ('bottom','bl'): '115°'}, names=None)
  triangle(angles=(A,B,C) or points=[...], vertices=('A','B','C'), angle_labels={'A':'50°'}, side_labels={'AB':'7 cm'},
           ticks={'AB':1,'AC':1}, right_at=None)
  polygon(points, vertices=None, angle_labels=None, side_labels=None, ticks=None, arrows=None, right_at=None, fill=True)
  quadrilateral(kind, side_labels=None, angle_labels=None)   # square, rectangle, parallelogram, rhombus, trapezium, kite
  regular_polygon(n, angle_label=None, side_label=None, exterior=None)
  composite(points, side_labels, dashed=None)            # rectilinear / composite shapes with measurements
  circle(radius=None, diameter=None, centre='O', sector=None, sector_label=None, circumference=None)
  cuboid(l, w, h)  cube(s)  cylinder(r, h)  triangular_prism(b, h, l)  pyramid(b, h)  cone(r, h)  sphere(r)
  cartesian(xmin, xmax, ymin, ymax, points=[(x,y,'A')], lines=[(m,c,'y = 2x + 1')], segments=[((x1,y1),(x2,y2))],
            shape=[(x,y),...], curve=None)
  number_line(lo, hi, step=1, points=[(v,'label')], ray=None)   # ray=(value, 'left'|'right', open_bool)
  bar_chart(categories, values, title=None, ylabel=None)   dot_plot(values, lo=None, hi=None)
  stem_leaf is text — write it as a Markdown table instead.
  venn(a='A', b='B', only_a=None, both=None, only_b=None, neither=None, total=None)
  tree(first=[('H','1/2'),('T','1/2')], second=[[('H','1/2'),('T','1/2')], [('H','1/2'),('T','1/2')]])
  right_triangle(a=None, b=None, c=None, angle=None, angle_label=None)   # legs a (base), b (height), hypotenuse c
  clock(hours, minutes)
  similar_triangles(scale, labels_small, labels_big)
"""
import math, os, re

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
INK = '#1F2937'; ACC = '#5B3ED6'; TINT = '#EDE9FE'; MUTED = '#6B7280'; FILL = '#F5F3FF'; GOOD = '#15803D'; RED = '#B42318'
FONT = "font-family=\"Inter, 'Segoe UI', Arial, sans-serif\""
W0, H0 = 520, 340


# ---------------------------------------------------------------- output
def svg(body, w=W0, h=H0, title=''):
    t = f'<title>{esc(title)}</title>' if title else ''
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w:.0f} {h:.0f}" width="{w:.0f}" height="{h:.0f}" role="img">{t}'
            f'<rect width="100%" height="100%" fill="#FFFFFF"/>{body}</svg>')


def save(s, name):
    """name like 'y7/maths/angles-1' → public/diagrams/y7/maths/angles-1.svg; returns the web path."""
    name = name.removesuffix('.svg')
    path = os.path.join(ROOT, 'public', 'diagrams', name + '.svg')
    os.makedirs(os.path.dirname(path), exist_ok=True)
    open(path, 'w').write(s)
    return f'diagrams/{name}.svg'


def preview(s, png_path, scale=1.5):
    import cairosvg
    cairosvg.svg2png(bytestring=s.encode(), write_to=png_path, scale=scale)
    return png_path


def esc(s):
    return str(s).replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')


# ---------------------------------------------------------------- primitives
def line(p, q, step=1, color=INK, width=2.6, dash=None):
    d = f' stroke-dasharray="{dash}"' if dash else ''
    return f'<line data-step="{step}" x1="{p[0]:.1f}" y1="{p[1]:.1f}" x2="{q[0]:.1f}" y2="{q[1]:.1f}" stroke="{color}" stroke-width="{width}" stroke-linecap="round"{d}/>'


def text(x, y, s, step=2, size=20, color=INK, anchor='middle', weight=600, italic=False):
    if s is None or s == '':
        return ''
    st = ' font-style="italic"' if italic else ''
    return (f'<text data-step="{step}" x="{x:.1f}" y="{y:.1f}" {FONT} font-size="{size}" font-weight="{weight}" fill="{color}" '
            f'text-anchor="{anchor}" dominant-baseline="central"{st}>{esc(s)}</text>')


def dot(p, step=1, r=4.5, color=INK, fill=None):
    return f'<circle data-step="{step}" cx="{p[0]:.1f}" cy="{p[1]:.1f}" r="{r}" fill="{fill or color}" stroke="{color}" stroke-width="2"/>'


def poly(pts, step=1, fill=FILL, color=INK, width=2.6, closed=True, dash=None):
    p = ' '.join(f'{x:.1f},{y:.1f}' for x, y in pts)
    tag = 'polygon' if closed else 'polyline'
    d = f' stroke-dasharray="{dash}"' if dash else ''
    return f'<{tag} data-step="{step}" points="{p}" fill="{fill if closed else "none"}" stroke="{color}" stroke-width="{width}" stroke-linejoin="round"{d}/>'


def unit(a):
    r = math.radians(a)
    return math.cos(r), -math.sin(r)  # SVG y points down; angles measured anticlockwise


def at(p, a, d):
    u = unit(a)
    return p[0] + u[0] * d, p[1] + u[1] * d


def heading(p, q):
    return math.degrees(math.atan2(-(q[1] - p[1]), q[0] - p[0])) % 360


def arc(v, a1, a2, r=34, step=2, label=None, color=ACC, fill=TINT, lr=None, size=19, square=True):
    """Angle arc at vertex v from direction a1 anticlockwise to a2 (degrees)."""
    sweep = (a2 - a1) % 360
    if square and abs(sweep - 90) < 0.01:
        return right_mark(v, a1, step=step) + (text(*at(v, a1 + 45, (lr or r + 30)), label, step, size, ACC) if label else '')
    p1 = at(v, a1, r); p2 = at(v, a2, r)
    large = 1 if sweep > 180 else 0
    path = f'M{v[0]:.1f},{v[1]:.1f} L{p1[0]:.1f},{p1[1]:.1f} A{r},{r} 0 {large} 0 {p2[0]:.1f},{p2[1]:.1f} Z'
    out = f'<path data-step="{step}" d="{path}" fill="{fill}" stroke="{color}" stroke-width="2"/>'
    if label:
        mid = a1 + sweep / 2
        dist = lr or (r + 22 if sweep >= 40 else r + 30)
        out += text(*at(v, mid, dist), label, step, size, ACC)
    return out


def right_mark(v, a1, s=16, step=2):
    p1 = at(v, a1, s); p3 = at(v, a1 + 90, s); p2 = (p1[0] + p3[0] - v[0], p1[1] + p3[1] - v[1])
    return f'<polyline data-step="{step}" points="{p1[0]:.1f},{p1[1]:.1f} {p2[0]:.1f},{p2[1]:.1f} {p3[0]:.1f},{p3[1]:.1f}" fill="none" stroke="{ACC}" stroke-width="2.2"/>'


def tick_marks(p, q, n, step=2):
    mx, my = (p[0] + q[0]) / 2, (p[1] + q[1]) / 2
    h = heading(p, q); out = ''
    for k in range(n):
        off = (k - (n - 1) / 2) * 7
        c = at((mx, my), h, off)
        out += line(at(c, h + 90, 8), at(c, h - 90, 8), step, ACC, 2.2)
    return out


def arrow_marks(p, q, n=1, step=2):
    mx, my = (p[0] + q[0]) / 2, (p[1] + q[1]) / 2
    h = heading(p, q); out = ''
    for k in range(n):
        tip = at((mx, my), h, 26 + k * 9)
        out += line(tip, at(tip, h + 150, 11), step, ACC, 2.2) + line(tip, at(tip, h - 150, 11), step, ACC, 2.2)
    return out


def side_label(p, q, s, centroid, step=2, gap=12, size=19):
    if s is None:
        return ''
    mx, my = (p[0] + q[0]) / 2, (p[1] + q[1]) / 2
    h = heading(p, q)
    tw = len(str(s)) * size * 0.56
    ux, uy = unit(h + 90)
    extra = abs(ux) * tw / 2 + abs(uy) * size / 2
    n1 = at((mx, my), h + 90, gap + extra); n2 = at((mx, my), h - 90, gap + extra)
    far = max((n1, n2), key=lambda n: (n[0] - centroid[0]) ** 2 + (n[1] - centroid[1]) ** 2)
    return text(*far, s, step, size, INK, weight=600)


def fit(points, w=W0, h=H0, pad=58):
    xs = [p[0] for p in points]; ys = [p[1] for p in points]
    sx = (w - 2 * pad) / max(1e-9, max(xs) - min(xs)); sy = (h - 2 * pad) / max(1e-9, max(ys) - min(ys))
    s = min(sx, sy)
    ox = (w - (max(xs) - min(xs)) * s) / 2 - min(xs) * s
    oy = (h - (max(ys) - min(ys)) * s) / 2 + max(ys) * s
    return [(ox + x * s, oy - y * s) for x, y in points], s


# ---------------------------------------------------------------- angles
def angle(deg, label=None, base=0, vertex=None, rays=(None, None), right=None):
    """One angle of `deg` degrees. label e.g. 'x' or '65°'. vertex name e.g. 'B'. rays: names of points on each arm."""
    v = (W0 / 2 - 60 * math.cos(math.radians(base + deg / 2)), H0 / 2 + 70 * math.sin(math.radians(base + deg / 2)))
    L = 190
    a = at(v, base, L); b = at(v, base + deg, L)
    out = line(v, a) + line(v, b) + dot(v, 1, 3.5)
    out += arc(v, base, base + deg, 40 if deg > 30 else 60, label=label)
    if vertex:
        out += text(*at(v, base + deg / 2 + 180, 24), vertex, 1, 20, INK)
    for nm, (p, ang) in zip(rays, ((a, base), (b, base + deg))):
        if nm:
            out += dot(p, 1, 3.5) + text(*at(p, ang, 18), nm, 1, 20, INK)
    return svg(out, title=f'An angle of {deg} degrees')


def angles_at_point(sectors, start=0, names=None):
    v = (W0 / 2, H0 / 2); L = 140; out = ''; a = start
    dirs = []
    for deg, lab in sectors:
        dirs.append(a); a += deg
    for d in dirs:
        out += line(v, at(v, d, L))
    a = start
    for deg, lab in sectors:
        out += arc(v, a, a + deg, 38, label=lab, fill=TINT if len(out) % 2 else '#E0F2FE')
        a += deg
    return svg(out + dot(v, 1, 3.5), title='Angles at a point')


def angles_on_line(sectors):
    v = (W0 / 2, H0 * 0.68); L = 220; out = line(at(v, 0, L), at(v, 180, L))
    a = 0
    for deg, lab in sectors[:-1]:
        a += deg
        out += line(v, at(v, a, 170))
    a = 0
    for deg, lab in sectors:
        out += arc(v, a, a + deg, 42, label=lab)
        a += deg
    return svg(out + dot(v, 1, 3.5), title='Angles on a straight line')


def vertically_opposite(deg, labels=None):
    """Two crossing lines; 'a' is the angle of size deg (right side, between the lines), 'b' above, 'c' opposite a, 'd' below."""
    labels = labels or {}
    v = (W0 / 2, H0 / 2); L = 200
    h1 = -deg / 2; h2 = deg / 2
    out = line(at(v, h1, L), at(v, h1 + 180, L)) + line(at(v, h2, L), at(v, h2 + 180, L))
    out += arc(v, h1, h2, 40, label=labels.get('a'))
    out += arc(v, h2, h1 + 180, 34, label=labels.get('b'), fill='#E0F2FE')
    out += arc(v, h1 + 180, h2 + 180, 40, label=labels.get('c'))
    out += arc(v, h2 + 180, h1 + 360, 34, label=labels.get('d'), fill='#E0F2FE')
    return svg(out + dot(v, 1, 3.5), title='Vertically opposite angles')


def parallel_lines(deg, labels=None, names=None):
    """Two horizontal parallel lines cut by a transversal at `deg` degrees (deg = the top-right angle at each crossing).
    labels: {('top'|'bottom', 'tr'|'tl'|'br'|'bl'): 'x'}."""
    labels = labels or {}
    y1, y2 = 110, 240
    dx = (y2 - y1) / math.tan(math.radians(deg))
    cx = W0 / 2
    p1 = (cx + dx / 2, y1); p2 = (cx - dx / 2, y2)
    out = line((40, y1), (W0 - 40, y1)) + line((40, y2), (W0 - 40, y2))
    out += arrow_marks((40, y1), (130, y1)) + arrow_marks((40, y2), (130, y2))
    t = heading(p2, p1)
    a = at(p1, t, 80); b = at(p2, t + 180, 80)
    out += line(b, a)
    sect = {'tr': (0, t), 'tl': (t, 180), 'bl': (180, t + 180), 'br': (t + 180, 360)}
    for (which, pos), lab in labels.items():
        v = p1 if which == 'top' else p2
        a1, a2 = sect[pos]
        out += arc(v, a1, a2, 30, label=lab, lr=56)
    if names:
        for (p, nm) in zip(((W0 - 30, y1), (W0 - 30, y2)), names):
            out += text(p[0], p[1] - 16, nm, 1, 18, MUTED)
    return svg(out + dot(p1, 1, 3) + dot(p2, 1, 3), title='Two parallel lines cut by a transversal')


# ---------------------------------------------------------------- polygons
def polygon(points, vertices=None, angle_labels=None, side_labels=None, ticks=None, arrows=None, right_at=None, fill=True,
            w=W0, h=H0, title='Shape'):
    """points in any units (y up). Labels keyed by vertex name, sides by 'AB'."""
    pts, s = fit(points, w, h)
    n = len(pts)
    names = vertices if vertices else [chr(65 + i) for i in range(n)]
    idx = {nm: i for i, nm in enumerate(names)}
    cen = (sum(p[0] for p in pts) / n, sum(p[1] for p in pts) / n)
    out = poly(pts, 1, FILL if fill else 'none')
    for nm, lab in (angle_labels or {}).items():
        i = idx[nm]; v = pts[i]; prev = pts[i - 1]; nxt = pts[(i + 1) % n]
        h1, h2 = heading(v, nxt), heading(v, prev)
        # interior side: choose the order whose sweep is < 180 for convex corners
        a1, a2 = (h1, h2) if (h2 - h1) % 360 < 180 else (h2, h1)
        if right_at and nm in right_at:
            out += right_mark(v, a1)
            continue
        sweep = (a2 - a1) % 360
        out += arc(v, a1, a2, 30 if sweep > 50 else 44, label=lab, square=False)
    for nm in (right_at or []):
        if nm not in (angle_labels or {}):
            i = idx[nm]; v = pts[i]; h1, h2 = heading(v, pts[(i + 1) % n]), heading(v, pts[i - 1])
            a1 = h1 if (h2 - h1) % 360 < 180 else h2
            out += right_mark(v, a1)
    for key, lab in (side_labels or {}).items():
        i, j = idx[key[0]], idx[key[1]]
        out += side_label(pts[i], pts[j], lab, cen)
    for key, k in (ticks or {}).items():
        i, j = idx[key[0]], idx[key[1]]
        out += tick_marks(pts[i], pts[j], k)
    for key, k in (arrows or {}).items():
        i, j = idx[key[0]], idx[key[1]]
        out += arrow_marks(pts[i], pts[j], k)
    if vertices is not False:
        for i, p in enumerate(pts):
            out += text(*at(p, heading(cen, p), 22), names[i], 1, 20, INK)
    return svg(out, w, h, title)


def triangle(angles=None, points=None, vertices=('A', 'B', 'C'), angle_labels=None, side_labels=None, ticks=None, right_at=None):
    if points is None:
        A, B, C = angles
        assert abs(A + B + C - 180) < 1e-6, 'angles must add to 180'
        c = 1.0; b = c * math.sin(math.radians(B)) / math.sin(math.radians(C))
        points = [(0, 0), (c, 0), (b * math.cos(math.radians(A)), b * math.sin(math.radians(A)))]
    return polygon(points, list(vertices), angle_labels, side_labels, ticks, None, right_at, title='Triangle')


def right_triangle(a=None, b=None, c=None, angle=None, angle_label=None, base=4, height=3):
    """Right angle at bottom-left. a = base label, b = height label, c = hypotenuse label; angle_label marks the angle at bottom-right."""
    al = {'B': angle_label} if angle_label else {}
    return polygon([(0, 0), (base, 0), (0, height)], False, al, {'AB': a, 'CA': b, 'BC': c}, None, None, ['A'], title='Right-angled triangle')


def quadrilateral(kind, side_labels=None, angle_labels=None, vertices=('A', 'B', 'C', 'D')):
    P = {
        'square': [(0, 0), (1, 0), (1, 1), (0, 1)],
        'rectangle': [(0, 0), (1.7, 0), (1.7, 1), (0, 1)],
        'parallelogram': [(0, 0), (1.6, 0), (2.1, 1), (0.5, 1)],
        'rhombus': [(0, 0), (1, 0), (1.5, 0.866), (0.5, 0.866)],
        'trapezium': [(0, 0), (2, 0), (1.5, 1), (0.4, 1)],
        'kite': [(0, 0.6), (0.8, 0), (2.2, 0.6), (0.8, 1.2)],
    }[kind]
    ticks = {'square': {'AB': 1, 'BC': 1, 'CD': 1, 'DA': 1}, 'rhombus': {'AB': 1, 'BC': 1, 'CD': 1, 'DA': 1},
             'rectangle': {'AB': 2, 'CD': 2, 'BC': 1, 'DA': 1}, 'parallelogram': {'AB': 2, 'CD': 2, 'BC': 1, 'DA': 1},
             'kite': {'AB': 1, 'DA': 1, 'BC': 2, 'CD': 2}, 'trapezium': {}}[kind]
    arrows = {'square': {}, 'rectangle': {}, 'parallelogram': {'AB': 1, 'DC': 1, 'AD': 2, 'BC': 2},
              'rhombus': {'AB': 1, 'DC': 1, 'AD': 2, 'BC': 2}, 'trapezium': {'AB': 1, 'DC': 1}, 'kite': {}}[kind]
    if kind in ('parallelogram', 'rhombus'):
        arrows = {'AB': 1, 'DC': 1}
    right = ['A', 'B', 'C', 'D'] if kind in ('square', 'rectangle') else None
    return polygon(P, list(vertices), angle_labels, side_labels, ticks, arrows, right, title=kind.capitalize())


def regular_polygon(n, angle_label=None, side_label=None, exterior=None):
    pts = [(math.cos(2 * math.pi * k / n - math.pi / 2 - math.pi / n), math.sin(2 * math.pi * k / n - math.pi / 2 - math.pi / n)) for k in range(n)]
    names = [chr(65 + i) for i in range(n)]
    s = polygon(pts, False, {'A': angle_label} if angle_label else None, {'AB': side_label} if side_label else None,
                {f'{names[i]}{names[(i + 1) % n]}': 1 for i in range(n)}, title=f'Regular {n}-sided polygon')
    return s


def composite(points, side_labels, dashed=None):
    """Rectilinear or other composite shape. side_labels: list, one per edge in order (None = no label).
    dashed: list of ((x1,y1),(x2,y2)) helper lines in the same units, to show how it splits."""
    pts, sc = fit(points)
    n = len(pts); cen = (sum(p[0] for p in pts) / n, sum(p[1] for p in pts) / n)
    out = poly(pts, 1)
    xs = [p[0] for p in points]; ys = [p[1] for p in points]
    # map raw→screen for dashed lines using the same transform
    def tr(p):
        (q,), _ = fit([p] + points), None
        return q
    for i, lab in enumerate(side_labels):
        out += side_label(pts[i], pts[(i + 1) % n], lab, cen)
    if dashed:
        allp, _ = fit(points + [q for seg in dashed for q in seg])
        base = allp[len(points):]
        for k in range(0, len(base), 2):
            out += line(base[k], base[k + 1], 2, ACC, 2.2, '7 6')
    return svg(out, title='Composite shape')


def circle(radius=None, diameter=None, centre='O', sector=None, sector_label=None, circumference=None):
    c = (W0 / 2, H0 / 2); r = 120
    out = f'<circle data-step="1" cx="{c[0]}" cy="{c[1]}" r="{r}" fill="{FILL}" stroke="{INK}" stroke-width="2.6"/>'
    out += dot(c, 1, 3.5) + (text(c[0] - 14, c[1] + 16, centre, 1, 18, INK) if centre else '')
    if diameter:
        out += line(at(c, 200, r), at(c, 20, r), 2, ACC, 2.6) + text(*at(c, 20, r / 2 + 10), '', 2)
        out += text(*at(at(c, 20, r * 0.15), 110, 22), diameter, 2, 19, ACC)
    if radius:
        out += line(c, at(c, 35, r), 2, ACC, 2.6) + text(*at(at(c, 35, r * 0.5), 125, 26), radius, 2, 19, ACC)
    if sector:
        out += arc(c, 200, 200 + sector, r, 2, sector_label, fill='#DDD6FE', lr=r * 0.55, square=False)
    if circumference:
        out += text(c[0] + r + 12, c[1] - r + 10, circumference, 2, 18, ACC, 'start')
    return svg(out, title='Circle')


# ---------------------------------------------------------------- 3D solids (oblique sketches)
def _solid(front, depth, labels, hidden=()):
    dx, dy = depth
    back = [(x + dx, y - dy) for x, y in front]
    out = poly(back, 1, 'none', MUTED, 2, dash='6 5')
    out += poly(front, 1, FILL)
    for a, b in zip(front, back):
        out += line(a, b, 1, INK, 2.4)
    out += poly([front[1], front[2], back[2], back[1]], 1, '#EDE9FE', INK, 2.4) if len(front) == 4 else ''
    out += poly([front[3], front[2], back[2], back[3]], 1, '#F5F3FF', INK, 2.4) if len(front) == 4 else ''
    return out + labels


def cuboid(l=None, w=None, h=None, cube_label=None):
    x0, y0 = 110, 285
    L, H = (170, 170) if cube_label else (240, 140)
    dx, dy = 90, 70
    A, B, C, Dd = (x0, y0), (x0 + L, y0), (x0 + L, y0 - H), (x0, y0 - H)
    off = lambda p: (p[0] + dx, p[1] - dy)
    hidden = off(A)
    out = poly([A, B, C, Dd], 1, FILL) + poly([Dd, C, off(C), off(Dd)], 1, '#EDE9FE') + poly([B, off(B), off(C), C], 1, '#E4DEFC')
    out += line(A, hidden, 1, MUTED, 1.8, '6 5') + line(hidden, off(B), 1, MUTED, 1.8, '6 5') + line(hidden, off(Dd), 1, MUTED, 1.8, '6 5')
    out += text(x0 + L / 2, y0 + 24, l or cube_label, 2) + text(x0 - 14, y0 - H / 2, h, 2, anchor='end')
    out += text(x0 + L + dx / 2 + 16, y0 - dy / 2 + 8, w, 2, anchor='start')
    return svg(out, title='Rectangular prism')


def cube(s=None):
    return cuboid(cube_label=s)


def triangular_prism(b=None, h=None, l=None):
    f = [(90, 285), (250, 285), (170, 145)]
    dx, dy = 190, 55
    bk = [(x + dx, y - dy) for x, y in f]
    out = poly([f[1], bk[1], bk[2], f[2]], 1, '#EDE9FE') + poly(f, 1, FILL)
    out += line(f[0], bk[0], 1, MUTED, 1.8, '6 5') + line(bk[0], bk[1], 1, MUTED, 1.8, '6 5')
    out += line((170, 285), (170, 145), 2, ACC, 2, '6 5') + right_mark((170, 285), 0, 11)
    out += text(170, 307, b) + text(180, 225, h, 2, 19, ACC, 'start') + text(250 + dx / 2 + 16, 285 - dy / 2 + 12, l, 2, anchor='start')
    return svg(out, title='Triangular prism')


def cylinder(r=None, h=None):
    cx, top, bot, rx, ry = 260, 90, 260, 110, 30
    out = f'<path data-step="1" d="M{cx - rx},{top} L{cx - rx},{bot} A{rx},{ry} 0 0 0 {cx + rx},{bot} L{cx + rx},{top}" fill="{FILL}" stroke="{INK}" stroke-width="2.6"/>'
    out += f'<path data-step="1" d="M{cx - rx},{bot} A{rx},{ry} 0 0 1 {cx + rx},{bot}" fill="none" stroke="{MUTED}" stroke-width="2" stroke-dasharray="6 5"/>'
    out += f'<ellipse data-step="1" cx="{cx}" cy="{top}" rx="{rx}" ry="{ry}" fill="#EDE9FE" stroke="{INK}" stroke-width="2.6"/>'
    out += dot((cx, top), 1, 3) + line((cx, top), (cx + rx, top), 2, ACC, 2.4)
    out += text(cx + rx / 2, top - 16, r, 2, 19, ACC) + text(cx + rx + 18, (top + bot) / 2, h, 2, anchor='start')
    out += line((cx + rx + 8, top), (cx + rx + 8, bot), 2, MUTED, 1.4, '3 4')
    return svg(out, title='Cylinder')


def pyramid(b=None, h=None):
    base = [(120, 270), (330, 270), (400, 220), (190, 220)]
    apex = (260, 70)
    out = poly(base, 1, FILL)
    for p in base:
        out += line(p, apex, 1, INK if p != (190, 220) else MUTED, 2.4, None if p != (190, 220) else '6 5')
    cen = (260, 245)
    out += line(apex, cen, 2, ACC, 2, '6 5') + right_mark(cen, 0, 10)
    out += text(225, 292, b) + text(272, 160, h, 2, 19, ACC, 'start')
    return svg(out, title='Square-based pyramid')


def cone(r=None, h=None, slant=None):
    cx, bot, apex, rx, ry = 260, 260, 60, 120, 32
    out = f'<path data-step="1" d="M{cx - rx},{bot} L{cx},{apex} L{cx + rx},{bot} A{rx},{ry} 0 0 1 {cx - rx},{bot}" fill="{FILL}" stroke="{INK}" stroke-width="2.6"/>'
    out += f'<path data-step="1" d="M{cx - rx},{bot} A{rx},{ry} 0 0 1 {cx + rx},{bot}" fill="none" stroke="{MUTED}" stroke-width="2" stroke-dasharray="6 5"/>'
    out += line((cx, apex), (cx, bot), 2, ACC, 2, '6 5') + line((cx, bot), (cx + rx, bot), 2, ACC, 2.4) + right_mark((cx, bot), 0, 11)
    out += text(cx + rx / 2, bot + 20, r, 2, 19, ACC) + text(cx - 14, (apex + bot) / 2, h, 2, 19, ACC, 'end')
    if slant:
        out += text(cx + rx / 2 + 26, (apex + bot) / 2 - 6, slant, 2, 19, INK, 'start')
    return svg(out, title='Cone')


def sphere(r=None):
    c = (W0 / 2, H0 / 2); R = 130
    out = f'<circle data-step="1" cx="{c[0]}" cy="{c[1]}" r="{R}" fill="{FILL}" stroke="{INK}" stroke-width="2.6"/>'
    out += f'<ellipse data-step="1" cx="{c[0]}" cy="{c[1]}" rx="{R}" ry="34" fill="none" stroke="{MUTED}" stroke-width="2" stroke-dasharray="6 5"/>'
    out += dot(c, 1, 3.5) + line(c, (c[0] + R, c[1]), 2, ACC, 2.4) + text(c[0] + R / 2, c[1] - 18, r, 2, 19, ACC)
    return svg(out, title='Sphere')


# ---------------------------------------------------------------- graphs
def cartesian(xmin=-5, xmax=5, ymin=-5, ymax=5, points=None, lines=None, segments=None, shape=None, curve=None, w=W0, h=H0 + 60,
              grid=True, labels_every=1):
    pad = 36
    sx = (w - 2 * pad) / (xmax - xmin); sy = (h - 2 * pad) / (ymax - ymin)
    s = min(sx, sy)
    gw, gh = (xmax - xmin) * s, (ymax - ymin) * s
    ox, oy = (w - gw) / 2, (h - gh) / 2
    X = lambda x: ox + (x - xmin) * s
    Y = lambda y: oy + (ymax - y) * s
    out = ''
    if grid:
        for x in range(int(math.ceil(xmin)), int(math.floor(xmax)) + 1):
            out += line((X(x), Y(ymin)), (X(x), Y(ymax)), 1, '#E5E7EB', 1)
        for y in range(int(math.ceil(ymin)), int(math.floor(ymax)) + 1):
            out += line((X(xmin), Y(y)), (X(xmax), Y(y)), 1, '#E5E7EB', 1)
    if xmin <= 0 <= xmax:
        out += line((X(0), Y(ymin)), (X(0), Y(ymax)), 1, INK, 2)
    if ymin <= 0 <= ymax:
        out += line((X(xmin), Y(0)), (X(xmax), Y(0)), 1, INK, 2)
    ax0 = Y(0) if ymin <= 0 <= ymax else Y(ymin); ay0 = X(0) if xmin <= 0 <= xmax else X(xmin)
    for x in range(int(math.ceil(xmin)), int(math.floor(xmax)) + 1):
        if x != 0 and x % labels_every == 0:
            out += text(X(x), ax0 + 14, str(x).replace('-', '−'), 1, 13, MUTED, weight=500)
    for y in range(int(math.ceil(ymin)), int(math.floor(ymax)) + 1):
        if y != 0 and y % labels_every == 0:
            out += text(ay0 - 12, Y(y), str(y).replace('-', '−'), 1, 13, MUTED, 'end', weight=500)
    out += text(X(xmax) - 6, ax0 - 14, 'x', 1, 16, INK, italic=True) + text(ay0 + 14, Y(ymax) + 8, 'y', 1, 16, INK, italic=True)
    if shape:
        out += poly([(X(x), Y(y)) for x, y in shape], 2, '#EDE9FE', ACC, 2.4)
    for seg in (segments or []):
        (x1, y1), (x2, y2) = seg[:2]
        out += line((X(x1), Y(y1)), (X(x2), Y(y2)), 2, ACC, 2.6)
    for ln in (lines or []):
        m, c = ln[0], ln[1]; lab = ln[2] if len(ln) > 2 else None
        pts = []
        for x in (xmin, xmax):
            pts.append((x, m * x + c))
        # clip to the y range
        (x1, y1), (x2, y2) = pts
        def clip(x, y, xo, yo):
            if y > ymax: return (xo + (ymax - yo) / m if m else x, ymax)
            if y < ymin: return (xo + (ymin - yo) / m if m else x, ymin)
            return (x, y)
        p1 = clip(x1, y1, x1 - y1 / m * 0 if False else 0, c) if m else (x1, y1)
        p1 = (x1, y1) if ymin <= y1 <= ymax else ((ymax - c) / m if y1 > ymax else (ymin - c) / m, ymax if y1 > ymax else ymin)
        p2 = (x2, y2) if ymin <= y2 <= ymax else ((ymax - c) / m if y2 > ymax else (ymin - c) / m, ymax if y2 > ymax else ymin)
        out += line((X(p1[0]), Y(p1[1])), (X(p2[0]), Y(p2[1])), 2, ACC, 2.8)
        if lab:
            lx = p1[0] + 0.8 * (p2[0] - p1[0]); ly = m * lx + c
            out += f'<rect data-step="3" x="{X(lx) + 10:.1f}" y="{Y(ly) - 12:.1f}" width="{len(lab) * 8.6 + 8:.1f}" height="24" rx="4" fill="#FFFFFF" fill-opacity="0.9"/>'
            out += text(X(lx) + 14, Y(ly), lab, 3, 16, ACC, 'start')
    if curve:
        f, lab = curve if isinstance(curve, tuple) else (curve, None)
        pts = []
        steps = 200
        for k in range(steps + 1):
            x = xmin + (xmax - xmin) * k / steps
            try:
                y = f(x)
            except Exception:
                continue
            if ymin - 1 <= y <= ymax + 1:
                pts.append((X(x), Y(max(ymin, min(ymax, y)))))
        if pts:
            out += poly(pts, 2, None, ACC, 2.8, closed=False)
        if lab:
            out += text(pts[-1][0] - 8, pts[-1][1] + 16, lab, 3, 16, ACC, 'end')
    for p in (points or []):
        x, y = p[0], p[1]; lab = p[2] if len(p) > 2 else None
        out += dot((X(x), Y(y)), 2, 5, ACC) + (text(X(x) + 10, Y(y) - 14, lab, 2, 16, INK, 'start') if lab else '')
    return svg(out, w, h, 'Cartesian plane')


def number_line(lo, hi, step=1, points=None, ray=None, w=W0, h=150):
    pad = 40
    X = lambda v: pad + (v - lo) / (hi - lo) * (w - 2 * pad)
    y = 70
    out = line((pad - 18, y), (w - pad + 18, y), 1, INK, 2.4)
    out += line((w - pad + 18, y), (w - pad + 8, y - 7), 1) + line((w - pad + 18, y), (w - pad + 8, y + 7), 1)
    out += line((pad - 18, y), (pad - 8, y - 7), 1) + line((pad - 18, y), (pad - 8, y + 7), 1)
    k = 0
    v = lo
    while v <= hi + 1e-9:
        out += line((X(v), y - 8), (X(v), y + 8), 1, INK, 2) + text(X(v), y + 26, fmt(v), 1, 15, MUTED, weight=500)
        v = round(lo + step * (k := k + 1), 10)
    if ray:
        val, direction, is_open = ray
        end = (pad - 10) if direction == 'left' else (w - pad + 10)
        out += line((X(val), y), (end, y), 2, ACC, 6)
        out += f'<circle data-step="2" cx="{X(val):.1f}" cy="{y}" r="8" fill="{"#FFFFFF" if is_open else ACC}" stroke="{ACC}" stroke-width="3"/>'
    for p in (points or []):
        val = p[0]; lab = p[1] if len(p) > 1 else None
        out += dot((X(val), y), 2, 6, ACC) + (text(X(val), y - 24, lab, 2, 17, ACC) if lab else '')
    return svg(out, w, h, 'Number line')


def fmt(v):
    s = f'{v:g}'
    return s.replace('-', '−')


def bar_chart(categories, values, title=None, ylabel=None, w=W0, h=H0):
    pad_l, pad_b, pad_t = 64, 56, 40 if title else 20
    top = max(values)
    stepv = nice_step(top)
    vmax = stepv * math.ceil(top / stepv)
    gx0, gy0, gx1, gy1 = pad_l, h - pad_b, w - 20, pad_t
    Y = lambda v: gy0 - (v / vmax) * (gy0 - gy1)
    out = ''
    v = 0
    while v <= vmax + 1e-9:
        out += line((gx0, Y(v)), (gx1, Y(v)), 1, '#E5E7EB', 1) + text(gx0 - 10, Y(v), fmt(v), 1, 13, MUTED, 'end', 500)
        v += stepv
    n = len(values); bw = (gx1 - gx0) / n
    for i, (c, val) in enumerate(zip(categories, values)):
        x = gx0 + i * bw + bw * 0.18
        out += f'<rect data-step="2" x="{x:.1f}" y="{Y(val):.1f}" width="{bw * 0.64:.1f}" height="{gy0 - Y(val):.1f}" fill="{ACC}" rx="3"/>'
        out += text(gx0 + i * bw + bw / 2, gy0 + 18, c, 1, 14, INK, weight=500)
    out += line((gx0, gy0), (gx1, gy0), 1, INK, 2) + line((gx0, gy0), (gx0, gy1), 1, INK, 2)
    if title:
        out += text(w / 2, 18, title, 1, 17, INK)
    if ylabel:
        out += f'<text data-step="1" x="16" y="{(gy0 + gy1) / 2:.0f}" {FONT} font-size="14" fill="{MUTED}" text-anchor="middle" transform="rotate(-90 16 {(gy0 + gy1) / 2:.0f})">{esc(ylabel)}</text>'
    return svg(out, w, h, title or 'Column graph')


def nice_step(top):
    for s in (1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000):
        if top / s <= 8:
            return s
    return 10 ** int(math.log10(top))


def dot_plot(values, lo=None, hi=None, w=W0, h=230):
    lo = min(values) if lo is None else lo; hi = max(values) if hi is None else hi
    pad = 40; y = h - 50
    X = lambda v: pad + (v - lo) / max(1, (hi - lo)) * (w - 2 * pad)
    out = line((pad - 10, y), (w - pad + 10, y), 1, INK, 2)
    for v in range(int(lo), int(hi) + 1):
        out += line((X(v), y - 6), (X(v), y + 6), 1) + text(X(v), y + 22, fmt(v), 1, 14, MUTED, weight=500)
    count = {}
    for v in values:
        count[v] = count.get(v, 0) + 1
        out += dot((X(v), y - 16 - (count[v] - 1) * 18), 2, 7, ACC)
    return svg(out, w, h, 'Dot plot')


def venn(a='A', b='B', only_a=None, both=None, only_b=None, neither=None, total=None):
    out = f'<rect data-step="1" x="20" y="20" width="{W0 - 40}" height="{H0 - 40}" rx="10" fill="none" stroke="{INK}" stroke-width="2.4"/>'
    out += text(42, 42, 'ξ' + (f' = {total}' if total else ''), 1, 18, INK, 'start')
    out += f'<circle data-step="1" cx="205" cy="175" r="105" fill="#EDE9FE" fill-opacity="0.8" stroke="{ACC}" stroke-width="2.6"/>'
    out += f'<circle data-step="1" cx="315" cy="175" r="105" fill="#E0F2FE" fill-opacity="0.7" stroke="#0369A1" stroke-width="2.6"/>'
    out += text(150, 64, a, 1, 19, ACC) + text(370, 64, b, 1, 19, '#0369A1')
    out += text(160, 178, only_a, 2, 24) + text(260, 178, both, 2, 24) + text(360, 178, only_b, 2, 24)
    out += text(W0 - 52, H0 - 44, neither, 2, 22)
    return svg(out, title='Venn diagram')


def tree(first, second=None):
    out = ''
    root = (50, H0 / 2)
    n1 = len(first)
    y1s = [H0 * (i + 0.5) / n1 for i in range(n1)]
    for i, (lab, p) in enumerate(first):
        node = (210, y1s[i])
        out += line(root, (node[0] - 18, node[1]), 1, INK, 2.2)
        out += text((root[0] + node[0]) / 2 - 6, (root[1] + node[1]) / 2 - 14, p, 2, 16, ACC)
        out += text(node[0], node[1], lab, 1, 20)
        if second:
            br = second[i]; n2 = len(br)
            span = H0 / n1
            for j, (lab2, p2) in enumerate(br):
                y2 = y1s[i] - span / 2 + span * (j + 0.5) / n2
                node2 = (390, y2)
                out += line((node[0] + 18, node[1]), (node2[0] - 18, node2[1]), 1, INK, 2.2)
                out += text((node[0] + node2[0]) / 2, (node[1] + node2[1]) / 2 - 13, p2, 2, 15, ACC)
                out += text(node2[0], y2, lab2, 1, 19) + text(470, y2, lab + lab2, 3, 16, MUTED)
    return svg(out, title='Tree diagram')


def clock(hours, minutes):
    c = (W0 / 2, H0 / 2); R = 130
    out = f'<circle data-step="1" cx="{c[0]}" cy="{c[1]}" r="{R}" fill="#FFFFFF" stroke="{INK}" stroke-width="3"/>'
    for k in range(12):
        a = 90 - k * 30
        out += line(at(c, a, R - 12), at(c, a, R - 2), 1, INK, 3) + text(*at(c, a, R - 30), str(k or 12), 1, 18, INK, weight=600)
    ha = 90 - ((hours % 12) + minutes / 60) * 30; ma = 90 - minutes * 6
    out += line(c, at(c, ha, R * 0.5), 2, INK, 6) + line(c, at(c, ma, R * 0.78), 2, ACC, 4) + dot(c, 2, 5)
    return svg(out, title=f'A clock showing {hours}:{minutes:02d}')


def similar_triangles(scale=2, small=None, big=None):
    small = small or {}; big = big or {}
    a = [(0, 0), (3, 0), (1, 2)]
    b = [(4.5 + x * scale, y * scale) for x, y in a]
    pts, _ = fit(a + b, W0, H0)
    s1, s2 = pts[:3], pts[3:]
    out = poly(s1, 1) + poly(s2, 1)
    for tri, labs in ((s1, small), (s2, big)):
        cen = (sum(p[0] for p in tri) / 3, sum(p[1] for p in tri) / 3)
        for key, idx in (('base', (0, 1)), ('right', (1, 2)), ('left', (2, 0))):
            out += side_label(tri[idx[0]], tri[idx[1]], labs.get(key), cen)
    return svg(out, title='Similar triangles')


# ---------------------------------------------------------------- checks used by the content checker
def check_svg(path):
    import xml.etree.ElementTree as ET
    probs = []
    try:
        root = ET.parse(path).getroot()
    except Exception as e:
        return [f'{path}: not valid SVG ({e})']
    if 'viewBox' not in root.attrib:
        probs.append(f'{path}: needs a viewBox')
    raw = open(path).read()
    if re.search(r'<script|onload=|javascript:|<foreignObject|xlink:href="http', raw, re.I):
        probs.append(f'{path}: scripts, links and foreignObject are not allowed')
    if len(raw) > 200_000:
        probs.append(f'{path}: too big')
    return probs
