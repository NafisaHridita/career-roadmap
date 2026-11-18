<?php
// [ashasuborna] Admin tools: activity log, login lockout, and the extra columns used by
// Career Management (category, demand level) and the Reports page (users.created_at).
require_once __DIR__ . '/module_helpers.php';

const LOGIN_MAX_FAILURES = 5;    // wrong passwords allowed...
const LOGIN_LOCK_MINUTES = 15;   // ...within this many minutes before the login is locked

const CAREER_CATEGORIES = ['Technology', 'Data & Analytics', 'Design & Creative', 'Business & Management', 'Education', 'Healthcare', 'Marketing & Media', 'Other'];
const CAREER_DEMAND_LEVELS = ['High', 'Medium', 'Low'];

// Creates the tables and columns once per request. Additive only: existing data is kept.
// [ashasuborna] Creates login_attempts, activity_log and new columns on older databases (#38, #39)
function ensureAdminToolTables(mysqli $conn): bool
{
    static $ready = null;
    if ($ready !== null) return $ready;

    $ok = (bool)$conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        attempt_id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (email, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ok = $ok && (bool)$conn->query("CREATE TABLE IF NOT EXISTS activity_log (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        user_name VARCHAR(100) NULL,
        action VARCHAR(60) NOT NULL,
        details VARCHAR(255) NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (created_at),
        INDEX (action)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Registration date, so Reports can chart sign-ups over time.
    $column = $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    if ($ok && $column && $column->num_rows === 0) {
        $ok = (bool)$conn->query('ALTER TABLE users ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }

    $ok = $ok && ensureUserApprovalColumn($conn) && ensureCareerTables($conn)
        && ensureCareerColumn($conn, 'category', 'VARCHAR(60) NULL')
        && ensureCareerColumn($conn, 'demand_level', "ENUM('High','Medium','Low') NULL");

    return $ready = $ok;
}

function adminToolsClientIp(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'cli'), 0, 45);
}

// Records who did what. Failing to log never breaks the page that called it.
// [ashasuborna] Activity log writer (#23)
function logActivity(mysqli $conn, string $action, string $details = '', ?int $userId = null, ?string $userName = null): void
{
    if (!ensureAdminToolTables($conn)) return;
    if ($userId === null && isset($_SESSION['user_id'])) $userId = (int)$_SESSION['user_id'];
    if ($userName === null && isset($_SESSION['name'])) $userName = (string)$_SESSION['name'];
    $details = mb_substr($details, 0, 255);
    $ip = adminToolsClientIp();
    $stmt = $conn->prepare('INSERT INTO activity_log (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) return;
    $stmt->bind_param('issss', $userId, $userName, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

// Failed logins for this email from this computer in the last LOGIN_LOCK_MINUTES.
// Returns ['locked' => bool, 'failures' => int, 'remaining' => int, 'minutes' => int].
// [ashasuborna] Login lockout check (#6)
function loginLockStatus(mysqli $conn, string $email): array
{
    $status = ['locked' => false, 'failures' => 0, 'remaining' => LOGIN_MAX_FAILURES, 'minutes' => 0];
    if (!ensureAdminToolTables($conn)) return $status;
    $email = strtolower(trim($email));
    $ip = adminToolsClientIp();
    $window = LOGIN_LOCK_MINUTES;
    $stmt = $conn->prepare('SELECT COUNT(*) AS n, TIMESTAMPDIFF(SECOND, NOW(), MAX(attempted_at) + INTERVAL ? MINUTE) AS secs
        FROM login_attempts WHERE email = ? AND ip_address = ? AND success = 0 AND attempted_at > NOW() - INTERVAL ? MINUTE');
    if (!$stmt) return $status;
    $stmt->bind_param('issi', $window, $email, $ip, $window);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $failures = (int)($row['n'] ?? 0);
    $status['failures'] = $failures;
    $status['remaining'] = max(0, LOGIN_MAX_FAILURES - $failures);
    if ($failures >= LOGIN_MAX_FAILURES) {
        $status['locked'] = true;
        $status['minutes'] = max(1, (int)ceil(((int)$row['secs']) / 60));
    }
    return $status;
}

// A successful login clears earlier failures for that email from this computer.
function recordLoginAttempt(mysqli $conn, string $email, bool $success): void
{
    if (!ensureAdminToolTables($conn)) return;
    $email = strtolower(trim($email));
    $ip = adminToolsClientIp();
    if ($success) {
        $stmt = $conn->prepare('DELETE FROM login_attempts WHERE email = ? AND ip_address = ? AND success = 0');
        $stmt->bind_param('ss', $email, $ip);
        $stmt->execute();
        $stmt->close();
    }
    $flag = $success ? 1 : 0;
    $stmt = $conn->prepare('INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)');
    $stmt->bind_param('ssi', $email, $ip, $flag);
    $stmt->execute();
    $stmt->close();
}

// Emails locked right now (any computer), for the admin "Locked logins" list.
// [ashasuborna] Locked logins list and unlock (#23)
function lockedLogins(mysqli $conn): array
{
    if (!ensureAdminToolTables($conn)) return [];
    $window = LOGIN_LOCK_MINUTES;
    $max = LOGIN_MAX_FAILURES;
    $stmt = $conn->prepare('SELECT email, ip_address, COUNT(*) AS failures, MAX(attempted_at) AS last_attempt
        FROM login_attempts WHERE success = 0 AND attempted_at > NOW() - INTERVAL ? MINUTE
        GROUP BY email, ip_address HAVING COUNT(*) >= ? ORDER BY last_attempt DESC');
    $stmt->bind_param('ii', $window, $max);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function unlockLogin(mysqli $conn, string $email): int
{
    if (!ensureAdminToolTables($conn)) return 0;
    $stmt = $conn->prepare('DELETE FROM login_attempts WHERE email = ? AND success = 0');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $count = $stmt->affected_rows;
    $stmt->close();
    return $count;
}

// [ashasuborna] Shared top navigation for the admin tool pages (#25).
function adminToolsNav(string $active): string
{
    $links = ['careers.php' => 'Careers', 'recommendations.php' => 'AI Recommendations', 'reports.php' => 'Reports', 'activity.php' => 'Activity log'];
    $html = '<nav class="tool-nav" aria-label="Admin tools">';
    foreach ($links as $href => $label) {
        $html .= '<a href="' . $href . '"' . ($href === $active ? ' class="active" aria-current="page"' : '') . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }
    return $html . '</nav>';
}

// Shared styles for the admin tool pages (dark neon theme).
function adminToolsStyles(): string
{
    return '<style>.tool-nav{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 18px}.tool-nav a{padding:7px 14px;border:1px solid #36456a;border-radius:999px;color:#cfe0ff;text-decoration:none;font-size:14px}.tool-nav a:hover{border-color:#7ff0e0}.tool-nav a.active{background:#7ff0e0;color:#08111d;border-color:#7ff0e0;font-weight:bold}'
        . '.stats{display:flex;gap:12px;flex-wrap:wrap;margin:6px 0 16px}.stats span{padding:8px 14px;border:1px solid #36456a;border-radius:999px;background:#151f36;color:#cfe0ff}.stats strong{color:#7ff0e0}'
        . '.note.ok{border-left:4px solid #48d6a8}.note.err{border-left:4px solid #ff7a99}.hint{color:#9fb0cc;font-size:14px}'
        . '.toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 12px}.toolbar input,.toolbar select{margin:0;padding:9px}.toolbar input[type=search]{flex:1;min-width:180px}'
        . '.btn-link{display:inline-block;padding:8px 14px;border-radius:8px;border:1px solid #36456a;color:#bff7ee;text-decoration:none;font-size:14px}.btn-link:hover{border-color:#7ff0e0}'
        . '@media print{.tool-nav,.toolbar,.admin-home-icon,a[href$="logout.php"],form,.no-print{display:none!important}body,.wrap{background:#fff!important;color:#000!important}}</style>';
}
?>
