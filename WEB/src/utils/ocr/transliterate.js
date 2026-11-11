export const SURNAME_MAP = {
    // L
    LENG: "ឡេង",
    LY: "លី",
    LIM: "លឹម",
    LEANG: "លាង",
    LOY: "លុយ",
    LONG: "ឡុង",

    // S
    SOK: "សុខ",
    SENG: "សេង",
    SIN: "ស៊ិន",
    SIM: "ស៊ឹម",
    SAM: "សំ",
    SO: "សូ",
    SON: "ស៊ុន",
    SUON: "សួន",

    // C / CH
    CHEA: "ជា",
    CHAN: "ចាន់",
    CHHUN: "ឈុន",
    CHHAY: "ឆាយ",
    CHENG: "ឆេង",

    // H
    HENG: "ហេង",
    HORN: "ហន",
    HOU: "ហ៊ូ",

    // P
    PENN: "ប៉ែន",
    PHAN: "ផាន",
    PICH: "ពេជ្រ",

    // K
    KIM: "គឹម",
    KHIEU: "ខៀវ",
    KEO: "កែវ",
    KONG: "គង់",

    // N
    NHEM: "ញ៉ែម",
    NGET: "ង៉ែត",

    // O
    OUK: "អ៊ុក",

    // T
    TEP: "ទេព",
    THONG: "ថុង",

    // V
    VAN: "វ៉ាន់",
    VONG: "វង្ស",

    // Y
    YANG: "យ៉ាង",
    YEM: "យេម",
};


const SYLLABLE_MAP = [
    // ---- common full endings ----
    ["KOURA", "គួរ៉ា"],
    ["KUNTHA", "គន្ធា"],
    ["RITHY", "រិទ្ធី"],
    ["SOPHEA", "សុភា"],
    ["SOPHIE", "សុភី"],
    ["SOVANN", "សុវណ្ណ"],
    ["SOVAN", "សុវណ្ណ"],
    ["MONY", "មុនី"],
    ["VANN", "វណ្ណ"],
    ["RATHA", "រដ្ឋា"],

    // ---- consonant clusters ----
    ["KOUR", "គួរ"],
    ["THA", "ថា"],
    ["PHA", "ផា"],
    ["CHA", "ចា"],
    ["KHA", "ខា"],
    ["SOK", "សុខ"],

    // ---- vowels + consonants ----
    ["KOU", "គួ"],
    ["LY", "លី"],
    ["LI", "លី"],
    ["LA", "ឡា"],
    ["LEA", "លា"],
    ["RA", "រ៉ា"],
    ["RI", "រិ"],
    ["RO", "រោ"],
    ["RY", "រី"],
    ["NA", "ណា"],
    ["NEA", "នៀ"],
    ["TA", "តា"],
    ["TH", "ធ"],

    // ---- single consonants ----
    ["R", "រ"],
    ["L", "ល"],
    ["K", "ក"],
    ["N", "ន"],
    ["M", "ម"],
    ["T", "ត"],
    ["P", "ព"],
    ["S", "ស"],
    ["H", "ហ"],

    // ---- vowels ----
    ["OU", "ូ"],
    ["OA", "ោ"],
    ["O", "ោ"],
    ["U", "ុ"],
    ["A", "ា"],
    ["E", "េ"],
    ["I", "ិ"],
    ["Y", "ី"],
];
export function transliterateEnglishToKhmer(fullName) {
    if (!fullName) return null;

    const parts = fullName.trim().toUpperCase().split(/\s+/);
    if (parts.length < 2) return null;

    const surnameEn = parts[0];
    const givenEn = parts.slice(1).join("");

    // Surname (authoritative)
    const khmerSurname = SURNAME_MAP[surnameEn] || "";

    let khmerGiven = "";
    let buffer = givenEn;

    while (buffer.length > 0) {
        let matched = false;

        for (const [latin, khmer] of SYLLABLE_MAP) {
            if (buffer.startsWith(latin)) {
                khmerGiven += khmer;
                buffer = buffer.slice(latin.length);
                matched = true;
                break;
            }
        }

        // Fallback: drop 1 char (avoid infinite loop)
        if (!matched) buffer = buffer.slice(1);
    }

    return `${khmerSurname} ${khmerGiven}`.trim();
}
