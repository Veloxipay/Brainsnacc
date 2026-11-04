# Brief: add a "Think of it like this" real-life analogy section to Brainsnacc lessons

Each lesson is a JSON file with a Markdown `body`. Your job: add ONE new section, `## Think of it like this`, to each lesson in your folders — nothing else.

For each `.json` file in your folders:
1. Skip it if its body already contains `## Think of it like this`.
2. Skip files whose name contains `summary-and-memory-tips`, `practice-exam` or `practice-test`.
3. Read the whole lesson. Write a `## Think of it like this` section that makes the main idea click using everyday life — things a 15–18 year old in Australia knows (sport, footy, cooking, phones, gaming, school canteen, traffic, public transport, money, music, social media, the beach, a party, a shopping centre).
   - 2 or 3 short analogies, each as a `###` sub-heading naming the idea, e.g. `### Enzymes are like a key and a lock`, followed by a short paragraph (3–5 sentences) that maps each part of the analogy onto the science, using the lesson's own terms in **bold** the first time.
   - Under each analogy add one line starting `*Where the analogy breaks down:*` saying honestly what the comparison gets wrong (e.g. "real enzymes change shape slightly when the substrate binds — the induced-fit model"). This stops students learning a misconception.
   - Where the topic has a clear real-world use, finish the section with `### In real life` and 2–3 sentences about where students meet this (e.g. car airbags for gas laws, lactose-free milk for enzymes, vaccines for immunity). No invented statistics, brand claims or dates.
   - Everything must be scientifically accurate and match the lesson (same terms, numbers and level: Year 10, VCE, or first-year uni). Plain, friendly Australian English (colour, sulfur, analyse).
4. Insert the section immediately before `## Key ideas`. If there is no `## Key ideas`, insert it after the opening paragraph (before the first `## ` heading).
5. Edit safely with Python: `json.load`, change only `body`, write back with `json.dump(data, f, ensure_ascii=False, indent=2)`. Don't change titles, questions or any other field. Write your helper script in YOUR OWN uniquely named file in the scratchpad (include your folder name in the file name) so you don't clash with other writers.
6. Re-load the file to confirm it parses and contains `## Think of it like this` exactly once.

Formatting: Markdown only, no HTML, no images, no bare `<` or `>` (write "less than"/"greater than").

Do the files one at a time. When done, reply with how many files you updated and any you skipped and why.
