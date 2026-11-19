<?php
// Downloads admin data as a CSV file that opens in Excel: ?type=careers | recommendations | students
// [ashasuborna] CSV export: careers, AI results, students; Excel-safe (#17, #21, #24)
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php'; require_once '../includes/module_helpers.php'; require_once '../includes/admin_tools.php';
ensureAdminToolTables($conn); ensureRecommendationTable($conn);

$exports = [
    'careers' => [
        'file' => 'careers',
        'headers' => ['Career ID', 'Career', 'Category', 'Job demand', 'Description', 'Required skills', 'Skill count', 'Created'],
        'sql' => "SELECT career_id, career_name, category, demand_level, description, required_skills, required_skills AS skill_count, created_at FROM careers ORDER BY career_name",
    ],
    'recommendations' => [
        'file' => 'ai-recommendations',
        'headers' => ['Result ID', 'Student', 'Email', 'Saved at', 'AI result'],
        'sql' => "SELECT r.recommendation_id, u.name, u.email, r.created_at, r.result_text FROM career_recommendations r LEFT JOIN users u ON u.user_id = r.user_id ORDER BY r.created_at DESC",
    ],
    'students' => [
        'file' => 'students',
        'headers' => ['User ID', 'Name', 'Email', 'Status', 'Registered', 'Saved AI results'],
        'sql' => "SELECT u.user_id, u.name, u.email, u.account_status, u.created_at, COUNT(r.recommendation_id) FROM users u LEFT JOIN career_recommendations r ON r.user_id = u.user_id WHERE u.role = 'student' GROUP BY u.user_id ORDER BY u.user_id DESC",
    ],
];

$type = requestText($_GET, 'type');
if (!isset($exports[$type])) { http_response_code(400); exit('Unknown export type. Use careers, recommendations or students.'); }
$export = $exports[$type];
$result = $conn->query($export['sql']);
if (!$result) { http_response_code(500); exit('Could not read the data for this export.'); }

// [ashasuborna] Cells that start with = + - @ (or a tab/CR) could run as formulas in Excel.
// Leading spaces and invisible characters are skipped first, so " =HYPERLINK(...)" is caught too.
// Such cells get a ' prefix so the spreadsheet shows them as plain text (#24).
function csvSafe($value): string
{
    $value = (string)($value ?? '');
    $trimmed = preg_replace('/^[\s\x00-\x1F\x{00A0}\x{FEFF}\x{200B}]+/u', '', $value) ?? $value;
    $first = $trimmed === '' ? '' : $trimmed[0];
    $risky = $first !== '' && strpbrk($first, '=+-@') !== false;
    $startsWithControl = $value !== '' && strpbrk($value[0], "\t\r\n") !== false;
    return ($risky || $startsWithControl) ? "'" . $value : $value;
}

logActivity($conn, 'export_csv', "Exported {$type} ({$result->num_rows} rows)");

$filename = 'careerroadmap-' . $export['file'] . '-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Bangla and other characters correctly
fputcsv($out, $export['headers']);
while ($row = $result->fetch_row()) {
    if ($type === 'careers') {
        // Skills one per cell line, plus a count.
        $skills = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string)$row[5])), static fn($s) => $s !== ''));
        $row[5] = implode(', ', $skills);
        $row[6] = count($skills);
    }
    fputcsv($out, array_map('csvSafe', $row));
}
fclose($out);
