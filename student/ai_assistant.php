
<?php
session_start();
require_once "../config/db.php";
require_once "../ai/career_assistant.php";

$response = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $question = trim($_POST["question"] ?? "");

    if ($question === "") {
        $error = "Please enter a question.";
    } else {
        $answer = careerAiAssistantReply($question);
        if ($answer['ok']) $response = $answer['text'];
        else $error = $answer['error'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AI Career Assistant</title>
    <style>
        body {
            font-family: Arial;
            background: #f3f4f6;
            padding: 30px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }

        textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            box-sizing: border-box;
        }

        button {
            background: #20876fcf;
            color: white;
            padding: 12px 25px;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }

        .answer {
            background: #eff6ff;
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
            white-space: pre-wrap;
        }

        .error {
            color: red;
        }
    </style>
<link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
<link rel="stylesheet" href="../assets/glass-surfaces.css?v=20261115-student-home"></head>

<body class="neon-app student-assistant-page">

<a href="dashboard.php" class="home-button assistant-home">⌂ Home</a>

<div class="container">

    <h2>AI Career Assistant</h2>

    <p>Ask anything about your career, skills or learning path.</p>

    <form method="POST">

        <textarea name="question"
        placeholder="Example: Which career is suitable for me?"
        required></textarea>

        <button type="submit">Ask AI</button>

    </form>

    <?php if ($response): ?>
        <div class="answer">
            <h3>AI Response</h3>
            <?= htmlspecialchars($response) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

</div>

</body>
</html>
