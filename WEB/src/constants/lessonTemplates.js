/*
 * Lesson templates: Grammar (PLAN.md → Appendix A), Reading (T31), Listening (T32),
 * Writing (T33), Vocabulary (T35).
 * The editor shows the sections as a guide and can insert the starting text.
 * Change the wording here; it is used for every new lesson of that skill.
 *
 * `heading` — the section's heading in the lesson text (used to tick it off
 *             in the guide). Sections without one are filled in elsewhere.
 * `field`   — a lesson field that ticks the section off once it is filled in.
 * `where`   — where the teacher fills it in (shown in the guide).
 */
export const GRAMMAR_SECTIONS = [
  { title: "Title and level", hint: "English + Khmer title, and the course (level)", where: "Details" },
  { title: "Explanation", hint: "Simple English + Khmer explanation", heading: "Explanation" },
  { title: "Form", hint: "Table: positive / negative / question (+ short answers)", heading: "Form" },
  { title: "When to use", hint: "3–5 uses, each with an example", heading: "When to use" },
  { title: "Examples", hint: "8–10 sentences with Khmer translation and audio", where: "Examples" },
  { title: "Common mistakes", hint: "❌ wrong → ✅ correct, with a short reason", heading: "Common mistakes" },
  { title: "Quiz", hint: "10–15 questions, mixed types, each with an explanation", where: "Quiz" },
];

const table = (head, rows) =>
  `<table><tbody><tr>${head.map((cell) => `<th><p>${cell}</p></th>`).join("")}</tr>${rows
    .map((row) => `<tr>${row.map((cell) => `<td><p>${cell}</p></td>`).join("")}</tr>`)
    .join("")}</tbody></table>`;

// Starting text for the English lesson body
export const GRAMMAR_TEMPLATE_EN = [
  "<h2>Explanation</h2>",
  "<p>Explain the grammar in simple English (2–4 short sentences).</p>",
  "<h2>Form</h2>",
  table(
    ["", "Example"],
    [
      ["Positive", "I …"],
      ["Negative", "I … not …"],
      ["Question", "… I …?"],
      ["Short answers", "Yes, I … / No, I …"],
    ],
  ),
  "<h2>When to use</h2>",
  "<ol><li><p>Use 1 — <em>example sentence</em></p></li><li><p>Use 2 — <em>example sentence</em></p></li><li><p>Use 3 — <em>example sentence</em></p></li></ol>",
  "<h2>Common mistakes</h2>",
  "<ul><li><p>❌ wrong sentence → ✅ correct sentence — short reason</p></li></ul>",
].join("");

// Starting text for the Khmer explanation
export const GRAMMAR_TEMPLATE_KM = [
  "<h2>ការពន្យល់</h2>",
  "<p>ពន្យល់វេយ្យាករណ៍នេះជាភាសាខ្មែរ ឲ្យខ្លី និងងាយយល់។</p>",
  "<h2>កំហុសដែលអ្នកនិយាយខ្មែរតែងធ្វើ</h2>",
  "<ul><li><p>❌ … → ✅ … — មូលហេតុ</p></li></ul>",
].join("");

// ---- Reading lesson: a short text, its new words, and questions about it ----
export const READING_SECTIONS = [
  { title: "Title and level", hint: "English + Khmer title, and the course (level)", where: "Details" },
  { title: "Before you read", hint: "1–2 sentences: what the text is about, what to look for", heading: "Before you read" },
  { title: "Read the text", hint: "The passage: short paragraphs, simple words for the level (A1 ≈ 100–200 words)", heading: "Read the text" },
  { title: "Words to know", hint: "5–10 new words from the text, with meaning and Khmer", where: "Words" },
  { title: "Quiz", hint: "5–10 questions about the text, each with an explanation", where: "Quiz" },
];

export const READING_TEMPLATE_EN = [
  "<h2>Before you read</h2>",
  "<p>Say in one or two sentences what the text is about.</p>",
  "<h2>Read the text</h2>",
  "<p>First paragraph of the text…</p>",
  "<p>Second paragraph…</p>",
].join("");

export const READING_TEMPLATE_KM = [
  "<h2>ការណែនាំ</h2>",
  "<p>អានអត្ថបទជាភាសាអង់គ្លេសម្តងពីរដង។ ព្យាយាមយល់អត្ថន័យទូទៅសិន មុននឹងមើលពាក្យថ្មី។ បន្ទាប់មកធ្វើលំហាត់ដើម្បីពិនិត្យថាអ្នកយល់។</p>",
].join("");

// ---- Listening lesson: audio, its transcript, and questions about it ----
export const LISTENING_SECTIONS = [
  { title: "Title and level", hint: "English + Khmer title, and the course (level)", where: "Details" },
  { title: "Before you listen", hint: "Who is talking, where, and what to listen for", heading: "Before you listen" },
  { title: "Audio", hint: "mp3, clear voices, 30 seconds – 3 minutes. Needed to publish.", field: "audio_url", where: "Details" },
  { title: "Transcript", hint: "Every word of the audio. Students open it after listening.", field: "transcript", where: "Transcript" },
  { title: "Words to know", hint: "5–10 new words from the audio, with meaning and Khmer", where: "Words" },
  { title: "Quiz", hint: "5–10 questions about the audio, each with an explanation", where: "Quiz" },
];

export const LISTENING_TEMPLATE_EN = [
  "<h2>Before you listen</h2>",
  "<p>Say who is talking and where. Give one or two things to listen for.</p>",
  "<h2>How to listen</h2>",
  "<ol><li><p>Listen one time. Do not read the transcript yet.</p></li><li><p>Listen again at <strong>0.75x</strong> speed.</p></li><li><p>Do the quiz. Then read the transcript and listen one more time.</p></li></ol>",
].join("");

export const LISTENING_TEMPLATE_KM = [
  "<h2>ការណែនាំ</h2>",
  "<ol><li><p>ស្តាប់ម្តងសិន។ កុំទាន់អានអត្ថបទ។</p></li><li><p>ស្តាប់ម្តងទៀតក្នុងល្បឿន <strong>0.75x</strong>។</p></li><li><p>ធ្វើលំហាត់។ បន្ទាប់មកអានអត្ថបទ ហើយស្តាប់ម្តងទៀត។</p></li></ol>",
].join("");

// ---- Writing lesson: a task, help, a checklist, a word target and a sample answer ----
export const WRITING_SECTIONS = [
  { title: "Title and level", hint: "English + Khmer title, and the course (level)", where: "Details" },
  { title: "Your task", hint: "What to write about, and how long", heading: "Your task" },
  { title: "Useful sentences", hint: "Model sentences or words students can use", heading: "Useful sentences" },
  { title: "Checklist", hint: "3–6 things to check before sending", field: "checklist", where: "Writing task" },
  { title: "Word target", hint: "e.g. 50–100 words for A1", field: "min_words", where: "Writing task" },
  { title: "Sample answer", hint: "A model answer. Students see it after they send theirs.", field: "sample_answer", where: "Sample answer" },
];

export const WRITING_TEMPLATE_EN = [
  "<h2>Your task</h2>",
  "<p>Write about … Say … Write <strong>50 to 100 words</strong>.</p>",
  "<h2>Useful sentences</h2>",
  "<ul><li><p>Model sentence 1</p></li><li><p>Model sentence 2</p></li></ul>",
].join("");

export const WRITING_TEMPLATE_KM = [
  "<h2>ការណែនាំ</h2>",
  "<p>ពន្យល់កិច្ចការជាភាសាខ្មែរ៖ ត្រូវសរសេរអំពីអ្វី និងប្រវែងប៉ុន្មានពាក្យ។</p>",
].join("");

// ---- Vocabulary lesson: words (flashcards + matching game), notes, and a quiz ----
export const VOCABULARY_SECTIONS = [
  { title: "Title and level", hint: "English + Khmer title, and the course (level)", where: "Details" },
  { title: "Words", hint: "8–15 words: simple English meaning, Khmer, an example, audio if you have it", where: "Words" },
  { title: "Good to know", hint: "Short notes: differences from Khmer, word families, common mistakes", heading: "Good to know" },
  { title: "Quiz", hint: "Optional: 5–10 questions. The matching game is made from the words.", where: "Quiz" },
];

export const VOCABULARY_TEMPLATE_EN = [
  "<p>Learn the words with the <strong>flashcards</strong>: tap a card to see the meaning. Then play the <strong>matching game</strong>.</p>",
  "<h2>Good to know</h2>",
  "<ul><li><p>A short note about the words…</p></li></ul>",
].join("");

export const VOCABULARY_TEMPLATE_KM = [
  "<h2>ចំណាំ</h2>",
  "<ul><li><p>ពន្យល់ចំណុចសំខាន់ៗអំពីពាក្យទាំងនេះជាភាសាខ្មែរ…</p></li></ul>",
].join("");

// Guide + starting text for each skill that has a template (other skills: no guide yet)
export const LESSON_GUIDES = {
  grammar: {
    title: "Grammar lesson guide",
    insert: "Insert grammar template",
    sections: GRAMMAR_SECTIONS,
    en: GRAMMAR_TEMPLATE_EN,
    km: GRAMMAR_TEMPLATE_KM,
  },
  reading: {
    title: "Reading lesson guide",
    insert: "Insert reading template",
    sections: READING_SECTIONS,
    en: READING_TEMPLATE_EN,
    km: READING_TEMPLATE_KM,
  },
  writing: {
    title: "Writing lesson guide",
    insert: "Insert writing template",
    sections: WRITING_SECTIONS,
    en: WRITING_TEMPLATE_EN,
    km: WRITING_TEMPLATE_KM,
  },
  vocabulary: {
    title: "Vocabulary lesson guide",
    insert: "Insert vocabulary template",
    sections: VOCABULARY_SECTIONS,
    en: VOCABULARY_TEMPLATE_EN,
    km: VOCABULARY_TEMPLATE_KM,
  },
  listening: {
    title: "Listening lesson guide",
    insert: "Insert listening template",
    sections: LISTENING_SECTIONS,
    en: LISTENING_TEMPLATE_EN,
    km: LISTENING_TEMPLATE_KM,
  },
};

/** Section headings found in the HTML, to tick them off in the guide. */
export const headingsIn = (html) =>
  [...String(html || "").matchAll(/<h[23][^>]*>(.*?)<\/h[23]>/gi)].map((match) =>
    match[1].replace(/<[^>]+>/g, "").trim().toLowerCase(),
  );
