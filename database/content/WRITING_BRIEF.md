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
6. `## Key terms` — a glossary list: `- **Term** — definition`.
7. `## Summary` — 4–6 bullet points.

Formatting rules:
- Markdown only. No HTML tags (they are stripped). No images.
- Chemical formulas and equations with Unicode sub/superscripts: H₂O, SO₄²⁻, Fe³⁺, ΔH = −286 kJ mol⁻¹. Put full equations on their own line inside backticks: `2H₂(g) + O₂(g) → 2H₂O(l)`. Include states.
- Tables are fine (Markdown pipe tables).
- Use SI units and the VCE data book conventions (e.g. kJ mol⁻¹, standard electrode potentials as in the VCE data book).

## Questions

8 multiple-choice questions per lesson, exam style, 4 options (A–D), exactly one correct.
- Mix: recall (2), understanding/explain (3), application or calculation (3).
- Distractors must be plausible common misconceptions, not silly.
- Spread the correct letters across A, B, C and D.
- Every explanation says why the right answer is right and why the most tempting wrong answer is wrong.
- Check every calculation twice.

## Accuracy

- Stick to established science and the scope of the VCE study design (Chemistry 2023–2027, Biology 2022–2026) or the Monash unit.
- Do not invent statistics, dates, quotes or sources. If unsure of a detail, leave it out.
- Write everything in your own words — do not copy textbook or website text.
