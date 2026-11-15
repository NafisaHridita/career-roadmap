<?php
if (!defined('CAREER_AI_ENDPOINT')) define('CAREER_AI_ENDPOINT', 'http://127.0.0.1:11434/api/generate');
// Override the model without editing code: set CAREER_AI_MODEL in the environment (e.g. qwen2.5:0.5b).
if (!defined('CAREER_AI_MODEL')) define('CAREER_AI_MODEL', getenv('CAREER_AI_MODEL') ?: 'llama3.2');

function careerAiGenerateText(string $prompt, array $generationOptions = [], int $timeout = 180, array|string|null $responseFormat = null): array
{
    $request = [
        'model' => CAREER_AI_MODEL,
        'prompt' => $prompt,
        'stream' => false,
        'keep_alive' => '30m',
        // Cast to object so empty options encode as {} (Ollama rejects a JSON array here).
        'options' => (object) $generationOptions,
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
    // Give PHP enough time to wait for the model, so a slow reply ends with a message, not a fatal timeout.
    @set_time_limit($timeout + 30);
    $raw = @file_get_contents(CAREER_AI_ENDPOINT, false, $context);
    if ($raw === false) return ['ok' => false, 'text' => '', 'error' => "Could not get a reply from local Ollama at 127.0.0.1:11434 within {$timeout} seconds. Make sure Ollama is running, then try again."];

    $data = json_decode($raw, true);
    if (is_array($data) && !empty($data['error'])) {
        return ['ok' => false, 'text' => '', 'error' => 'Ollama error: ' . (string)$data['error']];
    }
    $text = is_array($data) ? trim((string)($data['response'] ?? '')) : '';
    if ($text === '') return ['ok' => false, 'text' => '', 'error' => 'Ollama returned an empty response. Check that the configured model is available, then try again.'];
    return ['ok' => true, 'text' => $text, 'error' => ''];
}
?>
