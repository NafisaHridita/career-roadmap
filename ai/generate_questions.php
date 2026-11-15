<?php
require_once __DIR__ . '/ai_config.php';

function careerAiGenerateQuestions(string $category, int $count): array
{
    if ($count < 1 || $count > 50) return ['ok'=>false, 'questions'=>[], 'error'=>'Choose between 1 and 50 questions.'];
    $category = trim($category);
    if ($category === '') $category = 'Career Interest';
    $prompt = "Create exactly {$count} distinct career-interest quiz questions for the category '{$category}'. Ask about work activities, strengths, preferred work environments, and problem-solving styles that can help identify career interests. Avoid generic favorites, food, entertainment, and questions about a current job. Keep each question concise and make its answer choices meaningful and relevant. Return only the requested structured data. Do not add explanations, placeholders, or ellipses.";
    // Structured output constrains Ollama to the exact question and option counts.
    $responseFormat = [
        'type' => 'object',
        'properties' => [
            'questions' => [
                'type' => 'array',
                'minItems' => $count,
                'maxItems' => $count,
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'question' => ['type' => 'string', 'minLength' => 1],
                        'options' => [
                            'type' => 'array',
                            'minItems' => 4,
                            'maxItems' => 4,
                            'items' => ['type' => 'string', 'minLength' => 1],
                        ],
                    ],
                    'required' => ['question', 'options'],
                    'additionalProperties' => false,
                ],
            ],
        ],
        'required' => ['questions'],
        'additionalProperties' => false,
    ];
    // Keep enough output room for every question and its four options.
    $tokenLimit = min(8192, 320 + ($count * 125));
    $contextLimit = min(12288, max(3072, 1200 + ($count * 100)));
    $response = careerAiGenerateText($prompt, ['temperature'=>0.15, 'num_ctx'=>$contextLimit, 'num_predict'=>$tokenLimit], 180, $responseFormat);
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
