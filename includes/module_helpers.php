<?php
function ensureUserApprovalColumn(mysqli $conn): bool
{
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'account_status'");
    if ($result && $result->num_rows > 0) return true;
    // Existing accounts remain usable; only new registrations are pending.
    return (bool) $conn->query("ALTER TABLE users ADD COLUMN account_status ENUM('pending','approved') NOT NULL DEFAULT 'approved'");
}

// [ashasuborna] Adds missing career columns on older databases (#39)
function ensureCareerColumn(mysqli $conn, string $column, string $definition): bool
{
    $existing = $conn->query("SHOW COLUMNS FROM careers LIKE '" . $conn->real_escape_string($column) . "'");
    if (!$existing) return false;
    if ($existing->num_rows > 0) return true;
    return (bool) $conn->query("ALTER TABLE careers ADD COLUMN {$column} {$definition}");
}

function ensureCareerTables(mysqli $conn): bool
{
    $created = (bool) $conn->query("CREATE TABLE IF NOT EXISTS careers (
        career_id INT AUTO_INCREMENT PRIMARY KEY,
        career_name VARCHAR(150) NOT NULL,
        description TEXT NULL,
        required_skills TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$created) return false;
    // Imports from database.sql already have careers, so CREATE TABLE does not add later columns.
    if (!ensureCareerColumn($conn, 'required_skills', 'TEXT NULL')) return false;
    if (!ensureCareerColumn($conn, 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP')) return false;
    return true;
}

// [ashasuborna] Saves each required skill as its own row (#10)
function syncCareerSkillRows(mysqli $conn, int $careerId, string $skillsText): bool
{
    $table = $conn->query("SHOW TABLES LIKE 'career_skills'");
    if (!$table || $table->num_rows === 0) return true;

    $skills = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $skillsText)), static fn($skill) => $skill !== ''));
    $delete = $conn->prepare('DELETE FROM career_skills WHERE career_id = ?');
    if (!$delete) return false;
    $delete->bind_param('i', $careerId);
    $deleted = $delete->execute();
    $delete->close();
    if (!$deleted) return false;
    if (!$skills) return true;

    $insert = $conn->prepare('INSERT INTO career_skills (career_id, skill_name, importance) VALUES (?, ?, ?)');
    if (!$insert) return false;
    $importance = 'required';
    foreach ($skills as $skill) {
        $insert->bind_param('iss', $careerId, $skill, $importance);
        if (!$insert->execute()) {
            $insert->close();
            return false;
        }
    }
    $insert->close();
    return true;
}

function ensureRecommendationTable(mysqli $conn): bool
{
    return (bool) $conn->query("CREATE TABLE IF NOT EXISTS career_recommendations (
        recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        result_text MEDIUMTEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureAdminProfileTable(mysqli $conn): bool
{
    $created = (bool) $conn->query("CREATE TABLE IF NOT EXISTS admin_profiles (
        user_id INT PRIMARY KEY,
        phone VARCHAR(40) NULL,
        website VARCHAR(255) NULL,
        linkedin_url VARCHAR(255) NULL,
        github_url VARCHAR(255) NULL,
        photo_path VARCHAR(255) NULL,
        space_label VARCHAR(40) NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$created) return false;

    // Add the editable profile title to installations that already have this table.
    $column = $conn->query("SHOW COLUMNS FROM admin_profiles LIKE 'space_label'");
    if (!$column) return false;
    if ($column->num_rows === 0 && !$conn->query('ALTER TABLE admin_profiles ADD COLUMN space_label VARCHAR(40) NULL')) return false;
    return true;
}

function ensureAdminSocialLinksTable(mysqli $conn): bool
{
    return (bool) $conn->query("CREATE TABLE IF NOT EXISTS admin_profile_social_links (
        link_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        app_name VARCHAR(80) NOT NULL,
        profile_url VARCHAR(500) NULL,
        username VARCHAR(120) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
?>
