<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once '../includes/module_helpers.php'; require_once '../includes/admin_tools.php';
$error=''; $message='';
// Adds the category and demand_level columns (and the activity log) when missing.
ensureAdminToolTables($conn);
// Skills are typed one per line (commas also work); this matches how the student pages split them.
// [ashasuborna] Career Management: validation, duplicates, category and demand, search, totals, delete, CSV (#10-#17)
function adminCareerSkillList(string $text): array {
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $text)), static fn($s) => $s !== ''));
}
// Case-insensitive duplicate check, ignoring the career being edited.
// [ashasuborna] Duplicate career names blocked, case-insensitive (#13)
function adminCareerNameTaken(mysqli $conn, string $name, int $exceptId): bool {
    $stmt=$conn->prepare('SELECT career_id FROM careers WHERE LOWER(career_name)=LOWER(?) AND career_id<>? LIMIT 1');
    if (!$stmt) return false;
    $stmt->bind_param('si',$name,$exceptId); $stmt->execute();
    $taken=$stmt->get_result()->num_rows>0; $stmt->close();
    return $taken;
}
if (!ensureCareerTables($conn)) $error='Could not prepare the careers table. Check database permissions.';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_career']) && !$error) {
    $id=filter_input(INPUT_POST,'career_id',FILTER_VALIDATE_INT);
    if (!$id) $error='Career ID is missing.';
    else {
        $nameStmt=$conn->prepare('SELECT career_name FROM careers WHERE career_id=?'); $nameStmt->bind_param('i',$id); $nameStmt->execute();
        $deletedName=(string)($nameStmt->get_result()->fetch_assoc()['career_name']??''); $nameStmt->close();
        // [ashasuborna] Delete in one transaction (#12)
        // Remove rows that reference the career first so the foreign keys allow the delete.
        $conn->begin_transaction();
        try {
            foreach (['DELETE FROM career_skills WHERE career_id=?','UPDATE ai_recommendations SET career_id=NULL WHERE career_id=?','DELETE FROM careers WHERE career_id=?'] as $sql) {
                $stmt=$conn->prepare($sql);
                if (!$stmt) throw new RuntimeException('prepare failed');
                $stmt->bind_param('i',$id);
                if (!$stmt->execute()) throw new RuntimeException('execute failed');
                $deleted=$stmt->affected_rows;
                $stmt->close();
            }
            $conn->commit();
            $message=$deleted===1?'Career deleted.':'That career was already removed.';
            if ($deleted===1) logActivity($conn,'career_deleted',"Deleted career: {$deletedName}");
        } catch (Throwable $t) {
            $conn->rollback();
            $error='Could not delete this career.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_career']) && !$error) {
    $id=(int)($_POST['career_id']??0); $name=trim($_POST['career_name']??''); $description=trim($_POST['description']??''); $skills=trim($_POST['required_skills']??'');
    // [ashasuborna] Career category and job demand (#16)
    // Category and demand level are optional; anything outside the lists is saved as empty (NULL).
    $category=in_array($_POST['category']??'',CAREER_CATEGORIES,true)?$_POST['category']:null;
    $demand=in_array($_POST['demand_level']??'',CAREER_DEMAND_LEVELS,true)?$_POST['demand_level']:null;
    // [ashasuborna] Validation: name, at least one skill, duplicate name (#13)
    if ($name==='') $error='Career name is required.';
    elseif (mb_strlen($name)>150) $error='Career name must be 150 characters or fewer.';
    elseif (!adminCareerSkillList($skills)) $error='Add at least one required skill (one per line).';
    elseif (adminCareerNameTaken($conn,$name,$id)) $error='A career named "'.$name.'" already exists. Edit that career instead.';
    else if ($id>0) {
        $stmt=$conn->prepare('UPDATE careers SET career_name=?,description=?,required_skills=?,category=?,demand_level=? WHERE career_id=?');
        if (!$stmt) $error='Could not update career.';
        else {
            $stmt->bind_param('sssssi',$name,$description,$skills,$category,$demand,$id);
            if($stmt->execute()) {
                // [ashasuborna] Skills saved one per row, with error messages (#10, #11)
                $message = syncCareerSkillRows($conn, $id, $skills) ? 'Career updated.' : 'Career updated, but the skill list could not be saved.';
                logActivity($conn,'career_updated',"Updated career: {$name}");
            } else $error='Could not update career.';
            $stmt->close();
        }
    }
    else {
        $stmt=$conn->prepare('INSERT INTO careers (career_name,description,required_skills,category,demand_level) VALUES (?,?,?,?,?)');
        if (!$stmt) $error='Could not add career.';
        else {
            $stmt->bind_param('sssss',$name,$description,$skills,$category,$demand);
            if($stmt->execute()) {
                $newId = (int)$stmt->insert_id;
                $message = syncCareerSkillRows($conn, $newId, $skills) ? 'Career added.' : 'Career added, but the skill list could not be saved.';
                logActivity($conn,'career_added',"Added career: {$name}");
            } else $error='Could not add career.';
            $stmt->close();
        }
    }
}
$edit=null; if(isset($_GET['edit'])) { $id=(int)$_GET['edit']; $stmt=$conn->prepare('SELECT * FROM careers WHERE career_id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $edit=$stmt->get_result()->fetch_assoc(); $stmt->close(); }
// [ashasuborna] Form keeps input when a save fails (#13)
// Keep what the admin typed when saving fails, so nothing has to be retyped.
$form=$edit?:[];
if ($error && isset($_POST['save_career'])) $form=['career_id'=>(int)($_POST['career_id']??0),'career_name'=>trim($_POST['career_name']??''),'description'=>trim($_POST['description']??''),'required_skills'=>trim($_POST['required_skills']??''),'category'=>$_POST['category']??'','demand_level'=>$_POST['demand_level']??''];
$isEditing=(int)($form['career_id']??0)>0;
// [ashasuborna] Search and category/demand filters (#14, #16)
// Search by career name, description or skill, and filter by category and demand level.
$search=trim((string)($_GET['q']??''));
$filterCategory=in_array($_GET['category']??'',CAREER_CATEGORIES,true)?$_GET['category']:'';
$filterDemand=in_array($_GET['demand']??'',CAREER_DEMAND_LEVELS,true)?$_GET['demand']:'';
$where=[]; $types=''; $params=[];
if ($search!=='') { $like='%'.$search.'%'; $where[]='(career_name LIKE ? OR description LIKE ? OR required_skills LIKE ?)'; $types.='sss'; array_push($params,$like,$like,$like); }
if ($filterCategory!=='') { $where[]='category=?'; $types.='s'; $params[]=$filterCategory; }
if ($filterDemand!=='') { $where[]='demand_level=?'; $types.='s'; $params[]=$filterDemand; }
$filtering=(bool)$where;
$stmt=$conn->prepare('SELECT * FROM careers'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY career_name');
if ($params) $stmt->bind_param($types,...$params);
$stmt->execute(); $careers=$stmt->get_result(); $stmt->close();
// [ashasuborna] Totals for the stats bar (#15)
$careerTotal=(int)$conn->query('SELECT COUNT(*) AS n FROM careers')->fetch_assoc()['n'];
$skillTotal=0; foreach ($conn->query('SELECT required_skills FROM careers') as $row) $skillTotal+=count(adminCareerSkillList((string)$row['required_skills']));
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Career Management</title><style>body{font:16px Arial;background:#f4f6fb;color:#172033;margin:0}.wrap{max-width:1050px;margin:32px auto;background:white;padding:26px;border-radius:12px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}input,textarea{display:block;width:100%;box-sizing:border-box;padding:10px;margin:8px 0 14px}textarea{min-height:90px}button{padding:10px 16px}article{padding:12px;border-bottom:1px solid #ddd}.note{padding:10px;background:#eef6ff}</style>
<style>.stats{display:flex;gap:12px;flex-wrap:wrap;margin:6px 0 16px}.stats span{padding:8px 14px;border:1px solid #36456a;border-radius:999px;background:#151f36;color:#cfe0ff}.stats strong{color:#7ff0e0}.note.ok{border-left:4px solid #48d6a8}.note.err{border-left:4px solid #ff7a99}.search{display:flex;gap:8px;align-items:center;margin:0 0 10px}.search input{margin:0;flex:1}.hint{color:#9fb0cc;font-size:14px}.pill{display:inline-block;margin:3px 4px 3px 0;padding:4px 10px;border-radius:999px;background:#18324a;color:#bff7ee;font-size:13px}.count{font-size:13px;font-weight:normal;color:#9fb0cc}.two{display:grid;grid-template-columns:1fr 1fr;gap:12px}select{display:block;width:100%;padding:10px;margin:8px 0 14px}.filters{flex-wrap:wrap}.filters input[type=search]{flex:1 1 100%}.filters select{width:auto;flex:1;margin:0}.badges{margin:4px 0}.badge{display:inline-block;margin-right:6px;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:bold}.badge.cat{background:#252a52;color:#c6c9ff}.badge.demand-high{background:#123a2c;color:#7ff0b8}.badge.demand-medium{background:#3a3214;color:#ffe08a}.badge.demand-low{background:#3a1a22;color:#ffa3b5}h1 .btn-link{font-size:14px;vertical-align:middle;margin-left:10px}</style><?=adminToolsStyles()?><link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
</head><body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a><main class="wrap"><?=adminToolsNav('careers.php')?><h1>Career Management <a class="btn-link" href="export.php?type=careers">⬇ Export CSV</a></h1><p>Add careers and maintain each career's description and required skills (one skill per line). Students see these careers in Career Explorer, the Skill Gap Checker uses the skills, and the AI quiz prefers careers from this list.</p>
<div class="stats"><span><strong><?=$careerTotal?></strong> careers</span><span><strong><?=$skillTotal?></strong> required skills</span></div>
<?php foreach([[$message,'note ok'],[$error,'note err']] as [$n,$cls]):if($n):?><p class="<?=$cls?>" role="status"><?=e($n)?></p><?php endif;endforeach;?>
<div class="grid"><section><h2><?= $isEditing?'Edit career':'Add a career' ?></h2><form method="post"><input type="hidden" name="career_id" value="<?= (int)($form['career_id']??0) ?>"><label>Career name<input name="career_name" maxlength="150" required value="<?=e($form['career_name']??'')?>"></label><label>Description<textarea name="description"><?=e($form['description']??'')?></textarea></label><label>Required skills (one per line)<textarea name="required_skills" required placeholder="Communication&#10;Problem solving"><?=e($form['required_skills']??'')?></textarea></label><div class="two"><label>Category<select name="category"><option value="">Not set</option><?php foreach(CAREER_CATEGORIES as $cat):?><option<?=($form['category']??'')===$cat?' selected':''?>><?=e($cat)?></option><?php endforeach;?></select></label><label>Job demand<select name="demand_level"><option value="">Not set</option><?php foreach(CAREER_DEMAND_LEVELS as $lvl):?><option<?=($form['demand_level']??'')===$lvl?' selected':''?>><?=e($lvl)?></option><?php endforeach;?></select></label></div><button name="save_career" value="1"><?= $isEditing?'Update career':'Save career' ?></button><?php if($isEditing):?> <a href="careers.php">Cancel</a><?php endif;?></form></section>
<section><h2>Careers</h2><form method="get" class="search filters"><input type="search" name="q" value="<?=e($search)?>" placeholder="Search name, description or skill" aria-label="Search careers"><select name="category" aria-label="Filter by category"><option value="">All categories</option><?php foreach(CAREER_CATEGORIES as $cat):?><option<?=$filterCategory===$cat?' selected':''?>><?=e($cat)?></option><?php endforeach;?></select><select name="demand" aria-label="Filter by demand"><option value="">Any demand</option><?php foreach(CAREER_DEMAND_LEVELS as $lvl):?><option<?=$filterDemand===$lvl?' selected':''?>><?=e($lvl)?></option><?php endforeach;?></select><button>Filter</button><?php if($filtering):?> <a href="careers.php">Clear</a><?php endif;?></form>
<?php if($filtering):?><p class="hint"><?=(int)$careers->num_rows?> result(s)<?= $search!==''?' for "'.e($search).'"':'' ?><?= $filterCategory!==''?' in '.e($filterCategory):'' ?><?= $filterDemand!==''?' · '.e($filterDemand).' demand':'' ?></p><?php endif;?>
<?php if($careers->num_rows===0):?><p class="hint"><?= $filtering?'No careers match your search or filters.':'No careers yet. Add the first one with the form.' ?></p><?php endif;?>
<?php while($c=$careers->fetch_assoc()): $list=adminCareerSkillList((string)$c['required_skills']);?><article><h3><?=e($c['career_name'])?> <small class="count"><?=count($list)?> skill<?=count($list)===1?'':'s'?></small></h3><p class="badges"><?php if(!empty($c['category'])):?><span class="badge cat"><?=e($c['category'])?></span><?php endif;?><?php if(!empty($c['demand_level'])):?><span class="badge demand-<?=strtolower(e($c['demand_level']))?>"><?=e($c['demand_level'])?> demand</span><?php endif;?></p><p><?=nl2br(e($c['description']))?></p><p><?php foreach($list as $s):?><span class="pill"><?=e($s)?></span><?php endforeach;?></p><a href="?edit=<?=(int)$c['career_id']?>">Edit</a> <form method="post" style="display:inline" onsubmit="return confirm('Delete this career and its skill list?')"><input type="hidden" name="career_id" value="<?=(int)$c['career_id']?>"><button class="danger" name="delete_career" value="1">Delete</button></form></article><?php endwhile;?></section></div></main></body></html>
