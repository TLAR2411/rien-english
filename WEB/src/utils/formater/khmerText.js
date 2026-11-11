// Khmer text for official letters: digits, dates and amounts in words.

const DIGITS = ["០", "១", "២", "៣", "៤", "៥", "៦", "៧", "៨", "៩"];

export const KHMER_MONTHS = [
  "មករា", "កុម្ភៈ", "មីនា", "មេសា", "ឧសភា", "មិថុនា",
  "កក្កដា", "សីហា", "កញ្ញា", "តុលា", "វិច្ឆិកា", "ធ្នូ",
];

// Sunday first, the same order as Date.getDay()
export const KHMER_WEEKDAYS = ["អាទិត្យ", "ច័ន្ទ", "អង្គារ", "ពុធ", "ព្រហស្បតិ៍", "សុក្រ", "សៅរ៍"];
export const KHMER_WEEKDAY_LETTERS = ["អា", "ច", "អ", "ព", "ព្រ", "សុ", "ស"];

/** "2025" -> "២០២៥" (any non-digit is kept) */
export const toKhmerDigits = (value) => String(value ?? "").replace(/\d/g, (d) => DIGITS[d]);

/** Date -> "ថ្ងៃទី៣១ ខែកក្កដា ឆ្នាំ២០២៥" */
export const khmerDateLong = (date) => {
  const d = date instanceof Date ? date : new Date(date);
  return `ថ្ងៃទី${toKhmerDigits(String(d.getDate()).padStart(2, "0"))} ខែ${KHMER_MONTHS[d.getMonth()]} ឆ្នាំ${toKhmerDigits(d.getFullYear())}`;
};

/** "2025-07" -> "ខែកក្កដា ឆ្នាំ២០២៥" */
export const khmerMonthYear = (ym) => {
  const [y, m] = String(ym).split("-").map(Number);
  return `ខែ${KHMER_MONTHS[m - 1]} ឆ្នាំ${toKhmerDigits(y)}`;
};

const ONES = ["", "មួយ", "ពីរ", "បី", "បួន", "ប្រាំ", "ប្រាំមួយ", "ប្រាំពីរ", "ប្រាំបី", "ប្រាំបួន"];
const TENS = ["", "ដប់", "ម្ភៃ", "សាមសិប", "សែសិប", "ហាសិប", "ហុកសិប", "ចិតសិប", "ប៉ែតសិប", "កៅសិប"];
// Place names below a million: សែន (100,000), ម៉ឺន (10,000), ពាន់, រយ
const PLACES = [
  [100000, "សែន"],
  [10000, "ម៉ឺន"],
  [1000, "ពាន់"],
  [100, "រយ"],
];

const belowMillion = (n) => {
  let words = "";
  let rest = n;
  for (const [size, name] of PLACES) {
    const count = Math.floor(rest / size);
    if (count) words += ONES[count] + name;
    rest %= size;
  }
  return words + TENS[Math.floor(rest / 10)] + ONES[rest % 10];
};

/**
 * Whole number -> Khmer words.
 *   400000 -> "បួនសែន", 1250000 -> "មួយលានពីរសែនហាម៉ឺន", 0 -> "សូន្យ"
 */
export const khmerNumberWords = (value) => {
  let n = Math.floor(Math.abs(Number(value) || 0));
  if (n === 0) return "សូន្យ";

  let words = "";
  const billions = Math.floor(n / 1e9);
  if (billions) words += khmerNumberWords(billions) + "ពាន់លាន";
  n %= 1e9;
  const millions = Math.floor(n / 1e6);
  if (millions) words += belowMillion(millions) + "លាន";
  n %= 1e6;
  return words + belowMillion(n);
};

/** Riel amount in words for a letter: 400000 -> "បួនសែនរៀលគត់" */
export const khmerRielWords = (value) => `${khmerNumberWords(value)}រៀលគត់`;
