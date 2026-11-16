<?php
// Load local settings from .env when present. Keep real API keys out of source control.
$careerAiEnvFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (is_file($careerAiEnvFile) && is_readable($careerAiEnvFile)) {
    $careerAiEnv = parse_ini_file($careerAiEnvFile, false, INI_SCANNER_RAW);
    if (is_array($careerAiEnv)) {
        foreach ($careerAiEnv as $careerAiName => $careerAiValue) {
            if (getenv($careerAiName) === false) putenv($careerAiName . '=' . $careerAiValue);
        }
    }
}

if (!defined('CAREER_AI_ENDPOINT')) define('CAREER_AI_ENDPOINT', getenv('CAREER_AI_ENDPOINT') ?: 'https://api.x.ai/v1/responses');
if (!defined('CAREER_AI_MODEL')) define('CAREER_AI_MODEL', getenv('CAREER_AI_MODEL') ?: 'grok-4.7');

function careerAiGenerateText(string $prompt, array $generationOptions = [], int $timeout = 180, array|string|null $responseFormat = null): array
{
    $apiKey = trim((string)getenv('XAI_API_KEY'));
    if ($apiKey === '') {
        return ['ok' => false, 'text' => '', 'error' => 'Grok API key is missing. Add XAI_API_KEY to the project .env file, then try again.'];
    }

    $request = [
        'model' => CAREER_AI_MODEL,
        'input' => $prompt,
        'store' => false,
    ];
    if (isset($generationOptions['temperature'])) $request['temperature'] = min(1.0, max(0.0, (float)$generationOptions['temperature']));
    if (isset($generationOptions['num_predict'])) $request['max_output_tokens'] = max(1, (int)$generationOptions['num_predict']);
    elseif (isset($generationOptions['max_output_tokens'])) $request['max_output_tokens'] = max(1, (int)$generationOptions['max_output_tokens']);

    if ($responseFormat !== null) {
        $schema = is_array($responseFormat) ? $responseFormat : null;
        $request['text'] = ['format' => $schema === null
            ? ['type' => 'json_object']
            : ['type' => 'json_schema', 'name' => 'career_ai_response', 'strict' => true, 'schema' => $schema]];
    }

    $payload = json_encode($request, JSON_UNESCAPED_UNICODE);
    if ($payload === false) return ['ok' => false, 'text' => '', 'error' => 'Could not prepare the Grok API request.'];

    $context = stream_context_create(['http' => [
        'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
        'method' => 'POST',
        'content' => $payload,
        'timeout' => $timeout,
        'ignore_errors' => true,
    ]]);
    @set_time_limit($timeout + 30);
    $raw = @file_get_contents(CAREER_AI_ENDPOINT, false, $context);
    if ($raw === false) return ['ok' => false, 'text' => '', 'error' => "Could not connect to the Grok API within {$timeout} seconds. Check the internet connection and try again."];

    $data = json_decode($raw, true);
    if (!is_array($data)) return ['ok' => false, 'text' => '', 'error' => 'Grok returned an invalid response. Please try again.'];
    if (!empty($data['error'])) {
        $apiMessage = is_array($data['error']) ? (string)($data['error']['message'] ?? 'Request failed.') : (string)$data['error'];
        return ['ok' => false, 'text' => '', 'error' => 'Grok API error: ' . $apiMessage];
    }
    $text = '';
    foreach (($data['output'] ?? []) as $outputItem) {
        if (($outputItem['type'] ?? '') !== 'message') continue;
        foreach (($outputItem['content'] ?? []) as $contentItem) {
            if (($contentItem['type'] ?? '') === 'output_text') $text .= (string)($contentItem['text'] ?? '');
        }
    }
    $text = trim($text);
    if ($text === '') return ['ok' => false, 'text' => '', 'error' => 'Grok returned an empty response. Check the model name and API access, then try again.'];
    return ['ok' => true, 'text' => $text, 'error' => ''];
}
?>
