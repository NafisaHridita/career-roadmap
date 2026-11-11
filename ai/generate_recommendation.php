<?php
require_once __DIR__ . '/ai_config.php';

function careerAiGenerateRecommendation(string $answerText): array
{
    $prompt = "You are a professional AI career counselor. Analyze the student's quiz answers below.\n\n{$answerText}\n\nIMPORTANT:\n- Recommend exactly 5 real job professions, not hobbies or activities.\n- Explain why each career may fit.\n- Include required skills, skill gap analysis, a beginner learning roadmap, and final career advice.\n- Give practical, clear English.\n- Do not claim that a short quiz determines someone's future.";
    return careerAiGenerateText($prompt, ['temperature'=>0.3, 'num_predict'=>900], 180);
}
?>
