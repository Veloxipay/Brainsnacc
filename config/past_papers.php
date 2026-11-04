<?php

/*
 * Past VCE (Year 12, Units 3 and 4) exams and examiners' reports, linked from the official VCAA website.
 * Links checked on 5 Nov 2026 against
 *   https://www.vcaa.vic.edu.au/assessment/vce/examination-specifications-past-examinations-and-examination-reports/chemistry
 *   https://www.vcaa.vic.edu.au/assessment/vce/examination-specifications-past-examinations-and-examination-reports/biology
 * 'current' = written for the current study design (Chemistry from 2024, Biology from 2022).
 * NHT = Northern Hemisphere Timetable exam (a second paper sat mid-year).
 */
return [
    'chemistry' => [
        'source' => 'https://www.vcaa.vic.edu.au/assessment/vce/examination-specifications-past-examinations-and-examination-reports/chemistry',
        'current_from' => 2024,
        'extras' => [
            ['label' => 'Sample exam for the current course', 'url' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/chemistry-sample-w.pdf'],
            ['label' => 'VCE Chemistry Data Book (2026)', 'url' => 'https://www.vcaa.vic.edu.au/sites/default/files/2026-02/2026-ChemistryDataBook_0.pdf'],
            ['label' => 'Exam specifications (what the exam looks like)', 'url' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-04/chemistry_specs_w.docx'],
        ],
        'papers' => [
            ['year' => 2026, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/2026-07/2026-NHT-Chemistry.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/2026-09/2026-Chemistry-NHT-report.docx', 'current' => true],
            ['year' => 2025, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-12/2025-Chemistry.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/2026-07/2025-Chemistry-report.docx', 'current' => true],
            ['year' => 2025, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-08/2025-NHT-chemistry.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-10/2025-NHT-chemistry-report_0.docx', 'current' => true],
            ['year' => 2024, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-03/2024chemistry-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-10/2024chemistry-report.docx', 'current' => true],
            ['year' => 2024, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2024/NHT/2024chemistry-nht-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2024/NHT/2024NHT-chemistry-report.docx', 'current' => false],
            ['year' => 2023, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2023/2023chemistry-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2023/2023chemistry-report.docx', 'current' => false],
            ['year' => 2023, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2023/NHT/2023chem-nht-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2023/NHT/2023chemistry-NHT-report.docx', 'current' => false],
            ['year' => 2022, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2022/2022chem-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2022/2022chem-report.docx', 'current' => false],
            ['year' => 2022, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2022/NHT/2022ChemistryNHT-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2022/NHT/2022-chemistry-NHT-report.docx', 'current' => false],
            ['year' => 2021, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2021/2021chem-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2021/2021Chemistry-report.docx', 'current' => false],
            ['year' => 2021, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2021/NHT/2021chem-nht-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2021/NHT/2021NHT-Chemistry-exam-report.docx', 'current' => false],
            ['year' => 2020, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2020/2020chem-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2020/2020VCEChemistry-exam-report.docx', 'current' => false],
            ['year' => 2019, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2019/2019chem-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2019/chemistry_examrep19.pdf', 'current' => false],
            ['year' => 2019, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2019/NHT/2019chem-nht-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/2026-03/chemistrynht_examrep19.pdf', 'current' => false],
            ['year' => 2018, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2018/2018chem-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2018/chemistryexamrep18.pdf', 'current' => false],
            ['year' => 2018, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2018/nht/2018chem-nht-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2018/nht/chemistrynht_examrep18.pdf', 'current' => false],
            ['year' => 2017, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2017/2017chem-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2017/chemistry_examrep17.pdf', 'current' => false],
            ['year' => 2017, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2017/nht/2017chem-nht-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2017/nht/chemistrynht_examrep17.pdf', 'current' => false],
            ['year' => 2016, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2016/2016chem-amd-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/chemistry/2016/chemistry_examrep16.pdf', 'current' => false],
        ],
    ],
    'biology' => [
        'source' => 'https://www.vcaa.vic.edu.au/assessment/vce/examination-specifications-past-examinations-and-examination-reports/biology',
        'current_from' => 2022,
        'extras' => [
            ['label' => 'Sample exam for the current course', 'url' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/biology-samp-w.pdf'],
            ['label' => 'Exam specifications (what the exam looks like)', 'url' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-04/biology-specs-w.docx'],
        ],
        'papers' => [
            ['year' => 2025, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-12/2025-Biology.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/2026-02/2025-Biology-report.docx', 'current' => true],
            ['year' => 2025, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-08/2025-NHT-biology.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-10/2025-NHT-biology-report.docx', 'current' => true],
            ['year' => 2024, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-03/2024biology-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-03/2024biology-report.docx', 'current' => true],
            ['year' => 2024, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2024/NHT/2024biology-nht-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2024/NHT/2024NHTbiology-report.docx', 'current' => true],
            ['year' => 2023, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2023/2023biology-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2023/2023biology-report.docx', 'current' => true],
            ['year' => 2023, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2023/NHT/2023biology-nht-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2023/NHT/2023biology%20NHT-report.docx', 'current' => true],
            ['year' => 2022, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2022/2022biology-emark-cpr-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2022/2022Biology-report.docx', 'current' => true],
            ['year' => 2022, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2022/NHT/2022biol-NHT-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2022/NHT/2022biol-NHT-report.docx', 'current' => false],
            ['year' => 2021, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2021/2021biology-cpr-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2021/2021VCEBiology-report.docx', 'current' => false],
            ['year' => 2020, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2020/2020biology-cpr-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/2025-07/2020%20VCE%20Biology%20examination%20report.docx', 'current' => false],
            ['year' => 2019, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2019/2019biology-cpr-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2019/biology_examrep19.pdf', 'current' => false],
            ['year' => 2018, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2018/2018biology-cpr-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2018/biology_examrep18.pdf', 'current' => false],
            ['year' => 2018, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2018/nht/2018biology-nht-cpr-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2018/nht/biologyNHT_examrep18.pdf', 'current' => false],
            ['year' => 2017, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2017/2017biology-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2017/biology_examrep17.pdf', 'current' => false],
            ['year' => 2017, 'nht' => true, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2017/nht/2017biology-nht-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2017/nht/biologynht_examrep17.pdf', 'current' => false],
            ['year' => 2016, 'nht' => false, 'exam' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2016/2016biology-w.pdf', 'report' => 'https://www.vcaa.vic.edu.au/sites/default/files/Documents/exams/biology/2016/biology_examrep16.pdf', 'current' => false],
        ],
    ],
];
