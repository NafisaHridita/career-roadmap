<?php
require_once __DIR__ . '/ai_config.php';

function careerAiAssistantReply(string $question): array
{
    $question = trim($question);
    if ($question === '') return ['ok'=>false, 'text'=>'', 'error'=>'Please enter a question.'];
    $prompt = "You are CareerExplorer AI Career Assistant. Give simple, helpful, and accurate career guidance to students.\n\nStudent question: {$question}";
    return careerAiGenerateText($prompt, ['temperature'=>0.4], 120);
}
?>
