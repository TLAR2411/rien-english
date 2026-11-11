/*
 * Fixed lists of the learning platform. They must match the PHP enums in
 * API/app/Enums (the API rejects any other value). `title` is an i18n key.
 */

// API/app/Enums/PublishStatus.php
export const PUBLISH_STATUSES = [
  { value: "draft", title: "Draft", color: "warning" },
  { value: "published", title: "Published", color: "success" },
];

// API/app/Enums/LessonSkill.php
export const SKILLS = [
  { value: "grammar", title: "Grammar", icon: "tabler-abc" },
  { value: "reading", title: "Reading", icon: "tabler-book" },
  { value: "listening", title: "Listening", icon: "tabler-headphones" },
  { value: "writing", title: "Writing", icon: "tabler-pencil" },
  { value: "vocabulary", title: "Vocabulary", icon: "tabler-vocabulary" },
];

// File picker filters. The real limits are checked by the API
// (API/config/learning.php → uploads): audio mp3 ≤ 10 MB, image ≤ 2 MB.
export const UPLOAD_ACCEPT = {
  audio: "audio/mpeg,.mp3",
  image: "image/jpeg,image/png,image/webp",
};

// Grammar lessons should have 8–10 examples (PLAN.md Appendix A)
export const RECOMMENDED_EXAMPLES = { min: 8, max: 10 };

export const findOption = (list, value) => list.find((item) => item.value === value);
