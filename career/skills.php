<?php
session_start(); require_once __DIR__ . '/common.php'; careerPageRequireStudent();
$ready=ensureCareerTables($conn); $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0; $careers=$ready?$conn->query('SELECT career_id,career_name,required_skills FROM careers ORDER BY career_name'):false;
careerPageStyles('Career skills'); echo '<main class="career-wrap">'; careerPageHeader('Skills by career');
if(!$ready) echo '<p class="notice">Career skills are not available.</p>';
elseif($id){
    $career=careerPageGetById($conn,$id);
    if(!$career) echo '<p class="content-box">Career not found.</p>';
    else {
        echo '<h2>'.careerPageEscape($career['career_name']).'</h2>';
        $skills=careerPageSkills($career['required_skills']??'');
        if(!$skills) echo '<p class="content-box">No skills listed for this career yet.</p>';
        else {
            // [ashasuborna] Skill Gap Checker: match percentage, progress bar, missing skills (#26)
            // Skill gap checker: the student ticks the skills they already have.
            $checked=$_SERVER['REQUEST_METHOD']==='POST';
            $have=$checked?array_values(array_intersect($skills,array_map('strval',(array)($_POST['have']??[])))):[];
            $missing=array_values(array_diff($skills,$have));
            if($checked){
                $percent=(int)round(count($have)/count($skills)*100);
                echo '<section class="content-box gap-result"><strong>Skill match: '.$percent.'%</strong> ('.count($have).' of '.count($skills).' skills)<div class="gap-bar"><span style="width:'.$percent.'%"></span></div>';
                if($missing){
                    echo '<p>Skills to build next:</p>';
                    foreach($missing as $skill) echo '<span class="skill-pill gap-pill">'.careerPageEscape($skill).'</span>';
                    // [ashasuborna] Send the missing skills to the roadmap page (#27)
                    echo '<form method="post" action="roadmap.php?id='.(int)$career['career_id'].'">';
                    foreach($missing as $skill) echo '<input type="hidden" name="focus[]" value="'.careerPageEscape($skill).'">';
                    echo '<p><button type="submit">Build a roadmap for my missing skills</button></p></form>';
                } else echo '<p>You already have every listed skill for this career. Try a roadmap for advanced practice.</p>';
                echo '</section>';
            }
            echo '<h3>Skill gap checker</h3><p>Tick the skills you already have, then check your gap.</p><form method="post" class="gap-form">';
            foreach($skills as $skill){
                $on=in_array($skill,$have,true)?' checked':'';
                echo '<label class="gap-check"><input type="checkbox" name="have[]" value="'.careerPageEscape($skill).'"'.$on.'> '.careerPageEscape($skill).'</label>';
            }
            echo '<p><button type="submit">Check my skill gap</button> <a class="career-link" href="roadmap.php?id='.(int)$career['career_id'].'">Full roadmap</a></p></form>';
        }
    }
}
elseif(!$careers||$careers->num_rows===0) echo '<p class="content-box">No career skills are listed yet.</p>';
else{echo '<section class="career-grid">';while($career=$careers->fetch_assoc()){echo '<article class="career-card"><h2>'.careerPageEscape($career['career_name']).'</h2>'; $skills=careerPageSkills($career['required_skills']??''); if($skills){foreach($skills as $skill)echo '<span class="skill-pill">'.careerPageEscape($skill).'</span>';}else echo '<p>No skills listed yet.</p>'; echo '<p><a class="career-link" href="skills.php?id='.(int)$career['career_id'].'">Check my skill gap</a></p></article>';}echo '</section>';}
echo '</main><script src="../assets/page-navigation.js" defer></script></body></html>';
?>
