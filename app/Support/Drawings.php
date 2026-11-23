<?php

namespace App\Support;

/**
 * Drawings for teaching scripts that change from one line to the next, so students see what happens at each step:
 * cubes taken off both sides of a balance (they turn red, get crossed out and fade), the scale tipping when only one
 * side changes, cubes shared out between the bags, or a jump along a number line.
 *
 * Specs (in a script line's "draw"):
 *   {"type": "balance", "left": {"bags": 4, "cubes": 7}, "right": {"cubes": 35},
 *    "remove": 7, "removeFrom": "both|left|right", "add": 11, "addTo": "both|left|right",
 *    "share": true, "tip": "left|right", "labels": ["4x + 7", "35"]}
 *   {"type": "numberline", "from": -14, "to": 4, "start": 2, "jump": -14}
 *   {"type": "shapes", "width": 560, "height": 320, "items": [
 *      {"kind": "rect", "x": 20, "y": 40, "w": 100, "h": 40, "fill": "blue", "label": "1/4"},
 *      {"kind": "circle", "x": 300, "y": 160, "r": 40, "fill": "orange", "change": "remove"},
 *      {"kind": "line"|"arrow", "x": 10, "y": 10, "x2": 200, "y2": 10, "color": "red", "change": "add"},
 *      {"kind": "polygon", "points": [[10,10],[90,10],[50,80]], "fill": "green"},
 *      {"kind": "text", "x": 50, "y": 50, "text": "x = 7", "size": 22, "color": "purple", "change": "highlight"}]}
 *   "change": "add" (appears in this step, green), "remove" (crossed out, then fades), "highlight" (yellow glow).
 * Negative cubes ("cubes": -11) are drawn as red "minus" cubes.
 */
class Drawings
{
    private static ?string $phase = null;

    /**
     * $phase is for still pictures (videos), where nothing animates:
     *   'before' — the picture before this step's change, 'during' — what's being taken off is crossed out,
     *   'after' — the result (taken-off cubes faded, scale tipped, new cubes in, cubes shared out).
     * Null: one animated picture for the web page.
     */
    public static function render(?array $spec, ?string $phase = null): ?string
    {
        if (! is_array($spec)) {
            return null;
        }
        self::$phase = $phase;
        if ($phase === 'before') {
            unset($spec['remove'], $spec['add'], $spec['tip'], $spec['share'], $spec['jump'], $spec['labels']);
        } elseif ($phase === 'during') {
            unset($spec['add'], $spec['tip'], $spec['share']);
            if (isset($spec['jump'])) {
                unset($spec['jump']);
            }
        }

        return match ($spec['type'] ?? null) {
            'balance' => self::balance($spec),
            'numberline' => self::numberLine($spec),
            'shapes' => self::shapes($spec),
            default => null,
        };
    }

    private static function int(mixed $v, int $min, int $max, int $default = 0): int
    {
        return is_numeric($v) ? max($min, min($max, (int) $v)) : $default;
    }

    private static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function balance(array $s): string
    {
        $side = function (string $name) use ($s) {
            $x = (array) ($s[$name] ?? []);

            return ['bags' => self::int($x['bags'] ?? 0, 0, 8), 'cubes' => self::int($x['cubes'] ?? 0, -40, 40)];
        };
        $L = $side('left');
        $R = $side('right');
        $remove = self::int($s['remove'] ?? 0, 0, 40);
        $add = self::int($s['add'] ?? 0, 0, 40);
        $from = in_array($s['removeFrom'] ?? 'both', ['both', 'left', 'right'], true) ? ($s['removeFrom'] ?? 'both') : 'both';
        $to = in_array($s['addTo'] ?? 'both', ['both', 'left', 'right'], true) ? ($s['addTo'] ?? 'both') : 'both';
        $tip = $s['tip'] ?? null;
        $angle = $tip === 'left' ? 7 : ($tip === 'right' ? -7 : 0);
        $share = ! empty($s['share']) && $L['bags'] > 0 && $L['cubes'] === 0 && $R['cubes'] !== 0;

        $W = 560;
        $px = $W / 2;
        $beamY = 60;
        $arm = 172;
        $rad = deg2rad($angle);
        $dyL = $arm * sin($rad);   // tip left: left end goes down
        $dyR = -$dyL;

        $svg = '<svg class="drawing balance" viewBox="0 0 '.$W.' 330" role="img" xmlns="http://www.w3.org/2000/svg"><title>'.self::e(self::balanceText($L, $R)).'</title>';
        // stand
        $svg .= '<polygon points="'.($px - 34).',310 '.($px + 34).',310 '.$px.','.$beamY.'" fill="#E6E2D6" stroke="#6B6658" stroke-width="2"/>';
        $svg .= '<line x1="'.($px - 90).'" y1="312" x2="'.($px + 90).'" y2="312" stroke="#6B6658" stroke-width="4" stroke-linecap="round"/>';
        // beam (with a little swing when it tips)
        $svg .= self::$phase === 'after' && $angle
            ? '<g transform="rotate('.(-$angle).' '.$px.' '.$beamY.')">'
            : '<g class="'.($angle ? 'tipping' : '').'" style="--tip:'.(-$angle).'deg;transform-origin:'.$px.'px '.$beamY.'px">';
        $svg .= '<line x1="'.($px - $arm).'" y1="'.$beamY.'" x2="'.($px + $arm).'" y2="'.$beamY.'" stroke="#3A3A3A" stroke-width="6" stroke-linecap="round"/>';
        $svg .= '</g><circle cx="'.$px.'" cy="'.$beamY.'" r="8" fill="#3A3A3A"/>';

        foreach (['left' => [$px - $arm, $dyL, $L], 'right' => [$px + $arm, $dyR, $R]] as $name => [$cx, $dy, $content]) {
            $panY = 200 + $dy;
            $topY = $beamY + $dy;
            $svg .= '<g class="'.($angle ? 'pan-move' : '').'" style="--dy:'.round($dy, 1).'px">';
            $svg .= '<line x1="'.($cx - 85).'" y1="'.($panY).'" x2="'.$cx.'" y2="'.$topY.'" stroke="#8A8577" stroke-width="1.5"/>';
            $svg .= '<line x1="'.($cx + 85).'" y1="'.($panY).'" x2="'.$cx.'" y2="'.$topY.'" stroke="#8A8577" stroke-width="1.5"/>';
            $svg .= '<path d="M'.($cx - 95).','.$panY.' L'.($cx + 95).','.$panY.' L'.($cx + 80).','.($panY + 12).' L'.($cx - 80).','.($panY + 12).' Z" fill="#C9C4B6" stroke="#6B6658" stroke-width="2"/>';
            $rm = ($from === 'both' || $from === $name) ? $remove : 0;
            $ad = ($to === 'both' || $to === $name) ? $add : 0;
            $svg .= self::panItems($cx, $panY, $content, $rm, $ad, $name === 'right' && $share ? $L['bags'] : 0);
            $label = $s['labels'][$name === 'left' ? 0 : 1] ?? self::expr($content);
            $svg .= '<text x="'.$cx.'" y="'.($panY + 36).'" text-anchor="middle" font-size="20" font-weight="700" fill="#4B3FB5" font-family="Inter, sans-serif">'.self::e((string) $label).'</text>';
            $svg .= '</g>';
        }

        return $svg.'</svg>';
    }

    /** Bags along the pan, then cubes stacked in columns. The last $remove cubes are taken off; $add new cubes arrive. */
    private static function panItems(float $cx, float $panY, array $c, int $remove, int $add, int $groups): string
    {
        $out = '';
        $bagW = 30;
        $cube = 15;
        $neg = $c['cubes'] < 0;
        $n = abs($c['cubes']);
        $cols = $groups ? 0 : max(1, (int) ceil(($n + $add) / 6));
        $cubesW = $groups ? 0 : $cols * $cube;
        $total = $c['bags'] * $bagW + ($c['bags'] && ($n + $add) ? 10 : 0) + $cubesW;
        $x = $cx - $total / 2;

        for ($i = 0; $i < $c['bags']; $i++) {
            $bx = $x + $i * $bagW + 2;
            $out .= '<g><path d="M'.($bx + 4).','.($panY - 2).' Q'.($bx - 1).','.($panY - 22).' '.($bx + 7).','.($panY - 30).' L'.($bx + 19).','.($panY - 30).' Q'.($bx + 27).','.($panY - 22).' '.($bx + 22).','.($panY - 2).' Z" fill="#F2C94C" stroke="#8C6B1F" stroke-width="1.5"/>'
                .'<text x="'.($bx + 13).'" y="'.($panY - 9).'" text-anchor="middle" font-size="14" font-weight="700" fill="#5A4410" font-family="Inter, sans-serif">x</text></g>';
        }
        $x += $c['bags'] * $bagW + ($c['bags'] && ($n + $add) ? 10 : 0);

        if ($groups) {
            // Share the cubes out: one group for each bag.
            $per = intdiv($n, $groups);
            $gW = min(46, 186 / $groups);
            $start = $cx - $gW * $groups / 2;
            for ($g = 0; $g < $groups; $g++) {
                $gx = $start + $g * $gW + 4;
                $colsG = max(1, (int) ceil($per / 6));
                for ($k = 0; $k < $per; $k++) {
                    $out .= self::cube($gx + intdiv($k, 6) * 11 + 2, $panY - 12 - ($k % 6) * 11, 11, $neg, 'share', 0.4 + 0.3 * $g);
                }
                $out .= '<rect class="group" x="'.round($gx, 1).'" y="'.($panY - 76).'" width="'.round($colsG * 11 + 4, 1).'" height="72" rx="5" fill="none" stroke="#2F6BEA" stroke-dasharray="4 3" style="animation-delay:'.(0.4 + 0.3 * $g).'s"/>';
                $out .= '<text x="'.round($gx + ($colsG * 11 + 4) / 2, 1).'" y="'.($panY - 82).'" text-anchor="middle" font-size="13" font-weight="700" fill="#2F6BEA" font-family="Inter, sans-serif">'.($neg ? '−' : '').$per.'</text>';
            }

            return $out;
        }

        for ($k = 0; $k < $n + $add; $k++) {
            $cxk = $x + intdiv($k, 6) * $cube;
            $cyk = $panY - $cube - ($k % 6) * $cube;
            $class = $k >= $n ? 'add' : ($k >= $n - $remove ? 'rm' : '');
            $out .= self::cube($cxk, $cyk, $cube, $neg, $class, $class === 'add' ? 0.6 + 0.05 * ($k - $n) : ($class === 'rm' ? 0.6 + 0.05 * ($k - $n + $remove) : 0));
        }

        return $out;
    }

    private static function cube(float $x, float $y, float $size, bool $neg, string $class, float $delay): string
    {
        $fill = $neg ? '#F7C6C2' : '#CFE0FF';
        $stroke = $neg ? '#B42318' : '#2F6BEA';
        $s = round($size - 2, 1);
        $g = '<g class="cube '.$class.'" style="animation-delay:'.round($delay, 2).'s"'.(self::$phase === 'after' && $class === 'rm' ? ' opacity="0.25"' : '').'><rect x="'.round($x, 1).'" y="'.round($y, 1).'" width="'.$s.'" height="'.$s.'" rx="2" fill="'.$fill.'" stroke="'.$stroke.'" stroke-width="1.2"/>';
        if ($neg) {
            $g .= '<line x1="'.round($x + 3, 1).'" y1="'.round($y + $s / 2, 1).'" x2="'.round($x + $s - 3, 1).'" y2="'.round($y + $s / 2, 1).'" stroke="#B42318" stroke-width="1.5"/>';
        }
        if ($class === 'rm') {
            $g .= '<path class="cross" d="M'.round($x, 1).','.round($y, 1).' l'.$s.','.$s.' M'.round($x + $s, 1).','.round($y, 1).' l-'.$s.','.$s.'" stroke="#B42318" stroke-width="2"/>';
        }

        return $g.'</g>';
    }

    private static function expr(array $c): string
    {
        $parts = [];
        if ($c['bags']) {
            $parts[] = ($c['bags'] === 1 ? '' : $c['bags']).'x';
        }
        if ($c['cubes'] || ! $parts) {
            $parts[] = $parts ? ($c['cubes'] < 0 ? '− '.abs($c['cubes']) : '+ '.$c['cubes']) : str_replace('-', '−', (string) $c['cubes']);
        }

        return implode(' ', $parts);
    }

    private static function balanceText(array $L, array $R): string
    {
        return 'Balance scale: '.self::expr($L).' on the left, '.self::expr($R).' on the right';
    }

    private const COLOURS = [
        'blue' => ['#CFE0FF', '#2F6BEA'], 'orange' => ['#FFE1C2', '#C2610C'], 'green' => ['#D5F0DF', '#1F7A4D'],
        'red' => ['#F9D2CE', '#B42318'], 'purple' => ['#E5DEFF', '#5B3FD6'], 'yellow' => ['#FFF1B8', '#9A6B00'],
        'grey' => ['#E8EAEE', '#5B6573'], 'pink' => ['#FCE0EE', '#B4236E'], 'teal' => ['#CCF2EE', '#0F766E'],
        'none' => ['none', '#3A3A3A'], 'white' => ['#FFFFFF', '#3A3A3A'], 'black' => ['#3A3A3A', '#141A22'],
    ];

    /** Simple shapes, lines and labels, each able to appear, be taken away or be highlighted at this step. */
    private static function shapes(array $s): string
    {
        $W = self::int($s['width'] ?? 560, 100, 900, 560);
        $H = self::int($s['height'] ?? 320, 60, 700, 320);
        $num = fn ($v, $d = 0) => is_numeric($v) ? round((float) $v, 1) : $d;
        $col = fn ($name, $i) => self::COLOURS[is_string($name) && isset(self::COLOURS[$name]) ? $name : 'blue'][$i];
        $svg = '<svg class="drawing shapes" viewBox="0 0 '.$W.' '.$H.'" role="img" xmlns="http://www.w3.org/2000/svg"><title>'.self::e((string) ($s['alt'] ?? 'Drawing')).'</title>';
        $svg .= '<defs><marker id="arrowhead" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" fill="context-stroke"/></marker></defs>';
        $n = 0;
        foreach (array_slice((array) ($s['items'] ?? []), 0, 80) as $it) {
            if (! is_array($it)) {
                continue;
            }
            $change = in_array($it['change'] ?? null, ['add', 'remove', 'highlight'], true) ? $it['change'] : null;
            if ($change === 'add' && in_array(self::$phase, ['before', 'during'], true)) {
                continue; // not there yet
            }
            $fill = $col($it['fill'] ?? 'blue', 0);
            $stroke = $col($it['color'] ?? ($it['fill'] ?? 'blue'), 1);
            if ($change === 'add') {
                $stroke = '#1F7A4D';
            }
            $sw = $num($it['stroke'] ?? 2, 2);
            $kind = $it['kind'] ?? 'rect';
            $x = $num($it['x'] ?? 0);
            $y = $num($it['y'] ?? 0);
            $box = null;
            $el = '';
            switch ($kind) {
                case 'rect':
                    $w = $num($it['w'] ?? 40, 40); $h = $num($it['h'] ?? 40, 40);
                    $el = '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="'.$num($it['rx'] ?? 3, 3).'" fill="'.$fill.'" stroke="'.$stroke.'" stroke-width="'.$sw.'"/>';
                    $box = [$x, $y, $w, $h];
                    break;
                case 'circle':
                    $r = $num($it['r'] ?? 20, 20);
                    $el = '<circle cx="'.$x.'" cy="'.$y.'" r="'.$r.'" fill="'.$fill.'" stroke="'.$stroke.'" stroke-width="'.$sw.'"/>';
                    $box = [$x - $r, $y - $r, 2 * $r, 2 * $r];
                    break;
                case 'line':
                case 'arrow':
                    $x2 = $num($it['x2'] ?? $x); $y2 = $num($it['y2'] ?? $y);
                    $c = $col($it['color'] ?? 'black', 1);
                    $el = '<line x1="'.$x.'" y1="'.$y.'" x2="'.$x2.'" y2="'.$y2.'" stroke="'.($change === 'add' ? '#1F7A4D' : $c).'" stroke-width="'.$num($it['stroke'] ?? 3, 3).'" stroke-linecap="round"'.($kind === 'arrow' ? ' marker-end="url(#arrowhead)"' : '').(! empty($it['dashed']) ? ' stroke-dasharray="6 5"' : '').'/>';
                    $box = [min($x, $x2), min($y, $y2) - 4, abs($x2 - $x), abs($y2 - $y) + 8];
                    break;
                case 'polygon':
                    $pts = array_filter(array_map(fn ($p) => is_array($p) && count($p) >= 2 ? [$num($p[0]), $num($p[1])] : null, array_slice((array) ($it['points'] ?? []), 0, 40)));
                    if (count($pts) < 2) {
                        continue 2;
                    }
                    $xs = array_column($pts, 0); $ys = array_column($pts, 1);
                    $el = '<polygon points="'.implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $pts)).'" fill="'.$fill.'" stroke="'.$stroke.'" stroke-width="'.$sw.'" stroke-linejoin="round"/>';
                    $box = [min($xs), min($ys), max($xs) - min($xs), max($ys) - min($ys)];
                    break;
                case 'text':
                    $size = $num($it['size'] ?? 20, 20);
                    $c = $change === 'add' ? '#1F7A4D' : $col($it['color'] ?? 'black', 1);
                    $anchor = in_array($it['anchor'] ?? 'middle', ['start', 'middle', 'end'], true) ? ($it['anchor'] ?? 'middle') : 'middle';
                    $el = '<text x="'.$x.'" y="'.$y.'" font-size="'.$size.'" font-weight="'.(! empty($it['bold']) ? 700 : 500).'" fill="'.$c.'" text-anchor="'.$anchor.'" font-family="Inter, sans-serif">'.self::e(mb_substr((string) ($it['text'] ?? ''), 0, 60)).'</text>';
                    $tw = mb_strlen((string) ($it['text'] ?? '')) * $size * 0.55;
                    $box = [$anchor === 'middle' ? $x - $tw / 2 : ($anchor === 'end' ? $x - $tw : $x), $y - $size, $tw, $size * 1.2];
                    break;
                default:
                    continue 2;
            }
            if (isset($it['label']) && $box && $kind !== 'text') {
                $el .= '<text x="'.round($box[0] + $box[2] / 2, 1).'" y="'.round($box[1] + $box[3] / 2 + 6, 1).'" font-size="'.$num($it['labelSize'] ?? 17, 17).'" font-weight="700" fill="'.$col($it['color'] ?? ($it['fill'] ?? 'black'), 1).'" text-anchor="middle" font-family="Inter, sans-serif">'.self::e(mb_substr((string) $it['label'], 0, 30)).'</text>';
            }
            if ($change === 'highlight' && $box) {
                $el = '<rect class="glow" x="'.round($box[0] - 6, 1).'" y="'.round($box[1] - 6, 1).'" width="'.round($box[2] + 12, 1).'" height="'.round($box[3] + 12, 1).'" rx="8" fill="#FFE066" opacity="'.(self::$phase === 'before' ? 0 : 0.55).'"/>'.$el;
            }
            if ($change === 'remove' && $box && self::$phase !== 'before') {
                [$bx, $by, $bw, $bh] = array_map(fn ($v) => round($v, 1), $box);
                $el .= '<path class="cross" d="M'.$bx.','.$by.' L'.($bx + $bw).','.($by + $bh).' M'.($bx + $bw).','.$by.' L'.$bx.','.($by + $bh).'" stroke="#B42318" stroke-width="3" stroke-linecap="round"/>';
            }
            $cls = $change === 'remove' ? 'item rm' : ($change === 'add' ? 'item add' : ($change === 'highlight' ? 'item hl' : 'item'));
            $op = $change === 'remove' && self::$phase === 'after' ? ' opacity="0.25"' : '';
            $svg .= '<g class="'.$cls.'" style="animation-delay:'.round(0.5 + 0.08 * $n++, 2).'s"'.$op.'>'.$el.'</g>';
        }

        return $svg.'</svg>';
    }

    private static function numberLine(array $s): string
    {
        $from = self::int($s['from'] ?? -10, -100, 100, -10);
        $to = self::int($s['to'] ?? 10, $from + 1, 120, 10);
        $start = self::int($s['start'] ?? 0, $from, $to);
        $jump = self::int($s['jump'] ?? 0, $from - $start, $to - $start);
        $end = $start + $jump;
        $W = 560;
        $pad = 30;
        $step = ($W - 2 * $pad) / ($to - $from);
        $X = fn ($v) => round($pad + ($v - $from) * $step, 1);
        $y = 110;
        $every = max(1, (int) ceil(($to - $from) / 20));

        $svg = '<svg class="drawing numberline" viewBox="0 0 '.$W.' 150" role="img" xmlns="http://www.w3.org/2000/svg"><title>Number line: start at '.$start.', move '.abs($jump).' '.($jump < 0 ? 'left' : 'right').', land on '.$end.'</title>';
        $svg .= '<line x1="'.($pad - 10).'" y1="'.$y.'" x2="'.($W - $pad + 10).'" y2="'.$y.'" stroke="#3A3A3A" stroke-width="2"/>';
        for ($v = $from; $v <= $to; $v++) {
            $big = $v === 0 || $v % $every === 0;
            $svg .= '<line x1="'.$X($v).'" y1="'.($y - ($big ? 7 : 4)).'" x2="'.$X($v).'" y2="'.($y + ($big ? 7 : 4)).'" stroke="#3A3A3A" stroke-width="'.($v === 0 ? 2.5 : 1.2).'"/>';
            if ($big || $v === $start || $v === $end) {
                $svg .= '<text x="'.$X($v).'" y="'.($y + 26).'" text-anchor="middle" font-size="13" font-family="Inter, sans-serif" fill="'.($v === $end ? '#B42318' : '#3A3A3A').'" font-weight="'.($v === $start || $v === $end ? 700 : 400).'">'.str_replace('-', '−', (string) $v).'</text>';
            }
        }
        if ($jump) {
            $x1 = $X($start);
            $x2 = $X($end);
            $mid = ($x1 + $x2) / 2;
            $h = min(80, 20 + abs($x2 - $x1) / 4);
            $svg .= '<path class="jump" d="M'.$x1.','.($y - 8).' Q'.$mid.','.($y - 8 - $h * 2).' '.$x2.','.($y - 8).'" fill="none" stroke="#2F6BEA" stroke-width="3" pathLength="100"/>';
            $svg .= '<text class="jump-label" x="'.$mid.'" y="'.round($y - 14 - $h, 1).'" text-anchor="middle" font-size="15" font-weight="700" fill="#2F6BEA" font-family="Inter, sans-serif">'.($jump < 0 ? '− '.abs($jump) : '+ '.$jump).'</text>';
            $svg .= '<circle class="land" cx="'.$x2.'" cy="'.$y.'" r="7" fill="#B42318"/>';
        }
        $svg .= '<circle cx="'.$X($start).'" cy="'.$y.'" r="7" fill="#1F7A4D"/>';

        return $svg.'</svg>';
    }
}
