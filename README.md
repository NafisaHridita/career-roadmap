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
viii. Run the setup script once from the project folder: `C:\xampp\php\php.exe tools\setup.php`. It asks for the first admin's name, email and password, then adds sample careers and approved quiz questions (it skips anything that already exists).
ix. Copy `.env.example` to `.env` and choose the AI:
   - **Grok (online):** add your xAI API key as `XAI_API_KEY`. Create a key at [console.x.ai](https://console.x.ai/). Usage is billed per token.
   - **Ollama (free, offline):** install [Ollama](https://ollama.com/), run `ollama pull llama3.2`, and add `CAREER_AI_PROVIDER=ollama` to `.env`. No key is needed, but answers are slower on a laptop (the quiz result takes about 4 minutes).
x. Run the project through localhost.

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
- A student registers on `register.php` (valid email, password of 8+ characters typed twice, email not already used). The account is saved as `pending`.
- A pending student cannot log in yet: the login page says the account is waiting for admin approval.
- The admin reviews new registrations under Student Approvals. Approving moves the student to the Admin Student List, and the student can now log in.
- `login.php` checks the hashed password and the approval status, then sends each user to the student or admin dashboard by role. Every student and admin page checks the session role and redirects to the login page if it does not match.

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

**11.1 My files**

| File | Feature area |
|---|---|
| `register.php` | Student registration |
| `login.php` | Login and approval check |
| `admin/careers.php` | Career Management |
| `admin/recommendations.php` | AI Recommendations review |
| `database.sql` | Database schema |
| `DEMO_GUIDE.md` | Demo and test guide |
| `admin/reports.php` (new) | Reports & Analytics dashboard |
| `admin/activity.php` (new) | Activity Log and login unlock |
| `admin/export.php` (new) | CSV export |
| `includes/admin_tools.php` (new) | Shared helpers: activity log, login lockout, admin menu |
| `tools/setup.php` | Sample data, now with career category and demand |

**11.2 What I implemented**

*Registration (`register.php`)*
- Server-side checks, so the rules hold even if the browser form is bypassed: all fields required, name up to 100 characters (matches the `users.name` column), valid email, password of at least 8 characters.
- **Confirm password** field; the two passwords must match.
- Clear message when the email is already registered, checked before saving.
- New students are saved as `pending` and sent to the login page with "Registration received! An admin must approve your account before you can log in."

*Login (`login.php`)*
- **Approval check:** a pending student cannot log in and sees "Your account is waiting for admin approval." After the admin approves the account in Student Approvals, the same login works.
- One message, "Incorrect email or password.", for a wrong email or a wrong password, so the page does not reveal which emails are registered.
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
- Setup with free Ollama or Grok, the approval flow, a demo script for my part and a test checklist.

**11.2b Extra features you can see in the UI**

| Feature | Where | What it shows |
|---|---|---|
| **Reports & Analytics dashboard** | Admin → Reports (`admin/reports.php`) | 8 key numbers (approved/pending students, careers, quiz results, quiz rate, logins, failed logins, admins) and 7 charts: daily registrations, daily quiz results, most recommended careers, approved vs pending donut, careers by category, job demand, most needed skills. Period: 7, 14 or 30 days. **Print report** button. |
| **Login lockout (brute-force protection)** | `login.php` | After each wrong password: "N attempt(s) left". After 5 wrong passwords in 15 minutes the login is locked for 15 minutes (🔒 message), even with the right password. |
| **Activity Log** | Admin → Activity log (`admin/activity.php`) | Every login, wrong password, lockout, registration, career change, deleted result and export, with time, user and IP. Filter by action, search, and an **Unlock** button for locked logins. |
| **CSV export (Excel)** | ⬇ Export CSV buttons on Careers, AI Recommendations and Reports | Downloads careers, AI results or students as a CSV that opens in Excel (UTF-8, formula-safe). |
| **Career category and job demand** | Career Management | Category (Technology, Healthcare, …) and demand (High/Medium/Low) on each career, shown as coloured badges, with filters. Used by the Reports charts. |
| **Password strength meter** | `register.php` | Colour bar (Too short → Weak → Fair → Good → Strong) and a live "✓ Passwords match" check. |
| **Print one AI result** | AI Recommendations → 🖨 Print | Prints only that student's result. |
| **Admin tools menu** | Top of Careers, AI Recommendations, Reports, Activity log | One click between the four pages. |

Security points to mention: passwords hashed (`password_hash`), prepared statements everywhere (no SQL injection), all output escaped (no XSS), new session ID on login, approval before login, brute-force lockout, admin-only pages, CSV formula-injection protection.

**11.3 How to check my work (about 10 minutes)**
1. Start the app (see `DEMO_GUIDE.md`, Part B) and open http://127.0.0.1:8080.
2. **Register:** click Register and try a wrong email, a 5-character password and two different passwords. Each shows its own message. Then register correctly: you land on the login page with the "admin must approve" message.
3. **Pending login:** log in with the new account. You see "Your account is waiting for admin approval."
4. **Approve:** log in as admin → Student Approvals → **Save & approve**. Log out.
5. **Approved login:** log in as the student. The Student Dashboard opens.
6. **Wrong password:** log out and try a wrong password. You see "Incorrect email or password."
7. **Career Management** (as admin): check the totals, search `sql`, add a career with skills on separate lines, try adding it again (blocked as a duplicate), edit it, then delete it.
8. **AI Recommendations** (as admin): check the summary, search a student name, open and close a result, and delete a test result.
9. **Database:** in phpMyAdmin import `database.sql` again. It finishes without errors and nothing is lost.
10. **Password strength:** on Register, type `abcdefgh` (Weak) then `Demo2026!pass` (Strong); type the confirm password and see "✓ Passwords match".
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

*Developed*
- **First-time setup script** (`tools/setup.php`): creates the first admin account (registration only creates students, so there was no way to get an admin) and seeds 8 sample careers with 48 skills and 8 approved quiz questions. It is safe to run again.
- **Skill Gap Checker** (`career/skills.php`): students tick the skills they have and get a match percentage, a progress bar and the list of missing skills.
- **Roadmap focused on missing skills** (`career/roadmap.php`, `ai/generate_roadmap.php`): one click from the checker builds an AI roadmap that targets only the student's gaps.
- **Delete careers** in Career Management (`admin/careers.php`): removes the career and its skill rows in one transaction and keeps old AI recommendation records.
- **Database config template** (`config/db.example.php`): `config/db.php` is gitignored, so a fresh clone now has a template to copy.
- **Free local AI option** (`ai/ai_config.php`): set `CAREER_AI_PROVIDER=ollama` in `.env` to run every AI feature on local Ollama (`llama3.2`) without an API key. Grok stays the default.
- Updated the setup steps in this README and added the How It Works section.
