<?php
require_once __DIR__ . '/ai_config.php';

function careerAiRecommendationSchema(): array
{
    $stringList = ['type' => 'array', 'items' => ['type' => 'string']];
    return [
        'type' => 'object',
        'properties' => [
            'overview' => ['type' => 'string'],
            'careers' => [
                'type' => 'array', 'minItems' => 5, 'maxItems' => 5,
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'fit_level' => ['type' => 'string', 'enum' => ['Strong match', 'Worth exploring', 'Stretch option']],
                        'evidence' => ['type' => 'string'],
                        'day_to_day' => ['type' => 'string'],
                        'skills_to_build' => $stringList,
                        'first_step' => ['type' => 'string'],
                        'reality_check' => ['type' => 'string'],
                    ],
                    'required' => ['name', 'fit_level', 'evidence', 'day_to_day', 'skills_to_build', 'first_step', 'reality_check'],
                    'additionalProperties' => false,
                ],
            ],
            'roadmap' => [
                'type' => 'object',
                'properties' => [
                    'goal' => ['type' => 'string'],
                    'stages' => [
                        'type' => 'array', 'minItems' => 4, 'maxItems' => 4,
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'timeframe' => ['type' => 'string'],
                                'title' => ['type' => 'string'],
                                'focus' => ['type' => 'string'],
                                'actions' => ['type' => 'array', 'minItems' => 2, 'maxItems' => 3, 'items' => ['type' => 'string']],
                                'milestone' => ['type' => 'string'],
                                'video_search' => ['type' => 'string'],
                            ],
                            'required' => ['timeframe', 'title', 'focus', 'actions', 'milestone', 'video_search'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['goal', 'stages'],
                'additionalProperties' => false,
            ],
            'reality_check' => ['type' => 'string'],
        ],
        'required' => ['overview', 'careers', 'roadmap', 'reality_check'],
        'additionalProperties' => false,
    ];
}

function careerAiRecommendationPrompt(string $answerText, array $careers = []): string
{
    // [ashasuborna] Career catalog from Career Management in the prompt (#30)
    $catalog = '';
    if ($careers) {
        $catalog = "CAREER CATALOG (prefer relevant careers from this list; do not force a poor fit):\n";
        foreach ($careers as $career) {
            $name = trim((string)($career['career_name'] ?? ''));
            if ($name === '') continue;
            $description = trim((string)($career['description'] ?? ''));
            $skills = trim((string)($career['required_skills'] ?? ''));
            $catalog .= "- {$name}" . ($description !== '' ? ": {$description}" : '') . "\n";
            if ($skills !== '') $catalog .= "  Skills: {$skills}\n";
        }
    }

    return "You are a careful career guide for a student. Use the quiz answers as limited clues, not proof of ability or destiny.\n\n{$catalog}\nQUIZ ANSWERS:\n{$answerText}\n\nReturn useful, realistic JSON matching the schema. Give exactly five distinct real job roles. For each, name the specific answer evidence behind the fit; describe ordinary day-to-day work; give 2-4 skills to build; one low-cost first step; and one honest reality check (entry requirements, routine work, competition, or uncertainty). Do not invent salaries, job guarantees, qualifications, or claim certainty. Do not assume the student's country, budget, grades, or available hours.\n\nCreate a beginner 12-week roadmap in four stages with practical actions and observable milestones. Make the plan modest enough for a student with limited time and money; label suggested study time as adjustable. Each stage must include a short video_search phrase, not a made-up video URL. Explain that credentials and hiring requirements vary by location. Keep wording concrete, encouraging, and concise.";
}

function careerAiRecommendationOptions(): array
{
    return ['temperature' => 0.3, 'num_predict' => 1800];
}

function careerAiGenerateRecommendation(string $answerText, array $careers = []): array
{
    return careerAiGenerateText(careerAiRecommendationPrompt($answerText, $careers), careerAiRecommendationOptions(), 180, careerAiRecommendationSchema());
}
?>
