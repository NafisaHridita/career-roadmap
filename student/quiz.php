
<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$questions = [];
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

    $prompt = "Create exactly 5 multiple-choice career interest questions.
Each question must have exactly 4 options.
Questions should explore interests in technology, creativity,
problem solving, communication, science and business.

Return ONLY valid JSON:
{
  \"questions\": [
    {
      \"question\": \"Question text\",
      \"options\": [\"Option 1\", \"Option 2\", \"Option 3\", \"Option 4\"]
    }
  ]
}";

    $data = [
        "model" => "qwen2.5:0.5b",
        "prompt" => $prompt,
        "stream" => false,
        "options" => [
            "temperature" => 0.7
        ]
    ];

    $options = [
        "http" => [
            "header" => "Content-Type: application/json\r\n",
            "method" => "POST",
            "content" => json_encode($data),
            "timeout" => 120
        ]
    ];

    $context = stream_context_create($options);

    $result = @file_get_contents(
        "http://localhost:11434/api/generate",
        false,
        $context
    );

    if ($result === false) {
        $error = "AI connection failed. Please check Ollama.";
    } else {
        $resultData = json_decode($result, true);
        $aiResponse = $resultData["response"] ?? "";

        preg_match('/\{.*\}/s', $aiResponse, $matches);

        if (!empty($matches[0])) {
            $decoded = json_decode($matches[0], true);
            $questions = $decoded["questions"] ?? [];
        }

        if (count($questions) != 5) {
            $error = "AI could not generate valid questions. Please try again.";
            $questions = [];
        } else {
            $_SESSION['quiz_questions'] = $questions;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AI Career Quiz</title>
</head>
<body>

<h1>AI Career Interest Quiz</h1>

<p>Let AI generate your career interest questions.</p>

<?php if (empty($questions)): ?>

    <?php if ($error): ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST">
        <button type="submit" name="generate">
            Generate AI Quiz
        </button>
    </form>

<?php else: ?>

    <form action="quiz_result.php" method="POST">

        <?php foreach ($questions as $index => $question): ?>

            <h3>
                <?php echo ($index + 1) . ". " .
                htmlspecialchars($question['question']); ?>
            </h3>

            <?php foreach ($question['options'] as $option): ?>

                <label>
                    <input type="radio"
                           name="answers[<?php echo $index; ?>]"
                           value="<?php echo htmlspecialchars($option); ?>"
                           required>

                    <?php echo htmlspecialchars($option); ?>
                </label>

                <br>

            <?php endforeach; ?>

        <?php endforeach; ?>

        <br>
        <button type="submit">Submit Answers</button>

    </form>

<?php endif; ?>

<br>
<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>
