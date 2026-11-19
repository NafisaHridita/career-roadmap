# Career Roadmap
~ AI-Based Career Exploration and Roadmap Platform

1. Introduction
Career Roadmap is a web-based platform designed to help students explore suitable career paths based on their interests, skills, and preferences.

2. Objectives
- Help students identify suitable career options.
- Evaluate interests through career quizzes.
- Identify skill gaps.
- Provide personalized learning roadmaps.
- Offer AI-based career assistance.

3. Main Features
- Student Registration and Login
- Admin Dashboard
- Career Quiz
- Career Recommendation
- Skill Gap Analysis
- Learning Roadmap
- AI Career Assistant
- Student Profile

4. Technologies Used
- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL
- Local Server: XAMPP
- Database Tool: phpMyAdmin
- AI Integration: Grok API through xAI (default model `grok-4.7`)

5. Database
The project uses MySQL to store user accounts, quiz questions, career information, and related data.
Database name: `career_roadmap`
Database structure can be imported using `database.sql`. The `careers` table stores `required_skills` and `created_at`. Opening Career Management also adds those columns on databases imported before this update.

6. Database Setup

i. Open XAMPP Control Panel.
ii. Start Apache and MySQL.
iii. Open phpMyAdmin.
iv. Open the **Import** tab (no need to create a database first).
v. Choose `database.sql` and click **Import**. The file creates the `career_roadmap` database and all 13 tables. Importing it again is safe: it keeps existing data.
vi. Or import from a terminal: `C:\xampp\mysql\bin\mysql.exe -u root < database.sql`
vii. Copy `config/db.example.php` to `config/db.php` and set your MySQL user, password and database name.
viii. Run the setup script once from the project folder: `C:\xampp\php\php.exe tools\setup.php`. It asks for the first admin's name, email and password, then adds sample careers and approved quiz questions (it skips anything that already exists). To only create or recover the admin without touching careers and questions, run `C:\xampp\php\php.exe tools\setup.php --admin-only`.
ix. Copy `.env.example` to `.env` and choose the AI:
   - **Grok (online):** add your xAI API key as `XAI_API_KEY`. Create a key at [console.x.ai](https://console.x.ai/). Usage is billed per token.
   - **Ollama (free, offline):** install [Ollama](https://ollama.com/), run `ollama pull llama3.2`, and add `CAREER_AI_PROVIDER=ollama` to `.env`. No key is needed, but answers are slower on a laptop (the quiz result takes about 4 minutes).
x. Configure authenticated SMTP in `.env` (`MAIL_SMTP_HOST`, `MAIL_SMTP_PORT`, `MAIL_SMTP_SECURITY`, `MAIL_SMTP_USERNAME`, `MAIL_SMTP_PASSWORD`, and a matching `MAIL_FROM_ADDRESS`). Registration codes and password-reset links use this connection; XAMPP sendmail settings are not required.
xi. Run the project through localhost.

7. Local Project URL

`http://localhost/CareerRoadmap/`

8. Project Purpose
Career Roadmap aims to support students in making informed career decisions through career exploration, skill assessment, personalized learning guidance, and AI assistance.

9. Feature Modules

- `ai/` contains reusable Grok API configuration and generation logic. Student and Admin pages call these functions instead of repeating AI request code.
- `career/` contains Student-facing career browsing, career details, required skills, quiz recommendation history, and AI-generated learning roadmaps.
- `admin/careers.php` is the Admin interface for maintaining career descriptions and required skills. Saving a career also refreshes matching rows in `career_skills`. These records appear in the `career/` pages.
- `ai/generate_recommendation.php` builds the quiz recommendation. When Career Management has careers, that catalog and its required skills are included in the prompt. Results are stored in `career_recommendations` and shown to the student and on the admin AI Recommendations page.
- `student/ai_assistant.php` remains the AI Assistant page and uses the shared function in `ai/career_assistant.php`.

Career and recommendation tables are created by the application when their modules are first opened. With Grok, AI features require internet access and a valid `XAI_API_KEY` in `.env`. To use another model available to your xAI account, change `CAREER_AI_MODEL` in `.env`. With `CAREER_AI_PROVIDER=ollama`, the same features run on a local Ollama model (`OLLAMA_MODEL`, default `llama3.2`).

10. How It Works

**Accounts and roles**
- A student enters an email and password on `register.php`; no name is required. A 6-digit code is emailed and must be entered within 10 minutes to finish registration. The display name starts from the email prefix and can be edited in the profile.
- Verified students can log in right away. Admin Student Approvals is only the admin's personal list workflow: saving a registration adds it to the Admin Student List and does not approve or block login.
- `login.php` checks the hashed password (with a lockout after 5 wrong passwords in 15 minutes) and sends each user to the student or admin dashboard by role. Every student and admin page checks the session role and redirects to the login page if it does not match.

**Student journey**
1. **Career Interest Quiz** (`student/quiz.php`) shows the admin-approved questions one step at a time and keeps the answers in the session.
2. **AI recommendation** (`student/quiz_result.php`) sends the answers, plus the career catalog and required skills from Career Management, to the Grok API. It returns 5 suggested careers with reasons, a skill gap analysis and a starter roadmap. The result is saved in `career_recommendations`.
3. **Career Explorer** (`career/career_list.php`, `career_details.php`) lists every career with its description and required skills.
4. **Skill Gap Checker** (`career/skills.php?id=…`): the student ticks the skills they already have and sees a match percentage and the skills still missing.
5. **Learning Roadmap** (`career/roadmap.php`) asks the AI for a 4-stage plan. When opened from the Skill Gap Checker, the plan focuses on the missing skills.
6. **My Recommendations** (`career/recommendation.php`) shows past quiz results.
7. **AI Career Assistant** (`student/ai_assistant.php`) answers free-form career questions.

**Admin journey**
- **Question management**: add, edit, approve or reject quiz questions. AI can draft questions (`ai/generate_questions.php`). Only approved questions appear in the quiz.
- **Career Management** (`admin/careers.php`): add, edit, delete and search careers and their required skills. Duplicate career names are blocked. Saving also refreshes the `career_skills` rows.
- **AI Recommendations** (`admin/recommendations.php`): read every saved student analysis, search by student or career, print one result, export all as CSV, and delete test results.
- **Reports & Analytics** (`admin/reports.php`): live numbers and charts (registrations, quiz results, most recommended careers, careers by category and demand, most needed skills).
- **Activity Log** (`admin/activity.php`): logins, wrong passwords, lockouts, registrations, career changes and exports, with an **Unlock** button for locked logins.
- **CSV export** (`admin/export.php`): careers, AI recommendations and students as Excel-ready files.
- **Student Approvals / Student List** and **Admin Profile**.

**AI layer**
- All AI calls go through `careerAiGenerateText()` in `ai/ai_config.php`. It posts to xAI's Grok API (or to local Ollama when `CAREER_AI_PROVIDER=ollama`) and returns `ok`, `text` and `error`, so pages can show a friendly message when the key, network, or API request is unavailable.

```
Browser ──> PHP page ──> MySQL (users, questions, careers, recommendations)
                    └──> ai/*.php ──> Grok API (xAI)
```

11. Contributions by Suborna

Team: **Hridita**, **Joynab** and **Suborna**. This section lists Suborna's part (GitHub: `ashasuborna`).

**11.0 Complete feature list (all 5 commits)**

Step-by-step test and demo for every feature below, with code line references: **`DEMO_GUIDE.md` → Part F**.

In the code, each of these changes is marked with a comment that starts with `[ashasuborna]` and ends with the feature number, for example `// [ashasuborna] Login lockout check (#6)`. In VS Code press `Ctrl+Shift+F` and search `[ashasuborna]` to list all of them (61 places in 22 files).

Commits: `b5cfd8b` (careers + AI catalog), `9a0d0b3` (AI fixes, Skill Gap, setup, security), `f7839e7` (demo guide), `2cdffda` (registration checks, career and recommendation tools), `c544281` (reports, lockout, activity log, CSV export), plus the review fixes merged with Hridita's email-verification update.

Note: `register.php` was later rebuilt by Hridita with email verification (email + password + 6-digit code, no name). My earlier register-page checks were replaced by that flow, and the approval-before-login rule was removed after review, because the team rule is that verified students can log in right away. Features 1, 2, 4 and 5 below describe what is in the code now.

*Accounts*
1. Every verified registration is written to the Activity Log and gets a registration date (`users.created_at`) for Reports (`register.php`)
2. Safe request values: a value sent as a list (for example `?q[]=x`) no longer causes PHP warnings or a fatal error on Login, Careers, AI Recommendations, Activity Log and CSV export (`requestText()` in `includes/admin_tools.php`)
3. Password strength meter (Too short → Weak → Fair → Good → Strong) on the registration form (`register.php`)
4. Database errors handled: PHP 8.1+ mysqli throws exceptions, so setup, career save, recommendation delete, the activity log and the login lockout catch them and show a clear message instead of a fatal error
5. Login follows the team rule: verified students log in right away; `account_status` only controls the admin Student List (`login.php`)
6. Login lockout: "N attempt(s) left", then locked for 15 minutes after 5 wrong passwords (`login.php`, `includes/admin_tools.php`)
7. One "Incorrect email or password" message for both cases, so emails are not revealed (`login.php`)
8. New session ID after login (session fixation protection) and email stored in the session (`login.php`)
9. Removed the blank line before `<?php` in `login.php` and `register.php` that broke redirect headers

*Career Management (`admin/careers.php`)*

10. Required skills saved one per row in `career_skills` on every add and edit (`syncCareerSkillRows` in `includes/module_helpers.php`)
11. Error messages when a career cannot be added, updated or its skills saved
12. Delete a career in one transaction (skill rows removed, old AI records kept)
13. Duplicate career names blocked (case-insensitive); at least one skill required; form keeps your input on error
14. Search by name, description or skill, with a result count
15. Totals (careers, required skills), skill count per career, skills shown as tags
16. Career category and job demand (High/Medium/Low) with coloured badges and filters
17. CSV export of all careers

*AI Recommendations (`admin/recommendations.php`)*

18. Summary: saved results, number of students, latest date
19. Search by student name, email or career
20. Show / Hide each result, print one result, delete a result (with confirmation)
21. CSV export of all results

*New admin pages*

22. Reports & Analytics (`admin/reports.php`): 8 key numbers, 7 charts, period 7/14/30 days, print, students CSV
23. Activity Log (`admin/activity.php`): logins, wrong passwords, lockouts, registrations, career changes, deleted results and exports; filter, search, locked-logins list and **Unlock**
24. CSV export page (`admin/export.php`): careers, AI results, students; UTF-8 for Excel and formula-injection safe (cells starting with `=`, `+`, `-` or `@`, even after leading spaces, are kept as plain text)
25. Admin tools menu on Careers, AI Recommendations, Reports and Activity log; all four pages are admin-only

*Student features*

26. Skill Gap Checker (`career/skills.php`): tick your skills → "Skill match: N%", progress bar, missing skills; fake skill names are ignored
27. Roadmap focused on the missing skills (`career/roadmap.php`, `ai/generate_roadmap.php`)
28. Roadmap AI runs only when **Generate learning roadmap** is clicked, not every time the page opens (`career/roadmap.php`)
29. Skill Gap styles: tick boxes, progress bar, missing-skill tags (`career/common.php`)
30. AI quiz recommendation uses the admin's career list and its required skills (`student/quiz_result.php`, `ai/generate_recommendation.php`)

*AI*

31. AI model fixed: the missing `qwen2.5:0.5b` model replaced by `llama3.2`; the local model can be changed with `OLLAMA_MODEL` (`ai/ai_config.php`)
32. Ollama request fix: empty options are sent as `{}` instead of `[]`, which Ollama rejected (`ai/ai_config.php`)
33. Timeout fix: PHP waits as long as the AI (`set_time_limit`), local Ollama calls get at least 10 minutes, and a slow or stopped AI shows a friendly message instead of a fatal error (`ai/ai_config.php`)
34. Free local AI option: `CAREER_AI_PROVIDER=ollama` runs every AI feature on Ollama without an API key; Grok stays the default (`ai/ai_config.php`)

*Security fixes*

35. AI Career Assistant now requires a student login; `includes/auth_check.php` (previously empty) provides `requireRole()` (`student/ai_assistant.php`)
36. Removed `test.php`, which exposed the server configuration through `phpinfo()`

*Database and setup*

37. `database.sql` imports into a new database (foreign key checks paused), creates the database itself, is safe to import again, and has no stale `AUTO_INCREMENT` counters
38. New columns and tables: `careers.required_skills`, `careers.created_at`, `careers.category`, `careers.demand_level`, `users.created_at`, `login_attempts`, `activity_log` (13 tables)
39. Older databases are upgraded automatically: missing columns and tables are added when the pages first run (`ensureCareerColumn`, `ensureAdminToolTables`)
40. First-time setup script (`tools/setup.php`): first admin account, 8 sample careers with 48 skills, 8 approved quiz questions, career categories and demand; safe to run again
41. Database config template (`config/db.example.php`)

*Documentation*

42. README: setup steps, How It Works, and this contributions section
43. `DEMO_GUIDE.md`: setup, start, demo script, 30 tests and troubleshooting

**11.1 My files**

| File | Feature area |
|---|---|
| `register.php` | Student registration |
| `login.php` | Login lockout and safe login form |
| `admin/careers.php` | Career Management |
| `admin/recommendations.php` | AI Recommendations review |
| `database.sql` | Database schema |
| `DEMO_GUIDE.md` | Demo and test guide |
| `admin/reports.php` (new) | Reports & Analytics dashboard |
| `admin/activity.php` (new) | Activity Log and login unlock |
| `admin/export.php` (new) | CSV export |
| `includes/admin_tools.php` (new) | Shared helpers: activity log, login lockout, admin menu |
| `tools/setup.php` | First-time setup and sample data, with career category and demand |
| `career/skills.php`, `career/roadmap.php`, `career/common.php` | Skill Gap Checker and focused roadmap |
| `ai/ai_config.php`, `ai/generate_recommendation.php`, `ai/generate_roadmap.php` | AI fixes, career catalog in the quiz, free Ollama option |
| `student/quiz_result.php` | Quiz recommendation uses the career list |
| `student/ai_assistant.php`, `includes/auth_check.php` | Login required for the AI Assistant |
| `includes/module_helpers.php` | Skill rows sync and automatic column upgrades |
| `config/db.example.php` | Database config template |
| `README.md` | Setup, How It Works, contributions |

**11.2 What I implemented**

*Registration (`register.php`, on top of Hridita's email-verification flow)*
- **Password strength meter** under the password field.
- Each verified registration is written to the **Activity Log**, and `users.created_at` records the date for the Reports chart.

*Login (`login.php`)*
- **Login lockout:** 5 wrong passwords in 15 minutes lock the login; each try shows the attempts left. An admin can unlock it from the Activity Log.
- Verified students log in right away (team rule); `account_status` only controls the admin Student List.
- One message, "Incorrect email or password.", for a wrong email or a wrong password, so the page does not reveal which emails are registered.
- Form values are read safely, so a value sent as a list cannot cause warnings or a crash.
- A new session ID after login (`session_regenerate_id`) to prevent session fixation. The user's email is also stored in the session.
- Removed a blank line before `<?php` in both files; it sent output before the redirect headers.

*Career Management (`admin/careers.php`)*
- Add, edit and delete careers. Delete runs in one transaction and also removes the career's skill rows.
- Every save also refreshes the `career_skills` rows used by the Skill Gap Checker.
- **Duplicate names blocked** (case-insensitive), at least one required skill needed, and the form keeps what you typed when a save fails.
- **Search** by career name, description or skill, with a result count.
- **Totals** (number of careers and required skills), a skill count on each career, and skills shown as tags.

*AI Recommendations (`admin/recommendations.php`)*
- **Summary**: saved results, number of students, latest result date.
- **Search** by student name, email or anything in the result (for example a career name).
- **Delete** a saved result (for example a test run), with a confirmation.
- Each result opens and closes with **Show / Hide**, so a long list stays readable.

*Database (`database.sql`)*
- Creates the `career_roadmap` database itself (`CREATE DATABASE IF NOT EXISTS` + `USE`), so it can be imported without creating the database first.
- Every table uses `CREATE TABLE IF NOT EXISTS`, so importing again is safe and keeps data.
- Removed the leftover `AUTO_INCREMENT=…` counters from the old export, so a new database starts IDs at 1.
- Foreign key checks are paused during import, so tables can be created in any order.
- New: `users.created_at`, `careers.category`, `careers.demand_level`, and two new tables, `login_attempts` and `activity_log` (13 tables in total). Older databases get these automatically the first time the pages run.
- Tested: a fresh import creates the same 13 tables and columns as the running database.

*Demo guide (`DEMO_GUIDE.md`)*
- Setup with free Ollama or Grok, email setup, a demo script for my part and a test checklist.

**11.2b Extra features you can see in the UI**

| Feature | Where | What it shows |
|---|---|---|
| **Reports & Analytics dashboard** | Admin → Reports (`admin/reports.php`) | 8 key numbers (students in the admin list / new students, careers, quiz results, quiz rate, logins, failed logins, admins) and 7 charts: daily registrations, daily quiz results, most recommended careers, admin list vs new students donut, careers by category, job demand, most needed skills. Period: 7, 14 or 30 days. **Print report** button. |
| **Login lockout (brute-force protection)** | `login.php` | After each wrong password: "N attempt(s) left". After 5 wrong passwords in 15 minutes the login is locked for 15 minutes (🔒 message), even with the right password. |
| **Activity Log** | Admin → Activity log (`admin/activity.php`) | Every login, wrong password, lockout, registration, career change, deleted result and export, with time, user and IP. Filter by action, search, and an **Unlock** button for locked logins. |
| **CSV export (Excel)** | ⬇ Export CSV buttons on Careers, AI Recommendations and Reports | Downloads careers, AI results or students as a CSV that opens in Excel (UTF-8, formula-safe). |
| **Career category and job demand** | Career Management | Category (Technology, Healthcare, …) and demand (High/Medium/Low) on each career, shown as coloured badges, with filters. Used by the Reports charts. |
| **Password strength meter** | `register.php` | Colour bar under the password field (Too short → Weak → Fair → Good → Strong). |
| **Print one AI result** | AI Recommendations → 🖨 Print | Prints only that student's result. |
| **Admin tools menu** | Top of Careers, AI Recommendations, Reports, Activity log | One click between the four pages. |

Security points to mention: passwords hashed (`password_hash`), prepared statements everywhere (no SQL injection), all output escaped (no XSS), new session ID on login, brute-force lockout, admin-only pages, CSV formula-injection protection.

**11.3 How to check my work (about 10 minutes)**
1. Start the app (see `DEMO_GUIDE.md`, Part B) and open http://127.0.0.1:8080.
2. **Register:** click Register, enter an email you can open and a password, then enter the 6-digit code from the email. Watch the password strength bar while typing. You land on the login page with "Registration successful! You can log in now."
3. **Login:** log in with the new account. The Student Dashboard opens right away (no approval needed).
4. **Student list (optional):** log in as admin → Student Approvals → **Save**. This only adds the student to the admin list.
5. **Activity log:** as admin open Activity log; the registration and the login are listed.
6. **Wrong password:** log out and try a wrong password. You see "Incorrect email or password. N attempt(s) left…"
7. **Career Management** (as admin): check the totals, search `sql`, add a career with skills on separate lines, try adding it again (blocked as a duplicate), edit it, then delete it.
8. **AI Recommendations** (as admin): check the summary, search a student name, open and close a result, and delete a test result.
9. **Database:** in phpMyAdmin import `database.sql` again. It finishes without errors and nothing is lost.
10. **Password strength:** on Register, type a short lowercase password (Weak), then a long one with capitals, numbers and a symbol (Strong).
11. **Lockout:** log out and enter a wrong password 5 times for one account. Each try shows the attempts left; the 5th locks the login for 15 minutes, even with the right password.
12. **Activity log:** log in as admin → Activity log. See the wrong passwords and the lock, then click **Unlock**. The student can log in again.
13. **Category and demand:** in Career Management, edit a career, set Category and Job demand, save; then filter by category or "High" demand.
14. **Reports:** open Reports, switch the period (7/14/30 days) and click **Print report**.
15. **CSV export:** click ⬇ Export CSV on Careers and AI Recommendations, and ⬇ Students CSV on Reports. Open the files in Excel.

**11.4 Earlier fixes and features (commits `b5cfd8b`, `9a0d0b3`, `f7839e7`)**

*Fixed*
- **`database.sql` could not be imported into a new database.** Tables were created before the tables their foreign keys point to. The script now pauses foreign key checks during import.
- **The AI Career Assistant was open to anyone.** `student/ai_assistant.php` had no login check. It now requires a student login through the shared guard in `includes/auth_check.php`, which was previously an empty file.
- **The public `test.php` page exposed the server configuration through `phpinfo()`.** It has been removed.
- **The AI model did not exist on the computer.** `qwen2.5:0.5b` was replaced by `llama3.2` (changeable with `CAREER_AI_MODEL`).
- **Ollama rejected requests with empty options.** They are now sent as a JSON object (`{}`), not an array.
- **The quiz result page stopped with "Maximum execution time exceeded".** PHP now waits as long as the AI, the recommendation gets up to 7 minutes, and a slow or stopped AI shows a friendly message.
- **The roadmap page called the AI every time it opened.** It now runs only when **Generate learning roadmap** is clicked.
- **Older databases missed the `required_skills` and `created_at` columns.** `ensureCareerColumn()` adds them automatically.

*Developed*
- **Career catalog in the AI quiz** (`student/quiz_result.php`, `ai/generate_recommendation.php`): the quiz recommendation now prefers careers from Career Management and uses their required skills.
- **Required skills saved per row** (`includes/module_helpers.php`): every career save refreshes its `career_skills` rows.
- **First-time setup script** (`tools/setup.php`): creates the first admin account (registration only creates students, so there was no way to get an admin) and seeds 8 sample careers with 48 skills and 8 approved quiz questions. It is safe to run again.
- **Skill Gap Checker** (`career/skills.php`): students tick the skills they have and get a match percentage, a progress bar and the list of missing skills.
- **Roadmap focused on missing skills** (`career/roadmap.php`, `ai/generate_roadmap.php`): one click from the checker builds an AI roadmap that targets only the student's gaps.
- **Delete careers** in Career Management (`admin/careers.php`): removes the career and its skill rows in one transaction and keeps old AI recommendation records.
- **Database config template** (`config/db.example.php`): `config/db.php` is gitignored, so a fresh clone now has a template to copy.
- **Free local AI option** (`ai/ai_config.php`): set `CAREER_AI_PROVIDER=ollama` in `.env` to run every AI feature on local Ollama (`llama3.2`) without an API key. Grok stays the default.
- Updated the setup steps in this README and added the How It Works section.
