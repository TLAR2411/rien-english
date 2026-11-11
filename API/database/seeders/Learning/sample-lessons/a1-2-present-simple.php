<?php

// Content must be reviewed by an English teacher before launch.
// (Khmer text: please also check with a Khmer teacher.)

return [
    'course' => 'grammar-a1',

    'lesson' => [
        'slug' => 'present-simple',
        'title_en' => 'Present Simple',
        'title_km' => 'បច្ចុប្បន្នកាលសាមញ្ញ (Present Simple)',
        'summary' => 'Talk about habits, routines and facts: I work, she works.',
        'sort_order' => 2,

        'content' => <<<'HTML'
            <h2>Explanation</h2>
            <p>We use the <strong>Present Simple</strong> for things that happen again and again (habits and routines) and for things that are always true.</p>
            <p>The verb is the same for I, you, we and they. With <strong>he, she and it</strong> we add <strong>-s</strong> or <strong>-es</strong>: <em>she works, he watches</em>.</p>
            <p>In Khmer the verb never changes. In English, remember the <strong>-s</strong> with he / she / it.</p>
            <h2>Form</h2>
            <table><tbody>
            <tr><th><p></p></th><th><p>Positive</p></th><th><p>Negative</p></th><th><p>Question</p></th></tr>
            <tr><td><p>I / you / we / they</p></td><td><p>I work.</p></td><td><p>I don't work.</p></td><td><p>Do you work?</p></td></tr>
            <tr><td><p>he / she / it</p></td><td><p>She works.</p></td><td><p>She doesn't work.</p></td><td><p>Does she work?</p></td></tr>
            </tbody></table>
            <p><strong>Short answers:</strong> Yes, I do. / No, I don't. · Yes, she does. / No, she doesn't.</p>
            <p><strong>Spelling with he / she / it:</strong> work → works · watch → watches · go → goes · study → studies · have → has</p>
            <h2>When to use</h2>
            <ol>
            <li><p>Habits — <em>I get up at six o'clock.</em></p></li>
            <li><p>Timetables — <em>The bus leaves at 7:30.</em></p></li>
            <li><p>Facts — <em>Water boils at 100 °C.</em></p></li>
            <li><p>Likes and dislikes — <em>She likes mangoes.</em></p></li>
            <li><p>Jobs and where people live — <em>My father works in Phnom Penh.</em></p></li>
            </ol>
            <h2>Common mistakes</h2>
            <ul>
            <li><p>❌ She work in a bank. → ✅ She works in a bank. — add <strong>-s</strong> with he / she / it.</p></li>
            <li><p>❌ He don't like coffee. → ✅ He doesn't like coffee. — use <strong>doesn't</strong> with he / she / it.</p></li>
            <li><p>❌ Does she works here? → ✅ Does she work here? — after does / doesn't, no -s.</p></li>
            <li><p>❌ I am go to school every day. → ✅ I go to school every day. — do not add am.</p></li>
            <li><p>❌ My mother cook every day. → ✅ My mother cooks every day. — my mother = she.</p></li>
            </ul>
            HTML,

        'content_km' => <<<'HTML'
            <h2>ការពន្យល់</h2>
            <p>យើងប្រើ <strong>Present Simple</strong> សម្រាប់អ្វីដែលកើតឡើងម្តងហើយម្តងទៀត (ទម្លាប់ប្រចាំថ្ងៃ) និងសម្រាប់ការពិតដែលត្រឹមត្រូវជានិច្ច។</p>
            <p>កិរិយាសព្ទដូចគ្នាសម្រាប់ I, you, we, they។ ជាមួយ <strong>he, she, it</strong> យើងបន្ថែម <strong>-s</strong> ឬ <strong>-es</strong>៖ <em>she works, he watches</em>។</p>
            <p>ក្នុងភាសាខ្មែរ កិរិយាសព្ទមិនប្តូរទេ។ ក្នុងភាសាអង់គ្លេស កុំភ្លេច <strong>-s</strong> ជាមួយ he / she / it។</p>
            <h2>កំហុសដែលអ្នកនិយាយខ្មែរតែងធ្វើ</h2>
            <ul>
            <li><p>❌ She work in a bank. → ✅ She works in a bank. — បន្ថែម -s ជាមួយ he / she / it។</p></li>
            <li><p>❌ He don't like coffee. → ✅ He doesn't like coffee. — ប្រើ doesn't ជាមួយ he / she / it។</p></li>
            <li><p>❌ I am go to school. → ✅ I go to school. — កុំបន្ថែម am។</p></li>
            </ul>
            HTML,
    ],

    'examples' => [
        ['I get up at six o\'clock.', 'ខ្ញុំក្រោកពីដំណេកនៅម៉ោងប្រាំមួយ។'],
        ['She works in a bank.', 'នាងធ្វើការនៅធនាគារ។'],
        ['We go to school by bike.', 'យើងទៅសាលាដោយកង់។'],
        ['My father drinks coffee every morning.', 'ឪពុកខ្ញុំផឹកកាហ្វេរៀងរាល់ព្រឹក។'],
        ['They don\'t eat meat.', 'ពួកគេមិនញ៉ាំសាច់ទេ។'],
        ['He doesn\'t watch TV at night.', 'គាត់មិនមើលទូរទស្សន៍នៅពេលយប់ទេ។'],
        ['Do you like mangoes?', 'តើអ្នកចូលចិត្តផ្លែស្វាយទេ?'],
        ['Does your sister study English?', 'តើប្អូនស្រីរបស់អ្នករៀនភាសាអង់គ្លេសទេ?'],
        ['The sun rises in the east.', 'ព្រះអាទិត្យរះនៅទិសខាងកើត។'],
        ['The market opens at five in the morning.', 'ផ្សារបើកនៅម៉ោងប្រាំព្រឹក។'],
    ],

    'quiz' => [
        'title' => 'Quiz: Present Simple',
        'questions' => [
            [
                'type' => 'multiple_choice',
                'prompt' => 'She ___ in a hospital.',
                'options' => ['works' => true, 'work' => false, 'working' => false],
                'explanation' => 'With he / she / it, add -s: she works.',
                'explanation_km' => 'ជាមួយ he / she / it បន្ថែម -s៖ she works។',
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'We ___ football on Sundays.',
                'options' => ['play' => true, 'plays' => false, 'playing' => false],
                'explanation' => 'With we, the verb has no -s.',
                'explanation_km' => 'ជាមួយ we កិរិយាសព្ទគ្មាន -s ទេ។',
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'My brother ___ like fish.',
                'options' => ['doesn\'t' => true, 'don\'t' => false, 'isn\'t' => false],
                'explanation' => 'My brother = he. Negative: doesn\'t + verb.',
                'explanation_km' => 'My brother = he។ អវិជ្ជមាន៖ doesn\'t + កិរិយាសព្ទ។',
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => '___ they live in Battambang?',
                'options' => ['Do' => true, 'Does' => false, 'Are' => false],
                'explanation' => 'Questions with they start with Do.',
                'explanation_km' => 'សំណួរជាមួយ they ចាប់ផ្តើមដោយ Do។',
            ],
            [
                'type' => 'true_false',
                'prompt' => '"Does she works here?" is a correct sentence.',
                'correct' => false,
                'explanation' => 'After does, the verb has no -s: Does she work here?',
                'explanation_km' => 'បន្ទាប់ពី does កិរិយាសព្ទគ្មាន -s ទេ៖ Does she work here?',
            ],
            [
                'type' => 'true_false',
                'prompt' => '"The shop opens at 8 o\'clock." is a correct sentence.',
                'correct' => true,
                'explanation' => 'The shop = it, so opens (with -s) is correct.',
                'explanation_km' => 'The shop = it ដូច្នេះ opens (មាន -s) ត្រឹមត្រូវ។',
            ],
            [
                'type' => 'fill_blank',
                'prompt' => 'He ___ (watch) TV every evening.',
                'answers' => ['watches'],
                'explanation' => 'Verbs that end in -ch add -es: watches.',
                'explanation_km' => 'កិរិយាសព្ទដែលបញ្ចប់ដោយ -ch បន្ថែម -es៖ watches។',
            ],
            [
                'type' => 'fill_blank',
                'prompt' => 'I ___ (not / drink) coffee.',
                'answers' => ['don\'t drink', 'do not drink'],
                'explanation' => 'With I, the negative is don\'t + verb.',
                'explanation_km' => 'ជាមួយ I អវិជ្ជមានគឺ don\'t + កិរិយាសព្ទ។',
            ],
            [
                'type' => 'word_order',
                'prompt' => 'Put the words in order.',
                'answers' => ['My mother cooks dinner every day.'],
                'explanation' => 'Who + verb (+ -s) + what + when.',
                'explanation_km' => 'នរណា + កិរិយាសព្ទ (+ -s) + អ្វី + ពេលណា។',
            ],
            [
                'type' => 'find_error',
                'prompt' => 'My sister study English every day.',
                'options' => ['My sister' => false, 'study' => true, 'English' => false, 'every day.' => false],
                'explanation' => 'My sister = she, so: studies.',
                'explanation_km' => 'My sister = she ដូច្នេះ៖ studies។',
            ],
        ],
    ],
];
