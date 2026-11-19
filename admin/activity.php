<?php
// [ashasuborna] Activity Log with filters, search and login unlock (#23)
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once '../includes/module_helpers.php'; require_once '../includes/admin_tools.php';
$error=''; $message='';
if (!ensureAdminToolTables($conn)) $error='Could not prepare the activity log. Check database permissions.';

// Unlock a login that was locked after too many wrong passwords.
if (!$error && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['unlock_email'])) {
    $email=requestText($_POST,'unlock_email');
    if ($email!=='' && unlockLogin($conn,$email)>0) {
        logActivity($conn,'login_unlocked',"Admin unlocked login for {$email}");
        header('Location: activity.php?unlocked='.urlencode($email)); exit();
    }
    $error='That login was not locked.';
}
if (requestText($_GET,'unlocked')!=='') $message='Login unlocked for '.requestText($_GET,'unlocked').'. They can try again now.';

// Readable names and colours for each action.
$actions=[
    'login_success'=>['Login','ok'],'login_failed'=>['Wrong password','warn'],'login_locked'=>['Login locked','bad'],'login_blocked'=>['Blocked attempt','bad'],
    'login_unlocked'=>['Unlocked','ok'],'register'=>['Registration','info'],
    'career_added'=>['Career added','info'],'career_updated'=>['Career updated','info'],'career_deleted'=>['Career deleted','bad'],
    'recommendation_deleted'=>['Result deleted','bad'],'export_csv'=>['CSV export','info'],
];
$filter=requestText($_GET,'action'); if (!isset($actions[$filter])) $filter='';
$search=requestText($_GET,'q');
$where=[]; $types=''; $params=[];
if ($filter!=='') { $where[]='action=?'; $types.='s'; $params[]=$filter; }
if ($search!=='') { $like='%'.$search.'%'; $where[]='(user_name LIKE ? OR details LIKE ?)'; $types.='ss'; array_push($params,$like,$like); }
$rows=[];
if (!$error) {
    $stmt=$conn->prepare('SELECT created_at,user_name,action,details,ip_address FROM activity_log'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY log_id DESC LIMIT 200');
    if ($params) $stmt->bind_param($types,...$params);
    $stmt->execute(); $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}
$locked=$error?[]:lockedLogins($conn);
$today=$error?0:(int)$conn->query('SELECT COUNT(*) FROM activity_log WHERE created_at >= CURDATE()')->fetch_row()[0];
$failedToday=$error?0:(int)$conn->query("SELECT COUNT(*) FROM activity_log WHERE created_at >= CURDATE() AND action IN ('login_failed','login_locked','login_blocked')")->fetch_row()[0];
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Activity Log | CareerRoadmap</title>
<style>body{font:16px Arial;margin:0}.wrap{max-width:1150px;margin:32px auto;padding:26px;border-radius:12px}</style>
<link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3"><?=adminToolsStyles()?>
<style>
table{width:100%;border-collapse:collapse;font-size:14px}th,td{padding:9px 10px;border-bottom:1px solid #26324f;text-align:left;vertical-align:top}th{color:#9fb0cc;font-weight:600}
.tag{display:inline-block;padding:2px 9px;border-radius:6px;font-size:12px;font-weight:bold;white-space:nowrap}
.tag.ok{background:#123a2c;color:#7ff0b8}.tag.warn{background:#3a3214;color:#ffe08a}.tag.bad{background:#3a1a22;color:#ffa3b5}.tag.info{background:#1d2a52;color:#b9c7ff}
.locked{padding:14px 16px;border:1px solid #a33a57;border-radius:12px;background:#2a1019;margin:0 0 18px}.locked h2{margin:0 0 8px;font-size:17px}.locked li{margin:6px 0}
.locked form{display:inline}.when{white-space:nowrap;color:#a9b8d4}
</style></head>
<body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a>
<main class="wrap"><?=adminToolsNav('activity.php')?>
<h1>Activity Log</h1><p class="hint">Who did what and when: logins, wrong passwords, lockouts, registrations, career changes and exports. Newest first (last 200).</p>
<div class="stats"><span><strong><?=$today?></strong> events today</span><span><strong><?=$failedToday?></strong> failed or blocked logins today</span><span><strong><?=count($locked)?></strong> locked right now</span></div>
<?php if($message):?><p class="note ok" role="status"><?=e($message)?></p><?php endif;?><?php if($error):?><p class="note err" role="alert"><?=e($error)?></p><?php endif;?>
<?php if($locked):?><section class="locked"><h2>🔒 Locked logins</h2><p class="hint">Locked after <?=LOGIN_MAX_FAILURES?> wrong passwords. The lock ends by itself after <?=LOGIN_LOCK_MINUTES?> minutes, or unlock it now.</p><ul>
<?php foreach($locked as $l):?><li><strong><?=e($l['email'])?></strong> · <?=(int)$l['failures']?> failed attempts · last at <?=e($l['last_attempt'])?> <form method="post"><input type="hidden" name="unlock_email" value="<?=e($l['email'])?>"><button>Unlock</button></form></li><?php endforeach;?>
</ul></section><?php endif;?>
<form method="get" class="toolbar"><input type="search" name="q" value="<?=e($search)?>" placeholder="Search name, email or details" aria-label="Search activity"><select name="action" aria-label="Filter by action"><option value="">All actions</option><?php foreach($actions as $key=>[$label]):?><option value="<?=e($key)?>"<?=$filter===$key?' selected':''?>><?=e($label)?></option><?php endforeach;?></select><button>Filter</button><?php if($filter!==''||$search!==''):?><a href="activity.php">Clear</a><?php endif;?></form>
<table><thead><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody>
<?php if(!$rows):?><tr><td colspan="5" class="hint">No activity yet. Log in, add a career or export a file and it appears here.</td></tr><?php endif;?>
<?php foreach($rows as $r): [$label,$cls]=$actions[$r['action']]??[$r['action'],'info'];?><tr><td class="when"><?=e($r['created_at'])?></td><td><?=e($r['user_name']?:'Guest')?></td><td><span class="tag <?=$cls?>"><?=e($label)?></span></td><td><?=e($r['details'])?></td><td class="hint"><?=e($r['ip_address'])?></td></tr><?php endforeach;?>
</tbody></table></main><script src="../assets/page-navigation.js" defer></script></body></html>
