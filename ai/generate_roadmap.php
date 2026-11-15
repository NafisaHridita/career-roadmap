<?php
require_once __DIR__ . '/ai_config.php';

function careerAiGenerateRoadmap(string $careerName, array $skills, array $focusSkills = []): array
{
    $skillText = $skills ? implode(', ', $skills) : 'core skills for this career';
    $focusText = $focusSkills ? ' The student already has the other skills, so focus the plan on these missing skills: ' . implode(', ', $focusSkills) . '.' : '';
    $prompt = "Create a practical beginner learning roadmap for the career: {$careerName}. Required skills include: {$skillText}.{$focusText} Give a 4-stage plan with goals, topics, hands-on projects, and suggested completion checkpoints. Keep it realistic and clear; do not promise guaranteed employment.";
    return careerAiGenerateText($prompt, ['temperature'=>0.35, 'num_predict'=>800], 180);
}
?>
