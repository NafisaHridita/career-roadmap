# Career Roadmap: Demo and Test Guide

Follow these steps in order. Part A is a one-time setup, Part B starts the app, Part C is Suborna's demo script, Part D is the full project demo, Part E lists the checks to tick off and Part F walks through all 43 of Suborna's features one by one with code references.

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

## Part F: Step-by-step test of all 43 features (Suborna)

The numbers match **README → 11.0 Complete feature list**. For each feature: what to do, what you should see, and where the code is (open in VS Code with `Ctrl+P` for the file and `Ctrl+G` for the line).

**Before you start**
- App: http://127.0.0.1:8080 (Part B). Keep VS Code open beside the browser.
- Admin: `admin@gmail.com` / `12345678` · Approved student: `rina@test.com` / `password1` · Pending student: `student1@test.com` / `student123`
- Use a new email for registration tests, for example `demo1@test.com`, `demo2@test.com`.

### Accounts

**1. Registration checks on the server**
- Steps: Log out → **Register**. Try each of these and press **Create account**:
  1. Email `rina@` → *"Enter a valid email address."*
  2. Password `abc` (both boxes) → *"Password must be at least 8 characters."*
  3. Empty name: the browser stops you; the server also checks (*"All fields are required."*).
- Expected: one clear message each time; nothing is saved.
- Code: [register.php:20](register.php:20) (all fields) · [register.php:21](register.php:21) (name ≤ 100) · [register.php:23](register.php:23) (email) · [register.php:25](register.php:25) (8+ characters)

**2. Confirm password and "email already exists"**
- Steps: Register with two different passwords → *"The two passwords do not match."* Then use the email `rina@test.com` → *"An account with this email already exists. Please log in instead."*
- Code: [register.php:27](register.php:27) (passwords match) · [register.php:30](register.php:30) (`SELECT user_id FROM users WHERE email = ?`) · [register.php:37](register.php:37) (message) · [register.php:147](register.php:147) (confirm field)

**3. Password strength meter**
- Steps: In **Password** type `abcdefgh` → bar shows **Weak**. Change it to `Demo2026!pass` → **Strong** (green). Type the same in **Confirm password** → **✓ Passwords match**; change one letter → **✗ Passwords do not match yet**.
- Code: [register.php:123](register.php:123) (bar styles) · [register.php:156](register.php:156) and [register.php:167](register.php:167) (JavaScript score: length, upper/lower case, numbers, symbols)

**4. New students saved as pending**
- Steps: Register correctly: name *Demo One*, email `demo1@test.com`, password `Demo2026!pass` twice.
- Expected: login page with the green message *"Registration received! An admin must approve your account before you can log in."*
- Code: [register.php:43](register.php:43) (`INSERT INTO users ... 'pending'`) · [register.php:39](register.php:39) (`password_hash`) · [login.php:15](login.php:15) (message)

**5. Admin approval before login**
- Steps: Log in as `demo1@test.com` → *"Your account is waiting for admin approval."* (yellow). Log in as admin → **Student Approvals** → **Save & approve** for Demo One → Logout → log in as `demo1@test.com` → **Student Dashboard** opens.
- Code: [login.php:38](login.php:38) (`SELECT ... account_status`) · [login.php:59](login.php:59) (pending check)

**6. Login lockout**
- Steps: Log out. Log in as `rina@test.com` with a wrong password 5 times.
- Expected: *"4 attempt(s) left"*, *"3…"*, *"2…"*, *"1…"*, then 🔒 *"Too many failed attempts… locked for 15 minutes."* Now try the right password `password1` → still locked. (Unlock it in feature 23.)
- Code: [includes/admin_tools.php:6](includes/admin_tools.php:6) (5 tries, 15 minutes) · [includes/admin_tools.php:74](includes/admin_tools.php:74) (`loginLockStatus`) · [includes/admin_tools.php:100](includes/admin_tools.php:100) (`recordLoginAttempt`) · [login.php:31](login.php:31) (check before password) · [login.php:55](login.php:55) (attempts left)

**7. Same message for wrong email or wrong password**
- Steps: Log in with `nobody@test.com` / `anything`, then with a real email and a wrong password.
- Expected: both say *"Incorrect email or password…"*, so nobody can find out which emails are registered.
- Code: [login.php:47](login.php:47) (`password_verify`) · [login.php:55](login.php:55)

**8. New session ID after login**
- Steps: Show the code (no visible change). Optional: in the browser dev tools (F12 → Application → Cookies) the `PHPSESSID` value changes after you log in.
- Code: [login.php:67](login.php:67) (`session_regenerate_id(true)`) · [login.php:70](login.php:70) (email in session)

**9. No blank line before `<?php`**
- Steps: Open `login.php` and `register.php`: line 1 is `<?php`. Say: *"Output before `<?php` breaks `header()` redirects on servers without output buffering."*
- Code: [login.php:1](login.php:1) · [register.php:1](register.php:1)

### Career Management (Admin → Careers)

**10. Skills saved one per row**
- Steps: Add a career *Nurse* with skills on separate lines: `Patient care`, `Communication`. Open phpMyAdmin → `career_skills` → the two skills are two rows for that career.
- Code: [includes/module_helpers.php:34](includes/module_helpers.php:34) (`syncCareerSkillRows`) · [includes/module_helpers.php:39](includes/module_helpers.php:39) (split by line or comma)

**11. Error messages on save problems**
- Steps: Show the code (these appear only if the database fails).
- Code: [admin/careers.php:62](admin/careers.php:62) (*"skill list could not be saved"*) · [admin/careers.php:70](admin/careers.php:70) (*"Could not add career"*)

**12. Safe delete (transaction)**
- Steps: Click **Delete** on Nurse → confirm dialog → OK → *"Career deleted."*
- Code: [admin/careers.php:28](admin/careers.php:28) (`begin_transaction`; skills, AI links and the career together; `rollback` on failure)

**13. Duplicate names blocked, skill required, input kept**
- Steps: Add *Nurse* again, then try a career named `software developer` → *"A career named … already exists."* Add *Pilot* with no skills → *"Add at least one required skill"*, and the name you typed is still in the form.
- Code: [admin/careers.php:13](admin/careers.php:13) (`LOWER(career_name)=LOWER(?)`) · [admin/careers.php:54](admin/careers.php:54) · [admin/careers.php:84](admin/careers.php:84) (form keeps input)

**14. Search**
- Steps: Type `sql` → **Filter** → *"2 result(s) for "sql""*. Click **Clear**.
- Code: [admin/careers.php:92](admin/careers.php:92) (`LIKE ?` on name, description, skills)

**15. Totals, skill count and skill tags**
- Steps: Point to **8 careers · 48 required skills** at the top, *"6 skills"* next to each name, and the skill tags.
- Code: [admin/careers.php:99](admin/careers.php:99) · [admin/careers.php:100](admin/careers.php:100) · [admin/careers.php:112](admin/careers.php:112)

**16. Category and job demand**
- Steps: **Edit** Nurse → Category **Healthcare**, Job demand **High** → **Update career** → coloured badges appear. Filter **Technology** → 2 results; demand **High** → 3 results.
- Code: [admin/careers.php:50](admin/careers.php:50) (only allowed values saved) · [admin/careers.php:108](admin/careers.php:108) (dropdowns) · [admin/careers.php:112](admin/careers.php:112) (badges)

**17. Careers CSV**
- Steps: Click **⬇ Export CSV** next to the title → open `careerroadmap-careers-<date>.csv` in Excel.
- Code: [admin/careers.php:105](admin/careers.php:105) (button) · [admin/export.php:9](admin/export.php:9) (query)

### AI Recommendations (Admin → AI Recommendations)

**18. Summary**
- Steps: Point to *saved results*, *students* and *Latest* at the top.
- Code: [admin/recommendations.php:35](admin/recommendations.php:35) (`COUNT(*)`, `COUNT(DISTINCT user_id)`, `MAX(created_at)`)

**19. Search**
- Steps: Search `Data Analyst` or a student name → *"N result(s)"*.
- Code: [admin/recommendations.php:31](admin/recommendations.php:31) (`LEFT JOIN users ... LIKE ?`)

**20. Show / Hide, print, delete**
- Steps: Click a student name to open or close the result → **🖨 Print** (only that result is printed) → **Delete** on a test result → confirm → *"Recommendation deleted."*
- Code: [admin/recommendations.php:52](admin/recommendations.php:52) (`<details>`, confirm) · [admin/recommendations.php:57](admin/recommendations.php:57) (`printResult`) · [admin/recommendations.php:16](admin/recommendations.php:16) (`DELETE`)

**21. AI results CSV**
- Steps: Click **⬇ Export CSV** → open in Excel.
- Code: [admin/recommendations.php:43](admin/recommendations.php:43) · [admin/export.php:14](admin/export.php:14)

### New admin pages

**22. Reports & Analytics**
- Steps: Admin menu → **Reports**. Show the 8 numbers, then the 7 charts (registrations per day, quiz results per day, most recommended careers, approved vs pending donut, careers by category, job demand, most needed skills). Change **Period** to 7 and 30 days. Click **🖨 Print report** and **⬇ Students CSV**.
- Code: [admin/reports.php:11](admin/reports.php:11) (period) · [admin/reports.php:15](admin/reports.php:15) (counts) · [admin/reports.php:30](admin/reports.php:30) (`GROUP BY DATE(created_at)`) · [admin/reports.php:42](admin/reports.php:42) (top careers) · [admin/reports.php:54](admin/reports.php:54) (top skills) · [admin/reports.php:116](admin/reports.php:116) (CSS donut)

**23. Activity Log and Unlock**
- Steps: Admin menu → **Activity log**. The red **🔒 Locked logins** box shows `rina@test.com` (from feature 6) → **Unlock** → *"Login unlocked…"*. Filter **Wrong password**, then search `rina`. Log out and log in as Rina: it works again.
- Code: [admin/activity.php:11](admin/activity.php:11) (unlock) · [admin/activity.php:33](admin/activity.php:33) (query with filters) · [admin/activity.php:57](admin/activity.php:57) (locked box) · [includes/admin_tools.php:58](includes/admin_tools.php:58) (`logActivity`) · [includes/admin_tools.php:119](includes/admin_tools.php:119) (`lockedLogins`)

**24. Safe CSV export**
- Steps: Open http://127.0.0.1:8080/admin/export.php?type=students as admin → file downloads. Open http://127.0.0.1:8080/admin/export.php?type=bogus → *"Unknown export type…"*.
- Code: [admin/export.php:8](admin/export.php:8) (3 exports) · [admin/export.php:27](admin/export.php:27) · [admin/export.php:33](admin/export.php:33) (`csvSafe`: cells starting with `=`, `+`, `-`, `@` stay plain text) · [admin/export.php:47](admin/export.php:47) (UTF-8 BOM for Excel)

**25. Admin menu and admin-only pages**
- Steps: Use the menu (Careers · AI Recommendations · Reports · Activity log). Log in as a student and open http://127.0.0.1:8080/admin/reports.php → sent to the login page.
- Code: [includes/admin_tools.php:146](includes/admin_tools.php:146) (menu) · line 3 of each admin page (admin check)

### Student features (log in as `rina@test.com`)

**26. Skill Gap Checker**
- Steps: Dashboard → **Explore careers** → **Skills** → **Check my skill gap** on *Software Developer* → tick *Problem solving* and *Teamwork* → **Check my skill gap**.
- Expected: **Skill match: 33% (2 of 6 skills)**, progress bar, 4 missing skills. Tick all 6 → *"You already have every listed skill…"*
- Code: [career/skills.php:16](career/skills.php:16) (`array_intersect`, ignores fake skills) · [career/skills.php:17](career/skills.php:17) (`array_diff`) · [career/skills.php:19](career/skills.php:19) (percent) · [career/skills.php:27](career/skills.php:27)

**27. Roadmap for the missing skills**
- Steps: On the result click **Build a roadmap for my missing skills** → page shows *"Focusing on the skills you are missing"*.
- Code: [career/skills.php:25](career/skills.php:25) (hidden `focus[]`) · [career/roadmap.php:4](career/roadmap.php:4) · [ai/generate_roadmap.php:7](ai/generate_roadmap.php:7)

**28. Roadmap AI only on button click**
- Steps: The page does not call the AI on open. Click **Generate learning roadmap** → 1 to 2 minutes → 4-stage plan.
- Code: [career/roadmap.php:5](career/roadmap.php:5) (`isset($_POST['generate'])`)

**29. Skill Gap styles**
- Steps: Point to the tick boxes, the progress bar and the coloured missing-skill tags.
- Code: [career/common.php:35](career/common.php:35)

**30. AI quiz uses the admin's career list**
- Steps: Dashboard → **Start quiz** → answer 8 questions → **Finish quiz →** (about 4 minutes with Ollama). The 5 careers come from Career Management.
- Code: [student/quiz_result.php:37](student/quiz_result.php:37) (`$careerCatalog`) · [ai/generate_recommendation.php:63](ai/generate_recommendation.php:63) (catalog in the prompt)

### AI

**31. AI model fixed**
- Steps: In a terminal run `ollama list` → `llama3.2` is there. Show `.env` (`OLLAMA_MODEL=llama3.2`).
- Code: [ai/ai_config.php:79](ai/ai_config.php:79) (`OLLAMA_MODEL`, default `llama3.2`) · [ai/ai_config.php:14](ai/ai_config.php:14) (`CAREER_AI_MODEL` for Grok)

**32. Ollama options fix**
- Steps: Show the code; without it Ollama answered with an error.
- Code: [ai/ai_config.php:92](ai/ai_config.php:92) (`(object)$options` → `{}`)

**33. Timeout fix and friendly AI error**
- Steps: Quit Ollama (system tray → Quit). As a student ask the AI Assistant a question → *"Could not get a reply from local Ollama… Make sure Ollama is running"*, not a crash. Start Ollama again.
- Code: [ai/ai_config.php:81](ai/ai_config.php:81) (at least 10 minutes for local AI) · [ai/ai_config.php:53](ai/ai_config.php:53) (`set_time_limit`) · [ai/ai_config.php:107](ai/ai_config.php:107) (message)

**34. Free Ollama option**
- Steps: Open `.env` → `CAREER_AI_PROVIDER=ollama`. Ask the AI Assistant *"What skills do I need to become a data analyst?"* → answer in about 25 seconds, no API key.
- Code: [ai/ai_config.php:16](ai/ai_config.php:16) (provider switch) · [ai/ai_config.php:76](ai/ai_config.php:76) (`careerAiOllamaText`)

### Security fixes

**35. AI Assistant needs a login**
- Steps: Log out → open http://127.0.0.1:8080/student/ai_assistant.php → login page.
- Code: [includes/auth_check.php:5](includes/auth_check.php:5) (`requireRole`) · [student/ai_assistant.php:3](student/ai_assistant.php:3)

**36. `test.php` removed**
- Steps: Open http://127.0.0.1:8080/test.php → no PHP/server information appears.
- Code: the file no longer exists in the project.

### Database and setup

**37. `database.sql` import**
- Steps: phpMyAdmin (XAMPP Apache on) → **Import** → `database.sql` → success. Import again → success, data kept.
- Code: [database.sql:6](database.sql:6) (`CREATE DATABASE IF NOT EXISTS`) · [database.sql:7](database.sql:7) (`USE`) · [database.sql:10](database.sql:10) (foreign key checks paused)

**38. New columns and tables (13 tables)**
- Steps: phpMyAdmin → `career_roadmap` → see `login_attempts` and `activity_log`; open `careers` → `category`, `demand_level`; `users` → `created_at`.
- Code: [database.sql:94](database.sql:94) · [database.sql:125](database.sql:125) · [database.sql:138](database.sql:138) · [database.sql:149](database.sql:149)

**39. Older databases upgrade themselves**
- Steps: Show the code: on first run the pages add any missing column or table, so an old database keeps working.
- Code: [includes/module_helpers.php:10](includes/module_helpers.php:10) (`ensureCareerColumn`) · [includes/module_helpers.php:29](includes/module_helpers.php:29) · [includes/admin_tools.php:13](includes/admin_tools.php:13) (`ensureAdminToolTables`)

**40. Setup script**
- Steps: In the VS Code terminal run `C:\xampp\php\php.exe tools\setup.php` → *"already exists - skipping"* for each step (on an empty database it creates the admin, 8 careers, 8 questions and categories).
- Code: [tools/setup.php:7](tools/setup.php:7) (terminal only) · [tools/setup.php:32](tools/setup.php:32) (`password_hash`) · [tools/setup.php:61](tools/setup.php:61) · [tools/setup.php:68](tools/setup.php:68) (categories) · [tools/setup.php:93](tools/setup.php:93) (questions)

**41. Database config template**
- Steps: Open `config/db.example.php`; `config/db.php` is in `.gitignore`.
- Code: [config/db.example.php:4](config/db.example.php:4) · [.gitignore:7](.gitignore:7)

### Documentation

**42. README**
- Steps: Open `README.md` → sections 6 (setup), 10 (How It Works), 11 (Contributions by Suborna).

**43. Demo guide**
- Steps: Open this file: Part C (demo script), Part E (30 tests), Part F (this list).

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
