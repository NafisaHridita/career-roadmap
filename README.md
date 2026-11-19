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
iv. Create a database named `career_roadmap`.
v. Select the database.
vi. Import the `database.sql` file.
vii. Copy `config/db.example.php` to `config/db.php` and set your MySQL user, password and database name.
viii. Run the setup script once from the project folder: `C:\xampp\php\php.exe tools\setup.php`. It asks for the first admin's name, email and password, then adds sample careers and approved quiz questions (it skips anything that already exists).
ix. Copy `.env.example` to `.env` and add your xAI API key as `XAI_API_KEY`. Create a key at [console.x.ai](https://console.x.ai/).
x. Configure authenticated SMTP in `.env` (`MAIL_SMTP_HOST`, `MAIL_SMTP_PORT`, `MAIL_SMTP_SECURITY`, `MAIL_SMTP_USERNAME`, `MAIL_SMTP_PASSWORD`, and a matching `MAIL_FROM_ADDRESS`). Registration codes and password-reset links use this connection; XAMPP `sendmail` is not needed. For Gmail, use a Google App Password. Outlook.com SMTP requires OAuth2, so use a different SMTP sender that supports SMTP AUTH unless OAuth2 has been configured.
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

Career and recommendation tables are created by the application when their modules are first opened. AI features require internet access and a valid `XAI_API_KEY` in `.env`. To use another model available to your xAI account, change `CAREER_AI_MODEL` in `.env`.

10. How It Works

**Accounts and roles**
- A student enters an email and password on `register.php`; no name is required. A 6-digit code is emailed and must be entered within 10 minutes to finish registration. The display name starts from the email prefix and can be edited in the profile.
- Verified students can log in right away. Admin Student Approvals is only the admin's personal list workflow: saving a registration adds it to the Admin Student List and does not approve or block login.
- `login.php` checks the hashed password and sends each user to the student or admin dashboard by role. Every student and admin page checks the session role and redirects to the login page if it does not match.

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
- **Career Management** (`admin/careers.php`): add, edit and delete careers and their required skills. Saving also refreshes the `career_skills` rows.
- **AI Recommendations**: read every saved student analysis.
- **Student Approvals / Student List** and **Admin Profile**.

**AI layer**
- All AI calls go through `careerAiGenerateText()` in `ai/ai_config.php`. It posts to xAI's Grok API and returns `ok`, `text` and `error`, so pages can show a friendly message when the key, network, or API request is unavailable.

```
Browser ──> PHP page ──> MySQL (users, questions, careers, recommendations)
                    └──> ai/*.php ──> Grok API (xAI)
```

11. Contributions by Asha

**Fixed**
- **The AI features used a local model.** They now use the Grok API; set `XAI_API_KEY` in `.env` and choose the model with `CAREER_AI_MODEL`.
- **The quiz result page had unresolved merge conflicts.** It now routes recommendation generation through the shared AI helper and saves the completed response to recommendation history.
- **AI setup was unclear.** `.env.example` documents the required key without including a real secret.
- **`database.sql` could not be imported into a new database.** Tables were created before the tables their foreign keys point to. The script now pauses foreign key checks during import.
- **The AI Career Assistant was open to anyone.** `student/ai_assistant.php` had no login check. It now requires a student login through the shared guard in `includes/auth_check.php`, which was previously an empty file.
- **The public `test.php` page exposed the server configuration through `phpinfo()`.** It has been removed.

**Developed**
- **First-time setup script** (`tools/setup.php`): creates the first admin account (registration only creates students, so there was no way to get an admin) and seeds 8 sample careers with 48 skills and 8 approved quiz questions. It is safe to run again.
- **Skill Gap Checker** (`career/skills.php`): students tick the skills they have and get a match percentage, a progress bar and the list of missing skills.
- **Roadmap focused on missing skills** (`career/roadmap.php`, `ai/generate_roadmap.php`): one click from the checker builds an AI roadmap that targets only the student's gaps.
- **Delete careers** in Career Management (`admin/careers.php`): removes the career and its skill rows in one transaction and keeps old AI recommendation records.
- **Database config template** (`config/db.example.php`): `config/db.php` is gitignored, so a fresh clone now has a template to copy.
- Updated the setup steps in this README and added the How It Works section.
