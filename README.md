# StudyLab

Self-paced Chemistry and Biology for Year 10, VCE Units 1–4 and University Year 1. Built for family use first.

## How the course is organised

```
Level  (Year 10 · Year 11 VCE Units 1–2 · Year 12 VCE Units 3–4 · University Year 1 (Monash))
  └ Unit     (one per level + subject, e.g. "Unit 3", "Chemistry 1")
      └ Module   (an Area of Study, or a uni topic block)
          └ Lesson   (notes in Markdown, draft or live)
              └ Question  (multiple choice, marked instantly)
```

New levels (e.g. University Year 2) are added as data, no code changes needed.

## Who can do what

| Role    | Can |
|---------|-----|
| Student | Dashboard, course map, lessons, practice questions |
| Parent  | See their children's weekly progress |
| Admin   | Everything above, plus lessons, questions and accounts |

There is no public sign-up: the admin creates every account.

## Deploying on Laravel Cloud

1. Create a new application from this repo (branch `main`).
2. Attach a **database** (MySQL or Postgres). Don't use SQLite on Cloud — its files are wiped on each deploy.
3. Add environment variables: `APP_NAME=StudyLab`, `SEED_ADMIN_NAME`, `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD`.
4. Deploy commands: `php artisan migrate --force`.
5. Run once from the Cloud commands screen: `php artisan db:seed --force` — creates the levels, subjects, units, two sample lessons and the admin login. Safe to re-run.

## Running tests

```
composer install
php artisan test
```

## Still to come

- AI tutor on each lesson (hints first)
- Practice exams with marks by topic
- Lessons for the Monash first-year units (CHM1011, CHM1022, BIO1011, BIO1022)
- Weekly parent email
