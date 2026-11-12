<?php

// Content must be reviewed by an English teacher before launch.
// (Khmer text: please also check with a Khmer teacher.)

return [
    'course' => 'english-for-beginners',

    'lesson' => [
        'skill' => 'writing',
        'slug' => 'writing-about-my-family',
        'title_en' => 'Writing: About my family',
        'title_km' => 'ការសរសេរ៖ អំពីគ្រួសាររបស់ខ្ញុំ',
        'summary' => 'Write a short text about your family. A teacher reads it and gives you feedback.',
        'sort_order' => 7,

        'content' => <<<'HTML'
            <h2>Your task</h2>
            <p>Write about your family. Say who is in your family, what they do, and what they like. Write <strong>50 to 100 words</strong>.</p>
            <p>Read <strong>Reading: My family</strong> first — Sokha's text is a good model.</p>
            <h2>Useful sentences</h2>
            <ul>
            <li><p>There are <em>four</em> people in my family.</p></li>
            <li><p>My father is a <em>driver</em>. He works <em>in Phnom Penh</em>.</p></li>
            <li><p>My sister is <em>ten</em>. She likes <em>reading</em>.</p></li>
            <li><p>On Sundays we <em>visit my grandparents</em>.</p></li>
            </ul>
            HTML,

        'content_km' => <<<'HTML'
            <h2>ការណែនាំ</h2>
            <p>សរសេរអំពីគ្រួសាររបស់អ្នក៖ មានអ្នកណាខ្លះ ពួកគាត់ធ្វើការអ្វី និងចូលចិត្តអ្វី។ សរសេរពី ៥០ ទៅ ១០០ ពាក្យ។ ប្រើប្រយោគខ្លីៗ និងពាក្យដែលអ្នកបានរៀន។</p>
            <p>បន្ទាប់ពីផ្ញើ អ្នកនឹងឃើញចម្លើយគំរូ ហើយគ្រូនឹងអាន និងផ្តល់មតិយោបល់។</p>
            HTML,

        'checklist' => [
            'Say how many people are in your family.',
            'Write the job or the age of each person.',
            'Use is / are correctly (my father is, my parents are).',
            'Use the Present Simple: he works, she likes.',
            'Start each sentence with a capital letter and end it with a full stop.',
        ],
        'min_words' => 50,
        'max_words' => 100,

        'sample_answer' => <<<'HTML'
            <p>My name is Dara. There are four people in my family. My father is a driver. He works in Phnom Penh, and he gets up very early. My mother is a nurse. She works at a hospital near our house. My brother is twelve. He likes football and games. I am fifteen and I like English. On Sundays we visit my grandparents. They live in Kandal. I love my family very much.</p>
            HTML,
    ],

    'examples' => [
        ['There are four people in my family.', 'គ្រួសាររបស់ខ្ញុំមានមនុស្សបួននាក់។'],
        ['My mother is a nurse.', 'ម្តាយរបស់ខ្ញុំជាគិលានុបដ្ឋាយិកា។'],
        ['My brother likes football.', 'ប្អូនប្រុសរបស់ខ្ញុំចូលចិត្តបាល់ទាត់។'],
    ],
];
