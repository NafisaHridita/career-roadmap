<?php
if (!defined('CAREER_AI_ENDPOINT')) define('CAREER_AI_ENDPOINT', 'http://127.0.0.1:11434/api/generate');
if (!defined('CAREER_AI_MODEL')) define('CAREER_AI_MODEL', 'qwen2.5:0.5b');

function careerAiGenerateText(string $prompt, array $generationOptions = [], int $timeout = 180, array|string|null $responseFormat = null): array
{
    $request = [
        'model' => CAREER_AI_MODEL,
        'prompt' => $prompt,
        'stream' => false,
        'keep_alive' => '30m',
        'options' => $generationOptions,
    ];
    if ($responseFormat !== null) $request['format'] = $responseFormat;
    $payload = json_encode($request, JSON_UNESCAPED_UNICODE);
    if ($payload === false) return ['ok' => false, 'text' => '', 'error' => 'Could not prepare the AI request.'];

    $context = stream_context_create(['http' => [
        'header' => "Content-Type: application/json\r\n",
        'method' => 'POST',
        'content' => $payload,
        'timeout' => $timeout,
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents(CAREER_AI_ENDPOINT, false, $context);
    if ($raw === false) return ['ok' => false, 'text' => '', 'error' => 'Could not reach local Ollama at 127.0.0.1:11434. Start Ollama and try again.'];

    $data = json_decode($raw, true);
    if (is_array($data) && !empty($data['error'])) {
        return ['ok' => false, 'text' => '', 'error' => 'Ollama error: ' . (string)$data['error']];
    }
    $text = is_array($data) ? trim((string)($data['response'] ?? '')) : '';
    if ($text === '') return ['ok' => false, 'text' => '', 'error' => 'Ollama returned an empty response. Check that the configured model is available, then try again.'];
    return ['ok' => true, 'text' => $text, 'error' => ''];
}
?>
