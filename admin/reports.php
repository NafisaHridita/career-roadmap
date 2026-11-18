<?php
// [ashasuborna] Reports & Analytics dashboard: key numbers, charts, period, print (#22)
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once '../includes/module_helpers.php'; require_once '../includes/admin_tools.php';
ensureAdminToolTables($conn); ensureRecommendationTable($conn);

function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function scalar(mysqli $conn, string $sql): int { $r=$conn->query($sql); return $r ? (int)($r->fetch_row()[0] ?? 0) : 0; }

// Days to chart (7, 14 or 30).
$days=(int)($_GET['days']??14);
if (!in_array($days,[7,14,30],true)) $days=14;

// Headline numbers
$approved=scalar($conn,"SELECT COUNT(*) FROM users WHERE role='student' AND account_status='approved'");
$pending=scalar($conn,"SELECT COUNT(*) FROM users WHERE role='student' AND account_status='pending'");
$admins=scalar($conn,"SELECT COUNT(*) FROM users WHERE role='admin'");
$careerCount=scalar($conn,'SELECT COUNT(*) FROM careers');
$skillCount=scalar($conn,'SELECT COUNT(*) FROM career_skills');
$recCount=scalar($conn,'SELECT COUNT(*) FROM career_recommendations');
// Approved students who have at least one saved AI quiz result.
$quizStudents=scalar($conn,"SELECT COUNT(DISTINCT r.user_id) FROM career_recommendations r JOIN users u ON u.user_id=r.user_id WHERE u.role='student' AND u.account_status='approved'");
$logins=scalar($conn,"SELECT COUNT(*) FROM activity_log WHERE action='login_success' AND created_at > NOW() - INTERVAL {$days} DAY");
$failed=scalar($conn,"SELECT COUNT(*) FROM activity_log WHERE action IN ('login_failed','login_locked','login_blocked') AND created_at > NOW() - INTERVAL {$days} DAY");
$students=$approved+$pending;
$quizRate=$approved>0?round(min(100,$quizStudents/$approved*100)):0;

// Daily counts for the last $days days (missing days filled with 0).
// Uses the database clock so the days match DATE(created_at) even if PHP's timezone differs.
function dailySeries(mysqli $conn, string $sql, int $days): array {
    $today=new DateTime((string)$conn->query('SELECT CURDATE()')->fetch_row()[0]);
    $series=[]; for($i=$days-1;$i>=0;$i--) $series[(clone $today)->modify("-{$i} day")->format('Y-m-d')]=0;
    $r=$conn->query($sql); if($r) while($row=$r->fetch_assoc()) if(isset($series[$row['d']])) $series[$row['d']]=(int)$row['n'];
    return $series;
}
$signups=dailySeries($conn,"SELECT DATE(created_at) d, COUNT(*) n FROM users WHERE role='student' AND created_at > CURDATE() - INTERVAL {$days} DAY GROUP BY d",$days);
$quizzes=dailySeries($conn,"SELECT DATE(created_at) d, COUNT(*) n FROM career_recommendations WHERE created_at > CURDATE() - INTERVAL {$days} DAY GROUP BY d",$days);

// Most recommended careers: how many saved AI results mention each career in the catalog.
$topCareers=[];
$names=$conn->query('SELECT career_name FROM careers ORDER BY career_name');
$countStmt=$conn->prepare('SELECT COUNT(*) FROM career_recommendations WHERE result_text LIKE ?');
if($names && $countStmt) while($row=$names->fetch_assoc()){
    $like='%'.$row['career_name'].'%'; $countStmt->bind_param('s',$like); $countStmt->execute();
    $n=(int)$countStmt->get_result()->fetch_row()[0]; if($n>0) $topCareers[$row['career_name']]=$n;
}
arsort($topCareers); $topCareers=array_slice($topCareers,0,8,true);

// Careers by category and demand
$byCategory=[]; $r=$conn->query("SELECT COALESCE(NULLIF(category,''),'Not set') c, COUNT(*) n FROM careers GROUP BY c ORDER BY n DESC, c"); if($r) while($row=$r->fetch_assoc()) $byCategory[$row['c']]=(int)$row['n'];
$byDemand=['High'=>0,'Medium'=>0,'Low'=>0,'Not set'=>0]; $r=$conn->query("SELECT COALESCE(demand_level,'Not set') d, COUNT(*) n FROM careers GROUP BY d"); if($r) while($row=$r->fetch_assoc()) $byDemand[$row['d']]=(int)$row['n'];

// Skills needed by the most careers
$topSkills=[]; $r=$conn->query("SELECT skill_name s, COUNT(DISTINCT career_id) n FROM career_skills GROUP BY skill_name HAVING n>0 ORDER BY n DESC, s LIMIT 8"); if($r) while($row=$r->fetch_assoc()) $topSkills[$row['s']]=(int)$row['n'];

// Horizontal bar list
function hbars(array $data, string $unit, string $color): string {
    if(!$data) return '<p class="hint">No data yet.</p>';
    $max=max($data)?:1; $html='<ul class="hbars">';
    foreach($data as $label=>$n) $html.='<li><span class="lbl">'.e($label).'</span><span class="track"><span class="fill" style="width:'.round($n/$max*100).'%;background:'.$color.'"></span></span><span class="num">'.$n.' '.e($unit).'</span></li>';
    return $html.'</ul>';
}
// Vertical day columns
function columns(array $series, string $color): string {
    $max=max($series)?:1; $total=array_sum($series); $html='<div class="cols" role="img" aria-label="'.$total.' in total">';
    foreach($series as $day=>$n) $html.='<div class="col" title="'.e(date('j M',strtotime($day))).': '.$n.'"><span class="v">'.($n?:'').'</span><span class="bar" style="height:'.max(2,round($n/$max*100)).'%;background:'.$color.'"></span><span class="d">'.e(date('j',strtotime($day))).'</span></div>';
    return $html.'</div><p class="hint">Total: <strong>'.$total.'</strong> · '.e(date('j M',strtotime(array_key_first($series)))).' to '.e(date('j M',strtotime(array_key_last($series)))).'</p>';
}
$approvedPct=$students?round($approved/$students*100):0;
$generatedAt=(string)$conn->query("SELECT DATE_FORMAT(NOW(),'%e %b %Y, %l:%i %p')")->fetch_row()[0];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reports | CareerRoadmap</title>
<style>body{font:16px Arial;margin:0}.wrap{max-width:1150px;margin:32px auto;padding:26px;border-radius:12px}</style>
<link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3"><?=adminToolsStyles()?>
<style>
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin:14px 0 22px}
.kpi{padding:16px;border:1px solid #2c3a5c;border-radius:14px;background:linear-gradient(145deg,#121b30,#0e1526)}
.kpi b{display:block;font-size:30px;color:#7ff0e0;line-height:1.1}.kpi span{color:#a9b8d4;font-size:13px}.kpi.warn b{color:#ffd166}.kpi.bad b{color:#ff8ea9}
.charts{display:grid;grid-template-columns:repeat(auto-fit,minmax(330px,1fr));gap:16px}
.card{padding:18px;border:1px solid #2c3a5c;border-radius:14px;background:#0f1729}.card h2{font-size:17px;margin:0 0 4px}.card .sub{margin:0 0 14px;color:#9fb0cc;font-size:13px}
.cols{display:flex;align-items:flex-end;gap:4px;height:170px;padding-top:18px;border-bottom:1px solid #2c3a5c}
.col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;min-width:0}
.col .bar{width:100%;max-width:26px;border-radius:5px 5px 0 0;transition:height .6s}.col .v{font-size:11px;color:#cfe0ff;margin-bottom:3px}.col .d{font-size:10px;color:#7f8fae;margin-top:4px;position:relative;top:18px}
.cols+.hint{margin-top:24px}
.hbars{list-style:none;margin:0;padding:0}.hbars li{display:grid;grid-template-columns:150px 1fr 70px;gap:10px;align-items:center;margin:7px 0;font-size:14px}
.hbars .lbl{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#dfe8ff}.hbars .track{height:12px;border-radius:99px;background:#1b2540;overflow:hidden}.hbars .fill{display:block;height:100%;border-radius:99px}.hbars .num{color:#a9b8d4;font-size:13px;text-align:right}
.donut-wrap{display:flex;gap:18px;align-items:center;flex-wrap:wrap}
.donut{width:140px;height:140px;border-radius:50%;display:grid;place-items:center}
.donut i{width:92px;height:92px;border-radius:50%;background:#0f1729;display:grid;place-items:center;font-style:normal;font-size:22px;font-weight:bold;color:#7ff0e0}
.legend{list-style:none;padding:0;margin:0;font-size:14px}.legend li{margin:6px 0}.legend b{display:inline-block;width:12px;height:12px;border-radius:3px;margin-right:8px;vertical-align:-1px}
.head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
@media(max-width:600px){.hbars li{grid-template-columns:100px 1fr 55px}}
@media print{.card,.kpi{break-inside:avoid;border-color:#999;background:#fff!important}.kpi b,.donut i{color:#000}.cols .d{color:#000}}
</style></head>
<body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a>
<main class="wrap"><?=adminToolsNav('reports.php')?>
<div class="head"><div><h1>Reports &amp; Analytics</h1><p class="hint">Live numbers from the database · generated <?=e($generatedAt)?></p></div>
<form method="get" class="toolbar"><label class="hint">Period <select name="days" onchange="this.form.submit()"><?php foreach([7,14,30] as $d):?><option value="<?=$d?>"<?=$d===$days?' selected':''?>>Last <?=$d?> days</option><?php endforeach;?></select></label><noscript><button>Show</button></noscript><button type="button" class="no-print" onclick="window.print()">🖨 Print report</button><a class="btn-link" href="export.php?type=students">⬇ Students CSV</a></form></div>

<section class="kpis" aria-label="Key numbers">
<div class="kpi"><b><?=$approved?></b><span>Approved students</span></div>
<div class="kpi warn"><b><?=$pending?></b><span>Waiting for approval</span></div>
<div class="kpi"><b><?=$careerCount?></b><span>Careers · <?=$skillCount?> skills</span></div>
<div class="kpi"><b><?=$recCount?></b><span>AI quiz results saved</span></div>
<div class="kpi"><b><?=$quizRate?>%</b><span>Approved students who took the quiz</span></div>
<div class="kpi"><b><?=$logins?></b><span>Logins (last <?=$days?> days)</span></div>
<div class="kpi bad"><b><?=$failed?></b><span>Failed or blocked logins</span></div>
<div class="kpi"><b><?=$admins?></b><span>Admins</span></div>
</section>

<div class="charts">
<section class="card"><h2>New student registrations</h2><p class="sub">Per day, last <?=$days?> days</p><?=columns($signups,'linear-gradient(#7ff0e0,#3cb4c8)')?></section>
<section class="card"><h2>AI quiz results</h2><p class="sub">Career analyses saved per day, last <?=$days?> days</p><?=columns($quizzes,'linear-gradient(#a78bfa,#6d5bd0)')?></section>
<section class="card"><h2>Most recommended careers</h2><p class="sub">How many saved AI results mention each career</p><?=hbars($topCareers,'results','linear-gradient(90deg,#61e9d9,#8b91ff)')?></section>
<section class="card"><h2>Student accounts</h2><p class="sub">Approved vs waiting for approval</p>
<div class="donut-wrap"><div class="donut" style="background:conic-gradient(#48d6a8 0 <?=$approvedPct?>%,#ffd166 <?=$approvedPct?>% 100%)"><i><?=$students?></i></div>
<ul class="legend"><li><b style="background:#48d6a8"></b>Approved: <?=$approved?> (<?=$approvedPct?>%)</li><li><b style="background:#ffd166"></b>Pending: <?=$pending?> (<?=100-$approvedPct?>%)</li><li class="hint">Approve students in <a href="students.php">Student Approvals</a></li></ul></div></section>
<section class="card"><h2>Careers by category</h2><p class="sub">Set the category in Career Management</p><?=hbars($byCategory,'careers','linear-gradient(90deg,#8b91ff,#c6c9ff)')?></section>
<section class="card"><h2>Job demand</h2><p class="sub">Careers per demand level</p><?=hbars(array_filter($byDemand),'careers','linear-gradient(90deg,#48d6a8,#ffd166)')?></section>
<section class="card"><h2>Most needed skills</h2><p class="sub">Skills required by the most careers</p><?=hbars($topSkills,'careers','linear-gradient(90deg,#ffb36b,#ff7a99)')?></section>
</div>
</main></body></html>
