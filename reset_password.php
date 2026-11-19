<?php
require_once 'config/db.php';
require_once 'includes/password_reset_helpers.php';
session_start();
if (!ensurePasswordResetTable($conn)) die('Password reset is temporarily unavailable.');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$email = trim((string)($_GET['email'] ?? $_POST['email'] ?? ''));
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$message = '';
$error = '';
$valid = false;
if (filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT t.reset_id,t.user_id FROM password_reset_tokens t JOIN users u ON u.user_id=t.user_id WHERE u.email=? AND u.role='student' AND t.token_hash=? AND t.used_at IS NULL AND t.expires_at>NOW() LIMIT 1");
    $stmt->bind_param('ss', $email, $tokenHash); $stmt->execute(); $reset = $stmt->get_result()->fetch_assoc(); $stmt->close();
    $valid = (bool)$reset;
}
if (!$valid) $error = 'This reset link is invalid or has expired. Request a new one.';
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) $error = 'Your session expired. Refresh and try again.';
    else {
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        if (strlen($password) < 8) $error = 'Choose a password with at least 8 characters.';
        elseif ($password !== $confirm) $error = 'The passwords do not match.';
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $conn->begin_transaction();
                $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=? AND role='student'");
                $stmt->bind_param('si', $hash, $reset['user_id']);
                if (!$stmt->execute() || $stmt->affected_rows !== 1) throw new RuntimeException('Password update failed.');
                $stmt->close();
                $stmt = $conn->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE reset_id=? AND used_at IS NULL');
                $stmt->bind_param('i', $reset['reset_id']);
                if (!$stmt->execute() || $stmt->affected_rows !== 1) throw new RuntimeException('Reset link was already used.');
                $stmt->close();
                $conn->commit(); $message = 'Password updated. You can now log in.'; $valid = false;
            } catch (Throwable $exception) {
                $conn->rollback(); $error = 'Could not update the password. Request a new reset link and try again.';
            }
        }
    }
}
function resetEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset password | CareerRoadmap</title><style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(ellipse at 12% 10%,#183757 0,transparent 34%),radial-gradient(ellipse at 88% 90%,#261744 0,transparent 36%),#09101e;color:#edf3ff;font:16px Inter,"Segoe UI",Arial,sans-serif}.card{width:min(520px,100%);padding:clamp(26px,6vw,46px);border:1px solid #354865;border-radius:24px;background:linear-gradient(145deg,#111b2b,#0c1422);box-shadow:0 24px 80px #02061199}.brand,.back{color:#85eee0;text-decoration:none;font-weight:750}.eyebrow{margin:38px 0 8px;color:#83e9de;text-transform:uppercase;letter-spacing:1.8px;font-size:12px;font-weight:800}h1{margin:0;font-size:34px;color:#f4f7ff}p{color:#aebbd1;line-height:1.65}.notice{padding:13px;border-radius:11px;background:#102e2b;border:1px solid #286d65;color:#b1f8e8}.error{background:#381e2a;border-color:#7a4456;color:#ffc5d1}label{display:block;margin:19px 0 8px;font-size:14px;font-weight:750}input{width:100%;height:49px;padding:0 13px;border:1px solid #394d6d;border-radius:10px;background:#0a1220;color:#f3f6ff;font:inherit}input:focus{outline:2px solid #70e9db}.submit{width:100%;height:49px;margin-top:21px;border:0;border-radius:11px;background:linear-gradient(105deg,#66e9d9,#9b92ff);color:#08111d;font-weight:850;cursor:pointer}.foot{margin-top:22px}
</style></head><body class="neon-app"><main class="card"><a class="brand" href="index.php">CareerRoadmap</a><p class="eyebrow">Choose a new password</p><h1>Reset password</h1><?php if($message):?><p class="notice" role="status"><?=resetEscape($message)?></p><p class="foot"><a class="back" href="login.php">Log in</a></p><?php elseif($valid):?><p>Choose a new password with at least 8 characters.</p><?php if($error):?><p class="notice error" role="alert"><?=resetEscape($error)?></p><?php endif;?><form method="post"><input type="hidden" name="email" value="<?=resetEscape($email)?>"><input type="hidden" name="token" value="<?=resetEscape($token)?>"><input type="hidden" name="csrf_token" value="<?=resetEscape($_SESSION['csrf_token'])?>"><label for="password">New password</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required><button class="submit" type="submit">Save new password</button></form><?php else:?><p class="notice error" role="alert"><?=resetEscape($error)?></p><p class="foot"><a class="back" href="forgot_password.php">Request a new reset link</a></p><?php endif;?></main><script src="assets/page-navigation.js" defer></script></body></html>
