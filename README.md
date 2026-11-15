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
- AI Integration: local Ollama (default model `llama3.2`)

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
viii. Run the setup script once from the project folder: `C:
mpp\php\php.exe tools\setup.php`. It asks for the first admin's name, email and password, then adds sample careers and approved quiz questions (it skips anything that already exists).
ix. Start Ollama and download the AI model once: `ollama pull llama3.2`.
x. Run the project through localhost.

7. Local Project URL

`http://localhost/CareerRoadmap/`

8. Project Purpose
Career Roadmap aims to support students in making informed career decisions through career exploration, skill assessment, personalized learning guidance, and AI assistance.

9. Feature Modules

- `ai/` contains reusable Ollama configuration and generation logic. Student and Admin pages call these functions instead of repeating AI request code.
- `career/` contains Student-facing career browsing, career details, required skills, quiz recommendation history, and AI-generated learning roadmaps.
- `admin/careers.php` is the Admin interface for maintaining career descriptions and required skills. Saving a career also refreshes matching rows in `career_skills`. These records appear in the `career/` pages.
- `ai/generate_recommendation.php` builds the quiz recommendation. When Career Management has careers, that catalog and its required skills are included in the prompt. Results are stored in `career_recommendations` and shown to the student and on the admin AI Recommendations page.
- `student/ai_assistant.php` remains the AI Assistant page and uses the shared function in `ai/career_assistant.php`.

Career and recommendation tables are created by the application when their modules are first opened. Keep Ollama running with the configured model for AI generation features. To use another model, set the `CAREER_AI_MODEL` environment variable (for example `qwen2.5:0.5b`).

10. How It Works

**Accounts and roles**
- A student registers on `register.php`. The account is saved as `pending` and the student can log in right away.
- The admin reviews new registrations under Student Approvals. Approving moves the student to the Admin Student List.
- `login.php` checks the hashed password and sends each user to the student or admin dashboard by role. Every student and admin page checks the session role and redirects to the login page if it does not match.

**Student journey**
1. **Career Interest Quiz** (`student/quiz.php`) shows the admin-approved questions one step at a time and keeps the answers in the session.
2. **AI recommendation** (`student/quiz_result.php`) sends the answers, plus the career catalog and required skills from Career Management, to the local Ollama model. It returns 5 suggested careers with reasons, a skill gap analysis and a starter roadmap. The result is saved in `career_recommendations`.
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
- All AI calls go through `careerAiGenerateText()` in `ai/ai_config.php`. It posts to Ollama at `http://127.0.0.1:11434/api/generate` and returns `ok`, `text` and `error`, so pages can show a friendly message when Ollama is offline.

```
Browser ──> PHP page ──> MySQL (users, questions, careers, recommendations)
                    └──> ai/*.php ──> Ollama (llama3.2, on this computer)
```

11. Contributions by Asha

**Fixed**
- **The AI features did not work.** The configured model `qwen2.5:0.5b` was not installed, so every AI feature failed. The default is now `llama3.2`, and `CAREER_AI_MODEL` can change it without editing code (`ai/ai_config.php`).
- **AI requests with no options were rejected by Ollama.** Empty generation options were sent as a JSON array `[]` instead of an object `{}`. They are now always sent as an object.
- **The quiz result page crashed with a fatal timeout.** The AI recommendation can take over 3 minutes on a local model, longer than PHP's time limit. The AI helper now extends PHP's limit to match its own timeout, the recommendation gets up to 7 minutes, and a slow or offline Ollama now shows a clear message instead of a crash.
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

**Tested**
- Imported the schema into a fresh database, ran the setup script twice, and went through the full flow: register, student login, quiz, AI recommendation, skill gap check, focused roadmap, admin login, all admin pages and career delete. All pages returned without PHP errors, and student accounts are blocked from admin pages.
