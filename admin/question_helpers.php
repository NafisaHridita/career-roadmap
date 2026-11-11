<?php
function ensureQuestionStatusColumn(mysqli $conn): bool
{
    $result = $conn->query("SHOW COLUMNS FROM questions LIKE 'status'");
    if ($result && $result->num_rows > 0) {
        return true;
    }

    // Additive migration: existing question and option rows are preserved.
    return (bool) $conn->query(
        "ALTER TABLE questions ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'"
    );
}

function ensureOptionLabelColumn(mysqli $conn): bool
{
    $result = $conn->query("SHOW COLUMNS FROM options LIKE 'option_label'");
    if ($result && $result->num_rows > 0) {
        return true;
    }

    // Existing option text is preserved; old rows can keep a blank label.
    return (bool) $conn->query(
        "ALTER TABLE options ADD COLUMN option_label VARCHAR(50) NULL AFTER option_id"
    );
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
