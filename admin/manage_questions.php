<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php'); exit();
}
require_once '../config/db.php';
require_once 'question_helpers.php';
$message = '';
if (!ensureQuestionStatusColumn($conn) || !ensureOptionLabelColumn($conn)) {
    $message = 'Could not prepare question review status. Check database permissions.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['question_id'])) {
    $id = (int) $_POST['question_id'];
    if ($_POST['action'] === 'delete') {
        $conn->begin_transaction();
        $stmt = $conn->prepare('DELETE FROM options WHERE question_id = ?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        $stmt = $conn->prepare('DELETE FROM questions WHERE question_id = ?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        $conn->commit(); $message = 'Question deleted.';
    } elseif (in_array($_POST['action'], ['approved', 'rejected'], true)) {
        $status = $_POST['action'];
        $stmt = $conn->prepare('UPDATE questions SET status = ? WHERE question_id = ?');
        $stmt->bind_param('si', $status, $id); $stmt->execute(); $stmt->close();
        header('Location: manage_questions.php?status=' . ($status === 'approved' ? 'all&approved=1' : 'rejected&updated=1'));
        exit();
    }
}
$filter = $_GET['status'] ?? 'all';
$allowed = ['all', 'pending', 'rejected'];
if (!in_array($filter, $allowed, true)) $filter = 'all';
$sql = 'SELECT q.question_id, q.question_text, q.category, q.status, COUNT(o.option_id) option_count FROM questions q LEFT JOIN options o ON o.question_id=q.question_id';
if ($filter === 'all') $sql .= " WHERE q.status <> 'approved'";
else $sql .= ' WHERE q.status = ?';
$sql .= ' GROUP BY q.question_id ORDER BY q.question_id DESC';
$stmt = $conn->prepare($sql);
if ($filter !== 'all') $stmt->bind_param('s', $filter);
$stmt->execute(); $questions = $stmt->get_result();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Manage Quiz Questions</title>
<style>body{font:16px Arial,sans-serif;background:#f4f6fb;margin:0;color:#172033}.wrap{max-width:1050px;margin:35px auto;padding:24px;background:#fff;border-radius:12px}header,.row{display:flex;justify-content:space-between;align-items:center;gap:12px}a,button{color:#215caa}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{text-align:left;border-bottom:1px solid #ddd;padding:12px}form.inline{display:inline}.badge{font-weight:bold}button{padding:7px 10px;cursor:pointer}.notice{background:#eef6ff;padding:12px}</style><link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
<link rel="stylesheet" href="../assets/glass-surfaces.css?v=20261115-2218">
</head><body class="neon-app"><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a><a href="../logout.php" style="position:fixed;top:18px;right:22px;background:#5633a3;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;z-index:10">Logout</a><main class="wrap admin-questions">
<header><div><h1>Quiz question review</h1><p>Review pending and rejected questions. Approved questions move to Question List.</p></div><nav><a href="add_question.php">Generate questions</a><a href="question_list.php">Question List</a></nav></header>
<?php if ($message || isset($_GET['updated']) || isset($_GET['approved'])): ?><p class="notice"><?= h($message ?: (isset($_GET['approved']) ? 'Question approved and moved to Question List.' : 'Question review status updated.')) ?></p><?php endif; ?>
<p>Show: <?php foreach ($allowed as $s): ?><a href="?status=<?= h($s) ?>"><?= ucfirst($s) ?></a> &nbsp;<?php endforeach; ?></p>
<table><thead><tr><th>Question</th><th>Category</th><th>Options</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if ($questions->num_rows === 0): ?><tr><td colspan="5">No questions need review.</td></tr><?php else: while ($q = $questions->fetch_assoc()): ?><tr><td><?= h($q['question_text']) ?></td><td><?= h($q['category'] ?? '') ?></td><td><?= (int)$q['option_count'] ?></td><td class="badge"><?= h(ucfirst($q['status'])) ?></td><td class="actions-cell"><div class="question-actions"><a class="action-edit" href="edit_question.php?id=<?= (int)$q['question_id'] ?>">Edit</a>
<form class="inline" method="post"><input type="hidden" name="question_id" value="<?= (int)$q['question_id'] ?>"><button name="action" value="approved">Approve</button><button name="action" value="rejected">Reject</button><button name="action" value="delete" onclick="return confirm('Delete this question and its options?')">Delete</button></form></div></td></tr><?php endwhile; endif; ?>
</tbody></table></main></body></html>
