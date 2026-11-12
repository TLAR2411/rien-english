/*
 * Fixed lists of the learning platform. They must match the PHP enums in
 * API/app/Enums (the API rejects any other value). `title` is an i18n key.
 */

// API/app/Enums/PublishStatus.php
export const PUBLISH_STATUSES = [
  { value: "draft", title: "Draft", color: "warning" },
  { value: "published", title: "Published", color: "success" },
];

// API/app/Enums/WritingStatus.php — a student's writing
export const WRITING_STATUSES = [
  { value: "pending", title: "Waiting for review", short: "Waiting", color: "warning", icon: "tabler-clock-hour-4" },
  { value: "reviewed", title: "Reviewed", short: "Reviewed", color: "success", icon: "tabler-circle-check-filled" },
];

// API/app/Enums/LessonSkill.php
export const SKILLS = [
  { value: "grammar", title: "Grammar", icon: "tabler-abc" },
  { value: "reading", title: "Reading", icon: "tabler-book" },
  { value: "listening", title: "Listening", icon: "tabler-headphones" },
  { value: "writing", title: "Writing", icon: "tabler-pencil" },
  { value: "vocabulary", title: "Vocabulary", icon: "tabler-vocabulary" },
];

// API/app/Enums/QuestionType.php — `limits` = optionLimits() there.
// choice: the student picks one option (one is correct).
// typed:  the student types / builds the answer (every option is an accepted answer).
export const QUESTION_TYPES = [
  {
    value: "multiple_choice",
    title: "Multiple choice",
    icon: "tabler-list-check",
    answer: "choice",
    limits: [2, 6],
    promptHint: "She ___ a teacher.",
    help: "Write the answers and mark the correct one.",
    newOptions: () => [{ text: "", is_correct: true }, { text: "", is_correct: false }, { text: "", is_correct: false }],
  },
  {
    value: "true_false",
    title: "True / False",
    icon: "tabler-toggle-right",
    answer: "choice",
    fixed: true,
    limits: [2, 2],
    promptHint: '"I is happy." is a correct sentence.',
    help: "Is the sentence true or false?",
    newOptions: () => [{ text: "True", is_correct: true }, { text: "False", is_correct: false }],
  },
  {
    value: "fill_blank",
    title: "Fill in the blank",
    icon: "tabler-forms",
    answer: "typed",
    limits: [1, 10],
    promptHint: "They ___ students.",
    help: "Write ___ for the blank. Add every answer you accept (e.g. are, 're).",
    newOptions: () => [{ text: "", is_correct: true }],
  },
  {
    value: "word_order",
    title: "Word order",
    icon: "tabler-arrows-shuffle",
    answer: "typed",
    limits: [1, 5],
    promptHint: "Put the words in the right order.",
    help: "Write the correct sentence. Students get its words mixed up.",
    newOptions: () => [{ text: "", is_correct: true }],
  },
  {
    value: "find_error",
    title: "Find the error",
    icon: "tabler-zoom-exclamation",
    answer: "choice",
    limits: [2, 10],
    promptHint: "He are my brother.",
    help: "Split the sentence into parts and mark the wrong part.",
    newOptions: () => [],
  },
];

export const RECOMMENDED_QUESTIONS = { min: 10, max: 15 };

// File picker filters. The real limits are checked by the API
// (API/config/learning.php → uploads): audio mp3 ≤ 10 MB, image ≤ 2 MB.
export const UPLOAD_ACCEPT = {
  audio: "audio/mpeg,.mp3",
  image: "image/jpeg,image/png,image/webp",
};

// Grammar lessons should have 8–10 examples (PLAN.md Appendix A)
export const RECOMMENDED_EXAMPLES = { min: 8, max: 10 };

export const findOption = (list, value) => list.find((item) => item.value === value);

// Reading texts should have 5–10 "Words to know"
export const RECOMMENDED_WORDS = { min: 5, max: 10 };

// Speeds of the listening player (PLAN.md T32)
export const LISTENING_SPEEDS = [0.75, 1];

// Tabs of the lesson page next to "Lesson". Each part saves on its own,
// so they open once the lesson is saved. `skills`: only for lessons of these skills.
export const LESSON_TABS = [
  { value: "words", title: "Words", icon: "tabler-vocabulary", skills: ["reading", "listening", "vocabulary"] },
  { value: "examples", title: "Examples", icon: "tabler-message-2" },
  { value: "quiz", title: "Quiz", icon: "tabler-checklist" },
  { value: "preview", title: "Preview", icon: "tabler-eye" },
];
