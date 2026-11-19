<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once '../includes/module_helpers.php';
$error=''; $rows=null;
if (ensureRecommendationTable($conn)) {
    $rows=$conn->query('SELECT r.recommendation_id,r.result_text,r.created_at,u.name,u.email FROM career_recommendations r LEFT JOIN users u ON u.user_id=r.user_id ORDER BY r.created_at DESC');
    if(!$rows) $error='Could not load AI recommendations.';
} else $error='Could not prepare recommendations storage. Check database permissions.';
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>AI Recommendations</title><style>body{font:16px Arial;background:#f4f6fb;color:#172033;margin:0}.wrap{max-width:1050px;margin:32px auto;background:white;padding:26px;border-radius:12px}.item{border:1px solid #ddd;border-radius:8px;padding:18px;margin:16px 0}.result{white-space:pre-wrap;line-height:1.5}.note{padding:10px;background:#eef6ff}</style><link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
</head><body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a><main class="wrap"><h1>AI Recommendations</h1><p>Saved student career analyses, newest first. New quiz results use the careers and required skills from Career Management when that catalog has entries.</p><?php if($error):?><p class="note"><?=e($error)?></p><?php elseif($rows->num_rows===0):?><p>No recommendations have been saved yet. Students' new quiz results will appear here.</p><?php else:while($r=$rows->fetch_assoc()):?><article class="item"><h2><?=e($r['name']??'Deleted student')?></h2><p><?=e($r['email']??'')?> · <?=e($r['created_at'])?></p><div class="result"><?=e($r['result_text'])?></div></article><?php endwhile;endif;?></main><script src="../assets/page-navigation.js" defer></script></body></html>
