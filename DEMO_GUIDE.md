# Career Roadmap: Demo and Test Guide

Follow these steps in order. Part A is a one-time setup, Part B starts the app, Part C is Suborna's demo script, Part D is the full project demo and Part E lists the checks to tick off.

Team: **Hridita**, **Joynab** and **Suborna**. Suborna's files: `register.php`, `login.php`, `admin/careers.php`, `admin/recommendations.php`, `database.sql` and this guide.

---

## Part A: One-time setup (about 10 minutes)

1. **Start MySQL.** Open the **XAMPP Control Panel** and click **Start** next to **MySQL**. It turns green.
2. **Database:** on this computer the `career_roadmap` database already exists, so you can skip this. On a new computer, open phpMyAdmin (http://localhost/phpmyadmin), click **Import**, choose `database.sql` and click **Import**. The file creates the database and all 13 tables. Or run in a terminal from the project folder:

   ```
   C:\xampp\mysql\bin\mysql.exe -u root < database.sql
   ```

   Importing again is safe and keeps existing data.
3. **Database connection:** `config/db.php` already exists on this computer. On a new computer, copy `config/db.example.php` to `config/db.php`.
4. **Create your admin account and sample data.** Open a terminal in the project folder (`C:\Users\Asha\career-roadmap`) and run:

   ```
   C:\xampp\php\php.exe tools\setup.php
   ```

   Type your admin name, email and a password of 8 or more characters. It then adds 8 sample careers (with 48 skills) and 8 approved quiz questions. Write down the admin email and password. (Already done on this computer.)
5. **Choose the AI.** Copy `.env.example` to `.env` (already done on this computer), then pick one:
   - **Ollama, free (set up on this computer):** install [Ollama](https://ollama.com/), run `ollama pull llama3.2` once, and put `CAREER_AI_PROVIDER=ollama` in `.env`. No key and no internet needed, but slower: the quiz result takes about 4 minutes.
   - **Grok, online:** put `CAREER_AI_PROVIDER=grok` and your key from [console.x.ai](https://console.x.ai/) as `XAI_API_KEY` in `.env`. Do not share or commit the key. Grok usage is billed per token, so set a spending limit first.

---

## Part B: Start the app (every time)

1. **MySQL:** in the XAMPP Control Panel, MySQL must be green.
2. **AI:** with Ollama, check that the llama icon is in the system tray (or open http://127.0.0.1:11434, which says "Ollama is running"). With Grok, check the internet connection.
3. **Start the website** from a terminal in the project folder:

   ```
   C:\xampp\php\php.exe -S 127.0.0.1:8080 -t . tools\dev_router.php
   ```

   Keep this window open while you demo.
4. Open **http://127.0.0.1:8080** in your browser.

> Option: to use XAMPP Apache instead, copy the project folder to `C:\xampp\htdocs\CareerRoadmap`, start **Apache** in XAMPP, and open http://localhost/CareerRoadmap/.

**Tip:** ask the AI Assistant one short question about 5 minutes before the demo. With Ollama this loads the model into memory so later answers are faster; with Grok it confirms the key works.

---

## Part C: Suborna's demo (about 10 minutes)

Say first: *"My part is accounts (register and login with admin approval), Career Management, the AI Recommendations review page and the database schema."*

### C1. Registration with validation (2 min) · `register.php`
1. Log out if needed, then click **Register**.
2. Show the checks. Each one shows its own message:
   - email `rina@` → "Enter a valid email address."
   - password `abc` → "Password must be at least 8 characters."
   - two different passwords → "The two passwords do not match."
   - an email that already exists → "An account with this email already exists."
3. **Password strength meter:** type `abcdefgh` → the bar shows *Weak*; type `Demo2026!pass` → *Strong*. In Confirm password, the line turns to **✓ Passwords match**.
4. Register correctly, for example *Rina Akter*, `rina2@test.com`, `Demo2026!pass` twice.
5. You land on the login page with: **"Registration received! An admin must approve your account before you can log in."**

### C2. Login with admin approval (3 min) · `login.php`
1. Log in as the new student → **"Your account is waiting for admin approval."** Say: *"New students cannot log in until an admin approves them."*
2. Try a wrong password → **"Incorrect email or password. 4 attempt(s) left…"** Say: *"The same message for a wrong email or password, so the page does not reveal which emails exist."*
3. **Lockout:** enter a wrong password 4 more times → **🔒 "Too many failed attempts… locked for 15 minutes."** Now even the right password is refused. Say: *"This stops password-guessing (brute-force) attacks."*
4. Log in as **admin** → **Activity log**: show the wrong passwords and the lock in the list, then click **Unlock** next to the email.
5. **Student Approvals** → find the student → **Save & approve** → **Logout**.
6. Log in as the student again → the **Student Dashboard** opens.

### C3. Career Management (3 min) · `admin/careers.php`
1. Log in as admin → **Career Management**. Show the totals at the top (careers and required skills) and the skill tags and count on each career.
2. **Search** `sql` → only careers that mention SQL are listed. Click **Clear**.
3. **Add** a career, for example *Nurse* with skills on separate lines: Patient care, Communication, Medical knowledge, **Category** Healthcare, **Job demand** High → **Career added.** It shows coloured *Healthcare* and *High demand* badges.
   - Filter by **Technology** or **High** demand to show the filters.
4. Add *nurse* again → **blocked as a duplicate**. Say: *"Duplicate career names are blocked, and the form keeps what I typed."*
5. Click **Edit** on Nurse, change the description → **Update career** → **Career updated.**
6. Click **Delete** → confirm → **Career deleted.** Say: *"Delete also removes its skill rows in one transaction."*
7. Click **⬇ Export CSV** and open the file in Excel.
8. Say: *"These careers feed the student Career Explorer, the Skill Gap Checker and the AI quiz."*

### C4. AI Recommendations review (1 min) · `admin/recommendations.php`
1. Open **AI Recommendations** (use the menu at the top: Careers · AI Recommendations · Reports · Activity log). Show the summary: saved results, students, latest date.
2. **Search** a student's name or a career such as `Data Analyst`.
3. Click **Show / Hide** on a result, then **🖨 Print** to print only that result. Delete a test result if there is one (it asks to confirm).
4. **⬇ Export CSV** downloads every result for Excel.

### C4b. Reports & Analytics (2 min) · `admin/reports.php`
1. Open **Reports**. Show the 8 key numbers at the top.
2. Show the charts: new registrations per day, AI quiz results per day, most recommended careers, approved vs pending donut, careers by category, job demand, most needed skills.
3. Change **Period** to 7 or 30 days.
4. Click **🖨 Print report** and **⬇ Students CSV**. Say: *"The admin gets live analytics from the database, ready to print or export."*

### C4c. Activity Log (1 min) · `admin/activity.php`
1. Open **Activity log**: every login, wrong password, lockout, registration, career change and export, with time, user and IP.
2. Filter by **Wrong password** or **Career added**, or search a name.

### C5. Database (1 min) · `database.sql`
- Open `database.sql` and point out `CREATE DATABASE IF NOT EXISTS`, `CREATE TABLE IF NOT EXISTS` and the paused foreign key checks.
- Say: *"Importing creates the database and all 13 tables, and importing again is safe."*

### C6. Show the README (30 s)
- Open `README.md` → **11. Contributions by Suborna**: my files, what I implemented and how to check it.

---

## Part D: Full project demo (15 to 20 minutes)

### 1. Home page (1 min)
- Open http://127.0.0.1:8080 and show the landing page.
- Say: "Career Roadmap helps students find a career, see their skill gaps and get an AI learning plan."

### 2. Admin side (4 min)
1. Click **Log in** and use the admin account from Part A, step 4. You land on the **Admin Dashboard**.
2. **Career Management:** show the careers and their required skills (see C3).
3. **Manage Questions / Question List:** show the approved quiz questions. Only approved questions appear in the student quiz.
4. Click **Logout**.

### 3. Student registration and approval (2 min)
1. Click **Register** and create a student account.
2. Try to log in: the account is waiting for approval.
3. Log in as admin → **Student Approvals** → **Save & approve**, then log in as the student. You land on the **Student Dashboard**.

### 4. Skill Gap Checker (3 min)
1. In the student dashboard, click **Career Explorer → Explore careers**.
2. Open **Software Developer**, then click **Explore skills**, or go to **Skills** and click **Check my skill gap**.
3. Tick 2 skills, for example *Problem solving* and *Teamwork*, then click **Check my skill gap**.
4. Show the result: **Skill match: 33%**, the progress bar and the list of missing skills.

### 5. AI roadmap for missing skills (2 to 3 min)
1. On the same result, click **Build a roadmap for my missing skills**.
2. The page shows "Focusing on the skills you are missing". Click **Generate learning roadmap**.
3. Wait 1 to 2 minutes. The AI returns a 4-stage plan aimed only at the missing skills.

### 6. Career Interest Quiz and AI recommendation (5 min)
1. Go back to the dashboard, click **Start quiz** and answer all 8 questions with **Next**.
2. On the last question, click **Finish quiz →**. **With Ollama this takes about 4 minutes**, so start it, then talk about the project while it loads.
3. The result lists 5 career options with the evidence for each, skills to build, a first step, a reality check and a 12-week roadmap. It prefers careers from the admin's Career Management list.
4. Open **My Recommendations** to show the result was saved.
5. As admin, **AI Recommendations** shows the same result.

### 7. AI Career Assistant (1 min)
1. From the dashboard, open **Ask the assistant**.
2. Ask: *"What skills do I need to become a data analyst?"* With Ollama the answer takes about 25 seconds.

---

## Part E: Test checklist

Tick each one before the demo. Tests 1 to 12 are Suborna's part.

| # | Test | How | Expected result |
|---|------|-----|-----------------|
| 1 | Register: bad email | Register with email `rina@` | "Enter a valid email address." |
| 2 | Register: short password | Password `abc` | "Password must be at least 8 characters." |
| 3 | Register: passwords differ | Two different passwords | "The two passwords do not match." |
| 4 | Register: email exists | Use an email that is already registered | "An account with this email already exists." |
| 5 | Register: success | Register correctly | Login page with a green "admin must approve" message |
| 6 | Pending login blocked | Log in with the new account before approval | "Your account is waiting for admin approval." |
| 7 | Approval works | Admin → Student Approvals → Save & approve, then log in | Student Dashboard opens |
| 8 | Wrong password | Wrong password, or an email that does not exist | "Incorrect email or password." (same for both) |
| 9 | Careers: add, edit, delete | Career Management | "Career added", "Career updated", "Career deleted" |
| 10 | Careers: duplicate and search | Add an existing name; search `sql` | Duplicate blocked; only matching careers listed |
| 11 | Recommendations: search and delete | AI Recommendations | Search finds the student; delete asks first, then "Recommendation deleted." |
| 12 | Database import | Import `database.sql` again in phpMyAdmin | Finishes without errors; data is kept |
| 12a | Password strength | Register: type `abcdefgh`, then `Demo2026!pass` | *Weak*, then *Strong*; "✓ Passwords match" when both match |
| 12b | Login lockout | 5 wrong passwords for one email | "N attempt(s) left", then 🔒 locked for 15 minutes, even with the right password |
| 12c | Unlock | Admin → Activity log → Unlock | "Login unlocked"; the user can log in again |
| 12d | Activity log | Admin → Activity log, filter "Wrong password" | Only wrong-password events are listed |
| 12e | Category and demand | Add a career with Healthcare / High; filter High | Coloured badges; filter shows only High-demand careers |
| 12f | Reports | Admin → Reports, change period | Numbers and charts load; Print report opens the print dialog |
| 12g | CSV export | Export CSV on Careers, AI Recommendations, Reports | A `.csv` file downloads and opens in Excel |
| 12h | Admin-only | As a student, open http://127.0.0.1:8080/admin/reports.php | Sent to the login page |
| 13 | Home page | Open http://127.0.0.1:8080 | Landing page loads |
| 14 | Student can't open admin pages | As a student, open http://127.0.0.1:8080/admin/careers.php | Sent to the login page |
| 15 | Assistant needs login | Log out, then open http://127.0.0.1:8080/student/ai_assistant.php | Sent to the login page |
| 16 | Skill Gap Checker | Skills → Check my skill gap, tick 2 of 6 | "Skill match: 33%" and 4 missing skills |
| 17 | Focused roadmap | Build a roadmap for my missing skills → Generate | 4-stage plan about the missing skills |
| 18 | Quiz recommendation | Finish the quiz | 5 careers and a roadmap, no error |
| 19 | Saved results | My Recommendations (student) and AI Recommendations (admin) | The quiz result appears in both |
| 20 | AI Assistant | Ask a career question | An answer, not an error |
| 21 | Setup is safe to rerun | Run `C:\xampp\php\php.exe tools\setup.php` again | "already exists - skipping" for each step |
| 22 | phpinfo removed | Open http://127.0.0.1:8080/test.php | No server information is shown |

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| "Database connection failed" | Start MySQL in the XAMPP Control Panel |
| Can't log in as admin | Run `tools\setup.php` (Part A, step 4). If an admin already exists, use that account's email |
| A student can't log in | The account is still pending: approve it as admin under Student Approvals |
| "Too many failed attempts" | Wait 15 minutes, or log in as admin → Activity log → **Unlock** |
| Reports charts are empty | Normal on a new database: register students and finish a quiz first |
| "No approved quiz questions" or "No career paths" | Run `tools\setup.php`, or add them as admin |
| "Grok API key is missing" | Add `XAI_API_KEY` to `.env`, or set `CAREER_AI_PROVIDER=ollama` to use free local Ollama |
| "Could not get a reply from local Ollama" | Start Ollama and check that `ollama list` shows `llama3.2` |
| Grok API request fails | Check internet access, API key/model access, and available xAI API credits |
| Port 8080 already in use | Use another port: `C:\xampp\php\php.exe -S 127.0.0.1:8090 -t . tools\dev_router.php`, then open http://127.0.0.1:8090 |
