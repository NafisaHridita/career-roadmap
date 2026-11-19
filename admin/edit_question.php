<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once 'question_helpers.php';
ensureQuestionStatusColumn($conn); ensureOptionLabelColumn($conn);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
$error = ''; $message = '';
if (!$id) { http_response_code(400); exit('Question ID is required.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['question_text'] ?? ''); $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'pending'; $optionTexts = $_POST['options'] ?? []; $labels = $_POST['option_labels'] ?? [];
    $items = [];
    foreach (is_array($optionTexts) ? $optionTexts : [] as $i => $optionText) {
        $optionText = trim($optionText);
        if ($optionText !== '') $items[] = ['text'=>$optionText, 'label'=>trim((string)($labels[$i] ?? ''))];
    }
    if ($text === '' || count($items) < 2 || !in_array($status, ['pending','approved','rejected'], true)) {
        $error = 'Enter a question and at least two non-empty options.';
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('UPDATE questions SET question_text=?, category=?, status=? WHERE question_id=?');
            $stmt->bind_param('sssi', $text, $category, $status, $id); $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare('DELETE FROM options WHERE question_id=?'); $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare('INSERT INTO options (question_id, option_label, option_text, score_value, category) VALUES (?, ?, ?, 0, ?)');
            foreach ($items as $item) { $label = $item['label']; $optionText = $item['text']; $stmt->bind_param('isss', $id, $label, $optionText, $category); $stmt->execute(); }
            $stmt->close(); $conn->commit();
            if ($status === 'approved') { header('Location: question_list.php?approved=1'); exit(); }
            header('Location: manage_questions.php?updated=1'); exit();
        } catch (Throwable $e) { $conn->rollback(); $error = 'Could not save this question. Check the database schema.'; }
    }
}
$stmt = $conn->prepare('SELECT question_text, category, status FROM questions WHERE question_id=?'); $stmt->bind_param('i', $id); $stmt->execute(); $question = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$question) { http_response_code(404); exit('Question not found.'); }
$stmt = $conn->prepare('SELECT option_label, option_text FROM options WHERE question_id=? ORDER BY option_id'); $stmt->bind_param('i', $id); $stmt->execute(); $options = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Edit Quiz Question</title><style>body{font:16px Arial;background:#f4f6fb;color:#172033}.card{max-width:720px;margin:40px auto;background:white;padding:28px;border-radius:12px}label{display:block;margin-top:16px}input,textarea,select{width:100%;padding:10px;margin-top:6px;box-sizing:border-box}textarea{min-height:100px}button{margin-top:20px;padding:10px 18px}.notice{padding:10px;background:#eef6ff}</style><link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
<link rel="stylesheet" href="../assets/glass-surfaces.css?v=20261115-2218">
</head><body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a><main class="card admin-question-edit"><h1>Edit question</h1>
<?php foreach ([$message,$error] as $notice): if ($notice): ?><p class="notice"><?= h($notice) ?></p><?php endif; endforeach; ?>
<form method="post"><input type="hidden" name="question_id" value="<?= (int)$id ?>"><label>Question<textarea name="question_text" required><?= h($question['question_text']) ?></textarea></label><label>Category<input name="category" maxlength="100" value="<?= h($question['category'] ?? '') ?>"></label>
<?php foreach ($options as $i=>$option): ?><fieldset><legend>Choice <?= $i+1 ?></legend><label>Option label (for example A, 1, or First)<input name="option_labels[]" maxlength="50" value="<?= h($option['option_label'] ?? '') ?>"></label><label>Option text<input name="options[]" maxlength="255" value="<?= h($option['option_text']) ?>"></label></fieldset><?php endforeach; ?>
<?php for ($i=count($options); $i<4; $i++): ?><fieldset><legend>Choice <?= $i+1 ?></legend><label>Option label<input name="option_labels[]" maxlength="50" placeholder="A, 1, or First"></label><label>Option text<input name="options[]" maxlength="255"></label></fieldset><?php endfor; ?>
<label>Review status<select name="status"><?php foreach (['pending','approved','rejected'] as $s): ?><option value="<?= $s ?>" <?= $question['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></label><button type="submit">Save changes</button> <a href="manage_questions.php">Back to questions</a></form></main><script src="../assets/page-navigation.js" defer></script></body></html>
