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

## Maths lessons and chapter tests (Years 7–10)

Maths follows the Victorian Curriculum Mathematics 2.0. Each year has 11 chapters (the module names in CourseSeeder), a semester review after Chapter 5 and one at the end, and **4 chapter tests at the end of every chapter**. Lesson titles are our own breakdown — never call them official textbook sections, and write every word, example and question from scratch (never copy or imitate a textbook's exercises). Write for a 12–16 year old, in very simple steps. Australian spelling.

**Exact titles, levels and file paths** are in `database/content/maths-manifest.json` (fields: `year`, `module`, `title`, `kind`, `path`, `chapter_lessons`). Write each file at its `path`. Copy `level` (= `year`), `unit` (= `year`) and `title` exactly. `"subject": "maths"`.

**Check every file** with `python3 database/content/check_maths.py <file>` until it prints `OK`. The checker only checks structure — you must also work out every number in Python (answers, wrong options, worked examples).

### A normal lesson (`kind` = lesson)

Body 700–1,800 words, these `##` sections in order:

1. A 2–3 sentence opening: what it is and where it's used in real life (no heading).
2. `## Learning objective` — one sentence: "By the end of this lesson you can …".
3. `## Before you start` — the earlier skills this needs (name the earlier lesson, e.g. "Adding and subtracting integers (Year 7)") and a 2-question warm-up with answers.
4. `## Think of it like this` — one everyday analogy (`### <the analogy>`), then a line in italics starting *Where the analogy breaks down:*.
5. `## Key ideas` — short explanation in `###` parts; bold each new term.
6. `## Worked examples` — at least 3, easy → hard, each headed `### Example 1`, `### Example 2`…: the question, then numbered steps, each with its reason ("Step 2: subtract 7 from both sides — to get 3x on its own"), ending with a check where possible.
7. `## Common mistakes` — 3–5 bullets: the mistake, why it happens, the fix.
8. `## Tips to remember` — 3–6 memory aids specific to the topic (say when a trick is made up).
9. `## Key terms` — `- **Term** — definition`.
10. `## Summary` — 4–6 bullets.

12 questions: **3 easy, 3 medium, 3 hard, 3 challenge**. Easy = one skill, small numbers; medium = two steps or a small twist; hard = several steps, negatives/fractions, or a worded problem; challenge = unfamiliar context, reasoning, or combining two ideas.

### "… summary and memory tips" lessons

Condensed revision for the whole year: one `##` section per chapter (must-know facts, rules, one tiny example each), a big `## Tips to remember`, then `## Summary`. 1,200–2,800 words. 12 mixed questions, 3 per level, spread across the year.

### "Year N Semester 1 review" lessons

Practice-test layout covering Chapters 1–5: `## How to use this review`, `## Topics covered`, `## Short-answer questions` (5, with marks), `## Worked solutions`. 20 questions: 5 per level.

### Chapter tests (`kind` = test; titles "Chapter N · Test k")

`"kind": "test"`, a short body (60–150 words: what it covers, "about 25 minutes, no notes, no calculator unless the question says"), `"minutes": 25`, and **15 questions: 4 easy, 5 medium, 4 hard, 2 challenge**. Every question has `"topic"` = the title of the chapter lesson it tests, copied exactly from `chapter_lessons`; every lesson in the chapter is tested at least once. The four tests of a chapter test the same skills with **different questions** (new numbers and contexts) — the checker refuses repeats. Tests are done with no hints, so the wording must be completely clear on its own.

### Diagrams (geometry, measurement, graphs, data, probability — anything visual)

Students must SEE the shape, angle or graph. Draw diagrams with `video/diagrams.py` (read its top docstring for every function). Everything is drawn to scale.

```python
import sys; sys.path.insert(0, 'video'); import diagrams as D
s = D.parallel_lines(65, labels={('top', 'tr'): '65°', ('bottom', 'bl'): 'x'})
D.preview(s, '/tmp/check.png')          # then LOOK at the PNG (Read it) — fix anything that overlaps or is wrong
web = D.save(s, 'y7/maths/angles-and-parallel-lines-q3')   # → 'diagrams/y7/maths/angles-and-parallel-lines-q3.svg'
```

- Name files `<lesson-or-test file name>-<something short>` so they never clash with another lesson's.
- In a **question**: add `"diagram": "diagrams/y7/maths/….svg"` and `"diagram_alt": "a sentence describing what the diagram shows, including the numbers"`. The prompt can then say "Find x in the diagram." The diagram must not give away the answer (label the unknown `x` or `?`).
- In the **lesson body**: `![Two parallel lines cut by a transversal, with a 65° angle marked](/diagrams/y7/maths/….svg)` — in Key ideas (to show each new idea) and in Worked examples.
- Visual topics (geometry, measurement, angles, Pythagoras, trig, transformations, Cartesian plane and graphs, number lines and inequalities, statistics and probability, networks, scale drawings): the checker requires **at least 2 diagrams in the notes and 4 diagram questions per lesson, and 5 diagram questions per chapter test**. Other topics may use diagrams wherever they help (e.g. number lines for integers, bar models for fractions as a `composite`/`polygon`).
- A diagram is part of the answer's correctness: check every label matches the numbers in the prompt and steps.

### Every question (lessons and tests)

```json
{
  "difficulty": "easy",
  "topic": "Solving two-step equations",
  "prompt": "Solve 3x + 7 = 31.",
  "options": {"A": "x = 8", "B": "x = 12.7", "C": "x = 24", "D": "x = 10"},
  "answer": "A",
  "explanation": "One or two sentences: why A is right and why the most tempting wrong answer is wrong.",
  "steps": ["Subtract 7 from both sides: 3x = 24.", "Divide both sides by 3: x = 8.", "Check: 3 × 8 + 7 = 31 ✓"],
  "mistakes": {"B": "Step 1: you added 7 instead of subtracting it, then divided by 3.", "C": "Step 2: 3x = 24 isn't finished — divide both sides by 3.", "D": "Step 1: 31 − 7 = 24, not 30."}
}
```

(`topic` is only needed in chapter tests.)

- `steps`: short steps in order, each with its reason. Shown as free hints one at a time, never the last one — so the last step must be the one that gives the answer.
- `mistakes`: a note for EVERY wrong option saying which step goes wrong to give that answer and how to fix it, without stating the right answer. Each wrong option must be a real, common mistake.
- Spread the correct letters across A–D. 4 options, all different.
- Maths notation: Unicode (x², 10⁻³, √50, ½, π, ×, ÷, −, ≈, °). Write inequalities in words where possible. No HTML, no LaTeX.
- When a question contains a linear equation in one unknown, write it plainly (e.g. "Solve 2(3x − 4) = 10.") — the app offers to work it line by line in the tutor.
- Check every number by calculating it in Python, including wrong options and worked examples.

## Year 7 Maths · Advanced (selective / accelerated stream)

Same rules as the Maths section above (headings, 12 questions 3/3/3/3, steps, mistakes, diagrams for visual topics), with `"unit": "Year 7 Advanced"` and files in `database/content/y7/maths-advanced/`. Who it's for: a strong Year 7 student in a selective or accelerated class who has already done the normal Year 7 lesson on this topic. So:

- "Before you start" names the normal Year 7 lesson(s) it builds on.
- Go deeper, not just bigger numbers: multi-step problems, "explain why", unfamiliar contexts, working backwards, some Year 8 ideas (e.g. circles, trapeziums, inequalities, expanding and factorising, recurring decimals as fractions).
- Questions lean harder: easy here ≈ a normal Year 7 medium.
- Tests ("Advanced N · Test 1/2"): 15 questions — 2 easy, 5 medium, 5 hard, 3 challenge; each lesson of the chapter tested; the two tests use different questions.

## Selective entry exam prep (Victoria)

For Year 7 students preparing early for the Victorian selective entry high school test (sat in Year 8 for Year 9 entry). The real test has five sections: Mathematics, Quantitative reasoning, Verbal reasoning, Reading comprehension (multiple choice, about 30 minutes each) and Written expression. Write everything yourself — **never copy or closely imitate questions from any past paper, Edutest, ACER or commercial practice book**. Files: `database/content/y7/selective/`, `"level": "Year 7"`, `"subject": "selective"`, `"unit": "Selective"`. Australian spelling. Check with `python3 database/content/check_maths.py <file>` (it handles these too).

### Lessons

Each lesson teaches one question type and how to beat it. 700–1,800 words, these `##` sections in order:

1. A 2–3 sentence opening (no heading): what this question type looks like and why it's in the test.
2. `## What you will learn`
3. `## How these questions work` — the usual shapes of the question, with a small example of each.
4. `## Strategies` — numbered, step-by-step methods (e.g. "Find the relationship in the first pair, say it as a sentence, then test it on each option").
5. `## Worked examples` — at least 3, `### Example 1`…, easy → hard, each with the question, the thinking in numbered steps, the answer, and why the tempting wrong option is wrong.
6. `## Common traps`
7. `## Tips to remember`
8. `## Key terms`
9. `## Summary`

12 questions in the test style, 3 easy / 3 medium / 3 hard / 3 challenge, with `steps` (the reasoning) and `mistakes` (which trap gives each wrong option). The "How the selective maths test works", "What quantitative/verbal reasoning tests" and "Reading test strategies" lessons explain the section (timing, pacing, guessing, skipping) and still use this format.

- **Mathematics**: problem-solving maths up to about Year 8 level, worded and multi-step, no calculator. Diagrams for geometry/measurement/data (see Diagrams).
- **Quantitative reasoning**: number sequences, number grids, symbol/operation puzzles, comparing quantities, shape patterns, quick table/graph reading. Use diagrams for grids and shape patterns (`D.svg`, `D.poly`, `D.text`; a number grid can also be a Markdown table in the prompt).
- **Verbal reasoning**: analogies, odd one out, synonyms/antonyms, letter series and codes, hidden words, logical deduction. Use everyday vocabulary a strong 12–13 year old could know; one clearly best answer.
- **Reading comprehension**: lesson examples use short original passages written by you (fiction, non-fiction, a poem, a simple visual text described in words). In lesson questions, put a short passage (up to ~150 words) in the prompt itself (max 1,200 characters).
- **Written expression**: how the writing task is marked, planning, creative and persuasive writing, vocabulary and sentences, editing. Include short model paragraphs. Questions are multiple choice about craft (which opening is strongest, which sentence fixes the error, which plan fits the prompt). "Writing practice prompts" gives 8 original prompts (4 creative, 4 persuasive) with a planning checklist and one full model response.

### Practice tests (`kind` = test, 30 minutes)

Titles like "Mathematics · Test 2". Real-test size and mix (the checker enforces it):

| Section | Questions | easy / medium / hard / challenge |
|---|---|---|
| Mathematics | 30 | 6 / 10 / 10 / 4 |
| Quantitative reasoning | 35 | 7 / 12 / 11 / 5 |
| Verbal reasoning | 40 | 8 / 14 / 12 / 6 |
| Reading comprehension | 30 | 6 / 10 / 10 / 4 |

Each question's `topic` = one of the section's lesson titles (from `chapter_lessons`), every listed lesson covered. The three tests in a section use different questions. Body for Mathematics/QR/VR tests: 60–150 words of instructions. **Reading tests**: the body holds the passages (900–1,700 words): 3–4 original texts, each headed `## Passage A — <title>` (include one poem), and every question names its passage ("In Passage B, the word 'brittle' means…").
