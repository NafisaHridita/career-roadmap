<?php
session_start(); require_once __DIR__ . '/common.php'; careerPageRequireStudent();
$ready=ensureCareerTables($conn); $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0; $career=$ready&&$id?careerPageGetById($conn,$id):null;
careerPageStyles('Career details'); echo '<main class="career-wrap">';
if (!$ready) { careerPageHeader('Career details'); echo '<p class="notice">Career information is not available.</p>'; }
elseif (!$career) { careerPageHeader('Career not found'); echo '<p class="content-box">This career is unavailable. Choose another career from the list.</p>'; }
else {
    careerPageHeader($career['career_name']);
    echo '<section class="content-box">'.nl2br(careerPageEscape($career['description']??'More information will be added soon.')).'</section><h2>Required skills</h2>';
    $skills=careerPageSkills($career['required_skills']??'');
    if($skills){foreach($skills as $skill) echo '<span class="skill-pill">'.careerPageEscape($skill).'</span>';}
    else echo '<p class="content-box">Skills for this career have not been listed yet.</p>';
    echo '<p><a class="career-link" href="skills.php?id='.(int)$career['career_id'].'">Explore skills</a> <a class="career-link" href="roadmap.php?id='.(int)$career['career_id'].'">Build a learning roadmap</a></p>';
}
echo '</main><script src="../assets/page-navigation.js" defer></script></body></html>';
?>
