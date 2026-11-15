# Career Roadmap: Demo and Test Guide

Follow these steps in order. Part A is a one-time setup, Part B starts the app, Part C is the demo script and Part D lists the checks to tick off.

---

## Part A: One-time setup (about 10 minutes)

1. **Start MySQL.** Open the **XAMPP Control Panel** and click **Start** next to **MySQL**. It turns green.
2. **Database:** the database `career_roadmap` and its tables already exist on this computer, so skip this step. On a new computer, open phpMyAdmin (http://localhost/phpmyadmin), create a database named `career_roadmap`, and import `database.sql`.
3. **Database connection:** `config/db.php` already exists on this computer. On a new computer, copy `config/db.example.php` to `config/db.php`.
4. **Create your admin account and sample data.** Open a terminal in the project folder (`C:\Users\Asha\career-roadmap`) and run:

   ```
   C:\xampp\php\php.exe tools\setup.php
   ```

   Type your admin name, email and a password of 8 or more characters. It then adds 8 sample careers (with 48 skills) and 8 approved quiz questions. Write down the admin email and password.
5. **Install the AI model** (already done on this computer). Make sure **Ollama** is running (llama icon in the system tray), then run this once:

   ```
   ollama pull llama3.2
   ```

---

## Part B: Start the app (every time)

1. **MySQL:** in the XAMPP Control Panel, MySQL must be green.
2. **Ollama:** it must be running. To check, open http://127.0.0.1:11434 in a browser. It should say "Ollama is running".
3. **Start the website** from a terminal in the project folder:

   ```
   C:\xampp\php\php.exe -S 127.0.0.1:8080
   ```

   Keep this window open while you demo.
4. Open **http://127.0.0.1:8080** in your browser.

> Option: to use XAMPP Apache instead, copy the project folder to `C:\xampp\htdocs\CareerRoadmap`, start **Apache** in XAMPP, and open http://localhost/CareerRoadmap/.

**Tip for warming up the AI before the demo:** ask the AI Career Assistant one question about 5 minutes before you start. The first AI request loads the model into memory, so later answers are faster.

---

## Part C: Demo script (15 to 20 minutes)

### 1. Home page (1 min)
- Open http://127.0.0.1:8080 and show the landing page.
- Say: "Career Roadmap helps students find a career, see their skill gaps and get an AI learning plan."

### 2. Admin side (4 min)
1. Click **Log in** and use the admin account from Part A, step 4. You land on the **Admin Dashboard**.
2. **Career Management:** show the 8 careers and their required skills.
   - Add a new career, for example "Nurse" with skills on separate lines (Patient care, Communication, Medical knowledge), then click **Save career**.
   - Click **Edit** on it and change something.
   - Click **Delete** on it and confirm. Say: *"I added delete. It also removes the career's skill rows safely in one step."*
3. **Manage Questions / Question List:** show the approved quiz questions. Only approved questions appear in the student quiz.
4. Click **Logout**.

### 3. Student registration (2 min)
1. Click **Register** and create a student account (any name, email and password).
2. Log in as that student. You land on the **Student Dashboard**.
3. Optional: log back in as admin and open **Student Approvals** to show the new registration, then click **Save & approve**.

### 4. Skill Gap Checker (3 min), a feature you built
1. In the student dashboard, click **Career Explorer → Explore careers**.
2. Open **Software Developer**, then click **Explore skills**, or go to **Skills** and click **Check my skill gap**.
3. Tick 2 skills, for example *Problem solving* and *Teamwork*, then click **Check my skill gap**.
4. Show the result: **Skill match: 33%**, the progress bar and the list of missing skills.
5. Say: *"Students can see exactly which skills they still need."*

### 5. AI roadmap for missing skills (2 to 3 min), a feature you built
1. On the same result, click **Build a roadmap for my missing skills**.
2. The page shows "Focusing on the skills you are missing". Click **Generate learning roadmap**.
3. Wait 1 to 2 minutes. The AI returns a 4-stage plan aimed only at the missing skills.

### 6. Career Interest Quiz and AI recommendation (5 min)
1. Go back to the dashboard, click **Start quiz** and answer all 8 questions with **Next**.
2. On the last question, click **Finish quiz →**. The AI analyses the answers. **This takes about 4 minutes on this computer**, so start it, then talk about the project while it loads, or show step 7 in another tab.
3. The result lists 5 suggested careers with reasons, required skills, a skill gap analysis and a roadmap. It prefers careers from the admin's Career Management list.
4. Open **My Recommendations** to show the result was saved.
5. As admin, **AI Recommendations** shows the same result.

### 7. AI Career Assistant (1 min)
1. From the dashboard, open **Ask the assistant**.
2. Ask: *"What skills do I need to become a data analyst?"* The answer takes about 25 seconds.

### 8. Show your contributions (1 min)
- Open `README.md` on GitHub and scroll to **11. Contributions by Asha**.
- Read out the **Fixed** and **Developed** lists.

---

## Part D: Test checklist

Tick each one before the demo.

| # | Test | How | Expected result |
|---|------|-----|-----------------|
| 1 | Home page | Open http://127.0.0.1:8080 | Landing page loads |
| 2 | Admin login | Log in with the admin from setup | Admin Dashboard opens |
| 3 | Add, edit and delete career | Career Management | "Career added", "Career updated", "Career deleted" messages |
| 4 | Register | Register a new student | Redirects to the login page with a green success message |
| 5 | Student login | Log in as the student | Student Dashboard opens |
| 6 | Student can't open admin pages | While logged in as a student, open http://127.0.0.1:8080/admin/careers.php | Sent back to the login page |
| 7 | Assistant needs login | Log out, then open http://127.0.0.1:8080/student/ai_assistant.php | Sent to the login page (this was open to anyone before the fix) |
| 8 | Skill Gap Checker | Skills → Check my skill gap, tick 2 of 6 | "Skill match: 33%" and 4 missing skills |
| 9 | Focused roadmap | Build a roadmap for my missing skills → Generate | 4-stage plan about the missing skills (1 to 2 min) |
| 10 | Quiz recommendation | Finish the quiz | 5 careers with skill gap and roadmap (about 4 min), no error |
| 11 | Saved results | My Recommendations (student) and AI Recommendations (admin) | The quiz result appears in both |
| 12 | AI Assistant | Ask a career question | Answer in about 25 seconds |
| 13 | Ollama offline message | Quit Ollama, then ask the assistant a question | A friendly "Could not get a reply from local Ollama…" message, not a crash. Start Ollama again afterwards |
| 14 | Setup is safe to rerun | Run `C:\xampp\php\php.exe tools\setup.php` again | Says "already exists - skipping" for each step |
| 15 | phpinfo removed | Open http://127.0.0.1:8080/test.php | Not found |

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| "Database connection failed" | Start MySQL in the XAMPP Control Panel |
| Can't log in as admin | Run `tools\setup.php` (Part A, step 4). If an admin already exists, use that account's email |
| "No approved quiz questions" or "No career paths" | Run `tools\setup.php`, or add them as admin |
| "Could not get a reply from local Ollama" | Start Ollama and check that `ollama list` shows `llama3.2` |
| AI is very slow | Normal on a laptop CPU. Warm it up first (Part B tip), and close heavy apps |
| Port 8080 already in use | Use another port: `C:\xampp\php\php.exe -S 127.0.0.1:8090`, then open http://127.0.0.1:8090 |
