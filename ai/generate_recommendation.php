<?php
require_once __DIR__ . '/ai_config.php';

function careerAiGenerateRecommendation(string $answerText, array $careers = []): array
{
    $catalog = '';
    if ($careers) {
        $catalog = "Career catalog maintained in Career Management. Prefer these careers when the quiz answers fit them, and use their required skills for the skill-gap and roadmap sections.\n";
        foreach ($careers as $career) {
            $name = trim((string)($career['career_name'] ?? ''));
            if ($name === '') continue;
            $description = trim((string)($career['description'] ?? ''));
            $skills = trim((string)($career['required_skills'] ?? ''));
            $catalog .= "- {$name}";
            if ($description !== '') $catalog .= ": {$description}";
            $catalog .= "\n";
            if ($skills !== '') $catalog .= "  Required skills: {$skills}\n";
        }
        $catalog .= "If none of these careers fit, you may name other real professions and say they are outside the current catalog.\n\n";
    }

    $prompt = "You are a professional AI career counselor. Analyze the student's quiz answers below.\n\n{$catalog}{$answerText}\n\nIMPORTANT:\n- Recommend exactly 5 real job professions, not hobbies or activities.\n- Explain why each career may fit.\n- Include required skills, skill gap analysis, a beginner learning roadmap, and final career advice.\n- Give practical, clear English.\n- Do not claim that a short quiz determines someone's future.";
    return careerAiGenerateText($prompt, ['temperature'=>0.3, 'num_predict'=>900], 180);
}
?>
