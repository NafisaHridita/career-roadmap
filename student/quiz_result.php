
<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}
require_once '../config/db.php';
require_once '../includes/module_helpers.php';
require_once '../ai/generate_recommendation.php';

ini_set('max_execution_time', 180);

$answers = $_POST['answers'] ?? [];
$questions = $_SESSION['quiz_questions'] ?? [];

$result = "";
$error = "";
$saveError = "";

if (empty($answers) || empty($questions)) {
    $error = "Quiz data missing. Please generate a new quiz.";
} else {

    $answerText = "";

    foreach ($questions as $index => $question) {

        $questionText = $question['question'] ?? '';
        $answer = $answers[$index] ?? 'Not answered';

        $answerText .=
            "Question: " . $questionText . "\n" .
            "Student Answer: " . $answer . "\n\n";
    }

    $careerCatalog = [];
    if (ensureCareerTables($conn)) {
        $careerRows = $conn->query('SELECT career_name, description, required_skills FROM careers ORDER BY career_name');
        if ($careerRows) {
            while ($career = $careerRows->fetch_assoc()) $careerCatalog[] = $career;
        }
    }

    $recommendation = careerAiGenerateRecommendation($answerText, $careerCatalog);
    if (!$recommendation['ok']) {
        $error = $recommendation['error'];
    } else {
        $result = $recommendation['text'];
        if (ensureRecommendationTable($conn)) {
            $userId = (int) $_SESSION['user_id'];
            $save = $conn->prepare('INSERT INTO career_recommendations (user_id, result_text) VALUES (?, ?)');
            $save->bind_param('is', $userId, $result);
            if (!$save->execute()) $saveError = 'Your result was created, but could not be saved to history.';
            $save->close();
        } else {
            $saveError = 'Your result was created, but recommendation history could not be prepared.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Career Analysis</title>
    <style>body{margin:0;min-height:100vh;padding:36px 18px;background:linear-gradient(135deg,#faf6ff,#fff8fb 60%,#eff8ff);font:16px Inter,"Segoe UI",Arial,sans-serif;color:#382d48}.report{max-width:900px;margin:0 auto}.report-head{padding:30px;background:linear-gradient(120deg,#f1e8ff,#fff0f5);border:1px solid #eee3f5;border-radius:24px;margin-bottom:18px}.report-head h1{margin:0 0 8px;color:#4c3264}.report-head p{margin:0;color:#766d80}.result-card{background:#fff;border:1px solid #eeeaf3;border-radius:20px;padding:26px;line-height:1.75;box-shadow:0 8px 24px #48345c0a}.result-text{white-space:pre-wrap}.notice{padding:12px;border-radius:10px;background:#fff1f0;color:#a8332d}.back{display:inline-block;margin-top:18px;padding:11px 16px;border-radius:22px;background:#7550a5;color:white;text-decoration:none;font-weight:700}</style>
<link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
</head>
<body class="neon-app">
<main class="report"><header class="report-head"><h1>✨ Your Career Snapshot</h1><p>A starting point for exploring what may fit you.</p></header>

<?php if ($error): ?>

    <p class="notice"><?php echo htmlspecialchars($error); ?></p>

<?php else: ?>

    <?php if ($saveError): ?><p class="notice"><?php echo htmlspecialchars($saveError); ?></p><?php endif; ?>

    <div class="result-card result-text">
        <?php echo nl2br(htmlspecialchars($result)); ?>
    </div>

<?php endif; ?>

<a class="back home-button" href="dashboard.php">⌂ Home</a></main>

</body>
</html>
