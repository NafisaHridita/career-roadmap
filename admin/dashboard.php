
<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - CareerRoadmap</title>
    <style>
        body {
            font-family: Arial;
            background: #7317b1;
            margin: 0;
        }

        header {
            background: #7048b7;
            color: white;
            padding: 20px;
        }

        .container {
            padding: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            margin: 15px 0;
            border-radius: 10px;
        }

        a {
            color: #7b75ce5b;
            text-decoration: none;
        }
    </style>
</head>
<body>

<header>
    <h2>CareerRoadmap Admin Panel</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
</header>

<div class="container">

    <h1>Admin Dashboard</h1>

    <div class="card">
        <h3>Student Management</h3>
        <p>Manage registered students.</p>
    </div>

    <div class="card">
        <h3>AI Career Quiz</h3>
        <p>Monitor AI-generated career assessments.</p>
    </div>

    <div class="card">
        <h3>Career Management</h3>
        <p>Manage career categories and required skills.</p>
    </div>

    <div class="card">
        <h3>AI Recommendations</h3>
        <p>Monitor AI-generated recommendations.</p>
    </div>

    <a href="../logout.php">Logout</a>

</div>

</body>
</html>
