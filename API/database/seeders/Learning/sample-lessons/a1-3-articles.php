<?php

// Content must be reviewed by an English teacher before launch.
// (Khmer text: please also check with a Khmer teacher.)

return [
    'course' => 'grammar-a1',

    'lesson' => [
        'slug' => 'a-an-the',
        'title_en' => 'A, an, the',
        'title_km' => 'ពាក្យនាំមុខនាម a, an, the',
        'summary' => 'Use a or an for one thing we talk about the first time, and the for a thing we both know.',
        'sort_order' => 3,

        'content' => <<<'HTML'
            <h2>Explanation</h2>
            <p>Khmer has no words like <strong>a</strong>, <strong>an</strong> and <strong>the</strong>, so they are easy to forget.</p>
            <p>Use <strong>a</strong> or <strong>an</strong> before one thing (a singular noun) when it is new or not special: <em>a book, an egg</em>. Use <strong>the</strong> when the listener knows which thing you mean: <em>the book on the table</em>.</p>
            <p><strong>A or an?</strong> Listen to the first sound, not the letter: <em>a book, a university</em> (a "you" sound) · <em>an apple, an hour</em> (the h is silent).</p>
            <h2>Form</h2>
            <table><tbody>
            <tr><th><p>Word</p></th><th><p>Use it for</p></th><th><p>Example</p></th></tr>
            <tr><td><p>a</p></td><td><p>one thing, before a consonant sound</p></td><td><p>a bag, a dog, a university</p></td></tr>
            <tr><td><p>an</p></td><td><p>one thing, before a vowel sound</p></td><td><p>an egg, an orange, an hour</p></td></tr>
            <tr><td><p>the</p></td><td><p>a thing we both know; the only one</p></td><td><p>the door, the sun, the Mekong</p></td></tr>
            <tr><td><p>(nothing)</p></td><td><p>many things or food, in general</p></td><td><p>Dogs are friendly. I like rice.</p></td></tr>
            </tbody></table>
            <h2>When to use</h2>
            <ol>
            <li><p>A / an the first time we talk about something — <em>I have a cat.</em></p></li>
            <li><p>The the next time — <em>The cat is black.</em></p></li>
            <li><p>A / an for jobs — <em>She is a doctor.</em></p></li>
            <li><p>The for things there is only one of — <em>The sun is hot.</em></p></li>
            <li><p>No word for things in general — <em>Cats like fish.</em></p></li>
            </ol>
            <h2>Common mistakes</h2>
            <ul>
            <li><p>❌ She is doctor. → ✅ She is a doctor. — jobs need a / an.</p></li>
            <li><p>❌ I have a apple. → ✅ I have an apple. — use <strong>an</strong> before a vowel sound.</p></li>
            <li><p>❌ I like the rice. → ✅ I like rice. — no <strong>the</strong> for food in general.</p></li>
            <li><p>❌ Sun is very hot today. → ✅ The sun is very hot today. — there is only one sun.</p></li>
            <li><p>❌ an university → ✅ a university — university starts with a "you" sound.</p></li>
            </ul>
            HTML,

        'content_km' => <<<'HTML'
            <h2>ការពន្យល់</h2>
            <p>ភាសាខ្មែរមិនមានពាក្យដូច <strong>a</strong>, <strong>an</strong>, <strong>the</strong> ទេ ដូច្នេះវាងាយភ្លេច។</p>
            <ul>
            <li><p><strong>a / an</strong> = «មួយ» — ប្រើមុខនាមឯកវចនៈ ពេលនិយាយពីវាជាលើកដំបូង៖ <em>a book</em> (សៀវភៅមួយ)។</p></li>
            <li><p><strong>the</strong> = «នោះ / នេះ» — ប្រើពេលអ្នកស្តាប់ដឹងថាយើងនិយាយពីវត្ថុណា៖ <em>the book</em> (សៀវភៅនោះ)។</p></li>
            <li><p>ប្រើ <strong>an</strong> មុខសំឡេងស្រៈ (a, e, i, o, u)៖ <em>an egg, an hour</em>។ មើលសំឡេង មិនមែនអក្សរទេ៖ <em>a university</em>។</p></li>
            </ul>
            <h2>កំហុសដែលអ្នកនិយាយខ្មែរតែងធ្វើ</h2>
            <ul>
            <li><p>❌ She is doctor. → ✅ She is a doctor. — មុខរបរត្រូវការ a / an។</p></li>
            <li><p>❌ I have a apple. → ✅ I have an apple. — ប្រើ an មុខសំឡេងស្រៈ។</p></li>
            <li><p>❌ Sun is hot. → ✅ The sun is hot. — ព្រះអាទិត្យមានតែមួយ ដូច្នេះប្រើ the។</p></li>
            </ul>
            HTML,
    ],

    'examples' => [
        ['I have a brother and a sister.', 'ខ្ញុំមានបងប្រុសម្នាក់ និងបងស្រីម្នាក់។'],
        ['She eats an egg every morning.', 'នាងញ៉ាំពងមាន់មួយរៀងរាល់ព្រឹក។'],
        ['He is an engineer.', 'គាត់ជាវិស្វករ។'],
        ['My mother is a teacher.', 'ម្តាយខ្ញុំជាគ្រូបង្រៀន។'],
        ['I have a bag. The bag is blue.', 'ខ្ញុំមានកាបូបមួយ។ កាបូបនោះពណ៌ខៀវ។'],
        ['Please close the door.', 'សូមបិទទ្វារ។'],
        ['The Mekong is a long river.', 'ទន្លេមេគង្គជាទន្លេវែងមួយ។'],
        ['The class is an hour long.', 'ថ្នាក់រៀនមានរយៈពេលមួយម៉ោង។'],
        ['There is a university in my town.', 'មានសាកលវិទ្យាល័យមួយនៅក្នុងទីក្រុងរបស់ខ្ញុំ។'],
        ['I like mangoes.', 'ខ្ញុំចូលចិត្តផ្លែស្វាយ។'],
    ],

    'quiz' => [
        'title' => 'Quiz: a, an, the',
        'questions' => [
            [
                'type' => 'multiple_choice',
                'prompt' => 'She is ___ nurse.',
                'options' => ['a' => true, 'an' => false, 'the' => false],
                'explanation' => 'Jobs use a / an. Nurse starts with a consonant sound: a nurse.',
                'explanation_km' => 'មុខរបរប្រើ a / an។ Nurse ចាប់ផ្តើមដោយសំឡេងព្យញ្ជនៈ៖ a nurse។',
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'I eat ___ orange every day.',
                'options' => ['an' => true, 'a' => false, 'the' => false],
                'explanation' => 'Orange starts with a vowel sound: an orange.',
                'explanation_km' => 'Orange ចាប់ផ្តើមដោយសំឡេងស្រៈ៖ an orange។',
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'Can you open ___ window, please?',
                'options' => ['the' => true, 'a' => false, 'an' => false],
                'explanation' => 'We both know which window: the window.',
                'explanation_km' => 'យើងទាំងពីរដឹងថាបង្អួចមួយណា៖ the window។',
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'He studies at ___ university in Phnom Penh.',
                'options' => ['a' => true, 'an' => false, 'the' => false],
                'explanation' => 'University starts with a "you" sound, so: a university.',
                'explanation_km' => 'University ចាប់ផ្តើមដោយសំឡេង «យូ» ដូច្នេះ៖ a university។',
            ],
            [
                'type' => 'true_false',
                'prompt' => '"The class is an hour long." is a correct sentence.',
                'correct' => true,
                'explanation' => 'The h in hour is silent, so it starts with a vowel sound: an hour.',
                'explanation_km' => 'អក្សរ h ក្នុង hour មិនបញ្ចេញសំឡេង ដូច្នេះវាចាប់ផ្តើមដោយសំឡេងស្រៈ៖ an hour។',
            ],
            [
                'type' => 'true_false',
                'prompt' => '"Moon is beautiful tonight." is a correct sentence.',
                'correct' => false,
                'explanation' => 'There is only one moon: The moon is beautiful tonight.',
                'explanation_km' => 'ព្រះចន្ទមានតែមួយ៖ The moon is beautiful tonight។',
            ],
            [
                'type' => 'fill_blank',
                'prompt' => 'My father is ___ engineer.',
                'answers' => ['an'],
                'explanation' => 'Engineer starts with a vowel sound: an engineer.',
                'explanation_km' => 'Engineer ចាប់ផ្តើមដោយសំឡេងស្រៈ៖ an engineer។',
            ],
            [
                'type' => 'fill_blank',
                'prompt' => 'I have a dog. ___ dog is very friendly.',
                'answers' => ['the'],
                'explanation' => 'The second time we talk about the dog, we use the.',
                'explanation_km' => 'លើកទីពីរដែលយើងនិយាយពីឆ្កែនោះ យើងប្រើ the។',
            ],
            [
                'type' => 'word_order',
                'prompt' => 'Put the words in order.',
                'answers' => ['She has an old bicycle.'],
                'explanation' => 'Old starts with a vowel sound, so: an old bicycle.',
                'explanation_km' => 'Old ចាប់ផ្តើមដោយសំឡេងស្រៈ ដូច្នេះ៖ an old bicycle។',
            ],
            [
                'type' => 'find_error',
                'prompt' => 'My brother is a artist.',
                'options' => ['My brother' => false, 'is' => false, 'a' => true, 'artist.' => false],
                'explanation' => 'Artist starts with a vowel sound: an artist.',
                'explanation_km' => 'Artist ចាប់ផ្តើមដោយសំឡេងស្រៈ៖ an artist។',
            ],
        ],
    ],
];
