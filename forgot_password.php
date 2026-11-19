<?php
require_once 'config/db.php';
require_once 'includes/password_reset_helpers.php';
$message = '';
$error = '';
if (!ensurePasswordResetTable($conn)) $error = 'Password reset is temporarily unavailable. Please try again later.';
session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } else {
            $message = 'If a student account uses that email, a reset link will be sent when email delivery is configured.';
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? AND role='student' LIMIT 1");
            $stmt->bind_param('s', $email); $stmt->execute(); $account = $stmt->get_result()->fetch_assoc(); $stmt->close();
            $settings = careerRoadmapEnv();
            $baseUrl = rtrim((string)($settings['APP_BASE_URL'] ?? 'http://localhost/CareerRoadmap'), '/');
            if ($account) {
                $userId = (int)$account['user_id'];
                $stmt = $conn->prepare('DELETE FROM password_reset_tokens WHERE user_id=? OR expires_at < NOW()');
                $stmt->bind_param('i', $userId); $stmt->execute(); $stmt->close();
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $stmt = $conn->prepare('INSERT INTO password_reset_tokens (user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 60 MINUTE))');
                $stmt->bind_param('is', $userId, $tokenHash);
                $created = $stmt->execute(); $stmt->close();
                $url = $baseUrl . '/reset_password.php?email=' . rawurlencode($email) . '&token=' . rawurlencode($token);
                if (!$created || !sendCareerRoadmapResetEmail($email, $url, $settings)) {
                    $stmt = $conn->prepare('DELETE FROM password_reset_tokens WHERE user_id=?');
                    $stmt->bind_param('i', $userId); $stmt->execute(); $stmt->close();
                }
            }
        }
    }
}
function forgotEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Forgot password | CareerRoadmap</title><style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(ellipse at 12% 10%,#183757 0,transparent 34%),radial-gradient(ellipse at 88% 90%,#261744 0,transparent 36%),#09101e;color:#edf3ff;font:16px Inter,"Segoe UI",Arial,sans-serif}.card{width:min(520px,100%);padding:clamp(26px,6vw,46px);border:1px solid #354865;border-radius:24px;background:linear-gradient(145deg,#111b2b,#0c1422);box-shadow:0 24px 80px #02061199;animation:enter .45s ease both}.brand,.back{color:#85eee0;text-decoration:none;font-weight:750}.eyebrow{margin:38px 0 8px;color:#83e9de;text-transform:uppercase;letter-spacing:1.8px;font-size:12px;font-weight:800}h1{margin:0;font-size:34px;color:#f4f7ff}p{color:#aebbd1;line-height:1.65}.notice{padding:13px;border-radius:11px;background:#102e2b;border:1px solid #286d65;color:#b1f8e8}.error{background:#381e2a;border-color:#7a4456;color:#ffc5d1}label{display:block;margin:20px 0 8px;font-size:14px;font-weight:750}input{width:100%;height:49px;padding:0 13px;border:1px solid #394d6d;border-radius:10px;background:#0a1220;color:#f3f6ff;font:inherit}input:focus{outline:2px solid #70e9db}.submit{width:100%;height:49px;margin-top:18px;border:0;border-radius:11px;background:linear-gradient(105deg,#66e9d9,#9b92ff);color:#08111d;font-weight:850;cursor:pointer;transition:transform .18s,box-shadow .18s}.submit:hover{transform:translateY(-2px);box-shadow:0 10px 24px #66e9d944}.foot{margin-top:23px}@keyframes enter{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}@media(prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
</style></head><body class="neon-app"><main class="card"><a class="brand" href="index.php">CareerRoadmap</a><p class="eyebrow">Account recovery</p><h1>Forgot your password?</h1><p>Enter your student account email. If the account exists, we'll send a one-time password reset link.</p><?php if($message):?><p class="notice" role="status"><?=forgotEscape($message)?></p><?php endif;?><?php if($error):?><p class="notice error" role="alert"><?=forgotEscape($error)?></p><?php endif;?><form method="post"><input type="hidden" name="csrf_token" value="<?=forgotEscape($_SESSION['csrf_token'])?>"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" required value="<?=forgotEscape($_POST['email']??'')?>"><button class="submit" type="submit">Send reset link</button></form><p class="foot"><a class="back" href="login.php">← Back to log in</a></p><p><small>On a local XAMPP setup, outgoing email must be configured in PHP for reset links to arrive.</small></p></main><script src="assets/page-navigation.js" defer></script></body></html>
