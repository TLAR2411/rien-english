/** "11 November 2026" in English, Khmer month names in Khmer mode. */
export const formatLearningDate = (value, locale) =>
  value
    ? new Date(value).toLocaleDateString(locale === "km" ? "km-KH" : "en-GB", {
        day: "numeric",
        month: "long",
        year: "numeric",
      })
    : null;
