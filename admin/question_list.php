<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php';
require_once 'question_helpers.php';
$error = '';
$message = isset($_GET['approved']) ? 'Question approved and moved to this list.' : '';
if (!ensureQuestionStatusColumn($conn) || !ensureOptionLabelColumn($conn)) $error = 'Could not prepare the question list. Check database permissions.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_question']) && !$error) {
    $id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
    if (!$id) $error = 'Question ID is missing.';
    else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM options WHERE question_id=?");
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare("DELETE FROM questions WHERE question_id=? AND status='approved'");
            $stmt->bind_param('i', $id); $stmt->execute();
            $deleted = $stmt->affected_rows; $stmt->close();
            if ($deleted !== 1) throw new RuntimeException('Question is no longer in the approved list.');
            $conn->commit();
            header('Location: question_list.php?deleted=1'); exit();
        } catch (Throwable $e) {
            $conn->rollback();
            $error = 'Could not delete this approved question.';
        }
    }
}
if (isset($_GET['deleted'])) $message = 'Approved question deleted.';
$questions = $error ? false : $conn->query("SELECT q.question_id,q.question_text,q.category,COUNT(o.option_id) AS option_count FROM questions q LEFT JOIN options o ON o.question_id=q.question_id WHERE q.status='approved' GROUP BY q.question_id ORDER BY q.question_id DESC");
function questionListEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Approved Question List</title><link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3"><style>body{margin:0;padding:70px 20px 30px;font:16px Inter,"Segoe UI",Arial,sans-serif}.list-wrap{max-width:1120px;margin:20px auto;padding:30px;border-radius:24px}.list-header{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap}.list-header p{margin-top:8px}.list-actions{display:flex;gap:10px;flex-wrap:wrap}.list-actions a{display:inline-flex;padding:10px 14px;border:1px solid #34546c;border-radius:12px;background:#112438;color:#87f4e4;text-decoration:none;font-weight:700}.list-wrap table{width:100%;border-collapse:collapse;margin-top:24px}.list-wrap th,.list-wrap td{padding:14px 12px;text-align:left;border-bottom:1px solid #30415e}.list-wrap th{color:#9bf3e6}.list-wrap td:last-child{width:120px}.list-wrap form{margin:0}.list-wrap .empty{text-align:center;color:#aebbd2;padding:30px}.list-wrap button{background:linear-gradient(110deg,#a52c59,#762b72);color:#fff}.list-notice{padding:13px 16px;margin:18px 0;border:1px solid #327770;border-radius:12px;background:#102b32;color:#c7fff5}@media(max-width:650px){.list-wrap{padding:21px 16px;overflow-x:auto}.list-wrap table{min-width:600px}}</style></head>
<body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:10px;text-decoration:none;z-index:10">Logout</a><main class="list-wrap"><header class="list-header"><div><h1>Approved Question List</h1><p>These are the questions students can see. Each question can only be deleted from this list.</p></div><nav class="list-actions"><a href="manage_questions.php">Review questions</a><a href="add_question.php">Generate questions</a></nav></header>
<?php if ($message): ?><p class="list-notice" role="status"><?= questionListEscape($message) ?></p><?php endif; ?><?php if ($error): ?><p class="error"><?= questionListEscape($error) ?></p><?php endif; ?>
<table><thead><tr><th>Question</th><th>Category</th><th>Options</th><th>Action</th></tr></thead><tbody><?php if (!$questions || $questions->num_rows===0): ?><tr><td class="empty" colspan="4">No approved questions yet. Approve questions in Review questions to add them here.</td></tr><?php else: while($question=$questions->fetch_assoc()): ?><tr><td><?=questionListEscape($question['question_text'])?></td><td><?=questionListEscape($question['category']??'')?></td><td><?= (int)$question['option_count'] ?></td><td><form method="post" onsubmit="return confirm('Delete this approved question? Students will no longer see it.')"><input type="hidden" name="question_id" value="<?= (int)$question['question_id'] ?>"><button type="submit" name="delete_question" value="1">Delete</button></form></td></tr><?php endwhile; endif; ?></tbody></table></main></body></html>
