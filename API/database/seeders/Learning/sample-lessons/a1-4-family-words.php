<?php

// Content must be reviewed by an English teacher before launch.
// (Khmer text: please also check with a Khmer teacher.)

return [
    'course' => 'english-for-beginners',

    'lesson' => [
        'skill' => 'vocabulary',
        'slug' => 'family-words',
        'title_en' => 'Family words',
        'title_km' => 'ពាក្យអំពីគ្រួសារ',
        'summary' => 'Learn the words for the people in your family: mother, father, brother, sister and more.',
        'sort_order' => 4,

        'content' => <<<'HTML'
            <h2>New words</h2>
            <table><tbody>
            <tr><th><p>English</p></th><th><p>Khmer</p></th></tr>
            <tr><td><p>mother / mom</p></td><td><p>ម្តាយ / ម៉ាក់</p></td></tr>
            <tr><td><p>father / dad</p></td><td><p>ឪពុក / ប៉ា</p></td></tr>
            <tr><td><p>parents</p></td><td><p>ឪពុកម្តាយ</p></td></tr>
            <tr><td><p>brother</p></td><td><p>បងប្រុស / ប្អូនប្រុស</p></td></tr>
            <tr><td><p>sister</p></td><td><p>បងស្រី / ប្អូនស្រី</p></td></tr>
            <tr><td><p>grandmother</p></td><td><p>យាយ</p></td></tr>
            <tr><td><p>grandfather</p></td><td><p>តា</p></td></tr>
            <tr><td><p>son</p></td><td><p>កូនប្រុស</p></td></tr>
            <tr><td><p>daughter</p></td><td><p>កូនស្រី</p></td></tr>
            <tr><td><p>uncle</p></td><td><p>ពូ / មា</p></td></tr>
            <tr><td><p>aunt</p></td><td><p>មីង / ម្តាយមីង</p></td></tr>
            <tr><td><p>cousin</p></td><td><p>បងប្អូនជីដូនមួយ</p></td></tr>
            </tbody></table>
            <h2>Good to know</h2>
            <ul>
            <li><p>Khmer has different words for older and younger (បង / ប្អូន). English uses one word: <strong>brother</strong> or <strong>sister</strong>. To be clear, say <em>older brother</em> or <em>younger sister</em>.</p></li>
            <li><p><strong>Parents</strong> = mother + father. <strong>Grandparents</strong> = grandmother + grandfather.</p></li>
            <li><p>Use <strong>'s</strong> to show who: <em>my mother's sister</em> = my aunt.</p></li>
            </ul>
            HTML,

        'content_km' => <<<'HTML'
            <h2>ចំណាំ</h2>
            <ul>
            <li><p>ភាសាខ្មែរមានពាក្យ «បង» និង «ប្អូន» ប៉ុន្តែភាសាអង់គ្លេសប្រើពាក្យតែមួយ៖ <strong>brother</strong> ឬ <strong>sister</strong>។ បើចង់ឱ្យច្បាស់ និយាយ <em>older brother</em> (បងប្រុស) ឬ <em>younger sister</em> (ប្អូនស្រី)។</p></li>
            <li><p><strong>Parents</strong> = ឪពុក និងម្តាយ។ <strong>Grandparents</strong> = យាយ និងតា។</p></li>
            </ul>
            HTML,
    ],

    'examples' => [
        ['This is my mother.', 'នេះជាម្តាយរបស់ខ្ញុំ។'],
        ['My father is a farmer.', 'ឪពុករបស់ខ្ញុំជាកសិករ។'],
        ['I have two older brothers.', 'ខ្ញុំមានបងប្រុសពីរនាក់។'],
        ['My younger sister is ten.', 'ប្អូនស្រីរបស់ខ្ញុំអាយុដប់ឆ្នាំ។'],
        ['My grandparents live in Siem Reap.', 'យាយតារបស់ខ្ញុំរស់នៅសៀមរាប។'],
        ['My aunt has a son and a daughter.', 'មីងរបស់ខ្ញុំមានកូនប្រុសម្នាក់ និងកូនស្រីម្នាក់។'],
    ],

    'quiz' => [
        'title' => 'Quiz: family words',
        'questions' => [
            [
                'type' => 'multiple_choice',
                'prompt' => 'My mother\'s mother is my ___.',
                'options' => ['grandmother' => true, 'aunt' => false, 'sister' => false],
                'explanation' => 'Your mother\'s mother (or your father\'s mother) is your grandmother.',
                'explanation_km' => 'ម្តាយរបស់ម្តាយ (ឬម្តាយរបស់ឪពុក) គឺជាយាយរបស់អ្នក។',
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'My father\'s brother is my ___.',
                'options' => ['uncle' => true, 'cousin' => false, 'son' => false],
                'explanation' => 'The brother of your mother or father is your uncle.',
                'explanation_km' => 'បងប្អូនប្រុសរបស់ម្តាយ ឬឪពុក គឺជាពូ ឬមារបស់អ្នក។',
            ],
            [
                'type' => 'true_false',
                'prompt' => '"Parents" means your mother and your father.',
                'correct' => true,
                'explanation' => 'Parents = mother + father.',
                'explanation_km' => 'Parents = ម្តាយ និងឪពុក។',
            ],
            [
                'type' => 'fill_blank',
                'prompt' => 'My aunt\'s children are my ___.',
                'answers' => ['cousins', 'cousin'],
                'explanation' => 'The children of your aunt or uncle are your cousins.',
                'explanation_km' => 'កូនរបស់មីង ឬពូ គឺជាបងប្អូនជីដូនមួយរបស់អ្នក។',
            ],
            [
                'type' => 'word_order',
                'prompt' => 'Put the words in order.',
                'answers' => ['I have one younger sister.'],
                'explanation' => 'Younger goes before sister: one younger sister.',
                'explanation_km' => 'Younger នៅមុខ sister៖ one younger sister។',
            ],
        ],
    ],
];
