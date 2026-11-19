<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once '../includes/module_helpers.php';
$message=''; $error='';
if (!ensureUserApprovalColumn($conn)) $error='Could not prepare approval status. Check database permissions.';
if ($_SERVER['REQUEST_METHOD']==='POST' && !$error) {
    $id=filter_input(INPUT_POST,'user_id',FILTER_VALIDATE_INT); $action=$_POST['action']??'';
    if (!$id) $error='Student ID is missing.';
    elseif ($action==='approve') {
        $name=trim($_POST['name']??'');
        if($name==='') $error='Student name cannot be empty.';
        else { $stmt=$conn->prepare("UPDATE users SET name=?,account_status='approved' WHERE user_id=? AND role='student' AND account_status='pending'"); $stmt->bind_param('si',$name,$id);
            if($stmt->execute() && $stmt->affected_rows===1) $message='Name saved. Student approved and moved to Student List.'; else $error='Could not approve this student.'; $stmt->close(); }
    } elseif ($action==='delete') {
        $stmt=$conn->prepare("DELETE FROM users WHERE user_id=? AND role='student' AND account_status='pending'"); $stmt->bind_param('i',$id);
        if($stmt->execute() && $stmt->affected_rows===1) $message='Pending registration deleted.'; else $error='Could not delete this registration.'; $stmt->close();
    }
}
$pending=$conn->query("SELECT user_id,name,email FROM users WHERE role='student' AND account_status='pending' ORDER BY user_id DESC");
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Student Approvals | CareerRoadmap</title><style>body{font:16px Arial;background:#f4f6fb;color:#172033;margin:0}.wrap{max-width:1050px;margin:32px auto;background:#fff;padding:26px;border-radius:12px}nav{margin:14px 0}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{text-align:left;padding:12px;border-bottom:1px solid #ddd}button{padding:8px 14px;cursor:pointer}.note{padding:10px;background:#eef6ff}</style><link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
<link rel="stylesheet" href="../assets/glass-surfaces.css?v=20261115-2218">
</head><body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a><main class="wrap admin-students"><h1>Student Approvals</h1><nav><a href="students_list.php">See Student List</a></nav><p>Review new registrations. Approve &amp; Save adds a student to your Admin Student List only; students can log in whether or not they are on this list.</p><?php foreach([$message,$error] as $n):if($n):?><p class="note"><?=e($n)?></p><?php endif;endforeach;?>
<table><thead><tr><th>Name</th><th>Email</th><th>Actions</th></tr></thead><tbody><?php if($pending && $pending->num_rows):while($s=$pending->fetch_assoc()):?><tr><td><form id="approve-<?=(int)$s['user_id']?>" method="post"><input type="hidden" name="user_id" value="<?=(int)$s['user_id']?>"><input name="name" aria-label="Edit student name" maxlength="150" required value="<?=e($s['name'])?>"></form></td><td><?=e($s['email'])?></td><td><button form="approve-<?=(int)$s['user_id']?>" name="action" value="approve">Save &amp; approve</button> <form method="post" style="display:inline" onsubmit="return confirm('Delete this pending registration?')"><input type="hidden" name="user_id" value="<?=(int)$s['user_id']?>"><button name="action" value="delete">Delete</button></form></td></tr><?php endwhile;else:?><tr><td colspan="3">No new registrations to review.</td></tr><?php endif;?></tbody></table></main><script src="../assets/page-navigation.js" defer></script></body></html>
