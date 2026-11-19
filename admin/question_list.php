<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../config/db.php';
require_once 'question_helpers.php';

if (empty($_SESSION['question_list_csrf'])) $_SESSION['question_list_csrf'] = bin2hex(random_bytes(32));
$csrfToken = (string)$_SESSION['question_list_csrf'];
$error = '';
$message = isset($_GET['approved']) ? 'Question approved and moved to this list.' : '';
$listReady = ensureQuestionStatusColumn($conn) && ensureOptionLabelColumn($conn);
if (!$listReady) {
    $error = 'Could not prepare the question list. Check database permissions.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['delete_selected']) || isset($_POST['delete_question'])) && !$error) {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Session expired. Reload the page and try again.';
    } else {
        $rawIds = isset($_POST['delete_selected'])
            ? (array)($_POST['question_ids'] ?? [])
            : [(string)($_POST['question_id'] ?? '')];
        $ids = [];
        foreach ($rawIds as $rawId) {
            $id = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id !== false) $ids[] = (int)$id;
        }
        $ids = array_values(array_unique($ids));

        if (!$ids) {
            $error = 'Select at least one approved question to delete.';
        } else {
            $conn->begin_transaction();
            try {
                $check = $conn->prepare("SELECT question_id FROM questions WHERE question_id=? AND status='approved' FOR UPDATE");
                $deleteOptions = $conn->prepare('DELETE FROM options WHERE question_id=?');
                $deleteQuestion = $conn->prepare("DELETE FROM questions WHERE question_id=? AND status='approved'");
                if (!$check || !$deleteOptions || !$deleteQuestion) throw new RuntimeException('Could not prepare deletion.');

                foreach ($ids as $id) {
                    $check->bind_param('i', $id);
                    $check->execute();
                    $check->store_result();
                    $isApproved = $check->num_rows === 1;
                    $check->free_result();
                    if (!$isApproved) throw new RuntimeException('One or more questions are no longer approved.');

                    $deleteOptions->bind_param('i', $id);
                    $deleteOptions->execute();
                    $deleteQuestion->bind_param('i', $id);
                    $deleteQuestion->execute();
                    if ($deleteQuestion->affected_rows !== 1) throw new RuntimeException('A question could not be deleted.');
                }

                $check->close();
                $deleteOptions->close();
                $deleteQuestion->close();
                $conn->commit();
                header('Location: question_list.php?deleted_count=' . count($ids));
                exit();
            } catch (Throwable $e) {
                $conn->rollback();
                if (isset($check) && $check instanceof mysqli_stmt) $check->close();
                if (isset($deleteOptions) && $deleteOptions instanceof mysqli_stmt) $deleteOptions->close();
                if (isset($deleteQuestion) && $deleteQuestion instanceof mysqli_stmt) $deleteQuestion->close();
                $error = 'Could not delete the selected approved questions. Please refresh the list and try again.';
            }
        }
    }
}

if (isset($_GET['deleted_count'])) {
    $deletedCount = filter_var($_GET['deleted_count'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($deletedCount) $message = $deletedCount === 1 ? 'Approved question deleted.' : "{$deletedCount} approved questions deleted.";
}
$questions = !$listReady ? false : $conn->query("SELECT q.question_id,q.question_text,q.category,COUNT(o.option_id) AS option_count FROM questions q LEFT JOIN options o ON o.question_id=q.question_id WHERE q.status='approved' GROUP BY q.question_id ORDER BY q.question_id DESC");
function questionListEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Approved Question List</title>
    <link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
    <style>
        body{margin:0;padding:70px 20px 30px;font:16px Inter,"Segoe UI",Arial,sans-serif}.list-wrap{max-width:1120px;margin:20px auto;padding:30px;border-radius:24px}.list-header{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap}.list-header p{margin-top:8px}.list-actions{display:flex;gap:10px;flex-wrap:wrap}.list-actions a{display:inline-flex;padding:10px 14px;border:1px solid #34546c;border-radius:12px;background:#112438;color:#87f4e4;text-decoration:none;font-weight:700}.list-wrap table{width:100%;border-collapse:collapse;margin-top:20px}.list-wrap th,.list-wrap td{padding:14px 12px;text-align:left;border-bottom:1px solid #30415e}.list-wrap th{color:#9bf3e6}.list-wrap form{margin:0}.list-wrap .empty{text-align:center;color:#aebbd2;padding:30px}.list-wrap button{background:linear-gradient(110deg,#a52c59,#762b72);color:#fff}.list-notice{padding:13px 16px;margin:18px 0;border:1px solid #327770;border-radius:12px;background:#102b32;color:#c7fff5}.bulk-bar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:22px;padding:14px 16px;border:1px solid #344363;border-radius:14px;background:#111d32}.bulk-bar p{margin:0;color:#aebbd2}.bulk-bar strong{color:#e6f2ff}.bulk-bar button:disabled{opacity:.45;cursor:not-allowed}.select-cell{width:48px}.select-cell input{width:18px;height:18px;accent-color:#75e8d8;cursor:pointer}.bulk-form{margin-top:0!important}.bulk-form table{margin-top:12px}@media(max-width:650px){.list-wrap{padding:21px 16px;overflow-x:auto}.bulk-form table{min-width:630px}.bulk-bar{min-width:630px}}
    </style>
</head>
<body class="neon-app">
<a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a>
<a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:10px;text-decoration:none;z-index:10">Logout</a>
<main class="list-wrap">
    <header class="list-header"><div><h1>Approved Question List</h1><p>Select several questions and remove them together. Only approved questions appear here.</p></div><nav class="list-actions"><a href="manage_questions.php">Review questions</a><a href="add_question.php">Generate questions</a></nav></header>
    <?php if ($message): ?><p class="list-notice" role="status"><?= questionListEscape($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error" role="alert"><?= questionListEscape($error) ?></p><?php endif; ?>
    <?php if (!$questions || $questions->num_rows === 0): ?>
        <table><tbody><tr><td class="empty">No approved questions yet. Approve questions in Review questions to add them here.</td></tr></tbody></table>
    <?php else: ?>
    <form class="bulk-form" id="bulkDeleteForm" method="post">
        <input type="hidden" name="csrf_token" value="<?= questionListEscape($csrfToken) ?>">
        <table><thead><tr><th class="select-cell"><input type="checkbox" id="selectAll" aria-label="Select all approved questions"></th><th>Question</th><th>Category</th><th>Options</th></tr></thead><tbody>
        <?php while ($question = $questions->fetch_assoc()): ?>
            <tr><td class="select-cell"><input class="question-select" type="checkbox" name="question_ids[]" value="<?= (int)$question['question_id'] ?>" aria-label="Select question <?= (int)$question['question_id'] ?>"></td><td><?= questionListEscape($question['question_text']) ?></td><td><?= questionListEscape($question['category'] ?? '') ?></td><td><?= (int)$question['option_count'] ?></td></tr>
        <?php endwhile; ?>
        </tbody></table>
        <div class="bulk-bar"><p><strong id="selectedCount">0</strong> questions selected</p><button type="submit" name="delete_selected" value="1" id="deleteSelected" disabled>Delete selected</button></div>
    </form>
    <?php endif; ?>
</main>
<script>
const selectAll=document.getElementById('selectAll');
const selectedQuestions=[...document.querySelectorAll('.question-select')];
const selectedCount=document.getElementById('selectedCount');
const deleteButton=document.getElementById('deleteSelected');
function updateSelection(){const count=selectedQuestions.filter(box=>box.checked).length;if(selectedCount)selectedCount.textContent=String(count);if(deleteButton)deleteButton.disabled=count===0;if(selectAll){selectAll.checked=count>0&&count===selectedQuestions.length;selectAll.indeterminate=count>0&&count<selectedQuestions.length}}
if(selectAll)selectAll.addEventListener('change',()=>{selectedQuestions.forEach(box=>box.checked=selectAll.checked);updateSelection()});
selectedQuestions.forEach(box=>box.addEventListener('change',updateSelection));
document.getElementById('bulkDeleteForm')?.addEventListener('submit',event=>{const count=selectedQuestions.filter(box=>box.checked).length;if(!count){event.preventDefault();return}if(!confirm(`Delete ${count} selected approved question${count===1?'':'s'} and their answer options?`))event.preventDefault()});
</script>
<script src="../assets/page-navigation.js" defer></script></body>
</html>
