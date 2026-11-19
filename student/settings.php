<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php';

$userId = (int) $_SESSION['user_id'];
$message = '';
$error = '';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session expired. Refresh and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $current = (string)($_POST['current_password'] ?? '');
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id=? AND role='student' LIMIT 1");
        $stmt->bind_param('i', $userId); $stmt->execute(); $account = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$account || !password_verify($current, $account['password'])) {
            $error = 'Your current password is incorrect.';
        } elseif ($action === 'change_email') {
            $newEmail = trim((string)($_POST['new_email'] ?? ''));
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL) || strlen($newEmail) > 150) $error = 'Enter a valid email address.';
            else {
                $check = $conn->prepare('SELECT user_id FROM users WHERE email=? AND user_id<>? LIMIT 1');
                $check->bind_param('si', $newEmail, $userId); $check->execute(); $exists = (bool)$check->get_result()->fetch_assoc(); $check->close();
                if ($exists) $error = 'That email address is already in use.';
                else {
                    $stmt = $conn->prepare("UPDATE users SET email=? WHERE user_id=? AND role='student'");
                    $stmt->bind_param('si', $newEmail, $userId);
                    if ($stmt->execute()) { $_SESSION['email'] = $newEmail; $message = 'Your email address has been updated.'; }
                    else $error = 'Could not update your email address. Please try again.';
                    $stmt->close();
                }
            }
        } elseif ($action === 'change_password') {
            $new = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');
            if (strlen($new) < 8) $error = 'Choose a password with at least 8 characters.';
            elseif ($new !== $confirm) $error = 'The new passwords do not match.';
            else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=? AND role='student'");
                $stmt->bind_param('si', $hash, $userId);
                if ($stmt->execute()) $message = 'Your password has been updated.';
                else $error = 'Could not update your password. Please try again.';
                $stmt->close();
            }
        } else {
            $error = 'Choose a valid settings action.';
        }
    }
}
$accountStmt = $conn->prepare("SELECT email FROM users WHERE user_id=? AND role='student' LIMIT 1");
$accountStmt->bind_param('i', $userId); $accountStmt->execute(); $currentAccount = $accountStmt->get_result()->fetch_assoc() ?: []; $accountStmt->close();
function settingsEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Account settings | CareerRoadmap</title>
<link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3"><link rel="stylesheet" href="../assets/glass-surfaces.css?v=20261115-2218">
<style>
body{margin:0;min-height:100vh;background:radial-gradient(ellipse at 10% 5%,#173555 0,transparent 32%),radial-gradient(ellipse at 92% 90%,#281747 0,transparent 35%),#09101e;color:#eaf1ff;font:16px Inter,"Segoe UI",Arial,sans-serif}.settings-shell{width:min(900px,calc(100% - 36px));margin:48px auto;padding:clamp(24px,5vw,52px);border:1px solid #354865;border-radius:26px;background:linear-gradient(145deg,#111b2b,#0c1422);box-shadow:0 24px 80px #02061188}.topline{display:flex;justify-content:space-between;gap:14px;align-items:center}.topline a,.back{color:#86eee1;text-decoration:none;font-weight:700}.eyebrow{color:#80eadd;text-transform:uppercase;letter-spacing:2px;font-size:12px;font-weight:800}.settings-shell h1{font-size:clamp(30px,5vw,42px);margin:12px 0;color:#f3f6ff}.intro{color:#aebbd1;line-height:1.65;margin:0 0 28px}.panel{padding:24px;margin-top:18px;border:1px solid #2e405d;border-radius:19px;background:#101a2a}.panel h2{margin:0 0 8px;font-size:21px}.panel>p{color:#aab9d0;line-height:1.55}.fields{display:grid;grid-template-columns:1fr 1fr;gap:0 15px}.field{margin-top:14px}.field label{display:block;margin-bottom:7px;font-size:14px;font-weight:700;color:#dce6f4}.field input{width:100%;height:46px;padding:0 13px;border:1px solid #3a4c69;border-radius:10px;background:#0a1220;color:#f1f5ff;font:inherit;outline:none;transition:border-color .2s,box-shadow .2s}.field input:focus{border-color:#70e9db;box-shadow:0 0 0 3px #70e9db22}.primary,.danger-link{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 18px;border:0;border-radius:11px;font-weight:800;text-decoration:none;cursor:pointer;transition:transform .18s,box-shadow .18s,filter .18s}.primary{margin-top:19px;background:linear-gradient(110deg,#66e9d9,#9b92ff);color:#08111e;box-shadow:0 8px 22px #59dfd51e}.primary:hover,.danger-link:hover{transform:translateY(-2px);filter:brightness(1.08);box-shadow:0 12px 26px #0004}.danger-zone{border-color:#75465c;background:linear-gradient(125deg,#1b1725,#171421)}.danger-zone h2{color:#ffc5d0}.danger-link{background:linear-gradient(110deg,#ff8a9e,#d95276);color:#1a0b13}.notice{padding:12px 15px;border-radius:11px;background:#102e2b;border:1px solid #286d65;color:#adf8e8}.error{background:#341d2a;border-color:#753e53;color:#ffc1cf}@media(max-width:650px){.settings-shell{margin:20px auto}.fields{grid-template-columns:1fr}.topline{align-items:flex-start}}
</style></head><body class="neon-app"><main class="settings-shell"><div class="topline"><a class="back" href="dashboard.php">← Student dashboard</a><a href="profile.php">My profile</a></div><p class="eyebrow">Account</p><h1>Settings</h1><p class="intro">Manage your sign-in details and account preferences.</p><?php if($message):?><p class="notice" role="status"><?=settingsEscape($message)?></p><?php endif;?><?php if($error):?><p class="notice error" role="alert"><?=settingsEscape($error)?></p><?php endif;?>
<section class="panel"><h2>Email address</h2><p>Change the email address you use to sign in. Confirm the change with your current password.</p><form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?=settingsEscape($_SESSION['csrf_token'])?>"><input type="hidden" name="action" value="change_email"><div class="fields"><div class="field"><label for="new_email">Email address</label><input id="new_email" name="new_email" type="email" maxlength="150" autocomplete="email" required value="<?=settingsEscape($currentAccount['email']??'')?>"></div><div class="field"><label for="email_current_password">Current password</label><input id="email_current_password" name="current_password" type="password" autocomplete="current-password" required></div></div><button class="primary" type="submit">Update email</button></form></section>
<section class="panel"><h2>Change password</h2><p>Use your current password to choose a new one. Forgot your current password? <a class="back" href="../forgot_password.php">Reset it by email</a>.</p><form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?=settingsEscape($_SESSION['csrf_token'])?>"><input type="hidden" name="action" value="change_password"><div class="fields"><div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div><div class="field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="8" autocomplete="new-password" required></div><div class="field"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required></div></div><button class="primary" type="submit">Update password</button></form></section>
<section class="panel"><h2>Contact details</h2><p>Add or remove your phone, website, and social accounts from your profile.</p><a class="primary" href="profile.php?edit=1">Manage contacts</a></section>
<section class="panel danger-zone"><h2>Delete account</h2><p>Permanently delete your account and associated quiz results. This action cannot be undone.</p><a class="danger-link" href="delete_account.php">Review account deletion</a></section></main><script src="../assets/page-navigation.js" defer></script></body></html>
