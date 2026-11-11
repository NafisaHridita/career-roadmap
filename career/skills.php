<?php
session_start(); require_once __DIR__ . '/common.php'; careerPageRequireStudent();
$ready=ensureCareerTables($conn); $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0; $careers=$ready?$conn->query('SELECT career_id,career_name,required_skills FROM careers ORDER BY career_name'):false;
careerPageStyles('Career skills'); echo '<main class="career-wrap">'; careerPageHeader('Skills by career');
if(!$ready) echo '<p class="notice">Career skills are not available.</p>';
elseif($id){$career=careerPageGetById($conn,$id);if(!$career)echo '<p class="content-box">Career not found.</p>';else{echo '<h2>'.careerPageEscape($career['career_name']).'</h2>'; $skills=careerPageSkills($career['required_skills']??''); if($skills){foreach($skills as $skill)echo '<span class="skill-pill">'.careerPageEscape($skill).'</span>';}else echo '<p class="content-box">No skills listed for this career yet.</p>';}}
elseif(!$careers||$careers->num_rows===0) echo '<p class="content-box">No career skills are listed yet.</p>';
else{echo '<section class="career-grid">';while($career=$careers->fetch_assoc()){echo '<article class="career-card"><h2>'.careerPageEscape($career['career_name']).'</h2>'; $skills=careerPageSkills($career['required_skills']??''); if($skills){foreach($skills as $skill)echo '<span class="skill-pill">'.careerPageEscape($skill).'</span>';}else echo '<p>No skills listed yet.</p>'; echo '<p><a class="career-link" href="skills.php?id='.(int)$career['career_id'].'">View skills</a></p></article>';}echo '</section>';}
echo '</main></body></html>';
?>
