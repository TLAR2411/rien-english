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

export const findOption = (list, value) => list.find((item) => item.value === value);
