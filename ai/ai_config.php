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
// [ashasuborna] Provider switch for the free local Ollama option (#34)
// "grok" (default) uses the xAI API. "ollama" uses a free local Ollama model instead, no API key needed.
if (!defined('CAREER_AI_PROVIDER')) define('CAREER_AI_PROVIDER', strtolower(trim((string)(getenv('CAREER_AI_PROVIDER') ?: 'grok'))));

function careerAiGenerateText(string $prompt, array $generationOptions = [], int $timeout = 180, array|string|null $responseFormat = null): array
{
    if (CAREER_AI_PROVIDER === 'ollama') return careerAiOllamaText($prompt, $generationOptions, $timeout, $responseFormat);

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

// [ashasuborna] Ollama provider: llama3.2 model, {} options fix, timeout and friendly error (#31-#34)
// Free local option: set CAREER_AI_PROVIDER=ollama in .env and run Ollama with the model pulled (ollama pull llama3.2).
function careerAiOllamaText(string $prompt, array $generationOptions, int $timeout, array|string|null $responseFormat): array
{
    $endpoint = getenv('OLLAMA_ENDPOINT') ?: 'http://127.0.0.1:11434/api/generate';
    $model = getenv('OLLAMA_MODEL') ?: 'llama3.2';
    // A local CPU model is much slower than the hosted API, so allow at least 10 minutes.
    $timeout = max($timeout, 600);

    $options = [];
    if (isset($generationOptions['temperature'])) $options['temperature'] = (float)$generationOptions['temperature'];
    if (isset($generationOptions['num_predict'])) $options['num_predict'] = (int)$generationOptions['num_predict'];
    $request = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'keep_alive' => '30m',
        // Cast to object so empty options encode as {} (Ollama rejects a JSON array here).
        'options' => (object)$options,
    ];
    if ($responseFormat !== null) $request['format'] = is_array($responseFormat) ? $responseFormat : 'json';

    $payload = json_encode($request, JSON_UNESCAPED_UNICODE);
    if ($payload === false) return ['ok' => false, 'text' => '', 'error' => 'Could not prepare the AI request.'];
    $context = stream_context_create(['http' => [
        'header' => "Content-Type: application/json\r\n",
        'method' => 'POST',
        'content' => $payload,
        'timeout' => $timeout,
        'ignore_errors' => true,
    ]]);
    @set_time_limit($timeout + 30);
    $raw = @file_get_contents($endpoint, false, $context);
    if ($raw === false) return ['ok' => false, 'text' => '', 'error' => "Could not get a reply from local Ollama within {$timeout} seconds. Make sure Ollama is running, then try again."];

    $data = json_decode($raw, true);
    if (is_array($data) && !empty($data['error'])) return ['ok' => false, 'text' => '', 'error' => 'Ollama error: ' . (string)$data['error']];
    $text = is_array($data) ? trim((string)($data['response'] ?? '')) : '';
    if ($text === '') return ['ok' => false, 'text' => '', 'error' => "Ollama returned an empty response. Check that the model {$model} is installed (ollama pull {$model})."];
    return ['ok' => true, 'text' => $text, 'error' => ''];
}
?>
