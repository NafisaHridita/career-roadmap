/* <?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
</head>
<body>

<h1>Welcome to CareerRoadmap!</h1>

<h2>Hello, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>

<p>Explore your interests and discover your career path.</p>

<a href="profile.php">My Profile</a>
<br><br>

<a href="quiz.php">Career Interest Quiz</a>
<br><br>

<a href="../logout.php">Logout</a>

</body>
</html>
*/


<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
</head>
<body>

<h1>Welcome to CareerRoadmap!</h1>

<h2>Hello, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>

<p>Explore your interests and discover your career path.</p>

<a href="profile.php">My Profile</a>
<br><br>

<a href="quiz.php">Career Interest Quiz</a>
<br><br>

<a href="ai_assistant.php">AI Career Assistant</a>
<br><br>

<a href="../logout.php">Logout</a>

</body>
</html>

