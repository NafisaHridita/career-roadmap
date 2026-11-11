<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once 'question_helpers.php'; require_once '../ai/generate_questions.php';
$error = ''; $message = '';
if (!ensureQuestionStatusColumn($conn) || !ensureOptionLabelColumn($conn)) $error = 'Could not prepare question review columns. Check database permissions.';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate']) && !$error) {
    $category = trim($_POST['category'] ?? 'Career Interest');
    $questionCount = filter_input(INPUT_POST, 'question_count', FILTER_VALIDATE_INT);
    if ($questionCount === false || $questionCount === null || $questionCount < 1 || $questionCount > 50) {
        $error = 'Choose a question count from 1 to 50.';
    }
    if (!$error) {
        $generatedResult = careerAiGenerateQuestions($category, $questionCount);
        if (!$generatedResult['ok']) $error = $generatedResult['error'];
        else {
            $generated = $generatedResult['questions'];
            $conn->begin_transaction();
            try {
                $q = $conn->prepare('INSERT INTO questions (question_text, category, status) VALUES (?, ?, \'pending\')');
                $o = $conn->prepare('INSERT INTO options (question_id, option_text, score_value, category) VALUES (?, ?, 0, ?)');
                foreach ($generated as $item) {
                    $questionText = trim((string)($item['question'] ?? '')); $choices = $item['options'] ?? [];
                    if ($questionText === '' || !is_array($choices) || count($choices) !== 4) throw new RuntimeException('Invalid generated question.');
                    $q->bind_param('ss', $questionText, $category); $q->execute(); $questionId = $conn->insert_id;
                    foreach ($choices as $choice) { $choice = trim((string)$choice); if ($choice === '') throw new RuntimeException('Empty option.'); $o->bind_param('iss', $questionId, $choice, $category); $o->execute(); }
                }
                $conn->commit(); $message = "{$questionCount} AI questions saved as pending. Edit and approve them in Manage questions.";
            } catch (Throwable $e) { $conn->rollback(); $error = 'The AI response could not be saved. Please generate again.'; }
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Generate Quiz Questions</title><style>body{font:16px Arial;background:#f4f6fb;color:#172033}.card{max-width:700px;margin:40px auto;background:white;padding:28px;border-radius:12px}input,button{padding:10px;margin:8px 0}input{width:100%;box-sizing:border-box}button{cursor:pointer}.notice{padding:12px;background:#eef6ff}</style><link rel="stylesheet" href="../assets/cute-theme.css">
</head><body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a><main class="card admin-generator"><h1>Generate AI questions</h1><p>Generated questions are saved as pending for you to edit and review.</p><?php foreach ([$message,$error] as $n): if($n): ?><p class="notice"><?= h($n) ?></p><?php endif; endforeach; ?><form method="post"><label>Question category <input name="category" maxlength="100" value="<?= h($_POST['category'] ?? 'Career Interest') ?>" required></label><label>Number of questions <input type="number" name="question_count" min="1" max="50" value="<?= h($_POST['question_count'] ?? '5') ?>" required></label><button type="submit" name="generate" value="1">Generate questions</button></form><p><a href="manage_questions.php">Manage questions</a></p></main></body></html>
