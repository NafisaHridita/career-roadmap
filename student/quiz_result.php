
<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

ini_set('max_execution_time', 180);

$answers = $_POST['answers'] ?? [];
$questions = $_SESSION['quiz_questions'] ?? [];

$result = "";
$error = "";

if (empty($answers) || empty($questions)) {
    $error = "Quiz data missing. Please generate a new quiz.";
} else {

    $answerText = "";

    foreach ($questions as $index => $question) {

        $questionText = $question['question'] ?? '';
        $answer = $answers[$index] ?? 'Not answered';

        $answerText .=
            "Question: " . $questionText . "\n" .
            "Student Answer: " . $answer . "\n\n";
    }

    $prompt = "
You are a professional AI career counselor.

Analyze the student's actual quiz questions and answers.

$answerText

IMPORTANT:
- Recommend exactly 5 REAL job professions.
- Never use hobbies or activities as career names.
- Use actual job titles such as Software Developer,
  Web Developer, Data Analyst, Graphic Designer,
  Teacher, Civil Engineer or Digital Marketer.
- Select careers based on the student's answers.
- Explain why each career may fit.

Give these sections:

1. TOP 5 RECOMMENDED CAREERS
2. WHY THESE CAREERS FIT
3. REQUIRED SKILLS
4. SKILL GAP ANALYSIS
5. BEGINNER LEARNING ROADMAP
6. FINAL CAREER ADVICE

Give practical, clear English.
Do not claim that a short quiz determines someone's future.
";

    $data = [
        "model" => "qwen2.5:0.5b",
        "prompt" => $prompt,
        "stream" => false,
        "options" => [
            "temperature" => 0.3,
            "num_predict" => 900
        ]
    ];

    $options = [
        "http" => [
            "header" => "Content-Type: application/json\r\n",
            "method" => "POST",
            "content" => json_encode($data),
            "timeout" => 180
        ]
    ];

    $context = stream_context_create($options);

    $response = @file_get_contents(
        "http://localhost:11434/api/generate",
        false,
        $context
    );

    if ($response === false) {
        $error = "AI connection failed. Please check Ollama.";
    } else {
        $responseData = json_decode($response, true);
        $result = $responseData["response"] ?? "";

        if (empty($result)) {
            $error = "AI could not generate a result.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AI Career Analysis</title>
</head>
<body>

<h1>Your AI Career Analysis</h1>

<?php if ($error): ?>

    <p><?php echo htmlspecialchars($error); ?></p>

<?php else: ?>

    <div>
        <?php echo nl2br(htmlspecialchars($result)); ?>
    </div>

<?php endif; ?>

<br>
<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>
