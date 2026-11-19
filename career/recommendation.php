<?php
session_start(); require_once __DIR__ . '/common.php'; careerPageRequireStudent();
$ready=ensureRecommendationTable($conn); $recommendations=false;
if($ready){$userId=(int)$_SESSION['user_id'];$stmt=$conn->prepare('SELECT result_text,created_at FROM career_recommendations WHERE user_id=? ORDER BY created_at DESC LIMIT 10');$stmt->bind_param('i',$userId);$stmt->execute();$recommendations=$stmt->get_result();$stmt->close();}
careerPageStyles('My career recommendations');echo '<main class="career-wrap">';careerPageHeader('My career recommendations');
if(!$ready) echo '<p class="notice">Recommendation history is not available.</p>';
elseif(!$recommendations||$recommendations->num_rows===0) echo '<p class="content-box">You do not have a quiz recommendation yet. Take the Career Interest Quiz to get started.</p><a class="career-link" href="../student/quiz.php">Take the quiz</a>';
else{while($item=$recommendations->fetch_assoc())echo '<article class="content-box"><small>'.careerPageEscape($item['created_at']).'</small><br>'.nl2br(careerPageEscape($item['result_text'])).'</article>';}
echo '</main><script src="../assets/page-navigation.js" defer></script></body></html>';
?>
