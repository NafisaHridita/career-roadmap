
<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once '../config/db.php';
require_once '../includes/module_helpers.php';
$adminPhoto = '';
if (ensureAdminProfileTable($conn)) {
    $profileStmt = $conn->prepare('SELECT photo_path FROM admin_profiles WHERE user_id = ?');
    $profileStmt->bind_param('i', $_SESSION['user_id']);
    $profileStmt->execute();
    $adminPhoto = $profileStmt->get_result()->fetch_assoc()['photo_path'] ?? '';
    $profileStmt->close();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | CareerRoadmap</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:linear-gradient(135deg,#faf7ff,#f4f7ff 55%,#fff8fb);color:#302a3b;font-family:Inter,Segoe UI,Arial,sans-serif;min-height:100vh}
        .topbar{height:76px;background:#ffffffd9;backdrop-filter:blur(12px);border-bottom:1px solid #eee8f5;display:flex;align-items:center;justify-content:space-between;padding:0 max(22px,calc((100% - 1160px)/2));position:sticky;top:0;z-index:10}
        .brand{font-size:20px;font-weight:800;color:#52317c;text-decoration:none;margin-left:58px}.brand-mark{display:inline-grid;place-items:center;width:36px;height:36px;border-radius:12px;margin-right:9px;background:#f0e5ff;color:#70469c}
        .admin-tools{display:flex;align-items:center;gap:10px;position:relative}.mini-profile{width:46px;height:46px;border-radius:50%;object-fit:cover;background:#f1e6ff;display:flex;align-items:center;justify-content:center;color:#5633a3;font-weight:bold;border:3px solid #fff;box-shadow:0 0 0 2px #e7d8f7;text-decoration:none}
        .dots{border:0;border-radius:50%;width:40px;height:40px;font-size:23px;line-height:1;background:#f2eafa;color:#59367f;cursor:pointer}.dots:hover{background:#e8dcf6}
        .profile-menu{position:absolute;right:0;top:55px;z-index:15;min-width:185px;background:#fff;border:1px solid #eee8f5;border-radius:14px;box-shadow:0 12px 30px #3b245422;padding:8px}.profile-menu[hidden]{display:none}.profile-menu a{display:block;padding:11px 12px;color:#342d42;border-radius:9px;text-decoration:none;font-size:14px}.profile-menu a:hover{background:#f7f2fc}.profile-menu a.menu-logout{color:#c43e56;border-top:1px solid #f0eaf4;margin-top:5px}
        .main{max-width:1160px;margin:38px auto 70px;padding:0 22px}.hero{position:relative;overflow:hidden;background:linear-gradient(120deg,#f1e8ff,#fff0f5 70%,#edf7ff);border:1px solid #eee4f7;border-radius:26px;padding:34px 40px;margin-bottom:26px}.hero:after{content:'✿';position:absolute;right:42px;top:10px;color:#d7bdea;font-size:90px;transform:rotate(14deg)}.eyebrow{font-size:12px;text-transform:uppercase;letter-spacing:1.8px;color:#8b6da5;font-weight:800}.hero h1{font-size:32px;margin:10px 0 8px;color:#4c3264}.hero p{margin:0;color:#766d80;line-height:1.6;max-width:620px;position:relative;z-index:1}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.card{position:relative;overflow:hidden;background:#fff;border:1px solid #eeeaf3;border-radius:22px;padding:25px;min-height:215px;box-shadow:0 8px 24px #48345c0a;transition:transform .18s,box-shadow .18s}.card:hover{transform:translateY(-3px);box-shadow:0 14px 30px #48345c16}.card-top{display:flex;align-items:center;gap:13px}.icon{width:48px;height:48px;border-radius:16px;display:grid;place-items:center;font-size:24px}.students .icon{background:#e6f6ee}.quiz .icon{background:#fff1dc}.careers .icon{background:#e9f1ff}.recommendations .icon{background:#fbe9f3}.card h2{font-size:19px;margin:0;color:#40364d}.card p{color:#81798b;line-height:1.55;margin:15px 0 18px}.actions{display:flex;flex-wrap:wrap;gap:9px}.actions a{display:inline-block;padding:9px 12px;border-radius:20px;background:#f4eff9;color:#63408b;text-decoration:none;font-size:13px;font-weight:700}.actions a:hover{background:#eaddf4}.students .actions a{background:#e9f7ef;color:#347754}.quiz .actions a{background:#fff4e3;color:#916022}.careers .actions a{background:#edf3ff;color:#4268a5}.recommendations .actions a{background:#fff0f6;color:#a34d77}
        @media(max-width:720px){.topbar{height:66px}.main{margin-top:22px}.hero{padding:27px 24px}.hero:after{right:12px;top:10px;font-size:60px}.hero h1{font-size:27px}.grid{grid-template-columns:1fr}.card{min-height:0}}
    </style>
<link rel="stylesheet" href="../assets/cute-theme.css">
<style>
    body{background:#090e1b;background-image:radial-gradient(ellipse at 12% 0%,#321e5f 0,transparent 34%),radial-gradient(ellipse at 92% 28%,#07364a 0,transparent 30%),linear-gradient(135deg,#090e1b,#10172a 58%,#111226);color:#e9edff}
    .topbar{background:rgba(10,15,29,.88);border-color:#26304a;box-shadow:0 8px 30px #05091455}
    .brand{color:#f1efff}.brand-mark{background:#20203c;color:#75f5e5;box-shadow:0 0 18px #6757ff35}
    .mini-profile{background:#181f35;color:#8ff5e7;border-color:#11182b;box-shadow:0 0 0 2px #7665ff,0 0 16px #6858ff55}
    .dots{background:#202744;color:#80f4e2;border:1px solid #39466d;box-shadow:0 0 14px #4f5aff25}.dots:hover{background:#2b3658}
    .profile-menu{background:#151d31;border-color:#33405e;box-shadow:0 14px 38px #0009}.profile-menu a{color:#edf1ff}.profile-menu a:hover{background:#242d48}.profile-menu a.menu-logout{color:#ff8ea9;border-color:#33405e}
    .hero{background:linear-gradient(115deg,rgba(25,29,57,.97),rgba(18,37,57,.97) 65%,rgba(36,24,59,.97));border-color:#39456b;box-shadow:0 0 0 1px #615bff20,0 18px 55px #0004}
    .hero:after{content:'✦';color:#70efe2;text-shadow:0 0 24px #53f5e1;font-size:100px}.eyebrow{color:#72f0dd}.hero h1{color:#f5f3ff}.hero p{color:#b4bfd7}
    .card{background:linear-gradient(145deg,rgba(20,28,48,.96),rgba(18,24,42,.98));border-color:#2c3858;box-shadow:0 10px 32px #03071266}.card:hover{border-color:#6468bb;box-shadow:0 0 0 1px #7a70ff35,0 18px 44px #0007}
    .card h2{color:#eff2ff}.card p{color:#aab5cf}.students .icon{background:#102f31;color:#72f1c3;box-shadow:inset 0 0 0 1px #246456}.quiz .icon{background:#382e19;color:#ffdc7a;box-shadow:inset 0 0 0 1px #645126}.careers .icon{background:#172c46;color:#8ac5ff;box-shadow:inset 0 0 0 1px #31547b}.recommendations .icon{background:#372139;color:#ffadd8;box-shadow:inset 0 0 0 1px #68406b}
    .actions a{border:1px solid #384363;background:#202941;color:#d5dbf2}.actions a:hover{filter:brightness(1.2);background:#293654}.students .actions a{background:#102d2c;color:#75f0c1;border-color:#28554d}.quiz .actions a{background:#342c18;color:#ffdb78;border-color:#63512a}.careers .actions a{background:#16283c;color:#91c7ff;border-color:#34516f}.recommendations .actions a{background:#342039;color:#f4a8d4;border-color:#654067}
</style>
</head>
<body><a class="admin-home-icon" href="dashboard.php" aria-label="Admin dashboard" title="Admin dashboard"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7"/><path d="M5.5 9v11h13V9M9 20v-6h6v6"/></svg></a>
    <nav class="topbar">
        <a class="brand" href="../index.php"><span class="brand-mark">↗</span>CareerRoadmap</a>
        <div class="admin-tools">
            <?php if ($adminPhoto): ?><a href="profile.php?view=1" aria-label="View admin profile"><img class="mini-profile" src="../<?php echo htmlspecialchars($adminPhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Admin profile photo"></a><?php else: ?><a class="mini-profile" href="profile.php?view=1" aria-label="View admin profile"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['name'] ?? 'A',0,1))); ?></a><?php endif; ?>
            <button type="button" class="dots" id="profileMenuButton" aria-label="Profile options">⋮</button>
            <div class="profile-menu" id="profileMenu" hidden><a href="profile.php?view=1">View profile</a><a href="profile.php?edit=1">Edit profile</a><a class="menu-logout" href="../logout.php">Logout</a></div>
        </div>
    </nav>
    <main class="main">
        <section class="hero"><div class="eyebrow">Your workspace ✨</div><h1>Welcome, <?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?>!</h1><p>Here’s your CareerRoadmap control center. Pick a space to manage students, quiz questions, careers, and recommendations.</p></section>
        <section class="grid">
            <article class="card students"><div class="card-top"><span class="icon">🌱</span><h2>Student Management</h2></div><p>Review new registrations and choose which students to save to your list.</p><div class="actions"><a href="students.php">Student approvals</a><a href="students_list.php">See Student List</a></div></article>
            <article class="card quiz"><div class="card-top"><span class="icon">📝</span><h2>AI Career Quiz</h2></div><p>Generate questions, then edit and review them before students see them.</p><div class="actions"><a href="add_question.php">Generate questions</a><a href="manage_questions.php">Review questions</a></div></article>
            <article class="card careers"><div class="card-top"><span class="icon">🌷</span><h2>Career Management</h2></div><p>Add career paths and keep their descriptions and required skills up to date.</p><div class="actions"><a href="careers.php">Manage careers and skills</a></div></article>
            <article class="card recommendations"><div class="card-top"><span class="icon">💌</span><h2>AI Recommendations</h2></div><p>Browse the career analyses students have received from the quiz.</p><div class="actions"><a href="recommendations.php">View recommendations</a></div></article>
        </section>
    </main>
    <script>
        const profileMenuButton=document.getElementById('profileMenuButton');
        const profileMenu=document.getElementById('profileMenu');
        profileMenuButton.addEventListener('click',()=>{profileMenu.hidden=!profileMenu.hidden;});
        document.addEventListener('click',event=>{if(!event.target.closest('.admin-tools'))profileMenu.hidden=true;});
    </script>
</body>
</html>
