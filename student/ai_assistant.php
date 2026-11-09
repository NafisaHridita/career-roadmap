
<?php
session_start();
require_once "../config/db.php";

$response = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $question = trim($_POST["question"] ?? "");

    if ($question === "") {
        $error = "Please enter a question.";
    } else {

        $data = [
            "model" => "qwen2.5:0.5b",
            "prompt" => "You are CareerExplorer AI Career Assistant.
Give simple, helpful and accurate career guidance to students.

Student question: " . $question,
            "stream" => false
        ];

       /* $ch = curl_init("http://localhost:11434/api/generate");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = "AI connection error: " . curl_error($ch);
        } else {
            $resultData = json_decode($result, true);
            $response = $resultData["response"] ?? "No response received.";
        }

        curl_close($ch); */

        $options = [
    "http" => [
        "header" => "Content-Type: application/json\r\n",
        "method" => "POST",
        "content" => json_encode($data),
        "timeout" => 120
    ]
];

$context = stream_context_create($options);

$result = file_get_contents(
    "http://localhost:11434/api/generate",
    false,
    $context
);

if ($result === false) {
    $error = "AI connection failed. Check Ollama.";
} else {
    $resultData = json_decode($result, true);
    $response = $resultData["response"] ?? "No response received.";
}
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
</head>

<body>

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
