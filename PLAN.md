# English Learning Platform for Cambodian Students — Build Plan

> **For Claude Code:** Follow the rules in `CLAUDE.md`. Work on **ONE task at a time**, in order.
> When a task is finished: tick its box `[x]`, update the **Status** table, add a line to the
> **Progress Log** at the bottom, then STOP and wait for the owner to say "next".

---

## Project summary

A website where Cambodian students learn English: **Grammar, Reading, Listening, Writing, Vocabulary**.

- **Visitors (no login)** open the public website (landing page) and **read all published lessons for free**.
- **To take a quiz, a visitor must sign up** (name + email + password). After sign-up they go back to the quiz.
- **Students (logged in)** see a **system-style dashboard** (side menu): take quizzes, see scores, progress, writing feedback.
- **Admin / Teacher** manage levels, courses, lessons, quizzes, questions, users, and review writing.
- **Version 1 is free.** The database must already support **paid courses later** (`courses.is_free`, `enrollments`).
- Interface in **Khmer and English**. Mobile-first (most students use Android phones).

## Tech stack

- **Backend:** Laravel (existing project, login already working)
- **Frontend:** Vue 3 (existing setup — Inertia or separate SPA, detect in T01)
- **Database:** MySQL
- **Packages to add:** `spatie/laravel-permission`, `@tiptap/vue-3`, `vue-i18n`, `pinia`, `chart.js` + `vue-chartjs`, Tailwind CSS (if not already installed)

## Golden rules for this project

1. **Quizzes are graded on the server (Laravel), never in Vue.** Correct answers are never sent to the browser before submission.
2. Every admin route is protected by role middleware (`admin` or `teacher`).
3. Visitors and students only see lessons with `status = published`.
4. **Reading lessons is public. Quizzes, scores, progress, and writing submissions need login.**
5. Every new feature gets at least one Feature test (`php artisan test`).
6. Do not break the existing login.

---

## Status

| Phase | Name | Tasks | Done |
|---|---|---|---|
| 0 | Setup, check & clean existing project | T01–T03 | 3/3 |
| 1 | Roles & admin area | T04–T06 | 3/3 |
| 2 | Database (levels, courses, lessons, quizzes) | T07–T10 | 4/4 |
| 3 | Admin: manage content | T11–T17 | 3/7 |
| 4 | Public website & student sign-up | T18–T22 | 0/5 |
| 5 | Student system: dashboard & quizzes | T23–T27 | 0/5 |
| 6 | Student progress & scores | T28–T30 | 0/3 |
| 7 | Reading, Listening, Writing, Vocabulary | T31–T35 | 0/5 |
| 8 | Khmer/English, Google sign-in, polish, launch | T36–T42 | 0/7 |
| 9 | (Later) Paid courses | T43–T46 | 0/4 |

**Current task:** T14

---

## Phase 0 — Setup & check existing project

- [x] **T01 — Inspect the existing project**
  - Read `composer.json`, `package.json`, `routes/`, `resources/js/`, `app/Models/User.php`, `database/migrations/`.
  - Report: Laravel version, Vue version, Inertia or separate SPA + Sanctum, how login works, CSS framework, test setup.
  - Write the findings into the **Project Notes** section at the bottom of this file.
  - **Do not change any code in this task.**
  - ✅ Done when: Project Notes filled in and owner confirms.

- [x] **T02 — Git & safety**
  - Make sure the project is a git repo; create branch `feature/learning-platform`.
  - Make sure `.env` is in `.gitignore`.
  - ✅ Done when: on the new branch, working tree clean.

- [x] **T03 — Clean up unused parts & check packages**
  - Owner decided (2026-11-11) to remove:
    - **Loan leftovers** — loan words in `en.json`/`km.json`, loan row actions, loan columns/filters, "Loan System Role" on positions, loan comments.
    - **"Devfin" name** — page title, API welcome message, "Powered by Devfin.", PWA short name → use the app name from `.env`.
    - **HR part** — employees, former employees, HR dashboard, HR nav + system part, `hr-allow-part` permission, `employees` table/model/controller/routes. Keep users working without `employee_id`.
    - **Company structure pages** — departments, positions, under-user (team leader), team diagram, transfer under-user. Keep **one hidden default branch** (every user needs `branch_id`).
    - **User access requests** — page, API, table.
  - **Keep:** login, users, roles & permissions, settings, activity log, **address part** (owner chose to keep it), shared components.
  - Packages: already installed (pinia, vue-i18n, TipTap, chart.js, vue-chartjs) — nothing to add. No spatie (we use Laratrust), no Tailwind (we use Vuetify). See Project Notes.
  - Remove each part's tests, seeders, permissions and menu items together. Remove composer/npm packages only if nothing else uses them.
  - ✅ Done when: no loan/HR/removed pages left, admin login + users + roles still work, `php artisan migrate:fresh --seed` (test DB only), `php artisan test` and `npm run build` all pass.

## Phase 1 — Roles & admin area

- [x] **T04 — Roles**
  - Roles use **Laratrust** (already installed, see Project Notes) — not spatie.
  - Seeder `RoleSeeder`: roles `admin`, `teacher`, `student`.
  - One shared place (e.g. an action class) that gives every new student account the `student` role — used by sign-up in T21.
  - Seeder creates one admin account using values from `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`) — never hard-code passwords.
  - ✅ Done when: tests prove roles exist, a new student account = student, admin seeded.

- [x] **T05 — Admin middleware & routes group**
  - Route group `/admin` with `auth` + `role:admin|teacher`.
  - Some actions (manage users) are `admin` only.
  - ✅ Done when: tests — student gets 403 on `/admin`, admin gets 200.

- [x] **T06 — Admin layout (Vue)**
  - Sidebar: Dashboard, Levels, Courses, Lessons, Quizzes, Users, Writing Review.
  - Responsive (works on phone). Link to admin only shown to admin/teacher.
  - ✅ Done when: admin sees empty admin dashboard page.

## Phase 2 — Database

- [x] **T07 — Levels & Courses**
  - `levels`: id, code (A1, A2, B1, B2, C1), name, sort_order.
  - `courses`: id, level_id, title, title_km, description, slug, is_free (default true), price (nullable), status (draft/published), sort_order.
  - Seeder: levels A1–C1 and one free course per level ("Grammar A1", ...).
  - ✅ Done when: migrate + seed works; models have relationships.

- [x] **T08 — Lessons**
  - `lessons`: id, course_id, skill (enum: grammar, reading, listening, writing, vocabulary), title_en, title_km, slug, summary, content (longText HTML), content_km (longText, nullable), audio_url (nullable), sort_order, status (draft/published), timestamps.
  - `lesson_examples`: id, lesson_id, sentence, translation_km, audio_url, sort_order.
  - ✅ Done when: models + relationships + factory exist.

- [x] **T09 — Quizzes & questions**
  - `quizzes`: id, lesson_id, title, pass_score (default 70), time_limit_minutes (nullable).
  - `questions`: id, quiz_id, type (multiple_choice, true_false, fill_blank, word_order, find_error), prompt, explanation, explanation_km, points (default 1), sort_order.
  - `question_options`: id, question_id, text, is_correct, sort_order.
  - For `fill_blank` / `word_order`: correct answer stored in `question_options` with `is_correct = true` (allow several accepted answers).
  - ✅ Done when: models + relationships + factories exist.

- [x] **T10 — Attempts, progress, enrollments**
  - `quiz_attempts`: id, user_id, quiz_id, score, total, percent, passed, started_at, finished_at.
  - `attempt_answers`: id, quiz_attempt_id, question_id, answer (text/json), is_correct.
  - `lesson_progress`: id, user_id, lesson_id, completed_at (unique user+lesson).
  - `writing_submissions`: id, user_id, lesson_id, text, status (pending/reviewed), score, feedback, reviewed_by, reviewed_at.
  - `enrollments`: id, user_id, course_id, amount_paid, paid_at (for future paid courses — not used yet).
  - ✅ Done when: `php artisan migrate:fresh --seed` runs clean.

## Phase 3 — Admin: manage content

- [x] **T11 — Levels & Courses CRUD**
  - List, create, edit, delete, reorder. Form validation via Form Requests.
  - ✅ Done when: feature tests for create/update/delete pass.

- [x] **T12 — Lesson list**
  - Filters: level, course, skill, status. Search by title. Pagination.
  - ✅ Done when: admin can filter lessons.

- [x] **T13 — Lesson editor**
  - Fields: course, skill, titles (EN/KM), summary, content (TipTap editor), content_km (TipTap), status.
  - Lesson editor follows the **Grammar Lesson Template** (see Appendix A) — show the sections as a guide.
  - ✅ Done when: admin can create & edit a lesson with formatted text.

- [ ] **T14 — Lesson examples & file upload**
  - Add/edit/remove/reorder example sentences on the lesson page.
  - Upload audio (mp3, max 10MB) and images (jpg/png/webp, max 2MB) to `storage/app/public` (`php artisan storage:link`).
  - Validate file type and size on the server.
  - ✅ Done when: lesson with examples + audio can be saved and played.

- [ ] **T15 — Quiz builder**
  - One quiz per lesson. Add/edit/delete/reorder questions.
  - Different form per question type (multiple choice, true/false, fill blank, word order, find error).
  - Each question has explanation (EN + KM).
  - ✅ Done when: admin can build a 10-question quiz with mixed types.

- [ ] **T16 — Preview**
  - Admin can preview a lesson and quiz exactly as a student sees it (even when draft).
  - ✅ Done when: preview button works.

- [ ] **T17 — Sample content seeder**
  - Seeder with 3 real A1 grammar lessons (to be, Present Simple, a/an/the) + a 10-question quiz each, using Appendix A.
  - Mark in a comment: **"Content must be reviewed by an English teacher before launch."**
  - ✅ Done when: seeded lessons appear in admin.

## Phase 4 — Public website & student sign-up

The public website looks like a normal, friendly website (not a system). No login needed.
**All public pages follow Appendix C — Design rules.**

- [ ] **T18 — Public layout & landing page**
  - Make the logo (SVG wordmark + square icon / favicon) and colors from Appendix C — Brand.
  - First: show the owner a simple design (screenshot of the landing page on phone + desktop) and get OK before building the other public pages.
  - Public layout: top bar (logo, Lessons, Log in, Sign up), footer. Different look from the system layout.
  - Landing page: welcome message, choose a skill, choose a level, latest lessons, "Sign up to take quizzes" call to action.
  - Mobile-first, Khmer font works.
  - ✅ Done when: a visitor (not logged in) opens `/` and sees the landing page on phone and desktop.

- [ ] **T19 — Public lesson list**
  - Public API (no login): list published lessons. Filter by skill and level, search by title, pagination.
  - Public page shows the list.
  - ✅ Done when: tests — a guest sees published lessons; draft lessons are hidden.

- [ ] **T20 — Public lesson page**
  - Show content, Khmer explanation toggle, examples with 🔊 audio (use uploaded audio, or browser `speechSynthesis` en-US as fallback).
  - Button "Take the quiz": a guest goes to the sign-up page, and after sign-up/login comes back to this quiz.
  - Button "Mark as completed": only shown to logged-in students.
  - The public lesson API never includes quiz questions or answers.
  - ✅ Done when: a guest can read a full lesson; tests — draft lesson returns 404, no quiz answers in the response.

- [ ] **T21 — Student sign-up**
  - Sign-up page: name, email, password, confirm password. Form Request validation (unique email, strong-enough password).
  - Sign-up API: throttled; uses the T04 action so the new user gets the `student` role; logs the user in right away.
  - After sign-up, go back to the page the student came from (e.g. the quiz), otherwise to the student dashboard.
  - "Log in" and "Sign up" pages link to each other. Existing admin login keeps working.
  - ✅ Done when: tests — sign-up works, role = student, duplicate email rejected, existing login still works.

- [ ] **T22 — Forgot password (email)**
  - "Forgot password?" link → email with a reset link → set a new password. (Skip parts that already exist — check in T01.)
  - Mail settings come from `.env` only.
  - ✅ Done when: tests — reset email is sent, password can be changed with a valid link only.

## Phase 5 — Student system: dashboard & quizzes

After login, students see a **system-style layout** (side menu), like the admin system.

- [ ] **T23 — Student layout & dashboard**
  - Student side menu: Dashboard, Lessons, My Quizzes, My Progress, My Writing, Profile. No admin links.
  - Dashboard: welcome, continue last lesson, recent quiz scores, choose skill/level.
  - Guests who open a student page are sent to the login page.
  - ✅ Done when: a logged-in student sees the dashboard on phone & desktop; test — guest is redirected / gets 401.

- [ ] **T24 — Quiz API (secure)**
  - Needs login. `GET /quizzes/{quiz}` returns questions + options **without `is_correct`**.
  - `POST /quizzes/{quiz}/submit` grades on server, saves `quiz_attempts` + `attempt_answers`, returns result with correct answers + explanations.
  - Fill-blank compare: trim, case-insensitive, collapse spaces.
  - ✅ Done when: tests prove answers are not leaked, guests get 401, and scoring is correct for each question type.

- [ ] **T25 — Quiz page (Vue)**
  - One question at a time, progress bar, back/next, submit at end, confirm before submit.
  - Word order: tap words to build the sentence (works on touch screens).
  - ✅ Done when: student can complete a mixed quiz on a phone.

- [ ] **T26 — Quiz result page & My Quizzes**
  - Score, percent, pass/fail, stars. List every question: your answer, correct answer, explanation (EN/KM).
  - Buttons: Retry, Back to lesson, Next lesson.
  - "My Quizzes" page: history of all attempts; open any old result.
  - ✅ Done when: result page shows after submit and from history.

- [ ] **T27 — Rate limiting & validation**
  - Throttle quiz submit. Validate every answer belongs to the quiz.
  - ✅ Done when: tests pass.

## Phase 6 — Student progress & scores

- [ ] **T28 — My Progress page**
  - Per skill and per level: % lessons completed.
  - Table of quiz scores per lesson/grammar topic: best score, last score, attempts count, date.
  - ✅ Done when: student sees their scores.

- [ ] **T29 — Charts & weak topics**
  - Chart of scores over time. List "Topics to review" (best score < pass_score).
  - ✅ Done when: chart renders with real data.

- [ ] **T30 — Admin: users & scores**
  - Admin list of users (search, role, block/unblock). User detail page with all quiz attempts.
  - Admin dashboard: total users, new sign-ups, active users (7 days), quizzes taken, average score, most popular lessons.
  - ✅ Done when: admin can see any student's scores.

## Phase 7 — Reading, Listening, Writing, Vocabulary

Lessons stay public. Quizzes and writing submissions need login (same as Phase 4–5).

- [ ] **T31 — Reading lessons**
  - Uses `skill = reading`. Passage + vocabulary list + comprehension quiz (reuse quiz system).
  - ✅ Done when: one sample reading lesson works end to end.

- [ ] **T32 — Listening lessons**
  - `skill = listening`. Audio player (play, pause, replay, speed 0.75x/1x). Transcript hidden until "Show transcript" or quiz finished.
  - ✅ Done when: one sample listening lesson works end to end.

- [ ] **T33 — Writing lessons & submission**
  - `skill = writing`. Prompt, sample answer (hidden until submit), checklist, word counter, textarea.
  - Guests can read the prompt; they must sign up to submit. Student submits → `writing_submissions` (pending).
  - ✅ Done when: student can submit and see "waiting for review".

- [ ] **T34 — Writing review (teacher)**
  - Admin/teacher list of pending submissions, give score + feedback. Student sees feedback in My Writing / My Progress.
  - ✅ Done when: full review loop works.

- [ ] **T35 — Vocabulary lessons**
  - `skill = vocabulary`. Flashcards (word, Khmer meaning, example, audio, tap to flip) — public. Matching quiz — needs login.
  - ✅ Done when: one vocabulary lesson works end to end.

## Phase 8 — Khmer/English, Google sign-in, polish, launch

- [ ] **T36 — Interface language (vue-i18n)**
  - All UI text in `km.json` and `en.json`. Language switcher saved to user profile (and localStorage for guests). Khmer font: Kantumruy Pro or Noto Sans Khmer.
  - ✅ Done when: every page (public and system) switches language.

- [ ] **T37 — Sign in with Google**
  - "Continue with Google" button on Log in and Sign up pages. New Google users get the `student` role (T04 action).
  - Google keys come from `.env` only.
  - ✅ Done when: tests — Google callback creates/logs in a student; email/password login still works.

- [ ] **T38 — Placement test**
  - 20 mixed questions A1→B1, result suggests a starting level saved to user profile.
  - ✅ Done when: new student can take it after signup.

- [ ] **T39 — Motivation**
  - Daily streak, lesson completion badges, simple certificate page per level completed.
  - ✅ Done when: streak updates after a lesson/quiz.

- [ ] **T40 — "Report a mistake" button**
  - On every lesson and question. Admin inbox of reports.
  - ✅ Done when: report reaches admin.

- [ ] **T41 — Performance, SEO & mobile check**
  - Eager loading (no N+1), pagination, image optimization, test on 375px width.
  - SEO for public pages: page titles/meta per lesson, nice URLs (slug), `sitemap.xml`. Check that Google can read lesson pages; if not, add pre-rendering.
  - ✅ Done when: checklist done, no N+1 in lesson/quiz pages.

- [ ] **T42 — Launch checklist**
  - `APP_DEBUG=false`, HTTPS, backups of database, error logging, `php artisan optimize`, queue/scheduler if used, privacy policy page.
  - ✅ Done when: deployed to production server.

## Phase 9 — (Later) Paid courses — do NOT start until owner says so

- [ ] **T43 — Course access middleware** `EnsureCourseAccess`: course is free OR user has enrollment.
- [ ] **T44 — Course sales page** price, what's included, "Buy" button.
- [ ] **T45 — Payment** ABA PayWay and/or Bakong KHQR; on success create `enrollment`. Webhook verification.
- [ ] **T46 — Admin sales** list of payments, revenue, coupons.

---

## Appendix A — Grammar Lesson Template

Every grammar lesson must have these sections:

1. **Title** (EN + KM) and level (A1–C1)
2. **Explanation** — simple English + Khmer explanation
3. **Form** — table: positive / negative / question (+ short answers)
4. **When to use** — 3–5 uses, each with an example
5. **Examples** — 8–10 sentences with Khmer translation and audio
6. **Common mistakes of Khmer speakers** — ❌ wrong → ✅ correct, with short reason
7. **Quiz** — 10–15 questions, mixed types, each with explanation

## Appendix B — Grammar syllabus (version 1)

- **A1:** to be · Present Simple · Present Continuous · plural nouns · a/an/the · this/that/these/those · subject pronouns · possessive adjectives · there is/are · can/can't · prepositions of place · prepositions of time · question words
- **A2:** Past Simple (regular) · Past Simple (irregular) · Past Continuous · will · going to · comparatives · superlatives · countable/uncountable · some/any · much/many/a lot of · adverbs of frequency · must/have to · should · object pronouns · possessive 's
- **B1:** Present Perfect · Present Perfect vs Past Simple · Present Perfect Continuous · first conditional · second conditional · passive voice · relative clauses · gerund vs infinitive · reported speech · used to · modals of possibility · linking words

## Appendix C — Design rules (clean, simple, Apple-style)

Goal: the website looks calm and professional, **not "made by AI"**, and a new student understands it in 5 seconds.

**Brand (chosen by owner, can change later)**
- **Name:** **Rien English** — "Rien" = រៀន (to learn). Khmer: **រៀន English**. Short name: **Rien**.
- **Tagline:** "Learn English, step by step." / "រៀនអង់គ្លេស មួយជំហានម្តងៗ"
- **Logo:** simple wordmark — the word **rien** in bold, lowercase, blue, with a small rounded speech-bubble shape as the dot/icon. Made as SVG in T18 (also a square icon for the browser tab and phone home screen).
- **Colors:**

| Use | Name | Hex |
|---|---|---|
| Main color (buttons, links, logo) | Rien Blue | `#2563EB` |
| Main color, darker (hover) | Rien Blue Dark | `#1D4ED8` |
| Light blue background (highlight box) | Blue Soft | `#EFF4FF` |
| Text | Ink | `#1D1D1F` |
| Second text | Grey | `#6E6E73` |
| Page background | White | `#FFFFFF` |
| Section background | Light Grey | `#F5F5F7` |
| Correct answer | Green | `#16A34A` |
| Wrong answer | Red | `#DC2626` |
| Stars / streak only | Amber | `#F59E0B` |

**Do**
- **Lots of white space.** Few things on each screen. One idea per section.
- **One main button per screen** (e.g. "Start learning"). Other buttons are plain text links.
- **Big, clear headings, short text.** Simple words a beginner can read. Khmer and English side by side where it helps.
- **One main color** (Rien Blue) + black/grey text on white. Green = correct, red = wrong. Only the colors in the Brand table.
- **Fonts:** Inter (English) + Kantumruy Pro (Khmer). Body text at least 16px; Khmer line height a little bigger (1.7).
- **Real content on the landing page:** show a real lesson example, a real quiz question — not empty promises.
- **Easy on phones:** buttons at least 44px tall, easy to tap with a thumb, no tiny text, works on 375px width.
- **Clear words on buttons:** "Read lesson", "Take the quiz", "Sign up free" — never "Get started on your journey".
- **Simple, small animations only** (fade, slide). Pages load fast on slow 3G/4G.
- **Accessible:** good color contrast, every image has alt text, works with keyboard.

**Don't (this is what makes a site look "AI-made")**
- No purple/pink gradients, glowing cards, glass effects, or neon colors.
- No emoji in headings, no 🚀✨ everywhere.
- No empty marketing text like "Unlock your potential" or "Revolutionize your learning".
- No fake numbers ("10,000+ happy students") or fake reviews.
- No generic 3D illustrations or random stock photos.
- No grid of 6 identical icon cards for every section.

**Inspiration:** apple.com (white space, big headings), Duolingo (friendly, clear actions), Linear / Stripe (calm, clean).

---

## Project Notes
_(Filled in by Claude Code in T01 — 2026-11-11)_

**Structure**
- Two folders: `API/` (Laravel backend) and `WEB/` (Vue frontend). **Separate SPA**, not Inertia. There is no `resources/js/` — the frontend lives in `WEB/src/`.
- Git repo: GitHub `TLAR2411/rien-english` (**public**). `main` = first commit; work happens on `feature/learning-platform`.

**Backend (`API/`)**
- PHP 8.4, **Laravel 12.32**, MySQL.
- **Login = Laravel Passport** (password grant, Bearer token). `POST /api/web/login` with header `X-CLIENT-ID`; `POST /api/web/refresh`; `auth:api` guard. Sanctum is installed but **not** used for login (`statefulApi()` must stay off).
- Login accepts **username or email**. Errors use `abort(500, '<Khmer message>')`.
- Every login sends a **Telegram alert** (if `default_login_alert_telegram` + `telegram_bot_token` settings are set).
- All API routes are under `/api/web`; feature routes are in `routes/modules/*.route.php` and use `auth:api` + `branch.access` + `permission:<name>` middleware.
- **Roles & permissions = Laratrust** (`santigarcor/laratrust`), not spatie. Roles after T03: `developer`, `system-administrator`, `staff`. Users also have `is_super` (all permissions).
- **`users` table** needs: `name_kh`, `username` (unique), `password`, `branch_id`, `role_id` (all required); `email` is unique but nullable. `default_part` enum = `admin` (T03 removed `hr`; no `position_id`, `under_user_id`, `employee_id` any more).
- Admin user `admin` is seeded; password from `ADMIN_PASSWORD` in `.env`.
- **No sign-up and no forgot-password** exist yet.
- Other packages: activity log, Excel, image (Intervention/Spatie), Reverb, Telegram SDK, log viewer.

**Frontend (`WEB/`)**
- **Vue 3.5**, Vite 5, **Vuetify 3.8** (Vuexy-style admin template). **No Tailwind.**
- Already installed: **pinia, vue-i18n (with `en.json` + `km.json`), TipTap, chart.js + vue-chartjs**, ApexCharts, PWA plugin.
- Khmer font **Kantumruy** is already set up in `src/assets/styles/styles.scss`.
- File-based routing (`src/pages/`). "System parts" (only `admin` after T03) are set in `src/composables/useSystemParts.js` + `src/navigation/vertical/`.
- Router guard (`src/router/guards.js`) already supports **`meta.public`** pages. Today `/` redirects to login or the user's dashboard.
- Page title fallback = `VITE_APP_TITLE` ("Devfin" removed in T03); primary color `#1976D2` in `WEB/.env`.
- One branch is seeded; the navbar branch picker hides itself when there is only one branch.

**Tests**
- PHPUnit 11, MySQL test DB `learning_english_test`. 34 tests pass (security/permission tests). `npm run build` passes. Pint is installed.

**Impact on the plan (to discuss with owner)**
- **T03:** do not add spatie (use Laratrust). Pinia, vue-i18n, TipTap, chart.js are already installed. **Do not add Tailwind** — use Vuetify (two CSS systems = messy and bigger site).
- **T04:** create `teacher` + `student` roles with Laratrust; reuse the existing admin seeder (username `admin`, not `ADMIN_EMAIL`). Add `student` to `users.default_part`.
- **T05:** existing admin routes already use `permission:` middleware — keep that style and add the role check.
- **T18:** `/` must become the public landing page; change "Devfin." title and color to the Rien brand.
- **T21:** sign-up must fill the required user fields (make `username` from the email, `name_kh` = name, a default branch, role `student`) and must **not** send a Telegram alert for every student login.

## Progress Log
_(One line per finished task: date — task — what changed — tests result)_

- 2026-11-11 — PLAN — owner decision: lessons are public (no login), quizzes need sign-up (name + email + password), Google sign-in later. Added T18 public landing, T21 sign-up, T22 forgot password, T23 student layout, T37 Google sign-in; renumbered tasks to T01–T46; paid courses moved to Phase 9 — tests: n/a (plan only)
- 2026-11-11 — PLAN — owner decision: T03 changed from "install packages" (already installed, see Project Notes) to "clean up unused parts" (loan leftovers, Devfin name, HR, company structure pages, user access requests). Do git (T02) first so deleted files can be restored — tests: n/a (plan only)
- 2026-11-11 — T02 — git init, first commit pushed to GitHub `main`, branch `feature/learning-platform`; removed real secrets from `API/.env.example` + `WEB/.env.example` (repo is public); fixed `laradumps.yaml` ignore — tests: pass
- 2026-11-11 — T01 — inspected API/WEB, filled Project Notes (owner confirmed by moving on) — tests: pass
- 2026-11-11 — T03 — removed HR part, departments, positions, team leader/diagram/transfer, user access requests, HR manager role, loan leftovers (40→15 row buttons, 216 unused loan translations), "Devfin" name; navbar branch picker hidden with one branch — tests: pass (28), migrate:fresh --seed on test DB: pass, build: pass
- 2026-11-11 — T04 — Laratrust roles `admin`, `teacher`, `student` added (old roles kept); `CreateStudentAccount` action (student role, username from email, head branch, `default_part=student`); new migration adds `student` to `users.default_part`; admin email/password from `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`) via `config/app.php`; admin seeder no longer assumes ID 1 — tests: pass (32), migrate:fresh --seed on test DB: pass, build: pass
- 2026-11-11 — T05 — new `config/roles.php` (role groups admin_area = admin|teacher, admin_only = admin; permissions per role) + `AccessGroup` helper; API routes split: admin area (learning `/api/web/admin/*`, address, export) and admin-only (users, roles, branches, settings, logs); RoleSeeder gives roles their permissions from config; `GET /api/web/admin/dashboard`; test users now get a real role — tests: pass (39)
- 2026-11-11 — T06 — new admin sidebar (Dashboard; People: Users; System: Roles, Branches, Settings, Activity Log, Address — each shown only with its permission); learning menu items are added by the tasks that build their pages; router blocks `/admin/*` without `admin-allow-part` (students); old staff dashboard replaced by a simple learning dashboard (students/teachers counts), stat tile moved to `components/AppStatTile.vue` — tests: pass (40), build: pass
- 2026-11-11 — T07 — tables `levels` (+ `name_km`) and `courses`; models in `app/Models/Learning`; shared model helpers `HasSortOrder`, `HasSlug`, `HasPublishStatus` + `PublishStatus` enum (status stored as text, so new values need no migration); `config/learning.php` (levels list, quiz pass score, rows per page); `LevelSeeder` A1–C1 + one free published "Grammar <level>" course each; factories — tests: pass (43), migrate:fresh --seed: pass
- 2026-11-11 — T08 — tables `lessons` + `lesson_examples`; `LessonSkill` enum (skill stored as text); models `Lesson`, `LessonExample` (examples sorted per lesson, deleted with their lesson; a course with lessons cannot be deleted); factories — tests: pass (46), migrate:fresh --seed: pass
- 2026-11-11 — T09 — tables `quizzes` (one per lesson, pass score default from `config/learning.php`), `questions`, `question_options`; `QuestionType` enum (choice vs typed answers); `QuestionOption.is_correct` hidden from JSON by default so answers cannot leak; factories — tests: pass (50), migrate:fresh --seed: pass
- 2026-11-11 — T10 — tables `quiz_attempts`, `attempt_answers`, `lesson_progress` (unique user+lesson), `writing_submissions`, `enrollments` (for later paid courses); models + `WritingStatus` enum; `User` has quizAttempts / lessonProgress / writingSubmissions / enrollments — tests: pass (52), migrate:fresh --seed: pass
- 2026-11-11 — T11 — Levels + Courses admin: API `/api/web/admin/levels|courses` (list with search/filters, `-all` for select boxes, create, update, move up/down, delete — blocked while it still has courses/lessons); Form Requests; permissions `view/add/edit/delete-levels|courses` given to admin + teacher; shared pieces for next screens: `AdminController`, `ChecksCrudPermissions`, `HasSortOrder::move()`, Vue `useCrud`, `StatusChip`, "Move up/down" row buttons in `useRowActions`; paid fields (is_free/price) not editable until Phase 9 — tests: pass (65), build: pass
- 2026-11-11 — T12 — Lessons list: API `GET /api/web/admin/lessons` (filters level, course, skill, status, search; pages; no lesson text in the list), move up/down inside the course, delete (blocked when students already took its quiz); permissions `*-lessons`; Vue list with filters (course list follows the chosen level); `SKILLS` in `constants/learning.js` — tests: pass (71), build: pass
- 2026-11-11 — T13 — Lesson editor: API show/create/update (`LessonRequest`); lesson HTML cleaned on save by `stevebauman/purify` (allowed tags in `config/purify.php` → `lesson`); Vue pages `/admin/lessons/create` and `/admin/lessons/:id` with details form, English/Khmer TipTap editors (`RichTextEditor.vue`, tables via `@tiptap/extension-table`), "Insert grammar template" + side guide that ticks off Appendix A sections (`constants/lessonTemplates.js`); shared lesson text style `assets/styles/lesson-content.scss` — tests: pass (75), build: pass
