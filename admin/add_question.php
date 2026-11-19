<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once 'question_helpers.php'; require_once '../ai/generate_questions.php';
$error = ''; $message = '';
if (!ensureQuestionStatusColumn($conn) || !ensureOptionLabelColumn($conn)) $error = 'Could not prepare question review columns. Check database permissions.';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate']) && !$error) {
    // Local CPU models can take longer than PHP's default 30-second request limit.
    @set_time_limit(300);
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
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('CareerRoadmap question generation save failed: ' . $e->getMessage());
                $error = 'Questions were generated but could not be saved. Check the questions/options database tables and try again.';
            }
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Generate Quiz Questions</title><style>body{font:16px Arial;background:#f4f6fb;color:#172033}.card{max-width:700px;margin:40px auto;background:white;padding:28px;border-radius:12px}input,button{padding:10px;margin:8px 0}input{width:100%;box-sizing:border-box}button{cursor:pointer}.notice{padding:12px;background:#eef6ff}</style><link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3"><style>.generate-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.question-list-button{display:inline-flex;align-items:center;min-height:42px;padding:0 15px;border:1px solid #3e6681;border-radius:9px;background:#13263a;color:#91f5e7;text-decoration:none;font-weight:700;transition:.2s}.question-list-button:hover{transform:translateY(-2px);box-shadow:0 0 18px #69eadd38}.button-spinner{display:none;width:15px;height:15px;margin-right:8px;vertical-align:-2px;border:2px solid #10253b55;border-top-color:#10253b;border-radius:50%;animation:generate-spin .7s linear infinite}.is-loading .button-spinner{display:inline-block}.generate-status{color:#91f4e5;font-size:14px}.is-loading button[type=submit]{opacity:.78;cursor:wait}@keyframes generate-spin{to{transform:rotate(360deg)}}</style>
<link rel="stylesheet" href="../assets/glass-surfaces.css?v=20261115-2218"></head><body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a><main class="card admin-generator"><h1>Generate AI questions</h1><p>Generated questions are saved as pending for you to edit and review.</p><?php foreach ([$message,$error] as $n): if($n): ?><p class="notice"><?= h($n) ?></p><?php endif; endforeach; ?><form id="generateForm" method="post"><input type="hidden" name="generate" value="1"><label>Question category <input name="category" maxlength="100" value="<?= h($_POST['category'] ?? 'Career Interest') ?>" required></label><label>Number of questions <input type="number" name="question_count" min="1" max="50" value="<?= h($_POST['question_count'] ?? '5') ?>" required></label><div class="generate-row"><button id="generateButton" type="submit"><span class="button-spinner" aria-hidden="true"></span><span id="generateButtonText">Generate questions</span></button><a href="question_list.php" class="question-list-button">Question list</a><span id="generateStatus" class="generate-status" role="status" aria-live="polite"></span></div></form><p><a href="manage_questions.php">Review pending questions</a></p></main><script>document.getElementById('generateForm').addEventListener('submit',function(){const button=document.getElementById('generateButton');button.classList.add('is-loading');button.disabled=true;document.getElementById('generateButtonText').textContent='Generating…';document.getElementById('generateStatus').textContent='The local AI is creating concise questions. Please keep this page open.';});</script><script src="../assets/page-navigation.js" defer></script></body></html>
