/**
 * Counts words the same way as the API (API/app/Support/WordCount.php):
 * everything between spaces / new lines.
 */
export const wordCount = (text) => String(text ?? "").trim().split(/\s+/u).filter(Boolean).length;

/** "50–100 words", "at least 50 words", "up to 100 words", or "" (no target). */
export const wordTargetText = (t, min, max) => {
  if (min && max) return t("{min}–{max} words", { min, max });
  if (min) return t("At least {min} words", { min });
  if (max) return t("Up to {max} words", { max });
  return "";
};
