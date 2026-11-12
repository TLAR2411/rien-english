<?php

// Content must be reviewed by an English teacher before launch.
// (Khmer text: please also check with a Khmer teacher.)
// The sample audio (sample-media/at-the-market.m4a) was made with a computer voice —
// replace it with a real recording before launch.

return [
    'course' => 'english-for-beginners',

    'audio' => 'at-the-market.m4a',

    'lesson' => [
        'skill' => 'listening',
        'slug' => 'listening-at-the-market',
        'title_en' => 'Listening: At the market',
        'title_km' => 'ការស្តាប់៖ នៅផ្សារ',
        'summary' => 'Listen to Dara buying fruit at the market, then answer the questions.',
        'sort_order' => 6,

        'content' => <<<'HTML'
            <h2>Before you listen</h2>
            <p>Dara is at the market. She wants to buy some fruit. Listen and find out: <strong>what fruit does she buy, and how much does she pay?</strong></p>
            <h2>How to listen</h2>
            <ol>
            <li><p>Listen one time. Do not read the transcript yet.</p></li>
            <li><p>Listen again at <strong>0.75x</strong> speed. Listen for numbers and fruit.</p></li>
            <li><p>Do the quiz. Then read the transcript and listen one more time.</p></li>
            </ol>
            HTML,

        'content_km' => <<<'HTML'
            <h2>ការណែនាំ</h2>
            <p>ដារ៉ានៅផ្សារ។ នាងចង់ទិញផ្លែឈើ។ ស្តាប់ ហើយរកមើលថា នាងទិញផ្លែឈើអ្វីខ្លះ ហើយបង់លុយប៉ុន្មាន។</p>
            <ol>
            <li><p>ស្តាប់ម្តងសិន។ កុំទាន់អានអត្ថបទ។</p></li>
            <li><p>ស្តាប់ម្តងទៀតក្នុងល្បឿន <strong>0.75x</strong>។ ស្តាប់រកលេខ និងឈ្មោះផ្លែឈើ។</p></li>
            <li><p>ធ្វើលំហាត់។ បន្ទាប់មកអានអត្ថបទ ហើយស្តាប់ម្តងទៀត។</p></li>
            </ol>
            HTML,

        'transcript' => <<<'HTML'
            <p><strong>Seller:</strong> Good morning! Can I help you?</p>
            <p><strong>Dara:</strong> Good morning. How much are the mangoes?</p>
            <p><strong>Seller:</strong> They are two thousand riels a kilo.</p>
            <p><strong>Dara:</strong> OK. Can I have two kilos, please?</p>
            <p><strong>Seller:</strong> Sure. Anything else?</p>
            <p><strong>Dara:</strong> Yes. Do you have bananas?</p>
            <p><strong>Seller:</strong> Sorry, no bananas today. But we have oranges.</p>
            <p><strong>Dara:</strong> Then one kilo of oranges, please. How much is that?</p>
            <p><strong>Seller:</strong> The oranges are three thousand riels. So that's seven thousand riels, please.</p>
            <p><strong>Dara:</strong> Here you are. Thank you!</p>
            <p><strong>Seller:</strong> Thank you. Have a nice day!</p>
            HTML,
    ],

    'words' => [
        ['mango', 'a sweet yellow fruit', 'ស្វាយ', 'How much are the mangoes?'],
        ['kilo', 'one kilogram (1,000 grams)', 'គីឡូ', 'Can I have two kilos, please?'],
        ['How much …?', 'a question about price', 'ប៉ុន្មាន?', 'How much is that?'],
        ['Anything else?', 'Do you want something more?', 'ចង់បានអ្វីទៀតទេ?', 'Sure. Anything else?'],
        ['Here you are.', 'what you say when you give something', 'នេះ / យកទៅ', 'Here you are. Thank you!'],
        ['Have a nice day!', 'a friendly goodbye', 'សូមឲ្យមានថ្ងៃល្អ!', 'Thank you. Have a nice day!'],
    ],

    'examples' => [
        ['How much are the mangoes?', 'ស្វាយប៉ុន្មាន?'],
        ['Can I have two kilos, please?', 'ខ្ញុំសុំពីរគីឡូបានទេ?'],
        ['Sorry, no bananas today.', 'សុំទោស ថ្ងៃនេះគ្មានចេកទេ។'],
        ['That\'s seven thousand riels, please.', 'សរុបប្រាំពីរពាន់រៀល។'],
    ],

    'quiz' => [
        'title' => 'Quiz: At the market',
        'questions' => [
            [
                'type' => 'multiple_choice',
                'prompt' => 'What does Dara ask about first?',
                'options' => ['Mangoes' => true, 'Bananas' => false, 'Oranges' => false],
                'explanation' => 'Dara says: "How much are the mangoes?"',
                'explanation_km' => 'ដារ៉ាសួរថា៖ «How much are the mangoes?»',
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'How much is one kilo of mangoes?',
                'options' => ['2,000 riels' => true, '3,000 riels' => false, '7,000 riels' => false],
                'explanation' => 'The seller says: "They are two thousand riels a kilo."',
                'explanation_km' => 'អ្នកលក់និយាយថា៖ «They are two thousand riels a kilo.»',
            ],
            [
                'type' => 'true_false',
                'prompt' => 'The seller has bananas today.',
                'correct' => false,
                'explanation' => 'The seller says: "Sorry, no bananas today."',
                'explanation_km' => 'អ្នកលក់និយាយថា ថ្ងៃនេះគ្មានចេកទេ។',
            ],
            [
                'type' => 'true_false',
                'prompt' => 'Dara buys one kilo of oranges.',
                'correct' => true,
                'explanation' => 'Dara says: "Then one kilo of oranges, please."',
                'explanation_km' => 'ដារ៉ានិយាយថា៖ «Then one kilo of oranges, please.»',
            ],
            [
                'type' => 'fill_blank',
                'prompt' => 'Dara pays ___ thousand riels.',
                'answers' => ['seven', '7'],
                'explanation' => 'Two kilos of mangoes (4,000) + one kilo of oranges (3,000) = 7,000 riels.',
                'explanation_km' => 'ស្វាយពីរគីឡូ (៤ ០០០) + ក្រូចមួយគីឡូ (៣ ០០០) = ៧ ០០០ រៀល។',
            ],
            [
                'type' => 'word_order',
                'prompt' => 'Put the words in the right order.',
                'answers' => ['Can I have two kilos, please?'],
                'explanation' => 'A polite question to buy something: Can I have … , please?',
                'explanation_km' => 'សំណួរគួរសមពេលទិញអ្វីមួយ៖ Can I have … , please?',
            ],
        ],
    ],
];
