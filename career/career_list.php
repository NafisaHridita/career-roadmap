<?php
session_start(); require_once __DIR__ . '/common.php'; careerPageRequireStudent();
$ready=ensureCareerTables($conn); $careers=$ready?$conn->query('SELECT career_id,career_name,description,required_skills FROM careers ORDER BY career_name'):false;
careerPageStyles('Explore careers'); echo '<main class="career-wrap">'; careerPageHeader('Explore careers');
if (!$ready) echo '<p class="notice">Career information is not available yet.</p>';
elseif (!$careers || $careers->num_rows===0) echo '<p class="content-box">No career paths have been added yet. Please check back later.</p>';
else { echo '<section class="career-grid">'; while($career=$careers->fetch_assoc()) { echo '<article class="career-card"><h2>'.careerPageEscape($career['career_name']).'</h2><p>'.careerPageEscape($career['description']??'Explore the skills and learning path for this career.').'</p><a class="career-link" href="career_details.php?id='.(int)$career['career_id'].'">View career →</a></article>'; } echo '</section>'; }
echo '</main><script src="../assets/page-navigation.js" defer></script></body></html>';
?>
