<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once '../includes/module_helpers.php';
$error=''; $message=''; $rows=null; $stats=['total'=>0,'students'=>0,'latest'=>null];
$ready=ensureRecommendationTable($conn);
if (!$ready) $error='Could not prepare recommendations storage. Check database permissions.';

// Delete one saved recommendation (for example a test run or a duplicate).
if ($ready && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_recommendation'])) {
    $id=filter_input(INPUT_POST,'recommendation_id',FILTER_VALIDATE_INT);
    if (!$id) $error='Recommendation ID is missing.';
    else {
        $stmt=$conn->prepare('DELETE FROM career_recommendations WHERE recommendation_id=?');
        $stmt->bind_param('i',$id);
        if ($stmt->execute() && $stmt->affected_rows===1) { $stmt->close(); header('Location: recommendations.php?deleted=1'); exit(); }
        $error='Could not delete this recommendation. It may already be removed.';
        $stmt->close();
    }
}
if (isset($_GET['deleted'])) $message='Recommendation deleted.';

// Search by student name, email or anything in the AI result text.
$search=trim((string)($_GET['q']??''));
if ($ready) {
    $sql='SELECT r.recommendation_id,r.result_text,r.created_at,u.name,u.email FROM career_recommendations r LEFT JOIN users u ON u.user_id=r.user_id';
    if ($search!=='') {
        $like='%'.$search.'%';
        $stmt=$conn->prepare($sql.' WHERE u.name LIKE ? OR u.email LIKE ? OR r.result_text LIKE ? ORDER BY r.created_at DESC, r.recommendation_id DESC');
        $stmt->bind_param('sss',$like,$like,$like); $stmt->execute(); $rows=$stmt->get_result(); $stmt->close();
    } else $rows=$conn->query($sql.' ORDER BY r.created_at DESC, r.recommendation_id DESC');
    if (!$rows) $error='Could not load AI recommendations.';
    $s=$conn->query('SELECT COUNT(*) AS total, COUNT(DISTINCT user_id) AS students, MAX(created_at) AS latest FROM career_recommendations');
    if ($s) $stats=$s->fetch_assoc();
}
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>AI Recommendations</title><style>body{font:16px Arial;background:#f4f6fb;color:#172033;margin:0}.wrap{max-width:1050px;margin:32px auto;background:white;padding:26px;border-radius:12px}.item{border:1px solid #ddd;border-radius:8px;padding:18px;margin:16px 0}.result{white-space:pre-wrap;line-height:1.5}.note{padding:10px;background:#eef6ff}</style><link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
<style>.stats{display:flex;gap:12px;flex-wrap:wrap;margin:6px 0 16px}.stats span{padding:8px 14px;border:1px solid #36456a;border-radius:999px;background:#151f36;color:#cfe0ff}.stats strong{color:#7ff0e0}.note.ok{border-left:4px solid #48d6a8}.note.err{border-left:4px solid #ff7a99}.search{display:flex;gap:8px;align-items:center;margin:0 0 6px}.search input{flex:1;padding:10px;margin:0}.hint{color:#9fb0cc;font-size:14px}.item summary{cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:baseline}.item summary::-webkit-details-marker{display:none}.item summary h2{margin:0;font-size:20px}.item summary .meta{color:#9fb0cc;font-size:14px}.item summary:after{content:"Show ▾";color:#7ff0e0;font-size:14px}.item[open] summary:after{content:"Hide ▴"}.item .result{margin-top:14px}.item form{margin-top:12px}</style>
</head><body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a><main class="wrap"><h1>AI Recommendations</h1><p>Saved student career analyses, newest first. New quiz results use the careers and required skills from Career Management when that catalog has entries.</p>
<div class="stats"><span><strong><?=(int)$stats['total']?></strong> saved results</span><span><strong><?=(int)$stats['students']?></strong> students</span><span>Latest: <strong><?=e($stats['latest'] ?: 'none yet')?></strong></span></div>
<?php if($message):?><p class="note ok" role="status"><?=e($message)?></p><?php endif;?>
<?php if($error):?><p class="note err" role="alert"><?=e($error)?></p><?php endif;?>
<?php if($rows):?>
<form method="get" class="search"><input type="search" name="q" value="<?=e($search)?>" placeholder="Search student name, email or career" aria-label="Search recommendations"><button>Search</button><?php if($search!==''):?> <a href="recommendations.php">Clear</a><?php endif;?></form>
<?php if($search!==''):?><p class="hint"><?=(int)$rows->num_rows?> result(s) for "<?=e($search)?>"</p><?php endif;?>
<?php if($rows->num_rows===0):?><p><?= $search!=='' ? 'No saved recommendations match your search.' : "No recommendations have been saved yet. Students' new quiz results will appear here." ?></p>
<?php else: $first=true; while($r=$rows->fetch_assoc()):?>
<details class="item"<?= $first?' open':'' ?>><summary><h2><?=e($r['name']??'Deleted student')?></h2><span class="meta"><?=e($r['email']??'')?> · <?=e($r['created_at'])?></span></summary><div class="result"><?=e($r['result_text'])?></div><form method="post" onsubmit="return confirm('Delete this saved recommendation? The student will no longer see it in My Recommendations.')"><input type="hidden" name="recommendation_id" value="<?=(int)$r['recommendation_id']?>"><button class="danger" name="delete_recommendation" value="1">Delete</button></form></details>
<?php $first=false; endwhile; endif; endif;?>
</main></body></html>
