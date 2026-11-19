<?php
// First-time setup, run from the project folder:
//   C:\xampp\php\php.exe tools\setup.php
// It creates the first admin account (registration only creates students) and adds
// sample careers and approved quiz questions when those tables are empty.
// Running it again is safe: it skips anything that already exists.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(); }
$adminOnly = in_array('--admin-only', $argv ?? [], true);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/module_helpers.php';

function ask(string $label): string
{
    echo $label . ': ';
    return trim((string) fgets(STDIN));
}

// 1. Admin account
ensureUserApprovalColumn($conn);
$admins = (int) $conn->query("SELECT COUNT(*) AS n FROM users WHERE role='admin'")->fetch_assoc()['n'];
if ($admins > 0) {
    echo "Admin account already exists - skipping.\n";
} else {
    echo "Create the first admin account.\n";
    $name = ask('Admin name');
    $email = ask('Admin email');
    $password = ask('Admin password (min 8 characters)');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        fwrite(STDERR, "Invalid name, email or password. Nothing was created.\n");
        exit(1);
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    // A deleted admin may still have a student row with the same unique email.
    // Promote that existing account so its user_id and related records survive.
    $find = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $find->bind_param('s', $email);
    if (!$find->execute()) { fwrite(STDERR, "Could not check the email address.\n"); exit(1); }
    $existing = $find->get_result()->fetch_assoc();
    if ($existing) {
        $userId = (int) $existing['user_id'];
        $stmt = $conn->prepare("UPDATE users SET name = ?, password = ?, role = 'admin', account_status = 'approved' WHERE user_id = ?");
        $stmt->bind_param('ssi', $name, $hash, $userId);
        if (!$stmt->execute()) { fwrite(STDERR, "Could not restore the existing account as admin.\n"); exit(1); }
        echo "Existing account promoted to admin for {$email}; its user ID and related records were preserved.\n";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, account_status) VALUES (?, ?, ?, 'admin', 'approved')");
        $stmt->bind_param('sss', $name, $email, $hash);
        if (!$stmt->execute()) { fwrite(STDERR, "Could not create admin account. Check the database and try again.\n"); exit(1); }
        echo "Admin account created for {$email}.\n";
    }
}

// Admin recovery should not seed sample careers/questions or alter existing
// project content. Use: php tools/setup.php --admin-only
if ($adminOnly) {
    echo "Admin-only setup finished. Career and quiz data were not changed.\n";
    exit();
}

// 2. Sample careers (with skill rows for the skill gap checker)
ensureCareerTables($conn);
$careerCount = (int) $conn->query('SELECT COUNT(*) AS n FROM careers')->fetch_assoc()['n'];
if ($careerCount > 0) {
    echo "Careers already exist - skipping sample careers.\n";
} else {
    $careers = [
        ['Software Developer', 'Designs, builds and maintains applications and websites.', "Programming fundamentals\nProblem solving\nVersion control (Git)\nDatabases and SQL\nTesting and debugging\nTeamwork"],
        ['Data Analyst', 'Turns raw data into reports and insights that help organisations decide.', "Spreadsheets\nSQL\nStatistics basics\nData visualisation\nPython or R\nCommunication"],
        ['UI/UX Designer', 'Researches users and designs clear, friendly digital interfaces.', "User research\nWireframing and prototyping\nVisual design\nDesign tools (e.g. Figma)\nUsability testing\nEmpathy"],
        ['Cybersecurity Analyst', 'Protects systems and networks by finding and fixing security weaknesses.', "Networking fundamentals\nOperating systems\nSecurity principles\nRisk assessment\nIncident response\nAttention to detail"],
        ['Digital Marketer', 'Plans online campaigns that reach and grow an audience.', "Content writing\nSocial media\nSEO basics\nAnalytics\nCreativity\nCommunication"],
        ['Teacher', 'Plans lessons and helps learners understand and grow.', "Subject knowledge\nLesson planning\nCommunication\nPatience\nClassroom management\nAssessment"],
        ['Graphic Designer', 'Creates visual content for print and digital media.', "Typography\nColour theory\nLayout design\nDesign software\nCreativity\nTime management"],
        ['Project Manager', 'Leads teams to deliver projects on time and within budget.', "Planning and scheduling\nLeadership\nCommunication\nRisk management\nBudgeting\nProblem solving"],
    ];
    $stmt = $conn->prepare('INSERT INTO careers (career_name, description, required_skills) VALUES (?, ?, ?)');
    foreach ($careers as [$name, $description, $skills]) {
        $stmt->bind_param('sss', $name, $description, $skills);
        $stmt->execute();
        syncCareerSkillRows($conn, (int) $stmt->insert_id, $skills);
    }
    echo 'Added ' . count($careers) . " sample careers.\n";
}

// 3. Sample approved quiz questions
$approved = (int) $conn->query("SELECT COUNT(*) AS n FROM questions WHERE status='approved'")->fetch_assoc()['n'];
if ($approved > 0) {
    echo "Approved quiz questions already exist - skipping sample questions.\n";
} else {
    $questions = [
        ['Which activity sounds most rewarding?', 'Interests', ['Solving a tricky problem', 'Helping someone learn', 'Making something creative', 'Organising a team project']],
        ['Which school subject do you enjoy most?', 'Interests', ['Mathematics or computer science', 'Languages or social studies', 'Art or design', 'Business studies']],
        ['How do you prefer to work?', 'Work style', ['Alone, focused on details', 'With people, talking and listening', 'Freely, trying new ideas', 'Leading and planning for a group']],
        ['What kind of task makes you lose track of time?', 'Interests', ['Building or fixing things on a computer', 'Explaining ideas to others', 'Drawing, writing or editing media', 'Planning events or budgets']],
        ['Which strength describes you best?', 'Strengths', ['Logical thinking', 'Patience and empathy', 'Imagination', 'Decision making']],
        ['What work environment do you prefer?', 'Environment', ['A quiet office or lab', 'A classroom or community space', 'A studio or flexible space', 'A busy, fast-moving team']],
        ['How do you like to solve a problem?', 'Problem solving', ['Analyse the data step by step', 'Talk it through with others', 'Sketch out different creative options', 'Make a plan and assign tasks']],
        ['Which goal matters most to you in a career?', 'Values', ['Building useful technology', 'Making a difference in people\'s lives', 'Expressing my ideas', 'Leading and growing a business']],
    ];
    $labels = ['A', 'B', 'C', 'D'];
    $q = $conn->prepare("INSERT INTO questions (question_text, category, status) VALUES (?, ?, 'approved')");
    $o = $conn->prepare('INSERT INTO options (option_label, question_id, option_text, score_value, category) VALUES (?, ?, ?, 0, ?)');
    foreach ($questions as [$text, $category, $options]) {
        $q->bind_param('ss', $text, $category);
        $q->execute();
        $questionId = (int) $q->insert_id;
        foreach ($options as $i => $optionText) {
            $label = $labels[$i];
            $o->bind_param('siss', $label, $questionId, $optionText, $category);
            $o->execute();
        }
    }
    echo 'Added ' . count($questions) . " approved quiz questions.\n";
}

echo "Setup finished.\n";
