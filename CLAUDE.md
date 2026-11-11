# CLAUDE.md — How to work on this project

This is an English learning website for Cambodian students (Laravel + Vue 3).
The full plan and task list is in **`PLAN.md`**. Always read it before starting work.

## Project folders
- `API/` — Laravel backend. Run `php artisan test` and `./vendor/bin/pint` here.
- `WEB/` — Vue 3 frontend. Run `npm run build` here.
- `README.md` — setup steps and how to add a new system part.

## The owner
- The owner is the project manager, is still learning English, and is not an expert programmer.
- Explain things in **simple English**, short sentences. Avoid jargon; if you must use a technical word, explain it in one line.
- Before running anything risky (deleting data, `migrate:fresh` on a real database, changing login), **ask first**.

## Workflow — ONE task at a time

When the owner says **"next"** (or "start", "continue"):
1. Open `PLAN.md`, find the **first task that is not ticked** `[ ]`.
2. Tell the owner in 2–4 lines: task ID, what you will do, which files you will change.
3. Do the task. Stay inside the task — do not start the next task.
4. Run checks: `php artisan test` (in `API/`) and `npm run build` (in `WEB/`), and `./vendor/bin/pint` (in `API/`) if installed.
5. If checks pass:
   - Tick the task `[x]` in `PLAN.md`
   - Update the **Status** table (Done column) and **Current task**
   - Add a line to **Progress Log**: `YYYY-MM-DD — T## — what changed — tests: pass`
   - Make a git commit: `T##: short description`
6. Give the owner a short report:
   - ✅ What was done
   - 🧪 How to try it (URL / steps to click)
   - ⚠️ Anything they must do (e.g. add a value to `.env`)
   - ➡️ Next task ID and name
7. **STOP and wait.** Do not continue until the owner says "next".

If a check fails and you cannot fix it in the task, do NOT tick the box. Explain the problem simply and ask.

## Other commands the owner may use
- **"status"** → show the Status table, current task, and the last 3 Progress Log lines.
- **"explain T##"** → explain that task in simple words, no code changes.
- **"redo T##"** / **"fix T##"** → reopen that task (untick it), fix, re-test, tick again, log it.
- **"skip T##"** → mark it `[~]` with reason in Progress Log, move on.
- **"test"** → run all tests and report results simply.

## Project rules (must always follow)
- Do not break the existing login/registration.
- Quizzes are **graded in Laravel only**. Never send `is_correct` or correct answers to the browser before submit.
- All `/admin` routes need `auth` + role `admin|teacher` middleware.
- Visitors and students only see `published` lessons.
- Reading lessons is public (no login). Quizzes, scores, progress, and writing submissions need login.
- Validate all input with Form Requests. Validate file uploads (type + size).
- Every feature gets at least one Feature test.
- Never hard-code passwords, API keys, or payment keys — use `.env`.
- Keep the UI mobile-first and support Khmer text (Khmer font).
- Follow **Appendix C — Design rules** in `PLAN.md`: clean, simple, Apple-style. Nothing that looks "AI-made".
- Do not start Phase 9 (paid courses) unless the owner asks.
- If you change the plan (add/split a task), update `PLAN.md` and tell the owner why.
