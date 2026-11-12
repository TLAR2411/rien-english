<?php

// Content must be reviewed by an English teacher before launch.
// (Khmer text: please also check with a Khmer teacher.)

return [
    'course' => 'english-for-beginners',

    'lesson' => [
        'skill' => 'reading',
        'slug' => 'reading-my-family',
        'title_en' => 'Reading: My family',
        'title_km' => 'ការអាន៖ គ្រួសាររបស់ខ្ញុំ',
        'summary' => 'Read a short text about Sokha and her family, then answer the questions.',
        'sort_order' => 5,

        'content' => <<<'HTML'
            <h2>Before you read</h2>
            <p>This text uses words from <strong>Family words</strong> and the grammar from <strong>To be</strong> and <strong>Present Simple</strong>.</p>
            <p>Look at the <strong>Words to know</strong> under the text first. Then read the text two times.</p>
            <h2>Read the text</h2>
            <p>Hello! My name is Sokha. I am fifteen years old and I live in Battambang. There are five people in my family.</p>
            <p>My father is a farmer. He works in the rice field every day. My mother is a teacher at a primary school. She gets up at five o'clock and makes breakfast for us.</p>
            <p>I have one older brother and one younger sister. My brother is nineteen. He studies at a university in Phnom Penh, so he comes home only at the weekend. My sister is eight. She likes drawing and playing with our cat.</p>
            <p>My grandparents live near our house. On Sundays we eat lunch together. I love my family!</p>
            HTML,

        'content_km' => <<<'HTML'
            <h2>ការណែនាំ</h2>
            <p>អានអត្ថបទជាភាសាអង់គ្លេសម្តងពីរដង។ ព្យាយាមយល់អត្ថន័យទូទៅសិន មុននឹងមើលពាក្យថ្មី។ បន្ទាប់មកធ្វើលំហាត់ដើម្បីពិនិត្យថាអ្នកយល់។</p>
            HTML,
    ],

    // "Words to know" — shown under the text
    'words' => [
        ['farmer', 'a person who grows rice or other food', 'កសិករ', 'My father is a farmer.'],
        ['rice field', 'land where rice grows', 'វាលស្រែ', 'He works in the rice field every day.'],
        ['primary school', 'a school for children from 6 to 12', 'សាលាបឋមសិក្សា', 'My mother is a teacher at a primary school.'],
        ['get up', 'leave your bed in the morning', 'ក្រោកពីដំណេក', "She gets up at five o'clock."],
        ['university', 'a place where you study after high school', 'សាកលវិទ្យាល័យ', 'He studies at a university in Phnom Penh.'],
        ['at the weekend', 'on Saturday and Sunday', 'នៅចុងសប្តាហ៍', 'He comes home only at the weekend.'],
        ['grandparents', 'the parents of your mother or father', 'ជីដូនជីតា', 'My grandparents live near our house.'],
        ['together', 'with each other', 'ជាមួយគ្នា', 'On Sundays we eat lunch together.'],
    ],

    'examples' => [
        ['There are five people in my family.', 'គ្រួសាររបស់ខ្ញុំមានមនុស្សប្រាំនាក់។'],
        ['He works in the rice field every day.', 'គាត់ធ្វើការនៅវាលស្រែរៀងរាល់ថ្ងៃ។'],
        ['He comes home only at the weekend.', 'គាត់មកផ្ទះតែនៅចុងសប្តាហ៍ប៉ុណ្ណោះ។'],
        ['On Sundays we eat lunch together.', 'នៅថ្ងៃអាទិត្យ យើងញ៉ាំអាហារថ្ងៃត្រង់ជាមួយគ្នា។'],
    ],

    'quiz' => [
        'title' => 'Quiz: My family',
        'questions' => [
            [
                'type' => 'multiple_choice',
                'prompt' => 'How many people are there in Sokha\'s family?',
                'options' => ['Five' => true, 'Four' => false, 'Seven' => false],
                'explanation' => 'The text says: "There are five people in my family."',
                'explanation_km' => 'អត្ថបទនិយាយថា៖ «There are five people in my family.»',
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'What is Sokha\'s mother\'s job?',
                'options' => ['She is a teacher.' => true, 'She is a farmer.' => false, 'She is a nurse.' => false],
                'explanation' => 'Her mother is a teacher at a primary school. Her father is the farmer.',
                'explanation_km' => 'ម្តាយរបស់នាងជាគ្រូនៅសាលាបឋមសិក្សា។ ឪពុករបស់នាងជាកសិករ។',
            ],
            [
                'type' => 'true_false',
                'prompt' => 'Sokha\'s brother lives at home every day.',
                'correct' => false,
                'explanation' => 'He studies in Phnom Penh and comes home only at the weekend.',
                'explanation_km' => 'គាត់រៀននៅភ្នំពេញ ហើយមកផ្ទះតែនៅចុងសប្តាហ៍ប៉ុណ្ណោះ។',
            ],
            [
                'type' => 'true_false',
                'prompt' => 'Sokha\'s sister is younger than Sokha.',
                'correct' => true,
                'explanation' => 'Sokha is fifteen and her sister is eight.',
                'explanation_km' => 'សុខាអាយុដប់ប្រាំឆ្នាំ ហើយប្អូនស្រីរបស់នាងអាយុប្រាំបីឆ្នាំ។',
            ],
            [
                'type' => 'fill_blank',
                'prompt' => 'Sokha lives in ___.',
                'answers' => ['Battambang'],
                'explanation' => 'The text says: "I live in Battambang."',
                'explanation_km' => 'អត្ថបទនិយាយថា៖ «I live in Battambang.»',
            ],
        ],
    ],
];
