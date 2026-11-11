/*
 * Fixed lists of the learning platform. They must match the PHP enums in
 * API/app/Enums (the API rejects any other value). `title` is an i18n key.
 */

// API/app/Enums/PublishStatus.php
export const PUBLISH_STATUSES = [
  { value: "draft", title: "Draft", color: "warning" },
  { value: "published", title: "Published", color: "success" },
];

export const findOption = (list, value) => list.find((item) => item.value === value);
