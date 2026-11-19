/*
  CareerRoadmap database query reference
  ======================================
  Table definitions and the normal fresh-install schema are in database.sql.
  This file documents the queries used by the PHP pages and the additive
  migrations currently performed by includes/module_helpers.php and
  admin/question_helpers.php.

  This is a reference file: do not run the entire file as a batch. Set the
  @variables for the record you intend to work with, then run only the query
  needed. PHP uses prepared statements for values; keep doing that in PHP.

  Database connection settings are in config/db.php:
  database=career_roadmap, host=localhost, user=root (local XAMPP default).
*/

USE career_roadmap;

/* --------------------------------------------------------------------------
   1. Schema checks and additive migrations
   Fresh installs: import database.sql first. These checks show the columns
   expected by the current application. Do not run ALTER statements if the
   corresponding column already exists.
   -------------------------------------------------------------------------- */

SHOW TABLES;
SHOW COLUMNS FROM users;
SHOW COLUMNS FROM questions;
SHOW COLUMNS FROM options;
SHOW COLUMNS FROM careers;
SHOW COLUMNS FROM admin_profiles;
SHOW COLUMNS FROM password_reset_tokens;
SHOW COLUMNS FROM registration_verifications;

-- Run only if users.account_status is missing:
-- ALTER TABLE users
--   ADD COLUMN account_status ENUM('pending','approved') NOT NULL DEFAULT 'approved';

-- Run only if questions.status is missing:
-- ALTER TABLE questions
--   ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending';

-- Run only if options.option_label is missing:
-- ALTER TABLE options ADD COLUMN option_label VARCHAR(50) NULL AFTER option_id;

-- Run only if careers.required_skills is missing:
-- ALTER TABLE careers ADD COLUMN required_skills TEXT NULL;

-- Password reset links are stored as hashes, expire, and can be used once.
CREATE TABLE IF NOT EXISTS password_reset_tokens (
  reset_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY user_id (user_id),
  KEY token_hash (token_hash),
  KEY expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registration codes and the unverified password hash are temporary and expire.
-- The 6-digit code itself is never stored in the database.
CREATE TABLE IF NOT EXISTS registration_verifications (
  verification_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY email (email),
  KEY expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Run only if careers.created_at is missing:
-- ALTER TABLE careers ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

-- Run only if admin_profiles.space_label is missing:
-- ALTER TABLE admin_profiles ADD COLUMN space_label VARCHAR(40) NULL;

/* The helpers create these tables when a related page is opened. */
CREATE TABLE IF NOT EXISTS careers (
  career_id INT AUTO_INCREMENT PRIMARY KEY,
  career_name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  required_skills TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS career_recommendations (
  recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  result_text MEDIUMTEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id),
  INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_profiles (
  user_id INT PRIMARY KEY,
  phone VARCHAR(40) NULL,
  website VARCHAR(255) NULL,
  linkedin_url VARCHAR(255) NULL,
  github_url VARCHAR(255) NULL,
  photo_path VARCHAR(255) NULL,
  space_label VARCHAR(40) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_profile_social_links (
  link_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  app_name VARCHAR(80) NOT NULL,
  profile_url VARCHAR(500) NULL,
  username VARCHAR(120) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* --------------------------------------------------------------------------
   2. Authentication and registration (login.php, register.php)
   Password values stored in users.password must be password_hash() output.
   -------------------------------------------------------------------------- */

-- Login looks up the account by email; PHP verifies password_hash with
-- password_verify() and checks role/account_status.
SET @email = 'student@example.com';
SELECT user_id, name, email, password, role
FROM users
WHERE email = @email
LIMIT 1;

-- Registration (PHP binds a password_hash() result as @password_hash).
-- New students are pending in the admin review list; login is not blocked by
-- being absent from the admin's saved-student list.
SET @name = 'Student Name';
SET @email = 'student@example.com';
SET @password_hash = '$2y$example_hash_created_by_php_password_hash';
INSERT INTO users (name, email, password, role, account_status)
VALUES (@name, @email, @password_hash, 'student', 'pending');

/* --------------------------------------------------------------------------
   3. Admin student review and saved list (admin/students*.php)
   Saving a pending registration approves the account and changes its name.
   The saved list is the approved-student list; removing from it deletes the
   student account as implemented in the current PHP page.
   -------------------------------------------------------------------------- */

SELECT user_id, name, email
FROM users
WHERE role = 'student' AND account_status = 'pending'
ORDER BY user_id DESC;

-- Save/approve one pending student (set @user_id and @name first).
SET @user_id = 1;
SET @name = 'Updated Student Name';
UPDATE users
SET name = @name, account_status = 'approved'
WHERE user_id = @user_id
  AND role = 'student'
  AND account_status = 'pending';

-- Delete one pending registration (destructive; run only for the intended ID).
-- DELETE FROM users
-- WHERE user_id = @user_id AND role = 'student' AND account_status = 'pending';

SELECT user_id, name, email
FROM users
WHERE role = 'student' AND account_status = 'approved'
ORDER BY user_id DESC;

-- Remove one saved student account (destructive; run only for the intended ID).
-- DELETE FROM users
-- WHERE user_id = @user_id AND role = 'student' AND account_status = 'approved';

/* --------------------------------------------------------------------------
   4. Admin and student profiles (admin/profile.php, student/profile.php)
   Both profile pages currently share admin_profiles and
   admin_profile_social_links, keyed by users.user_id.
   -------------------------------------------------------------------------- */

SET @user_id = 1;
SELECT name, email
FROM users
WHERE user_id = @user_id AND role IN ('admin','student');

SELECT phone, website, linkedin_url, github_url, photo_path, space_label, updated_at
FROM admin_profiles
WHERE user_id = @user_id;

SELECT app_name, profile_url, username
FROM admin_profile_social_links
WHERE user_id = @user_id
ORDER BY link_id;

-- Save profile fields. PHP uses INSERT ... ON DUPLICATE KEY UPDATE.
SET @phone = '';
SET @website = '';
SET @linkedin_url = '';
SET @github_url = '';
SET @photo_path = NULL;
SET @space_label = 'Admin Space';
INSERT INTO admin_profiles
  (user_id, phone, website, linkedin_url, github_url, photo_path, space_label)
VALUES
  (@user_id, @phone, @website, @linkedin_url, @github_url, @photo_path, @space_label)
ON DUPLICATE KEY UPDATE
  phone = VALUES(phone), website = VALUES(website),
  linkedin_url = VALUES(linkedin_url), github_url = VALUES(github_url),
  photo_path = VALUES(photo_path), space_label = VALUES(space_label);

-- Change the account name/email after validating them in the application.
-- UPDATE users SET name = 'New Name', email = 'new@example.com'
-- WHERE user_id = @user_id AND role IN ('admin','student');

-- Replace all social links for one profile; PHP does this in a transaction-like
-- save flow and inserts each submitted row afterward.
-- DELETE FROM admin_profile_social_links WHERE user_id = @user_id;
INSERT INTO admin_profile_social_links (user_id, app_name, profile_url, username)
VALUES (@user_id, 'Instagram', 'https://instagram.com/example', 'example');

-- Clear the stored profile photo path (the PHP page also removes its local file).
-- UPDATE admin_profiles SET photo_path = NULL WHERE user_id = @user_id;

/* --------------------------------------------------------------------------
   5. Quiz question generation, moderation and options
   (admin/add_question.php, edit_question.php, manage_questions.php,
   question_list.php, student/quiz.php)
   -------------------------------------------------------------------------- */

-- Admin generated questions are initially pending.
SET @question_text = 'What type of work interests you most?';
SET @category = 'Career Interest';
INSERT INTO questions (question_text, category, status)
VALUES (@question_text, @category, 'pending');
SET @question_id = LAST_INSERT_ID();

-- Add options for the just-created question. Labels are customizable.
INSERT INTO options (question_id, option_label, option_text, score_value, category)
VALUES
  (@question_id, 'A', 'Solving practical problems', 0, @category),
  (@question_id, 'B', 'Helping people learn', 0, @category),
  (@question_id, 'C', 'Designing or creating things', 0, @category),
  (@question_id, 'D', 'Planning and organizing work', 0, @category);

-- Admin question review list; optional status filter is supplied by PHP.
SELECT q.question_id, q.question_text, q.category, q.status,
       COUNT(o.option_id) AS option_count
FROM questions AS q
LEFT JOIN options AS o ON o.question_id = q.question_id
GROUP BY q.question_id, q.question_text, q.category, q.status
ORDER BY q.question_id DESC;

-- Filter the admin review list by one selected moderation status.
SET @status = 'pending';
SELECT q.question_id, q.question_text, q.category, q.status,
       COUNT(o.option_id) AS option_count
FROM questions AS q
LEFT JOIN options AS o ON o.question_id = q.question_id
WHERE q.status = @status
GROUP BY q.question_id, q.question_text, q.category, q.status
ORDER BY q.question_id DESC;

-- Load one question and its options for editing.
SELECT question_text, category, status
FROM questions WHERE question_id = @question_id;
SELECT option_label, option_text
FROM options WHERE question_id = @question_id ORDER BY option_id;

-- Update text/category/status; option edits replace the current option rows.
UPDATE questions
SET question_text = @question_text, category = @category, status = 'pending'
WHERE question_id = @question_id;
-- DELETE FROM options WHERE question_id = @question_id;

-- Moderation status changes. Set status to pending/approved/rejected as needed.
SET @status = 'approved';
UPDATE questions SET status = @status WHERE question_id = @question_id;

-- Student quiz fetches approved questions with their answers/options.
SELECT q.question_id, q.question_text, o.option_label, o.option_text
FROM questions AS q
JOIN options AS o ON o.question_id = q.question_id
WHERE q.status = 'approved'
ORDER BY q.question_id, o.option_id;

-- Approved-question list shown to admin.
SELECT q.question_id, q.question_text, q.category,
       COUNT(o.option_id) AS option_count
FROM questions AS q
LEFT JOIN options AS o ON o.question_id = q.question_id
WHERE q.status = 'approved'
GROUP BY q.question_id, q.question_text, q.category
ORDER BY q.question_id DESC;

-- Delete question options before deleting the question where FK cascading is
-- not configured (used by admin moderation pages).
-- DELETE FROM options WHERE question_id = @question_id;
-- DELETE FROM questions WHERE question_id = @question_id;

-- Approved-question removal is wrapped in a transaction in PHP.
-- START TRANSACTION;
-- SELECT question_id FROM questions
-- WHERE question_id = @question_id AND status = 'approved' FOR UPDATE;
-- DELETE FROM options WHERE question_id = @question_id;
-- DELETE FROM questions WHERE question_id = @question_id AND status = 'approved';
-- COMMIT;

/* --------------------------------------------------------------------------
   6. Career catalogue and skills (admin/careers.php, career/*.php)
   -------------------------------------------------------------------------- */

SELECT career_id, career_name, description, required_skills
FROM careers ORDER BY career_name;

SELECT career_id, career_name, description, required_skills
FROM careers WHERE career_id = @career_id;

-- The quiz result flow also reads the catalogue before matching careers.
SELECT career_name, description, required_skills
FROM careers ORDER BY career_name;

INSERT INTO careers (career_name, description, required_skills)
VALUES ('Career Name', 'Career description', 'Skill one\nSkill two');

UPDATE careers
SET career_name = 'Updated Career Name',
    description = 'Updated description',
    required_skills = 'Skill one\nSkill two'
WHERE career_id = @career_id;

-- When career_skills exists, the helper syncs it from required_skills text.
-- It removes the old rows for the career, then inserts each skill as required.
-- DELETE FROM career_skills WHERE career_id = @career_id;
INSERT INTO career_skills (career_id, skill_name, importance)
VALUES (@career_id, 'Communication', 'required');

-- Career removal also clears related skill/recommendation links first.
-- UPDATE ai_recommendations SET career_id = NULL WHERE career_id = @career_id;
-- DELETE FROM career_skills WHERE career_id = @career_id;
-- DELETE FROM careers WHERE career_id = @career_id;

/* --------------------------------------------------------------------------
   7. AI recommendations and result history
   -------------------------------------------------------------------------- */

-- The current quiz result flow stores the generated text in this table.
SET @result_text = 'Generated career result text';
INSERT INTO career_recommendations (user_id, result_text)
VALUES (@user_id, @result_text);

-- Student sees their last ten recommendations.
SELECT result_text, created_at
FROM career_recommendations
WHERE user_id = @user_id
ORDER BY created_at DESC
LIMIT 10;

-- Admin views recommendation history with student identity.
SELECT r.recommendation_id, r.result_text, r.created_at, u.name, u.email
FROM career_recommendations AS r
LEFT JOIN users AS u ON u.user_id = r.user_id
ORDER BY r.created_at DESC;

/* --------------------------------------------------------------------------
   8. Legacy assessment-based schema (present in database.sql)
   -------------------------------------------------------------------------- */

-- The current quiz stores answers in the PHP session and recommendation text
-- in career_recommendations. These legacy tables remain in database.sql for
-- the earlier assessment/recommendation design.
-- INSERT INTO assessments (user_id, result_summary) VALUES (@user_id, 'Summary');
-- INSERT INTO assessment_answers (assessment_id, question_id, option_id)
-- VALUES (@assessment_id, @question_id, @option_id);
-- INSERT INTO ai_recommendations
--   (assessment_id, career_id, ai_explanation, skill_gap, learning_roadmap)
-- VALUES (@assessment_id, @career_id, 'Explanation', 'Skills to build', 'Learning plan');

/* --------------------------------------------------------------------------
   9. Useful checks while debugging the local database
   -------------------------------------------------------------------------- */

SELECT user_id, name, email, role, account_status FROM users ORDER BY user_id;
SELECT question_id, category, status, question_text FROM questions ORDER BY question_id;
SELECT option_id, question_id, option_label, option_text FROM options ORDER BY question_id, option_id;
SELECT career_id, career_name, required_skills FROM careers ORDER BY career_name;
SELECT recommendation_id, user_id, created_at FROM career_recommendations ORDER BY created_at DESC;

-- tools/setup.php checks these counts before offering sample seed data.
SELECT COUNT(*) AS admin_count FROM users WHERE role = 'admin';
SELECT COUNT(*) AS career_count FROM careers;
SELECT COUNT(*) AS approved_question_count FROM questions WHERE status = 'approved';

