/*
 * Grammar Lesson Template (PLAN.md → Appendix A).
 * The editor shows these sections as a guide and can insert the starting text.
 * Change the wording here; it is used for every new grammar lesson.
 *
 * `heading` — the section's heading in the lesson text (used to tick it off
 *             in the guide). Sections without one are filled in elsewhere.
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

/** Section headings found in the HTML, to tick them off in the guide. */
export const headingsIn = (html) =>
  [...String(html || "").matchAll(/<h[23][^>]*>(.*?)<\/h[23]>/gi)].map((match) =>
    match[1].replace(/<[^>]+>/g, "").trim().toLowerCase(),
  );
