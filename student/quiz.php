<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php';

$error = '';
$grouped = [];
$check = $conn->query("SHOW COLUMNS FROM questions LIKE 'status'");
if (!$check || $check->num_rows === 0) {
    $error = 'Quiz questions are not configured yet. Please contact the administrator.';
} else {
    $labelCheck = $conn->query("SHOW COLUMNS FROM options LIKE 'option_label'");
    if (!$labelCheck || $labelCheck->num_rows === 0) $error = 'Quiz options are not configured yet. Please contact the administrator.';
    $sql = "SELECT q.question_id,q.question_text,o.option_label,o.option_text FROM questions q JOIN options o ON o.question_id=q.question_id WHERE q.status='approved' ORDER BY q.question_id,o.option_id";
    $result = $error ? false : $conn->query($sql);
    if ($result) while ($row = $result->fetch_assoc()) {
        $qid = (int)$row['question_id'];
        if (!isset($grouped[$qid])) $grouped[$qid] = ['question'=>$row['question_text'],'options'=>[]];
        $grouped[$qid]['options'][] = ['label'=>$row['option_label'] ?? '', 'text'=>$row['option_text']];
    }
    $grouped = array_values(array_filter($grouped, fn($q) => count($q['options']) >= 2));
    if (count($grouped) > 0) {
        $freshQuiz = !isset($_GET['step']) && $_SERVER['REQUEST_METHOD'] !== 'POST';
        if ($freshQuiz || empty($_SESSION['quiz_questions'])) {
            $_SESSION['quiz_questions'] = $grouped;
            $_SESSION['quiz_answers'] = [];
        } else {
            // Keep the quiz snapshot stable while the student moves between steps.
            $grouped = $_SESSION['quiz_questions'];
        }
    } else $error = 'No approved quiz questions are available yet. Please check back later.';
}

$total = count($grouped);
$step = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT);
if ($step === false || $step === null) $step = 0;
$step = max(0, min($step, max(0, $total - 1)));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $postedStep = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);
    if ($postedStep !== false && $postedStep !== null && isset($_POST['answers'][$postedStep])) {
        $_SESSION['quiz_answers'][$postedStep] = (string)$_POST['answers'][$postedStep];
    }
    $direction = $_POST['direction'] ?? 'next';
    if ($direction === 'back') {
        header('Location: quiz.php?step=' . max(0, $step - 1));
        exit();
    }
    header('Location: quiz.php?step=' . min($total - 1, $step + 1));
    exit();
}

$current = $grouped[$step] ?? null;
$savedAnswer = $_SESSION['quiz_answers'][$step] ?? '';
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Career Interest Quiz</title>
    <link rel="stylesheet" href="../assets/cute-theme.css">
    <style>
        body{margin:0;min-height:100vh;padding:32px 18px;font-family:Inter,"Segoe UI",Arial,sans-serif}
        .quiz-shell{width:min(760px,100%);margin:20px auto;padding:clamp(22px,5vw,42px);border:1px solid #344363;border-radius:26px;background:linear-gradient(145deg,rgba(18,25,45,.97),rgba(14,21,38,.97));box-shadow:0 20px 70px #02061188,0 0 32px #7761ff20;animation:neonRise .35s ease both}
        .quiz-top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:28px}.quiz-kicker{margin:0;color:#83f2e3;font-size:.82rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.quiz-top h1{margin:8px 0 0;font-size:clamp(1.5rem,4vw,2rem)}
        .step-count{white-space:nowrap;padding:9px 13px;border:1px solid #36536b;border-radius:999px;background:#102536;color:#a6e9ff;font-weight:750}
        .progress-track{height:8px;margin:0 0 30px;overflow:hidden;border-radius:999px;background:#202a43}.progress-fill{height:100%;border-radius:inherit;background:linear-gradient(90deg,#57e9d7,#9a7cff);box-shadow:0 0 16px #69e8dd77;transition:width .35s ease}
        .question-card{padding:clamp(20px,4vw,30px);border:1px solid #344363;border-radius:20px;background:linear-gradient(145deg,#141e34,#111a2e)}.question-card h2{margin:0 0 22px;font-size:clamp(1.2rem,3vw,1.55rem);line-height:1.5}
        .choice-list{display:grid;gap:12px}.choice{position:relative;display:flex;align-items:center;gap:13px;min-height:56px;padding:12px 16px;border:1px solid #344363;border-radius:15px;background:#0d1629;color:#dfe7ff;cursor:pointer;transition:border-color .18s ease,background .18s ease,transform .18s ease,box-shadow .18s ease}.choice:hover{transform:translateY(-1px);border-color:#67dcd6;background:#102335}.choice:has(input:checked){border-color:#74f0e1;background:linear-gradient(100deg,#12312f,#1d2440);box-shadow:0 0 18px #5ce7d522}.choice input{accent-color:#6de8db;width:18px;height:18px;flex:0 0 auto}.choice-label{display:grid;place-items:center;min-width:32px;height:32px;border:1px solid #39546d;border-radius:10px;color:#8ff4e6;font-weight:800}
        .quiz-actions{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:24px}.quiz-actions button{min-height:46px;padding:11px 20px;border-radius:14px}.quiz-actions .back-step{border:1px solid #3a496a;background:#18233a;color:#dbe5ff;box-shadow:none}.quiz-actions .back-step:disabled{opacity:.45;cursor:not-allowed;transform:none}.error{padding:14px;border:1px solid #713957;border-radius:12px;background:#351b2c;color:#ffc1d0}.home-row{display:flex;justify-content:center;margin-top:20px}
        @media(max-width:520px){body{padding:14px}.quiz-shell{margin:8px auto}.quiz-top{align-items:flex-start}.quiz-actions button{padding:10px 13px}.choice{padding:10px 12px}}
    </style>
</head>
<body class="neon-app">
<main class="quiz-shell">
    <header class="quiz-top"><div><p class="quiz-kicker">Career discovery · Quiz</p><h1>Your interests, one step at a time</h1></div><?php if (!$error): ?><span class="step-count"><?= $step + 1 ?> / <?= $total ?></span><?php endif; ?></header>
    <?php if ($error): ?><p class="error"><?= $escape($error) ?></p><?php elseif ($current): ?>
        <div class="progress-track" aria-label="Quiz progress"><div class="progress-fill" style="width:<?= (int)round((($step + 1) / $total) * 100) ?>%"></div></div>
        <form method="post" action="<?= $step === $total - 1 ? 'quiz_result.php' : 'quiz.php?step=' . $step ?>">
            <input type="hidden" name="step" value="<?= $step ?>">
            <?php if ($step === $total - 1): foreach ($_SESSION['quiz_answers'] ?? [] as $answerIndex => $answerValue): if ((int)$answerIndex !== $step): ?><input type="hidden" name="answers[<?= (int)$answerIndex ?>]" value="<?= $escape($answerValue) ?>"><?php endif; endforeach; endif; ?>
            <section class="question-card" aria-labelledby="question-title">
                <h2 id="question-title"><?= $step + 1 ?>. <?= $escape($current['question']) ?></h2>
                <div class="choice-list">
                    <?php foreach ($current['options'] as $optionIndex => $option): $value = (string)$option['text']; ?>
                        <label class="choice"><input type="radio" name="answers[<?= $step ?>]" value="<?= $escape($value) ?>" required <?= $savedAnswer === $value ? 'checked' : '' ?>><?php if ($option['label'] !== ''): ?><span class="choice-label"><?= $escape($option['label']) ?></span><?php else: ?><span class="choice-label"><?= chr(65 + $optionIndex) ?></span><?php endif; ?><span><?= $escape($value) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </section>
            <div class="quiz-actions">
                <?php if ($step > 0): ?><button class="back-step" type="submit" name="direction" value="back" formnovalidate>← Back</button><?php else: ?><button class="back-step" type="button" disabled>← Back</button><?php endif; ?>
                <?php if ($step === $total - 1): ?><button type="submit">Finish quiz →</button><?php else: ?><button type="submit" name="direction" value="next">Next question →</button><?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</main>
<div class="home-row"><a href="dashboard.php" class="home-button">⌂ Home</a></div>
</body>
</html>
