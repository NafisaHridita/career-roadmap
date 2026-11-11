<?php
session_start(); require_once __DIR__ . '/common.php'; require_once __DIR__ . '/../ai/generate_roadmap.php'; careerPageRequireStudent();
$ready=ensureCareerTables($conn); $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0; $career=$ready&&$id?careerPageGetById($conn,$id):null; $roadmap='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST' && $career){$generated=careerAiGenerateRoadmap($career['career_name'],careerPageSkills($career['required_skills']??''));if($generated['ok'])$roadmap=$generated['text'];else$error=$generated['error'];}
careerPageStyles('Learning roadmap');echo '<main class="career-wrap">';careerPageHeader('Learning roadmap');
if(!$ready) echo '<p class="notice">Career information is not available.</p>';
elseif(!$career) echo '<p class="content-box">Select a career to create a learning roadmap.</p><a class="career-link" href="career_list.php">Explore careers</a>';
else{echo '<p class="content-box">Build a beginner roadmap for <strong>'.careerPageEscape($career['career_name']).'</strong>. The AI will use its required skills to create suggested learning stages.</p>';if($error)echo '<p class="notice">'.careerPageEscape($error).'</p>';if($roadmap!=='')echo '<article class="content-box">'.nl2br(careerPageEscape($roadmap)).'</article>';echo '<form method="post"><button type="submit">Generate learning roadmap</button></form>';}
echo '</main></body></html>';
?>
