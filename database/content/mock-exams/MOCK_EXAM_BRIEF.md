# Brief: write a Brainsnacc VCE-style practice exam (original questions)

Brainsnacc students sit full-length practice exams in the official VCE format. You write ONE complete exam as JSON. Every question must be **original** — written by you, testing the study-design content. Do NOT copy, adapt or "change the numbers" of any VCAA exam question or any textbook/commercial question. Use your own scenarios, data and contexts.

## Official format (VCAA examination specifications)

| | Chemistry (study design from 2024) | Biology (study design from 2022) |
|---|---|---|
| Time | 15 min reading + 2 h 30 min writing | 15 min reading + 2 h 30 min writing |
| Section A | 30 multiple-choice × 1 mark = 30 | 40 multiple-choice × 1 mark = 40 |
| Section B | short-answer and extended-answer, multi-part; total **90** marks | short-answer and extended-answer, multi-part; total **80** marks; scenarios/experiments/data |
| Total | 120 | 120 |
| Content | all of Units 3 and 4 + key science skills | all of Units 3 and 4 + key science skills |

Cover Units 3 and 4 roughly evenly (Chemistry: fuels/energy/thermochemistry, galvanic and fuel cells, rates, equilibrium, electrolysis, organic structure/naming/reactions/pathways, analysis (chromatography, IR/NMR/MS, volumetric), food chemistry (carbohydrates/proteins/fats/vitamins/enzymes), plus practical-investigation skills. Biology: DNA/proteins/gene expression and regulation, DNA manipulation/CRISPR/GMOs, enzymes and coenzymes, photosynthesis and cellular respiration and factors, immunity (innate/adaptive/vaccines/immunotherapy/emerging diseases), genetic change in populations, speciation, fossils, phylogeny, human evolution, plus scientific-investigation skills). The Brainsnacc lessons in `database/content/y12/<subject>/u3` and `u4` show the exact scope and terms — read their titles and Key ideas.

Section B must include: several multi-part questions (2–6 parts each), calculations with working (Chemistry), at least one extended response (4–6 marks), data/graph-table interpretation (give data as a Markdown table), an experimental design / evaluation question (variables, validity, accuracy, precision, errors, ethics), and command words used correctly (identify, describe, explain, compare, evaluate, calculate, justify). Use Markdown for tables; no images. Chemical formulas with Unicode sub/superscripts.

## JSON shape (save to the path you are given)

```
{
  "subject": "chemistry",
  "title": "Brainsnacc VCE Chemistry practice exam 1",
  "slug": "chemistry-practice-exam-1",
  "reading_minutes": 15,
  "writing_minutes": 150,
  "section_a": [
    {"prompt": "...", "options": {"A": "...", "B": "...", "C": "...", "D": "..."}, "answer": "C",
     "explanation": "why C, and why the tempting wrong option is wrong", "topic": "Equilibrium"}
  ],
  "section_b": [
    {"number": 1, "topic": "Galvanic cells",
     "stem": "Shared information/scenario/data for all parts (Markdown).",
     "parts": [
       {"label": "a", "prompt": "Write the half-equation for ...", "marks": 1,
        "answer": "Model answer a strong student would write (Markdown).",
        "marking": ["1 mark: correct half-equation with states"]},
       {"label": "b", "prompt": "...", "marks": 3, "answer": "...",
        "marking": ["1 mark: ...", "1 mark: ...", "1 mark: ..."]}
     ]}
  ]
}
```

Rules:
- Section A count exactly 30 (Chemistry) / 40 (Biology); answers spread across A–D; plausible distractors; 1 mark each.
- Section B marks (sum of all `marks`) exactly 90 (Chemistry) / 80 (Biology). Each part's `marking` list has exactly as many entries as its marks, each starting "1 mark:".
- `answer` = full model answer, with working and units/sig figs for calculations.
- Difficulty: like a real end-of-year VCE exam — some easy recall, mostly application, a few hard discriminating questions.
- Accuracy matters more than anything: check every calculation with Python; use VCE Data Book values (Chemistry: standard electrode potentials, Avogadro 6.02 × 10²³, molar volume at SLC 24.8 L mol⁻¹, specific heat of water 4.18 J g⁻¹ °C⁻¹, energy content of food: carbohydrate 16 kJ g⁻¹, protein 17 kJ g⁻¹, fat 37 kJ g⁻¹, etc.). Australian spelling.
- No bare `<` or `>` in text (write "less than"/"greater than").

When finished run `python3 database/content/mock-exams/check_exam.py <your file>` from `/home/claude/studylab` and fix everything until it prints OK. Reply with the file path and a one-line topic summary.
