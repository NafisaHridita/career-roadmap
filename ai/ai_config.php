<?php
if (!defined('CAREER_AI_ENDPOINT')) define('CAREER_AI_ENDPOINT', 'http://localhost:11434/api/generate');
if (!defined('CAREER_AI_MODEL')) define('CAREER_AI_MODEL', 'qwen2.5:0.5b');

function careerAiGenerateText(string $prompt, array $generationOptions = [], int $timeout = 120): array
{
    $payload = json_encode([
        'model' => CAREER_AI_MODEL,
        'prompt' => $prompt,
        'stream' => false,
        'options' => $generationOptions,
    ], JSON_UNESCAPED_UNICODE);
    if ($payload === false) return ['ok' => false, 'text' => '', 'error' => 'Could not prepare the AI request.'];

    $context = stream_context_create(['http' => [
        'header' => "Content-Type: application/json\r\n",
        'method' => 'POST',
        'content' => $payload,
        'timeout' => $timeout,
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents(CAREER_AI_ENDPOINT, false, $context);
    if ($raw === false) return ['ok' => false, 'text' => '', 'error' => 'AI connection failed. Check that Ollama is running.'];

    $data = json_decode($raw, true);
    $text = is_array($data) ? trim((string)($data['response'] ?? '')) : '';
    if ($text === '') return ['ok' => false, 'text' => '', 'error' => 'AI did not return a response.'];
    return ['ok' => true, 'text' => $text, 'error' => ''];
}
?>
