<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once '../includes/module_helpers.php';
$message=''; $error='';
if (!ensureUserApprovalColumn($conn)) $error='Could not prepare approval status. Check database permissions.';
if ($_SERVER['REQUEST_METHOD']==='POST' && !$error) {
    $id=filter_input(INPUT_POST,'user_id',FILTER_VALIDATE_INT);
    if(!$id) $error='Student ID is missing.';
    else { $stmt=$conn->prepare("DELETE FROM users WHERE user_id=? AND role='student' AND account_status='approved'"); $stmt->bind_param('i',$id);
        if($stmt->execute() && $stmt->affected_rows===1) $message='Student removed from the approved list.'; else $error='Could not remove this student. The account may have related records.'; $stmt->close(); }
}
$students=$conn->query("SELECT user_id,name,email FROM users WHERE role='student' AND account_status='approved' ORDER BY user_id DESC");
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Admin Student List</title><style>body{font:16px Arial;background:#f4f6fb;color:#172033;margin:0}.wrap{max-width:1000px;margin:32px auto;background:white;padding:26px;border-radius:12px}nav{margin:14px 0}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{text-align:left;padding:12px;border-bottom:1px solid #ddd}button{padding:8px 14px;cursor:pointer}.note{padding:10px;background:#eef6ff}</style><link rel="stylesheet" href="../assets/cute-theme.css">
</head><body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a><main class="wrap admin-student-list"><h1>Admin Student List</h1><nav><a href="students.php">New registrations</a></nav><p>Students you saved from new registrations appear here. Removing a student deletes the account.</p><?php foreach([$message,$error] as $n):if($n):?><p class="note"><?=e($n)?></p><?php endif;endforeach;?>
<table><thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead><tbody><?php if($students && $students->num_rows):while($s=$students->fetch_assoc()):?><tr><td><?=e($s['name'])?></td><td><?=e($s['email'])?></td><td><form method="post" onsubmit="return confirm('Remove this student account?')"><input type="hidden" name="user_id" value="<?=(int)$s['user_id']?>"><button class="danger" type="submit">Remove</button></form></td></tr><?php endwhile;else:?><tr><td colspan="3">No approved students yet.</td></tr><?php endif;?></tbody></table></main></body></html>
