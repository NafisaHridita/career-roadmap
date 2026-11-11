<?php
require_once __DIR__ . '/ai_config.php';

function careerAiGenerateQuestions(string $category, int $count): array
{
    if ($count < 1 || $count > 50) return ['ok'=>false, 'questions'=>[], 'error'=>'Choose between 1 and 50 questions.'];
    $category = trim($category);
    if ($category === '') $category = 'Career Interest';
    $prompt = "Create exactly {$count} multiple-choice career interest quiz questions for category: {$category}. Each question must have exactly four distinct options. Return only valid JSON: {\"questions\":[{\"question\":\"Question text\",\"options\":[\"A\",\"B\",\"C\",\"D\"]}]}";
    $response = careerAiGenerateText($prompt, ['temperature'=>0.7], 120);
    if (!$response['ok']) return ['ok'=>false, 'questions'=>[], 'error'=>$response['error']];

    $json = $response['text'];
    if (preg_match('/\{.*\}/s', $json, $match)) $json = $match[0];
    $decoded = json_decode($json, true);
    $questions = is_array($decoded) ? ($decoded['questions'] ?? []) : [];
    if (!is_array($questions) || count($questions) !== $count) return ['ok'=>false, 'questions'=>[], 'error'=>"AI did not return exactly {$count} valid questions. Try again."];

    $clean = [];
    foreach ($questions as $item) {
        $text = trim((string)($item['question'] ?? ''));
        $options = $item['options'] ?? null;
        if ($text === '' || !is_array($options) || count($options) !== 4) return ['ok'=>false, 'questions'=>[], 'error'=>'AI returned a question with an invalid option set. Try again.'];
        $options = array_map(static fn($value) => trim((string)$value), array_values($options));
        if (in_array('', $options, true)) return ['ok'=>false, 'questions'=>[], 'error'=>'AI returned an empty option. Try again.'];
        $clean[] = ['question'=>$text, 'options'=>$options];
    }
    return ['ok'=>true, 'questions'=>$clean, 'error'=>''];
}
?>
