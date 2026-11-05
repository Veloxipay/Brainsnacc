<?php

// The Maths tutor's rules. Change the numbers here; they are read everywhere from this file.
return [
    // "Solved on your own": this many correct answers with no help at all, on different questions.
    'independent_successes' => (int) env('TUTOR_INDEPENDENT_SUCCESSES', 2),

    // "Remembered": one more unaided correct answer at least this many days after becoming independent.
    'retention_days' => (int) env('TUTOR_RETENTION_DAYS', 3),

    // "Needs foundations": the same mistake seen this many times, and the quick check on the earlier skill missed.
    'foundation_repeats' => (int) env('TUTOR_FOUNDATION_REPEATS', 2),

    // Most AI calls one student can make per day (explanations, step checks, photos). Built-in help keeps working after.
    'ai_daily_calls' => (int) env('TUTOR_AI_DAILY_CALLS', 60),

    // Photos of homework: largest upload in kilobytes. The image is read and then deleted; only the confirmed text is kept.
    'photo_max_kb' => (int) env('TUTOR_PHOTO_MAX_KB', 6000),
];
