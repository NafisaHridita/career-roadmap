
<?php
session_start();
include 'config/db.php';

$message = "";

if (isset($_GET['registered'])) {
    $message = "Registration successful! You can log in now.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT user_id, name, email, password, role
         FROM users WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: student/dashboard.php");
            }
            exit();

        } else {
            $message = "Incorrect password!";
        }

    } else {
        $message = "Account not found!";
    }

    $stmt->close();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | CareerRoadmap</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:28px;background:radial-gradient(circle at 10% 10%,#f1e9ff 0,transparent 30%),#f7f6fb;color:#22233a;font-family:Inter,Segoe UI,Arial,sans-serif}
        .shell{width:min(980px,100%);min-height:590px;display:grid;grid-template-columns:1fr 1fr;background:#fff;border:1px solid #eee8f5;border-radius:26px;overflow:hidden;box-shadow:0 24px 70px #39245d1c}
        .welcome{position:relative;overflow:hidden;padding:48px;background:linear-gradient(145deg,#492181,#7545b5 58%,#9a73d0);color:#fff;display:flex;flex-direction:column;justify-content:space-between}
        .welcome:before,.welcome:after{content:"";position:absolute;border-radius:50%;background:#ffffff12}
        .welcome:before{width:330px;height:330px;right:-150px;top:-120px}
        .welcome:after{width:260px;height:260px;left:-130px;bottom:-130px}
        .brand,.welcome-copy,.journey{position:relative;z-index:1}
        .brand{font-size:20px;font-weight:750;letter-spacing:.2px}
        .brand-mark{display:inline-grid;place-items:center;width:35px;height:35px;margin-right:9px;border-radius:11px;background:#ffffff24;vertical-align:middle}
        .welcome-copy{margin:60px 0}
        .eyebrow{text-transform:uppercase;letter-spacing:2px;font-size:12px;color:#e3d4fa;font-weight:700}
        .welcome h1{font-size:42px;line-height:1.12;margin:15px 0}
        .welcome-copy p{max-width:360px;color:#eee5fa;line-height:1.7;margin:0}
        .journey{display:flex;gap:10px;flex-wrap:wrap}
        .journey span{padding:9px 12px;border:1px solid #ffffff40;border-radius:20px;background:#ffffff12;font-size:13px}
        .form-side{padding:58px 64px;display:flex;flex-direction:column;justify-content:center}
        .form-side h2{font-size:30px;margin:0 0 9px;color:#28213a}
        .subtitle{color:#777386;margin:0 0 30px;line-height:1.5}
        .alert{padding:12px 14px;border-radius:10px;margin:0 0 20px;background:#fff1f0;color:#a8332d;border:1px solid #f6d0cd;line-height:1.5}
        .alert.success{background:#effaf3;color:#267141;border-color:#ccebd5}
        label{display:block;font-size:14px;font-weight:650;color:#39354a;margin:18px 0 7px}
        input{width:100%;height:50px;padding:0 14px;border:1px solid #ded9e8;border-radius:10px;font-size:15px;color:#28213a;background:#fff;outline:none;transition:.18s}
        input:focus{border-color:#8054bd;box-shadow:0 0 0 4px #8054bd20}
        .password-wrap{position:relative}.password-wrap input{padding-right:72px}
        .show-password{position:absolute;right:8px;top:7px;height:36px;border:0;border-radius:8px;background:#f4effa;color:#69429d;padding:0 10px;font-weight:650;cursor:pointer}
        .submit{width:100%;height:51px;border:0;border-radius:10px;margin-top:24px;background:linear-gradient(100deg,#583092,#7746b2);color:#fff;font-size:16px;font-weight:700;cursor:pointer;box-shadow:0 8px 18px #65409b30;transition:transform .15s,box-shadow .15s}
        .submit:hover{transform:translateY(-1px);box-shadow:0 11px 22px #65409b42}
        .register{margin:24px 0 0;text-align:center;color:#777386;font-size:14px}
        a{color:#68409e;font-weight:700;text-decoration:none}a:hover{text-decoration:underline}
        @media(max-width:760px){body{padding:14px}.shell{grid-template-columns:1fr;min-height:auto}.welcome{padding:26px 28px;min-height:250px}.welcome-copy{margin:32px 0 22px}.welcome h1{font-size:32px}.journey{display:none}.form-side{padding:34px 28px 38px}}
    </style>
<link rel="stylesheet" href="assets/cute-theme.css">
<style>
    body{background:radial-gradient(ellipse at 18% 20%,#152c47 0,transparent 32%),radial-gradient(ellipse at 86% 84%,#21173f 0,transparent 34%),#080d18;color:#eaf1ff}
    .shell{background:#0c1424;border:1px solid #354565;border-radius:24px;box-shadow:0 28px 90px #02061199,0 0 36px #55dfd51a;animation:auth-enter .7s cubic-bezier(.2,.7,.2,1) both}
    .welcome{background:linear-gradient(145deg,#0f1c31,#101c34 55%,#1b1b3e);isolation:isolate}.welcome:before,.welcome:after{background:transparent;border:1px solid #66ecd933;box-shadow:0 0 70px #6a69ff16}
    .brand,.welcome-copy,.journey{z-index:2}.brand{color:#f0f5ff}.brand-mark{background:linear-gradient(145deg,#132f3d,#29234b);color:#83f6e7;box-shadow:0 0 16px #5cead733}.brand:hover .brand-mark{transform:rotate(-10deg) scale(1.08)}
    .auth-neon-lines{position:absolute;z-index:1;inset:0;width:100%;height:100%;pointer-events:none;opacity:.72}.auth-neon-lines path{fill:none;stroke:#72f2e5;stroke-width:1.5;stroke-dasharray:8 14;filter:drop-shadow(0 0 6px #62e9dc);animation:auth-line-flow 22s linear infinite}.auth-neon-lines path:nth-child(2){stroke:#a78cff;stroke-dasharray:2 16;animation-duration:29s;animation-direction:reverse}@keyframes auth-line-flow{to{stroke-dashoffset:-420}}.welcome-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.22;filter:saturate(1.25) hue-rotate(92deg) brightness(.78);mix-blend-mode:screen;pointer-events:none}.welcome-art{position:absolute;z-index:1;width:95%;max-width:650px;right:-16%;bottom:7%;opacity:.22;pointer-events:none;animation:art-drift 9s ease-in-out infinite}.welcome-copy{animation:auth-rise .65s .12s both}.eyebrow{color:#82f0df}.welcome h1{color:#f3f6ff}.welcome-copy p{color:#b9c8dd}.journey span{border-color:#5a7092;background:#132036;color:#cbd9ee;transition:transform .2s,border-color .2s,box-shadow .2s}.journey span:hover{transform:translateY(-3px);border-color:#69ebdc;box-shadow:0 0 16px #5de9db2b}
    .form-side{background:linear-gradient(145deg,#10192a,#0c1424);animation:auth-form .65s .15s both}.form-side h2{color:#f0f4ff}.subtitle,.register{color:#a8b7d0}.alert.success{color:#b5ffe8;background:#102b2c;border-color:#286c68}.alert:not(.success){color:#ffc2d0;background:#351b2b;border-color:#713957}
    .form-side label{color:#d6e0f0}.form-side input{background:#0a1220;border-color:#344461;color:#eef5ff;transition:border-color .2s,box-shadow .2s,transform .2s}.form-side input::placeholder{color:#7587a4}.form-side input:hover{border-color:#6077a0}.form-side input:focus{border-color:#71e9dc;box-shadow:0 0 0 3px #71e9dc25,0 0 20px #71e9dc1b;transform:translateY(-1px)}
    .show-password{background:#152b3c;color:#8bf4e4;transition:transform .2s,box-shadow .2s}.show-password:hover{transform:translateY(-1px);box-shadow:0 0 15px #70eadb33}.submit{background:linear-gradient(105deg,#61e9d9,#8b91ff);color:#07111d;box-shadow:0 0 23px #61e9d933;background-size:180% 180%;transition:background-position .35s,transform .2s,box-shadow .2s}.submit:hover{background-position:100% 0;transform:translateY(-2px);box-shadow:0 0 28px #61e9d966}.register a{color:#84f1e3;transition:color .2s,text-shadow .2s}.register a:hover{color:#c1fff3;text-shadow:0 0 12px #69ebdb77}
    @keyframes auth-enter{from{opacity:0;transform:translateY(16px) scale(.99)}to{opacity:1;transform:translateY(0) scale(1)}}@keyframes auth-rise{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}@keyframes auth-form{from{opacity:0;transform:translateX(14px)}to{opacity:1;transform:translateX(0)}}@keyframes art-drift{0%,100%{transform:translateY(0) rotate(-1deg)}50%{transform:translateY(-12px) rotate(1deg)}}
    @media(max-width:760px){.welcome-art{width:75%;right:-15%;bottom:-3%;opacity:.27}.welcome-copy{margin:30px 0 20px}}
    @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}.welcome-video{display:none!important}}
</style>
</head>
<body>
    <main class="shell">
        <section class="welcome">
            <video class="welcome-video" autoplay muted loop playsinline preload="none" poster="assets/career-journey.svg" aria-hidden="true"><source src="https://videos.pexels.com/video-files/8836328/8836328-hd_1920_1080_25fps.mp4" type="video/mp4"></video><img class="welcome-art" src="assets/career-journey.svg" alt=""><svg class="auth-neon-lines" viewBox="0 0 800 900" preserveAspectRatio="none" aria-hidden="true"><path d="M-80 790 C130 650 280 820 390 590 S650 470 880 210"/><path d="M-90 250 C140 120 260 350 470 220 S690 240 870 60"/></svg>
            <a class="brand" href="index.php"><span class="brand-mark">↗</span>CareerRoadmap</a>
            <div class="welcome-copy">
                <div class="eyebrow">Your next chapter starts here</div>
                <h1>Find a path<br>that fits you.</h1>
                <p>Explore careers, understand your skills, and build a learning roadmap at your own pace.</p>
            </div>
            <div class="journey"><span>Explore careers</span><span>Build skills</span><span>Plan your future</span></div>
        </section>
        <section class="form-side">
            <h2>Welcome back</h2>
            <p class="subtitle">Log in to continue your CareerRoadmap journey.</p>
            <?php if ($message): ?>
                <p class="alert <?= isset($_GET['registered']) ? 'success' : '' ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="post">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" autocomplete="username" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="you@example.com">
                <label for="password">Password</label>
                <div class="password-wrap"><input id="password" type="password" name="password" autocomplete="current-password" required placeholder="Enter your password"><button class="show-password" type="button" id="togglePassword">Show</button></div>
                <button class="submit" type="submit">Log in</button>
            </form>
            <p class="register">New to CareerRoadmap? <a href="register.php">Create an account</a></p>
        </section>
    </main>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            this.textContent = showing ? 'Hide' : 'Show';
        });
    </script>
</body>
</html>
