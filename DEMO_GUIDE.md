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

   Type your admin name, email and a password of 8 or more characters. It then adds 8 sample careers (with 48 skills) and 8 approved quiz questions. Write down the admin email and password. To only recover the admin later, run `C:\xampp\php\php.exe tools\setup.php --admin-only`.
5. **Email (needed for registration):** copy `.env.example` to `.env` and fill in the SMTP settings (`MAIL_SMTP_HOST`, `MAIL_SMTP_PORT`, `MAIL_SMTP_SECURITY`, `MAIL_SMTP_USERNAME`, `MAIL_SMTP_PASSWORD`, `MAIL_FROM_ADDRESS`). Registration codes and password-reset links are sent through it. Do not commit `.env`.
6. **Choose the AI** in the same `.env`, then pick one:
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

Use your own test accounts: the admin you created with `tools\setup.php` and a student you register with an email you can open. Do not write real passwords in this file.

Say first: *"My part is login security (lockout, activity log), Career Management, the AI Recommendations review page, Reports, CSV export and the database schema."*

### C1. Registration and password strength (2 min) · `register.php`
1. Log out if needed, then click **Register**. Registration is Hridita's email-verification flow: email + password, then a 6-digit code.
2. **Password strength meter** (my part): type a short lowercase password → the bar shows *Weak*; type a long password with capitals, numbers and a symbol → *Strong*.
3. Enter an email you can open and click **Send verification code**. Enter the 6-digit code from the email.
4. You land on the login page with **"Registration successful! You can log in now."**

### C2. Login security (3 min) · `login.php`
1. Log in as the new student → the **Student Dashboard** opens right away. Say: *"The team rule is that verified students can log in immediately; the admin Student List is separate."*
2. Log out. Try a wrong password → **"Incorrect email or password. 4 attempt(s) left…"** Say: *"The same message for a wrong email or password, so the page does not reveal which emails exist."*
3. **Lockout:** enter a wrong password 4 more times → **🔒 "Too many failed attempts… locked for 15 minutes."** Now even the right password is refused. Say: *"This stops password-guessing (brute-force) attacks."*
4. Log in as **admin** → **Activity log**: show the registration, the wrong passwords and the lock, then click **Unlock** next to the email.
5. Optional: **Student Approvals** → **Save** adds the student to the admin's list. It does not affect login.

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
2. Show the charts: new registrations per day, AI quiz results per day, most recommended careers, admin list vs new students donut, careers by category, job demand, most needed skills.
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

### 3. Student registration (2 min)
1. Click **Register**, enter an email and password, then the 6-digit code from the email.
2. Log in as that student. The **Student Dashboard** opens without admin approval.
3. Optional: as admin open **Student Approvals** and click **Save** to add the student to the admin's list.

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
| 1 | Register: verification | Register with an email you can open, enter the 6-digit code | Login page: "Registration successful! You can log in now." |
| 2 | Register: wrong code | Enter a wrong 6-digit code | "That code is incorrect. N attempt(s) remain." |
| 3 | Register: email exists | Register with an email that already has an account | "An account with this email already exists." |
| 4 | Register: password strength | Type a short password, then a strong one | Bar shows *Weak*, then *Strong* |
| 5 | Login right away | Log in with the new account | Student Dashboard opens (no approval needed) |
| 6 | Student list is separate | Admin → Student Approvals → Save | Student moves to the admin Student List; login is unchanged |
| 7 | Activity log: registration | Admin → Activity log | A "Registration" entry for the new student |
| 8 | Wrong password | Wrong password, or an email that does not exist | "Incorrect email or password. N attempt(s) left…" (same for both) |
| 9 | Careers: add, edit, delete | Career Management | "Career added", "Career updated", "Career deleted" |
| 10 | Careers: duplicate and search | Add an existing name; search `sql` | Duplicate blocked; only matching careers listed |
| 11 | Recommendations: search and delete | AI Recommendations | Search finds the student; delete asks first, then "Recommendation deleted." |
| 12 | Database import | Import `database.sql` again in phpMyAdmin | Finishes without errors; data is kept |
| 12a | Safe request values | As admin open `admin/activity.php?action[]=x` and `admin/export.php?type[]=x` | Normal page / "Unknown export type", no PHP error |
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
- Admin: the admin account you created with `tools\setup.php`. Student: an account you registered with an email you can open. Do not put real passwords in this file.

### Accounts

**1. Registration is logged and dated**
- Steps: Register a new student (email, password, 6-digit code). As admin open **Activity log** → a *Registration* entry appears. In phpMyAdmin → `users` → the new row has `created_at`; **Reports** counts it in *New student registrations*.
- Code: [register.php:85](register.php:85) (`logActivity` after the verified insert) · [register.php:8](register.php:8) (`ensureAdminToolTables` adds `users.created_at`)

**2. Safe request values**
- Steps: As admin open http://127.0.0.1:8080/admin/activity.php?action[]=x and http://127.0.0.1:8080/admin/export.php?type[]=careers → the page loads normally / *"Unknown export type"*; no PHP warning or error. Before the fix these crashed.
- Code: [includes/admin_tools.php:74](includes/admin_tools.php:74) (`requestText`) · [login.php:27](login.php:27) · [admin/activity.php:27](admin/activity.php:27)

**3. Password strength meter**
- Steps: On **Register**, type a short lowercase password → **Weak**. Change it to a long one with capitals, numbers and a symbol → **Strong** (green).
- Code: [register.php:226](register.php:226) (bar styles) · [register.php:268](register.php:268) (bar) · [register.php:288](register.php:288) (JavaScript score: length, upper/lower case, numbers, symbols)

**4. Database errors handled**
- Steps: Show the code. PHP 8.1+ mysqli throws exceptions on database errors (for example a duplicate email), so these places catch them and show a clear message: setup admin creation, career save, recommendation delete, activity log and lockout.
- Code: [tools/setup.php:56](tools/setup.php:56) · [admin/careers.php:89](admin/careers.php:89) · [admin/recommendations.php:23](admin/recommendations.php:23) · [includes/admin_tools.php:62](includes/admin_tools.php:62) (`adminToolsSafely`)

**5. Login right after verification (team rule)**
- Steps: Log in with a newly registered student → the Student Dashboard opens. As admin, **Student Approvals → Save** only adds the student to the admin list.
- Code: [login.php:64](login.php:64) (no approval check; `account_status` is only for the admin list) · [login.php:16](login.php:16) (registration message)

**6. Login lockout**
- Steps: Log out. Log in as your test student with a wrong password 5 times.
- Expected: *"4 attempt(s) left"*, *"3…"*, *"2…"*, *"1…"*, then 🔒 *"Too many failed attempts… locked for 15 minutes."* Now try the right password → still locked. (Unlock it in feature 23.)
- Code: [includes/admin_tools.php:6](includes/admin_tools.php:6) (5 tries, 15 minutes) · [includes/admin_tools.php:106](includes/admin_tools.php:106) (`loginLockStatus`) · [includes/admin_tools.php:135](includes/admin_tools.php:135) (`recordLoginAttempt`) · [login.php:33](login.php:33) (check before password) · [login.php:59](login.php:59) (attempts left)

**7. Same message for wrong email or wrong password**
- Steps: Log in with `nobody@test.com` / `anything`, then with a real email and a wrong password.
- Expected: both say *"Incorrect email or password…"*, so nobody can find out which emails are registered.
- Code: [login.php:51](login.php:51) (`password_verify`) · [login.php:59](login.php:59)

**8. New session ID after login**
- Steps: Show the code (no visible change). Optional: in the browser dev tools (F12 → Application → Cookies) the `PHPSESSID` value changes after you log in.
- Code: [login.php:69](login.php:69) (`session_regenerate_id(true)`) · [login.php:72](login.php:72) (email in session)

**9. No blank line before `<?php`**
- Steps: Open `login.php` and `register.php`: line 1 is `<?php`. Say: *"Output before `<?php` breaks `header()` redirects on servers without output buffering."*
- Code: [login.php:1](login.php:1) · [register.php:1](register.php:1)

### Career Management (Admin → Careers)

**10. Skills saved one per row**
- Steps: Add a career *Nurse* with skills on separate lines: `Patient care`, `Communication`. Open phpMyAdmin → `career_skills` → the two skills are two rows for that career.
- Code: [includes/module_helpers.php:36](includes/module_helpers.php:36) (`syncCareerSkillRows`) · [includes/module_helpers.php:41](includes/module_helpers.php:41) (split by line or comma)

**11. Error messages on save problems**
- Steps: Show the code (these appear only if the database fails).
- Code: [admin/careers.php:70](admin/careers.php:70) (*"skill list could not be saved"*) · [admin/careers.php:78](admin/careers.php:78) (*"Could not add career"*)

**12. Safe delete (transaction)**
- Steps: Click **Delete** on Nurse → confirm dialog → OK → *"Career deleted."*
- Code: [admin/careers.php:31](admin/careers.php:31) (`begin_transaction`; skills, AI links and the career together; `rollback` on failure)

**13. Duplicate names blocked, skill required, input kept**
- Steps: Add *Nurse* again, then try a career named `software developer` → *"A career named … already exists."* Add *Pilot* with no skills → *"Add at least one required skill"*, and the name you typed is still in the form.
- Code: [admin/careers.php:15](admin/careers.php:15) (`LOWER(career_name)=LOWER(?)`) · [admin/careers.php:59](admin/careers.php:59) · [admin/careers.php:97](admin/careers.php:97) (form keeps input)

**14. Search**
- Steps: Type `sql` → **Filter** → *"2 result(s) for "sql""*. Click **Clear**.
- Code: [admin/careers.php:106](admin/careers.php:106) (`LIKE ?` on name, description, skills)

**15. Totals, skill count and skill tags**
- Steps: Point to **8 careers · 48 required skills** at the top, *"6 skills"* next to each name, and the skill tags.
- Code: [admin/careers.php:114](admin/careers.php:114) · [admin/careers.php:115](admin/careers.php:115) · [admin/careers.php:127](admin/careers.php:127)

**16. Category and job demand**
- Steps: **Edit** Nurse → Category **Healthcare**, Job demand **High** → **Update career** → coloured badges appear. Filter **Technology** → 2 results; demand **High** → 3 results.
- Code: [admin/careers.php:54](admin/careers.php:54) (only allowed values saved) · [admin/careers.php:123](admin/careers.php:123) (dropdowns) · [admin/careers.php:127](admin/careers.php:127) (badges)

**17. Careers CSV**
- Steps: Click **⬇ Export CSV** next to the title → open `careerroadmap-careers-<date>.csv` in Excel.
- Code: [admin/careers.php:120](admin/careers.php:120) (button) · [admin/export.php:10](admin/export.php:10) (query)

### AI Recommendations (Admin → AI Recommendations)

**18. Summary**
- Steps: Point to *saved results*, *students* and *Latest* at the top.
- Code: [admin/recommendations.php:43](admin/recommendations.php:43) (`COUNT(*)`, `COUNT(DISTINCT user_id)`, `MAX(created_at)`)

**19. Search**
- Steps: Search `Data Analyst` or a student name → *"N result(s)"*.
- Code: [admin/recommendations.php:38](admin/recommendations.php:38) (`LEFT JOIN users ... LIKE ?`)

**20. Show / Hide, print, delete**
- Steps: Click a student name to open or close the result → **🖨 Print** (only that result is printed) → **Delete** on a test result → confirm → *"Recommendation deleted."*
- Code: [admin/recommendations.php:60](admin/recommendations.php:60) (`<details>`, confirm) · [admin/recommendations.php:66](admin/recommendations.php:66) (`printResult`) · [admin/recommendations.php:18](admin/recommendations.php:18) (`DELETE`)

**21. AI results CSV**
- Steps: Click **⬇ Export CSV** → open in Excel.
- Code: [admin/recommendations.php:51](admin/recommendations.php:51) · [admin/export.php:15](admin/export.php:15)

### New admin pages

**22. Reports & Analytics**
- Steps: Admin menu → **Reports**. Show the 8 numbers, then the 7 charts (registrations per day, quiz results per day, most recommended careers, admin list vs new students donut, careers by category, job demand, most needed skills). Change **Period** to 7 and 30 days. Click **🖨 Print report** and **⬇ Students CSV**.
- Code: [admin/reports.php:12](admin/reports.php:12) (period) · [admin/reports.php:16](admin/reports.php:16) (counts) · [admin/reports.php:31](admin/reports.php:31) (`GROUP BY DATE(created_at)`) · [admin/reports.php:43](admin/reports.php:43) (top careers) · [admin/reports.php:55](admin/reports.php:55) (top skills) · [admin/reports.php:117](admin/reports.php:117) (CSS donut)

**23. Activity Log and Unlock**
- Steps: Admin menu → **Activity log**. The red **🔒 Locked logins** box shows the student's email (from feature 6) → **Unlock** → *"Login unlocked…"*. Filter **Wrong password**, then search the email. Log out and log in as the student: it works again.
- Code: [admin/activity.php:12](admin/activity.php:12) (unlock) · [admin/activity.php:34](admin/activity.php:34) (query with filters) · [admin/activity.php:58](admin/activity.php:58) (locked box) · [includes/admin_tools.php:87](includes/admin_tools.php:87) (`logActivity`) · [includes/admin_tools.php:158](includes/admin_tools.php:158) (`lockedLogins`)

**24. Safe CSV export**
- Steps: Open http://127.0.0.1:8080/admin/export.php?type=students as admin → file downloads. Open http://127.0.0.1:8080/admin/export.php?type=bogus → *"Unknown export type…"*.
- Code: [admin/export.php:9](admin/export.php:9) (3 exports) · [admin/export.php:28](admin/export.php:28) · [admin/export.php:36](admin/export.php:36) (`csvSafe`: cells starting with `=`, `+`, `-`, `@` stay plain text) · [admin/export.php:54](admin/export.php:54) (UTF-8 BOM for Excel)

**25. Admin menu and admin-only pages**
- Steps: Use the menu (Careers · AI Recommendations · Reports · Activity log). Log in as a student and open http://127.0.0.1:8080/admin/reports.php → sent to the login page.
- Code: [includes/admin_tools.php:190](includes/admin_tools.php:190) (menu) · line 3 of each admin page (admin check)

### Student features (log in as your test student)

**26. Skill Gap Checker**
- Steps: Dashboard → **Explore careers** → **Skills** → **Check my skill gap** on *Software Developer* → tick *Problem solving* and *Teamwork* → **Check my skill gap**.
- Expected: **Skill match: 33% (2 of 6 skills)**, progress bar, 4 missing skills. Tick all 6 → *"You already have every listed skill…"*
- Code: [career/skills.php:17](career/skills.php:17) (`array_intersect`, ignores fake skills) · [career/skills.php:18](career/skills.php:18) (`array_diff`) · [career/skills.php:20](career/skills.php:20) (percent) · [career/skills.php:29](career/skills.php:29)

**27. Roadmap for the missing skills**
- Steps: On the result click **Build a roadmap for my missing skills** → page shows *"Focusing on the skills you are missing"*.
- Code: [career/skills.php:27](career/skills.php:27) (hidden `focus[]`) · [career/roadmap.php:5](career/roadmap.php:5) · [ai/generate_roadmap.php:8](ai/generate_roadmap.php:8)

**28. Roadmap AI only on button click**
- Steps: The page does not call the AI on open. Click **Generate learning roadmap** → 1 to 2 minutes → 4-stage plan.
- Code: [career/roadmap.php:6](career/roadmap.php:6) (`isset($_POST['generate'])`)

**29. Skill Gap styles**
- Steps: Point to the tick boxes, the progress bar and the coloured missing-skill tags.
- Code: [career/common.php:36](career/common.php:36)

**30. AI quiz uses the admin's career list**
- Steps: Dashboard → **Start quiz** → answer 8 questions → **Finish quiz →** (about 4 minutes with Ollama). The 5 careers come from Career Management.
- Code: [student/quiz_result.php:38](student/quiz_result.php:38) (`$careerCatalog`) · [ai/generate_recommendation.php:64](ai/generate_recommendation.php:64) (catalog in the prompt)

### AI

**31. AI model fixed**
- Steps: In a terminal run `ollama list` → `llama3.2` is there. Show `.env` (`OLLAMA_MODEL=llama3.2`).
- Code: [ai/ai_config.php:81](ai/ai_config.php:81) (`OLLAMA_MODEL`, default `llama3.2`) · [ai/ai_config.php:14](ai/ai_config.php:14) (`CAREER_AI_MODEL` for Grok)

**32. Ollama options fix**
- Steps: Show the code; without it Ollama answered with an error.
- Code: [ai/ai_config.php:94](ai/ai_config.php:94) (`(object)$options` → `{}`)

**33. Timeout fix and friendly AI error**
- Steps: Quit Ollama (system tray → Quit). As a student ask the AI Assistant a question → *"Could not get a reply from local Ollama… Make sure Ollama is running"*, not a crash. Start Ollama again.
- Code: [ai/ai_config.php:83](ai/ai_config.php:83) (at least 10 minutes for local AI) · [ai/ai_config.php:54](ai/ai_config.php:54) (`set_time_limit`) · [ai/ai_config.php:109](ai/ai_config.php:109) (message)

**34. Free Ollama option**
- Steps: Open `.env` → `CAREER_AI_PROVIDER=ollama`. Ask the AI Assistant *"What skills do I need to become a data analyst?"* → answer in about 25 seconds, no API key.
- Code: [ai/ai_config.php:17](ai/ai_config.php:17) (provider switch) · [ai/ai_config.php:78](ai/ai_config.php:78) (`careerAiOllamaText`)

### Security fixes

**35. AI Assistant needs a login**
- Steps: Log out → open http://127.0.0.1:8080/student/ai_assistant.php → login page.
- Code: [includes/auth_check.php:6](includes/auth_check.php:6) (`requireRole`) · [student/ai_assistant.php:4](student/ai_assistant.php:4)

**36. `test.php` removed**
- Steps: Open http://127.0.0.1:8080/test.php → no PHP/server information appears.
- Code: the file no longer exists in the project.

### Database and setup

**37. `database.sql` import**
- Steps: phpMyAdmin (XAMPP Apache on) → **Import** → `database.sql` → success. Import again → success, data kept.
- Code: [database.sql:7](database.sql:7) (`CREATE DATABASE IF NOT EXISTS`) · [database.sql:8](database.sql:8) (`USE`) · [database.sql:12](database.sql:12) (foreign key checks paused)

**38. New columns and tables (13 tables)**
- Steps: phpMyAdmin → `career_roadmap` → see `login_attempts` and `activity_log`; open `careers` → `category`, `demand_level`; `users` → `created_at`.
- Code: [database.sql:97](database.sql:97) · [database.sql:129](database.sql:129) · [database.sql:143](database.sql:143) · [database.sql:155](database.sql:155)

**39. Older databases upgrade themselves**
- Steps: Show the code: on first run the pages add any missing column or table, so an old database keeps working.
- Code: [includes/module_helpers.php:11](includes/module_helpers.php:11) (`ensureCareerColumn`) · [includes/module_helpers.php:30](includes/module_helpers.php:30) · [includes/admin_tools.php:14](includes/admin_tools.php:14) (`ensureAdminToolTables`)

**40. Setup script**
- Steps: In the VS Code terminal run `C:\xampp\php\php.exe tools\setup.php` → *"already exists - skipping"* for each step (on an empty database it creates the admin, 8 careers, 8 questions and categories).
- Code: [tools/setup.php:8](tools/setup.php:8) (terminal only) · [tools/setup.php:34](tools/setup.php:34) (`password_hash`) · [tools/setup.php:92](tools/setup.php:92) · [tools/setup.php:100](tools/setup.php:100) (categories) · [tools/setup.php:125](tools/setup.php:125) (questions)

**41. Database config template**
- Steps: Open `config/db.example.php`; `config/db.php` is in `.gitignore`.
- Code: [config/db.example.php:5](config/db.example.php:5) · [.gitignore:7](.gitignore:7)

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
| No verification email arrives | Check the SMTP settings in `.env` and the spam folder; wait 1 minute before requesting a new code |
| "Too many failed attempts" | Wait 15 minutes, or log in as admin → Activity log → **Unlock** |
| Reports charts are empty | Normal on a new database: register students and finish a quiz first |
| "No approved quiz questions" or "No career paths" | Run `tools\setup.php`, or add them as admin |
| "Grok API key is missing" | Add `XAI_API_KEY` to `.env`, or set `CAREER_AI_PROVIDER=ollama` to use free local Ollama |
| "Could not get a reply from local Ollama" | Start Ollama and check that `ollama list` shows `llama3.2` |
| Grok API request fails | Check internet access, API key/model access, and available xAI API credits |
| Port 8080 already in use | Use another port: `C:\xampp\php\php.exe -S 127.0.0.1:8090 -t . tools\dev_router.php`, then open http://127.0.0.1:8090 |
