<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/module_helpers.php';

function careerPageEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function careerPageRequireStudent(): void
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
        header('Location: ../login.php');
        exit();
    }
}

function careerPageGetById(mysqli $conn, int $careerId): ?array
{
    $stmt = $conn->prepare('SELECT career_id,career_name,description,required_skills FROM careers WHERE career_id=?');
    $stmt->bind_param('i', $careerId);
    $stmt->execute();
    $career = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $career;
}

function careerPageSkills(?string $skills): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string)$skills)), static fn($skill) => $skill !== ''));
}

function careerPageStyles(string $title): void
{
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . careerPageEscape($title) . ' | CareerRoadmap</title><link rel="stylesheet" href="../assets/cute-theme.css"><style>body{margin:0;min-height:100vh;padding:28px 18px;font:16px Inter,"Segoe UI",Arial,sans-serif}.career-wrap{max-width:1000px;margin:24px auto;padding:28px;border-radius:22px}.career-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px}.career-card{padding:20px;border-radius:16px;border:1px solid #30405f;background:linear-gradient(145deg,#131d32,#10182a);transition:.2s}.career-card:hover{transform:translateY(-3px);box-shadow:0 0 22px #61e8dc25}.career-card p{line-height:1.6}.career-link{display:inline-block;padding:10px 14px;border-radius:20px;background:#17333b;color:#84f5e4;text-decoration:none;font-weight:700}.career-link:hover{box-shadow:0 0 18px #68f0df55}.skill-pill{display:inline-block;padding:8px 12px;margin:5px;border:1px solid #36536b;border-radius:20px;background:#122536;color:#a5dcff}.content-box{padding:20px;margin:14px 0;border-radius:15px;background:#111b2f;border:1px solid #2c3958;line-height:1.7;white-space:pre-wrap}.career-nav{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:18px}.career-nav a{color:#86f1e6}textarea{width:100%;min-height:150px;padding:12px;background:#0d1526;color:#eef2ff;border:1px solid #344363;border-radius:10px}@media(max-width:600px){.career-wrap{padding:20px}}</style></head><body class="neon-app">';
}

function careerPageHeader(string $title): void
{
    echo '<nav class="career-nav"><a href="career_list.php">Careers</a><a href="recommendation.php">My recommendations</a><a href="skills.php">Skills</a><a href="../student/dashboard.php" class="home-button">⌂ Home</a></nav><h1>' . careerPageEscape($title) . '</h1>';
}
?>
