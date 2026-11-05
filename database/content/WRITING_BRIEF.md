# Brief for writing Brainsnacc lessons

Brainsnacc is a self-study portal for Victorian students (Year 10, VCE Units 1–4, Monash University first year). Nobody else writes or checks these lessons, so each one must teach the topic fully and correctly on its own.

## Output

One JSON file per lesson, saved with the Write tool at the path you are given. Exact shape:

```json
{
  "level": "Year 12",
  "subject": "chemistry",
  "unit": "Unit 3",
  "title": "Fuel cells",
  "summary": "One plain sentence saying what the lesson covers.",
  "minutes": 20,
  "body": "Markdown lesson notes (see below)",
  "questions": [
    {
      "prompt": "Question text",
      "options": {"A": "...", "B": "...", "C": "...", "D": "..."},
      "answer": "C",
      "explanation": "Why C is right and why the tempting wrong answer is wrong."
    }
  ]
}
```

- `level`, `subject`, `unit` and `title` must be copied EXACTLY as given to you (title punctuation included) — they are how the lesson is matched.
- After writing each file, check it parses: `python3 -c "import json,sys; json.load(open(sys.argv[1]))" <file>`. Fix and rewrite if it fails.

## The lesson body (Markdown)

Aim for 1,000–1,600 words. Detailed, accurate, written for a 15–18 year old studying alone (or a first-year uni student for Monash units). Plain, clear English; Australian spelling (e.g. "colour", "analyse", "sulfur").

Use this structure with `##` headings:

1. A 2–3 sentence opening: what this is and why it matters (link to real life where natural).
2. `## Key ideas` — the core content, split into several `###` sub-sections. Explain *why*, not just *what*. Define every new term the first time it appears, in **bold**.
3. `## Worked examples` — at least 2 fully worked examples, step by step. For chemistry calculations show the formula, substitution, units and significant figures. For biology, use worked scenarios (e.g. a pedigree, an experiment's results, a data question).
4. `## Common mistakes` — 3–5 bullet points on traps students fall into.
5. `## Exam tips` — 2–4 bullets on how this is typically assessed in VCE (or uni) and what earns marks.
6. `## Tips to remember` — 3–6 memory aids that genuinely help this topic stick: mnemonics/acronyms (e.g. "OIL RIG", "An Ox, Red Cat"), rhymes, visual pictures to imagine, everyday analogies, "if you remember one thing…" lines, and quick self-checks. Make them accurate and specific to this lesson (not generic study advice). Where a common mnemonic exists, use it; otherwise invent a clear one and say it is a made-up memory trick.
7. `## Key terms` — a glossary list: `- **Term** — definition`.
8. `## Summary` — 4–6 bullet points.

Formatting rules:
- Markdown only. No HTML tags (they are stripped). No images.
- Chemical formulas and equations with Unicode sub/superscripts: H₂O, SO₄²⁻, Fe³⁺, ΔH = −286 kJ mol⁻¹. Put full equations on their own line inside backticks: `2H₂(g) + O₂(g) → 2H₂O(l)`. Include states.
- Tables are fine (Markdown pipe tables).
- Use SI units and the VCE data book conventions (e.g. kJ mol⁻¹, standard electrode potentials as in the VCE data book).

## Special lesson types

- **"… summary and memory tips"** lessons: a condensed revision sheet for the whole unit (or year). Body 1,500–2,500 words: one `##` section per module/Area of Study with the must-know facts, formulas and definitions, plus a big `## Tips to remember` section collecting the best memory aids for the unit, then `## Summary`. 12 mixed questions covering the whole unit.
- **"… practice exam" / "… practice test"** lessons: body = `## How to use this practice exam` (time limit, conditions), `## Topics covered`, then `## Short-answer questions` with 4–6 exam-style written questions (with marks) followed by `## Worked solutions` giving full model answers. Then 20 multiple-choice questions covering the whole unit, exam difficulty, each with a full explanation.
- **Skills lessons** (e.g. significant figures, command words, exam technique): teach the skill with many worked examples and common mistakes; 8–10 questions.

Keep `summary` under 250 characters. Avoid bare `<` and `>` signs in text where possible (write "less than"/"greater than").

## Questions

8 multiple-choice questions per normal lesson (more for the special types above), exam style, 4 options (A–D), exactly one correct.
- Mix: recall (2), understanding/explain (3), application or calculation (3).
- Distractors must be plausible common misconceptions, not silly.
- Spread the correct letters across A, B, C and D.
- Every explanation says why the right answer is right and why the most tempting wrong answer is wrong.
- Check every calculation twice.

## Accuracy

- Stick to established science and the scope of the VCE study design (Chemistry 2023–2027, Biology 2022–2026) or the Monash unit.
- Do not invent statistics, dates, quotes or sources. If unsure of a detail, leave it out.
- Write everything in your own words — do not copy textbook or website text.

## Maths lessons (Years 7–10)

Maths follows the Victorian Curriculum Mathematics 2.0. Each year is organised as 11 chapters (the chapter names in CourseSeeder) plus a semester review after Chapter 5 and at the end. The lesson titles inside each chapter are our own breakdown — never call them official textbook sections, and write all text, examples and questions from scratch (do not copy or imitate any textbook's exercises). Use `"subject": "maths"` and `"unit": "Year 7"` (etc., same as the level). Write for a 12–16 year old, in very simple steps.

Body structure for a normal Maths lesson (replaces the general structure above):

1. 2–3 sentence opening: what this is and where it's used in real life.
2. `## Learning objective` — one sentence: "By the end of this lesson you can …".
3. `## Before you start` — the skills this builds on, each linked by name to the earlier lesson (e.g. "Adding and subtracting integers (Year 7)"), with a 2-question warm-up.
4. `## Key ideas` — short explanation with `###` sub-sections; bold every new term.
5. `## Worked examples` — 3 or more, easy → hard. Every step on its own numbered line with a short reason ("Step 2: subtract 7 from both sides — to get 3x on its own"). Finish with a check.
6. `## Common mistakes` — the misconceptions behind the wrong options, with the fix.
7. `## Tips to remember`, `## Key terms`, `## Summary` — as above.

Maths notation: Unicode (x², 10⁻³, √50, ∛8, ½, π, ×, ÷, −, ≈, °). Write inequalities in words where possible ("x is less than −4").

Maths questions: 12 per lesson — **3 easy, 3 medium, 3 hard, 3 challenge** (the app sorts them easy → challenge). Each question has:

```json
{
  "difficulty": "easy",
  "prompt": "Solve 3x + 7 = 31.",
  "options": {"A": "x = 8", "B": "x = 12.7", "C": "x = 24", "D": "x = 10"},
  "answer": "A",
  "explanation": "One or two sentences: why A is right and why the most tempting wrong answer is wrong.",
  "steps": ["Subtract 7 from both sides: 3x = 24.", "Divide both sides by 3: x = 8.", "Check: 3 × 8 + 7 = 31 ✓"],
  "mistakes": {
    "B": "Step 1: you added 7 instead of subtracting it (3x = 38).",
    "C": "Step 2: 3x = 24 isn't finished — divide both sides by 3.",
    "D": "Step 1: 31 − 7 = 24, not 30."
  }
}
```

- `steps`: short numbered-in-order steps, each with the reason. These are shown once the student gets it right (or after a second wrong try).
- `mistakes`: one note for EVERY wrong option, saying which step goes wrong to give that answer and how to fix it — without giving the final answer. Every wrong option must be a real, common mistake.
- Levels: easy = one skill, small numbers; medium = two steps or a small twist; hard = several steps, negatives/fractions, or a worded problem; challenge = unfamiliar context, reasoning, or combining two topics.
- When a student gets a question wrong, they are asked "What steps did you follow?" and their steps are checked against `steps` and `mistakes`, so these must be accurate.
- **"Year N Semester 1 review"** and **"… Maths challenge"** lessons use the practice-test layout (`## How to use this challenge`, `## Topics covered`, `## Short-answer questions`, `## Worked solutions`, `## Challenge tips`) with 20 questions, 5 at each level.
- Check every number by actually calculating it (run it in Python), including the numbers in the wrong options.
