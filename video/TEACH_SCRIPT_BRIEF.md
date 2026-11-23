# Brief: write Brainsnacc teaching scripts ("the teacher at the board")

Each lesson's topic is taught like a good teacher at the board: a short spoken sentence, what the teacher writes on
the board, and a drawing that **changes at each step** so the student sees exactly what is different. The script is
JSON; the app plays it line by line and a renderer turns it into a narrated video. You only write the JSON.

**Read the finished example first:** `database/content/teach/year-7/maths/solving-two-step-equations.json`.
It is the standard to match: short sentences, one idea per line, and a picture that changes (cubes crossed off both
sides, the scale tipping when only one side changes, cubes shared out).

## For each lesson you are given (`<lesson file> -> <script file>`)

1. Read the lesson file (`database/content/...json`): the `body` (notes in Markdown) and the `questions`.
2. Write the script at the given path (`database/content/teach/<level>/<subject>/<lesson>.json`).
3. Run `python3 video/check_teach.py <script file>` from `/home/claude/brainsnacc` and fix everything it reports until it prints OK.

Do not change any other file. Do not commit. Do not render videos.

## Shape

```json
{
  "level": "Year 8", "subject": "maths", "title": "<exact lesson title>",
  "frames": [
    { "heading": "The balance rule",
      "lines": [
        { "say": "Think of an equation as a balance scale.", "draw": { ...a drawing... } },
        { "say": "Now take 7 cubes off both sides. It stays level.", "write": "4x + 7 − 7 = 35 − 7", "draw": { ... } },
        { "say": "Now 4 bags balance 28 cubes.", "write": "4x = 28" }
      ] }
  ]
}
```

- `level`, `subject`, `title`: copied exactly from the lesson file.
- 5–8 `frames` (screens). Each has a `heading` (≤ 48 characters) and 3–7 `lines`.
- A line has `say` (spoken, shown as a subtitle) and usually `write` (what goes on the board in chalk), and may have
  `draw` (a drawing made from data, below) or `diagram` (an existing picture: `"diagrams/y8/maths/....svg"` that exists
  in `public/`, with `alt`). Use the lesson's own diagrams where they fit — the lesson body links them as `/diagrams/...`.

## How to write it (this is what the owner asked for)

- **Transcript, not a textbook paragraph.** Say what a teacher says while writing: one short explanatory sentence per
  line, 8–16 words (hard limit 22). Plain words for a 12–18 year old. Talk to the student ("you", "we").
- **Every step illustrated.** One idea per line. Build it up: show it, then change one thing, then show the result.
- **Show the difference.** At least half the screens have a picture, and at least two drawings show a *change*
  (something removed, added or highlighted). Say what changed: "Take 7 off both sides. It stays level."
- `write` is short board writing, ≤ 36 characters, one line: an equation, a step, a key word, a formula
  ("6CO₂ + 6H₂O → C₆H₁₂O₆ + 6O₂" is fine). Leave it out when there is nothing to write.
- `say` is read aloud by a computer voice, so write numbers and symbols the way they are said when it matters
  ("x squared", "two over three", "H two O"). `write` can use symbols.
- Cover the topic's key ideas from the notes in order: what it is, the rule or method, one or two short worked
  demonstrations, mistakes to watch for, and a "Remember" screen to finish. The full worked examples and practice
  questions come after the video, so keep demonstrations short.
- Facts and numbers must match the lesson exactly and be correct. Australian spelling (colour, sulfur, analyse).
- Review / summary / practice-exam lessons: a short recap of the main ideas, each with a picture.

## Drawings (`draw`)

All drawings are 560 wide. Each one can show a change — the app animates it and the video shows before → after.

**Balance scale** (equations): `{"type": "balance", "left": {"bags": 4, "cubes": 7}, "right": {"cubes": 35}}`
- bags = x, cubes = 1s (negative = red minus cubes, e.g. `"cubes": -11`). Max 8 bags, 40 cubes.
- Changes: `"remove": 7` (from `"removeFrom": "both"|"left"|"right"`), `"add": 11` (`"addTo"`), `"tip": "left"|"right"`
  (only one side changed), `"share": true` (right-side cubes shared out between the bags), `"labels": ["4x + 7 − 7", "35 − 7"]`.

**Number line**: `{"type": "numberline", "from": -14, "to": 4, "start": 2, "jump": -14}` — draws the jump.

**Shapes** (everything else: fractions, area, angles, graphs, cells, atoms, particles, timelines, word problems…):
```json
{"type": "shapes", "width": 560, "height": 300, "alt": "what it shows", "items": [
  {"kind": "rect", "x": 20, "y": 40, "w": 120, "h": 60, "fill": "blue", "label": "1/4"},
  {"kind": "circle", "x": 300, "y": 150, "r": 40, "fill": "orange", "label": "Na⁺", "change": "remove"},
  {"kind": "polygon", "points": [[60,250],[260,250],[60,90]], "fill": "green"},
  {"kind": "line", "x": 20, "y": 200, "x2": 540, "y2": 200, "color": "black", "dashed": true},
  {"kind": "arrow", "x": 300, "y": 120, "x2": 420, "y2": 120, "color": "purple", "change": "add"},
  {"kind": "text", "x": 280, "y": 280, "text": "3/4 − 1/4 = 2/4", "size": 24, "bold": true, "color": "purple", "change": "highlight"}
]}
```
- kinds: `rect` (x, y, w, h), `circle` (x, y = centre, r), `line` / `arrow` (x, y → x2, y2), `polygon` (points), `text` (x, y,
  text ≤ 60 chars, size ≥ 14, `anchor` start|middle|end). Any shape can have a `label` written in its middle.
- colours: blue, orange, green, red, purple, yellow, grey, pink, teal, white, black, none (`fill` and `color`).
- `"change"`: `"add"` (appears in this step, outlined green), `"remove"` (crossed out in red, then fades),
  `"highlight"` (yellow glow). Items without a change are already there.
- Keep everything inside width × height (height ≤ 400). Keep it simple and big: at most ~25 items, text ≥ 16 where possible.
- To show steps, repeat the drawing on the next lines with the change applied: line 1 shows 4 quarter bars; line 2 the
  same bars with one marked `"change": "remove"`; line 3 the 3 remaining bars and the answer highlighted.

When you finish your batch, reply with the number of scripts written and any lesson you skipped, with the reason.
