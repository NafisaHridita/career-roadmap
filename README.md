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
- AI Integration: AI-based assistance

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
vii. Configure your local database connection.
viii. Run the project through localhost.

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

Career and recommendation tables are created by the application when their modules are first opened. Keep Ollama running with the configured model for AI generation features.
