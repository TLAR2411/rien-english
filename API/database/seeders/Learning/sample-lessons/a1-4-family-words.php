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
            <p>Learn the words with the <strong>flashcards</strong>: tap a card to see the meaning. Then play the <strong>matching game</strong>.</p>
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

    // Flashcards + matching game: [word, simple English meaning, Khmer, example]
    'words' => [
        ['mother', 'the woman who is your parent', 'ម្តាយ / ម៉ាក់', 'This is my mother.'],
        ['father', 'the man who is your parent', 'ឪពុក / ប៉ា', 'My father is a farmer.'],
        ['parents', 'your mother and your father', 'ឪពុកម្តាយ', 'My parents live in Battambang.'],
        ['brother', 'a boy or man with the same parents as you', 'បងប្រុស / ប្អូនប្រុស', 'I have two older brothers.'],
        ['sister', 'a girl or woman with the same parents as you', 'បងស្រី / ប្អូនស្រី', 'My younger sister is ten.'],
        ['grandmother', 'the mother of your mother or father', 'យាយ', 'My grandmother tells good stories.'],
        ['grandfather', 'the father of your mother or father', 'តា', 'My grandfather is seventy.'],
        ['son', 'a boy child of a mother or father', 'កូនប្រុស', 'My aunt has a son.'],
        ['daughter', 'a girl child of a mother or father', 'កូនស្រី', 'They have one daughter.'],
        ['uncle', 'the brother of your mother or father', 'ពូ / មា', 'My uncle is a doctor.'],
        ['aunt', 'the sister of your mother or father', 'មីង / ម្តាយមីង', 'My aunt has a son and a daughter.'],
        ['cousin', 'the child of your uncle or aunt', 'បងប្អូនជីដូនមួយ', 'My cousin lives in Phnom Penh.'],
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
