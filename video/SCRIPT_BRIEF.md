# Brief: write Brainsnacc explainer-video scripts

Each lesson gets a short narrated video (about 1.5–2.5 minutes) that a computer voice reads while simple animated slides appear. You write the **script** as JSON; a renderer turns it into the video. You do NOT render anything.

Look at the finished example first: `video/scripts/y11/chemistry/u1/the-mole-and-molar-mass.json`.

## For each lesson file you are given

1. Read the lesson JSON (`database/content/<path>.json`): especially the opening, `## Think of it like this`, `## Key ideas`, `## Worked examples`, `## Tips to remember` and `## Summary`.
2. Write `video/scripts/<same path>.json` (same folders and file name as the lesson, under `video/scripts/` instead of `database/content/`).
3. Run `python3 video/check_script.py video/scripts/<same path>.json` from `/home/claude/studylab` and fix anything it reports until it prints OK.

## Script shape

```json  (the // notes are explanations only — real files are plain JSON with no comments)
{
  "lesson": "y11/chemistry/u1/the-mole-and-molar-mass.json",
  "level": "Year 11",            // Year 10 | Year 11 | Year 12 | Uni Year 1
  "subject": "chemistry",        // chemistry | biology
  "title": "<lesson title>",
  "icon": "⚖️",                  // one emoji for the topic
  "scenes": [ ... 6 to 9 scenes ... ]
}
```

Every scene has `type` and `say` (what the voice reads). Optional `caption`: the on-screen subtitle, when it should show symbols/numbers differently from how they are spoken (e.g. say "six point zero two times ten to the twenty-three", caption "6.02 × 10²³"). If `say` has no tricky symbols, leave `caption` out.

Scene types (keep to the limits — the checker enforces them):

| type | fields | use it for |
|---|---|---|
| `title` | `heading`, `sub` (one line hook), `icon` | always the FIRST scene: a hook question/sentence |
| `analogy` | `everyday` (≤80 chars), `science` (≤80), `icon` (everyday emoji), `icon2` (science emoji), `breaks` (≤150, where the analogy breaks down) | at least ONE; take it from the lesson's "Think of it like this" |
| `points` | `heading`, `points` (1–5, each ≤64 chars), optional `icon` | key facts/definitions |
| `equation` | `heading`, `equation` (≤34 chars), `parts` ([[symbol, meaning]] ×≤4) | a formula or a key chemical equation |
| `example` | `heading`, `question` (≤130), `lines` (≤5 steps, each ≤58; last line = the answer) | a short worked example from the lesson |
| `compare` | `heading`, `left`/`right`: {`title` ≤26, `icon`, `points` ≤4 × ≤46 chars} | two things students mix up |
| `steps` | `heading`, `steps` (3–5, each ≤44 chars) | a process or sequence |
| `recap` | `points` (≤5 × ≤60 chars), optional `closing` | always the LAST scene; end the say with "Now try the practice questions!" |

## Narration rules

- 180–320 words in total; each scene's `say` at most 75 words. Friendly, encouraging, plain English for a 15–18 year old (or first-year uni student). Talk to the student ("you").
- Write `say` exactly as it should be SPOKEN: numbers and symbols in words ("H two O", "eighteen grams per mole", "delta H", "A, T, G and C"). The voice can't read sub/superscripts, arrows or abbreviations like "mol⁻¹". Spell out abbreviations the first time (e.g. "D N A" is fine as "DNA").
- Australian spelling in captions and slides (colour, sulfur, analyse).
- Slide text is short and punchy; the narration explains. Every number and fact must match the lesson exactly and be scientifically correct. No invented statistics.
- Use simple, common emoji only (things like ⚽ 🍕 🔋 🧪 🧬 🦠 🌱 🔥 💧 🧲 ⚡ 🏉 🚗 📱 🏠 🔑 🧱 🍳 🎯 🧠 ❤️ 🌏 ☀️ 🌊 🍎). Avoid flags and new/rare emoji.

## Special lessons

Skills lessons (exam technique, command words, investigations, significant figures): same format; use `steps`, `points`, `compare` and an `example`; the analogy can be about study habits.

When done, reply with the number of scripts written and any lesson you skipped.
