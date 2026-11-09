
<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT name, email FROM users WHERE user_id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
</head>
<body>

<h1>My Profile</h1>

<p><strong>Name:</strong>
<?php echo htmlspecialchars($user['name']); ?></p>

<p><strong>Email:</strong>
<?php echo htmlspecialchars($user['email']); ?></p>

<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>
