<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php';
$userId = (int) $_SESSION['user_id'];
$error = '';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm = trim((string)($_POST['confirm_delete'] ?? ''));
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) $error = 'Your session expired. Refresh and try again.';
    elseif ($confirm !== 'DELETE') $error = 'Type DELETE exactly to confirm account removal.';
    else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id=? AND role='student' LIMIT 1");
        $stmt->bind_param('i', $userId); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$row || !password_verify($password, $row['password'])) $error = 'Your password is incorrect.';
        else {
            try {
                $conn->begin_transaction();
                foreach ([
                    'DELETE FROM assessment_answers WHERE assessment_id IN (SELECT assessment_id FROM assessments WHERE user_id=?)',
                    'DELETE FROM ai_recommendations WHERE assessment_id IN (SELECT assessment_id FROM assessments WHERE user_id=?)',
                    'DELETE FROM career_recommendations WHERE user_id=?',
                    'DELETE FROM assessments WHERE user_id=?',
                    'DELETE FROM admin_profile_social_links WHERE user_id=?',
                    'DELETE FROM admin_profiles WHERE user_id=?',
                    "DELETE FROM users WHERE user_id=? AND role='student'"
                ] as $sql) {
                    $delete = $conn->prepare($sql); $delete->bind_param('i', $userId);
                    if (!$delete->execute()) throw new RuntimeException('Could not remove all account records.');
                    $delete->close();
                }
                $conn->commit();
                $_SESSION = [];
                if (ini_get('session.use_cookies')) { $params = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']); }
                session_destroy();
                header('Location: ../login.php?account_deleted=1'); exit();
            } catch (Throwable $exception) {
                $conn->rollback();
                $error = 'Account could not be deleted because some related data could not be removed. Please contact support.';
            }
        }
    }
}
function deleteEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Delete account | CareerRoadmap</title><link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3"><link rel="stylesheet" href="../assets/glass-surfaces.css?v=20261115-2218"><style>
body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(ellipse at 10% 5%,#173555 0,transparent 32%),radial-gradient(ellipse at 92% 90%,#281747 0,transparent 35%),#09101e;color:#eaf1ff;font:16px Inter,"Segoe UI",Arial,sans-serif}.delete-card{width:min(880px,100%);display:grid;grid-template-columns:1fr .78fr;overflow:hidden;border:1px solid #384765;border-radius:26px;background:#0e1727;box-shadow:0 28px 90px #020611aa;animation:enter .5s ease both}.content{padding:clamp(26px,5vw,52px)}.brand{color:#8cefe1;font-weight:800;text-decoration:none}.mark{display:grid;place-items:center;width:50px;height:50px;margin-top:38px;border-radius:15px;background:#33202c;color:#ff9caf;font-size:25px}.eyebrow{margin:20px 0 7px;color:#ff9caf;font-size:12px;font-weight:800;letter-spacing:1.8px;text-transform:uppercase}h1{margin:0;font-size:clamp(30px,5vw,42px);color:#fff}p{color:#afbbce;line-height:1.65}.list{padding-left:20px;color:#c7d1e1;line-height:1.9}.side{padding:clamp(26px,4vw,40px);display:flex;flex-direction:column;justify-content:center;background:linear-gradient(155deg,#21182c,#171724);border-left:1px solid #493346}.side h2{margin:0 0 6px;color:#ffd1d9;font-size:19px}.side p{font-size:14px}.field{margin-top:15px}.field label{display:block;margin-bottom:7px;font-size:14px;font-weight:750}.field input{width:100%;height:46px;padding:0 12px;border:1px solid #4a4155;border-radius:10px;background:#101523;color:#fff;font:inherit}.field input:focus{outline:2px solid #ff8ca5;outline-offset:1px}.confirm-box{padding:14px;margin-top:20px;border:1px solid #614b59;border-radius:12px;background:#171522;color:#c9c1d1;font-size:14px;line-height:1.5}.confirm-box code{color:#ffb4c2;font-weight:800}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 16px;border:0;border-radius:10px;font-weight:800;text-decoration:none;cursor:pointer;transition:transform .18s,box-shadow .18s,filter .18s}.btn:hover{transform:translateY(-2px);filter:brightness(1.08)}.cancel{background:#1c2c40;color:#b9f7ee;border:1px solid #466375}.delete{background:linear-gradient(110deg,#ff879e,#cd426d);color:#210b13;box-shadow:0 7px 20px #e4517430}.error{padding:11px 13px;border-radius:10px;background:#3a1c2a;border:1px solid #784252;color:#ffc6d2}@keyframes enter{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}@media(max-width:720px){.delete-card{grid-template-columns:1fr}.side{border-left:0;border-top:1px solid #493346}}
</style></head><body class="neon-app"><main class="delete-card"><section class="content"><a class="brand" href="dashboard.php">CareerRoadmap</a><div class="mark" aria-hidden="true">!</div><p class="eyebrow">Permanent action</p><h1>Delete your account?</h1><p>We're sorry to see you go. This will permanently remove your student account and the data connected to it.</p><ul class="list"><li>Your profile and sign-in will be removed.</li><li>Your quiz submissions and saved results will be deleted.</li><li>You won't be able to undo this action.</li></ul><a class="btn cancel" href="settings.php">Keep my account</a></section><section class="side"><h2>Confirm account deletion</h2><p>For your security, enter your current password and type <code>DELETE</code>.</p><?php if($error):?><p class="error" role="alert"><?=deleteEscape($error)?></p><?php endif;?><form method="post"><input type="hidden" name="csrf_token" value="<?=deleteEscape($_SESSION['csrf_token'])?>"><div class="field"><label for="password">Current password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><div class="field"><label for="confirm_delete">Type DELETE to continue</label><input id="confirm_delete" name="confirm_delete" autocomplete="off" required></div><div class="confirm-box">This permanently deletes your CareerRoadmap account and related quiz data.</div><div class="actions"><button class="btn delete" type="submit">Delete my account</button><a class="btn cancel" href="settings.php">Cancel</a></div></form></section></main><script src="../assets/page-navigation.js" defer></script></body></html>
