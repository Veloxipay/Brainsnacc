# Brief: add "Tips to remember" to existing Brainsnacc lessons

Each lesson is a JSON file with a Markdown `body`. Many were written before we added a `## Tips to remember` section. Your job is to add that section to each file in your folders that doesn't have one — nothing else.

For each `.json` file in your folders:
1. Skip it if its body already contains `## Tips to remember`.
2. Skip files whose name contains `summary-and-memory-tips`, `practice-exam` or `practice-test` (another writer handles those).
3. Read the lesson body carefully. Write a `## Tips to remember` section with 3–6 bullet points that genuinely help THIS lesson's content stick:
   - well-known mnemonics/acronyms where they exist (e.g. "OIL RIG", "An Ox, Red Cat", "LEO the lion says GER", "King Henry Died By Drinking Chocolate Milk"),
   - otherwise a clear made-up memory trick (say it's a made-up trick),
   - vivid pictures to imagine, everyday analogies, rhymes, "if you remember one thing…" lines, quick self-checks.
   Every tip must be scientifically accurate and match what the lesson teaches (same terms, same numbers). Pitch it at the lesson's level (Year 10, VCE, or first-year uni).
4. Insert the section immediately before `## Key terms`. If there is no `## Key terms`, insert it before `## Summary`; if neither exists, add it at the end of the body.
5. Edit safely with Python: load with `json.load`, change only `body`, write back with `json.dump(data, f, ensure_ascii=False, indent=2)`. Don't change titles, questions or any other field.
6. Re-load the file to confirm it parses and now contains the section.

Do the files one at a time. When done, reply with how many files you updated and any you skipped and why.
