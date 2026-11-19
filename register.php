<?php
session_start();
require_once 'config/db.php';
require_once 'includes/module_helpers.php';
require_once 'includes/registration_verification_helpers.php';
// [ashasuborna] Activity log and users.created_at for Reports (#23, #38)
require_once 'includes/admin_tools.php';
ensureAdminToolTables($conn);

if (!ensureUserApprovalColumn($conn) || !ensureRegistrationVerificationTable($conn)) {
    http_response_code(500);
    die('Registration is temporarily unavailable. Check the database setup and try again.');
}
$conn->query('DELETE FROM registration_verifications WHERE expires_at < NOW()');

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$message = '';
$messageType = 'error';
$step = 'details';
$emailValue = '';

function registrationEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function registrationDisplayName(string $email): string
{
    $localPart = (string)strstr($email, '@', true);
    $name = preg_replace('/[._+\-]+/', ' ', $localPart) ?? '';
    $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
    return mb_substr($name !== '' ? mb_convert_case($name, MB_CASE_TITLE, 'UTF-8') : 'Student', 0, 100);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = strtolower(trim((string)($_POST['email'] ?? '')));
    $action = (string)($_POST['action'] ?? 'send_code');
    $postedToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif (!filter_var($emailValue, FILTER_VALIDATE_EMAIL) || strlen($emailValue) > 150) {
        $message = 'Enter a valid email address.';
    } elseif ($action === 'verify_code') {
        $step = 'verify';
        $code = trim((string)($_POST['verification_code'] ?? ''));
        if (!preg_match('/^\d{6}$/', $code)) {
            $message = 'Enter the 6-digit code from your email.';
        } else {
            $stmt = $conn->prepare('SELECT verification_id,password_hash,code_hash,(expires_at > NOW()) AS is_valid,attempts FROM registration_verifications WHERE email=? LIMIT 1');
            $stmt->bind_param('s', $emailValue);
            $stmt->execute();
            $verification = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$verification) {
                $message = 'No active verification code was found. Request a new code.';
                $step = 'details';
            } elseif (!(int)$verification['is_valid']) {
                $stmt = $conn->prepare('DELETE FROM registration_verifications WHERE email=?');
                $stmt->bind_param('s', $emailValue); $stmt->execute(); $stmt->close();
                $message = 'That code expired. Enter your details to request a new one.';
                $step = 'details';
            } elseif ((int)$verification['attempts'] >= 5) {
                $message = 'Too many incorrect codes. Wait 1 minute, then request a new code.';
            } elseif (!password_verify($code, $verification['code_hash'])) {
                $stmt = $conn->prepare('UPDATE registration_verifications SET attempts=attempts+1 WHERE email=?');
                $stmt->bind_param('s', $emailValue); $stmt->execute(); $stmt->close();
                $remaining = max(0, 4 - (int)$verification['attempts']);
                $message = $remaining > 0 ? "That code is incorrect. {$remaining} attempt(s) remain." : 'Too many incorrect codes. Wait 1 minute, then request a new code.';
            } else {
                $passwordHash = (string)$verification['password_hash'];
                $name = registrationDisplayName($emailValue);
                try {
                    // Email ownership is confirmed before a student row is created.
                    // Pending here means "not yet saved to the admin list"; login remains open.
                    $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,account_status) VALUES (?,?,?,'student','pending')");
                    $stmt->bind_param('sss', $name, $emailValue, $passwordHash);
                    $stmt->execute();
                    $newUserId = (int)$stmt->insert_id;
                    $stmt->close();
                    $stmt = $conn->prepare('DELETE FROM registration_verifications WHERE email=?');
                    $stmt->bind_param('s', $emailValue); $stmt->execute(); $stmt->close();
                    // [ashasuborna] Activity log entry for each verified registration (#23)
                    logActivity($conn, 'register', "New student registered: {$emailValue}", $newUserId, $name);
                    header('Location: login.php?registered=1');
                    exit();
                } catch (mysqli_sql_exception $exception) {
                    if ((int)$exception->getCode() === 1062) {
                        $stmt = $conn->prepare('DELETE FROM registration_verifications WHERE email=?');
                        $stmt->bind_param('s', $emailValue); $stmt->execute(); $stmt->close();
                        $message = 'An account with this email already exists. Please log in instead.';
                        $step = 'details';
                    } else {
                        $message = 'Registration could not be completed. Please try again.';
                    }
                }
            }
        }
    } elseif ($action === 'resend_code') {
        $step = 'verify';
        $stmt = $conn->prepare('SELECT created_at,(created_at > NOW() - INTERVAL 60 SECOND) AS cooldown FROM registration_verifications WHERE email=? LIMIT 1');
        $stmt->bind_param('s', $emailValue); $stmt->execute();
        $previous = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$previous) {
            $step = 'details';
            $message = 'No active verification was found. Enter your details to request a code.';
        } elseif ((int)$previous['cooldown']) {
            $message = 'Please wait 1 minute between verification code requests.';
        } else {
            $code = (string)random_int(100000, 999999);
            $codeHash = password_hash($code, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE registration_verifications SET code_hash=?,expires_at=DATE_ADD(NOW(),INTERVAL 10 MINUTE),attempts=0,created_at=NOW() WHERE email=?');
            $stmt->bind_param('ss', $codeHash, $emailValue); $saved = $stmt->execute(); $stmt->close();
            if ($saved && sendCareerRoadmapVerificationEmail($emailValue, $code, careerRoadmapEnv())) {
                $message = 'A new verification code was sent. It expires in 10 minutes.';
                $messageType = 'success';
            } else {
                $message = 'We could not send the verification email. Check the email delivery settings and try again later.';
            }
        }
    } else {
        $step = 'details';
        $password = (string)($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters.';
        } else {
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE email=? LIMIT 1');
            $stmt->bind_param('s', $emailValue); $stmt->execute();
            $accountExists = $stmt->get_result()->num_rows > 0; $stmt->close();
            if ($accountExists) {
                $message = 'An account with this email already exists. Please log in instead.';
            } else {
                $stmt = $conn->prepare('SELECT created_at,(created_at > NOW() - INTERVAL 60 SECOND) AS cooldown FROM registration_verifications WHERE email=? LIMIT 1');
                $stmt->bind_param('s', $emailValue); $stmt->execute();
                $previous = $stmt->get_result()->fetch_assoc(); $stmt->close();
                if ($previous && (int)$previous['cooldown']) {
                    $step = 'verify';
                    $message = 'A code was sent recently. Check your inbox or wait 1 minute before requesting another.';
                } else {
                    $code = (string)random_int(100000, 999999);
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $codeHash = password_hash($code, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO registration_verifications (email,password_hash,code_hash,expires_at,attempts,created_at) VALUES (?,?,?,DATE_ADD(NOW(),INTERVAL 10 MINUTE),0,NOW()) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),code_hash=VALUES(code_hash),expires_at=VALUES(expires_at),attempts=0,created_at=NOW()");
                    $stmt->bind_param('sss', $emailValue, $passwordHash, $codeHash);
                    $saved = $stmt->execute(); $stmt->close();
                    $settings = careerRoadmapEnv();
                    if ($saved && sendCareerRoadmapVerificationEmail($emailValue, $code, $settings)) {
                        $step = 'verify';
                        $message = 'We sent a 6-digit verification code to your email. It expires in 10 minutes.';
                        $messageType = 'success';
                    } else {
                        $stmt = $conn->prepare('DELETE FROM registration_verifications WHERE email=?');
                        $stmt->bind_param('s', $emailValue); $stmt->execute(); $stmt->close();
                        $message = 'We could not send the verification email. Set a working authenticated SMTP sender in the project .env file, then try again.';
                    }
                }
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account | CareerRoadmap</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:26px;background:radial-gradient(circle at 90% 12%,#f3e8ff 0,transparent 28%),#f7f6fb;color:#28213a;font-family:Inter,Segoe UI,Arial,sans-serif}
        .shell{width:min(980px,100%);display:grid;grid-template-columns:1fr 1.05fr;background:#fff;border:1px solid #eee8f5;border-radius:26px;overflow:hidden;box-shadow:0 24px 70px #39245d1c}
        .welcome{position:relative;overflow:hidden;min-height:620px;padding:46px;background:linear-gradient(145deg,#492181,#7545b5 58%,#a17bd4);color:#fff;display:flex;flex-direction:column;justify-content:space-between}
        .welcome:before,.welcome:after{content:"";position:absolute;border-radius:50%;background:#ffffff12}.welcome:before{width:330px;height:330px;right:-145px;top:-130px}.welcome:after{width:270px;height:270px;left:-145px;bottom:-145px}
        .brand,.welcome-copy,.note{position:relative;z-index:1}.brand{font-size:20px;font-weight:750}.brand-mark{display:inline-grid;place-items:center;width:35px;height:35px;margin-right:9px;border-radius:11px;background:#ffffff24;vertical-align:middle}
        .welcome-copy{margin:auto 0}.eyebrow{text-transform:uppercase;letter-spacing:2px;font-size:12px;color:#e3d4fa;font-weight:700}.welcome h1{font-size:42px;line-height:1.12;margin:15px 0}.welcome-copy p{max-width:360px;color:#eee5fa;line-height:1.7}
        .note{padding:13px 15px;border-radius:12px;background:#ffffff18;border:1px solid #ffffff30;color:#fff;line-height:1.5}
        .form-side{padding:48px 60px;display:flex;flex-direction:column;justify-content:center}.form-side h2{font-size:30px;margin:0 0 8px}.subtitle{color:#777386;margin:0 0 25px;line-height:1.5}
        .error{padding:11px 13px;border-radius:10px;margin:0 0 14px;background:#fff1f0;color:#a8332d;border:1px solid #f6d0cd}.error.success{background:#e8fbf5;color:#145d4c;border-color:#b8eadb}
        label{display:block;font-size:14px;font-weight:650;color:#39354a;margin:17px 0 7px}input{width:100%;height:49px;padding:0 14px;border:1px solid #ded9e8;border-radius:10px;font-size:15px;color:#28213a;outline:none;transition:.18s}input:focus{border-color:#8054bd;box-shadow:0 0 0 4px #8054bd20}
        .password-wrap{position:relative}.password-wrap input{padding-right:72px}.show-password{position:absolute;right:8px;top:7px;height:35px;border:0;border-radius:8px;background:#f4effa;color:#69429d;padding:0 10px;font-weight:650;cursor:pointer}
        .submit{width:100%;height:51px;border:0;border-radius:10px;margin-top:24px;background:linear-gradient(100deg,#583092,#7746b2);color:#fff;font-size:16px;font-weight:700;cursor:pointer;box-shadow:0 8px 18px #65409b30}.login{margin:22px 0 0;text-align:center;color:#777386;font-size:14px}a{color:#68409e;font-weight:700;text-decoration:none}a:hover{text-decoration:underline}
        @media(max-width:760px){body{padding:14px}.shell{grid-template-columns:1fr}.welcome{min-height:230px;padding:25px 28px}.welcome-copy{margin:28px 0 0}.welcome h1{font-size:32px}.form-side{padding:32px 28px 38px}}
    </style>
<link rel="stylesheet" href="assets/cute-theme.css">
<style>
    .shell{animation:card-arrive .8s cubic-bezier(.2,.75,.25,1) both;transform-origin:center}
    .welcome:before{animation:orb-drift 11s ease-in-out infinite alternate}
    .welcome:after{animation:orb-drift 13s ease-in-out infinite alternate-reverse}
    .welcome-copy{animation:soft-rise .75s .12s both}
    .brand-mark{transition:transform .35s,box-shadow .35s}.brand:hover .brand-mark{transform:rotate(-12deg) scale(1.08);box-shadow:0 0 22px #fff5}
    .note{animation:soft-rise .65s .28s both}
    .form-side{animation:form-arrive .7s .16s both}
    .form-side input{transition:border-color .2s,box-shadow .2s,transform .2s}
    .form-side input:hover{border-color:#c6b1df}
    .form-side input:focus{transform:translateY(-1px)}
    .form-side form>label:nth-of-type(1){animation:soft-rise .45s .2s both}
    .form-side form>input:nth-of-type(1){animation:soft-rise .45s .25s both}
    .form-side form>label:nth-of-type(2){animation:soft-rise .45s .3s both}
    .form-side form>input:nth-of-type(2){animation:soft-rise .45s .35s both}
    .password-wrap{animation:soft-rise .45s .42s both}
    .submit{background-size:200% 200%;background-position:0% 50%;transition:background-position .45s,transform .2s,box-shadow .2s}
    .submit:hover{background-position:100% 50%;transform:translateY(-2px);box-shadow:0 13px 25px #65409b45}
    .login a{transition:color .2s}.login a:hover{color:#a15ac2}
    @keyframes card-arrive{from{opacity:0;transform:translateY(18px) scale(.985)}to{opacity:1;transform:translateY(0) scale(1)}}
    @keyframes soft-rise{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
    @keyframes form-arrive{from{opacity:0;transform:translateX(14px)}to{opacity:1;transform:translateX(0)}}
    @keyframes orb-drift{from{transform:translate3d(0,0,0) scale(.96)}to{transform:translate3d(-18px,16px,0) scale(1.06)}}
    @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;scroll-behavior:auto!important;transition-duration:.01ms!important}}
</style>
<style>
    body{background:radial-gradient(ellipse at 18% 20%,#152c47 0,transparent 32%),radial-gradient(ellipse at 86% 84%,#21173f 0,transparent 34%),#080d18;color:#eaf1ff}
    .shell{background:#0c1424;border:1px solid #354565;border-radius:24px;box-shadow:0 28px 90px #02061199,0 0 36px #55dfd51a;animation:auth-enter .7s cubic-bezier(.2,.7,.2,1) both}
    .welcome{background:linear-gradient(145deg,#0f1c31,#101c34 55%,#1b1b3e);isolation:isolate}.welcome:before,.welcome:after{background:transparent;border:1px solid #66ecd933;box-shadow:0 0 70px #6a69ff16}
    .brand,.welcome-copy,.note{z-index:2}.brand{color:#f0f5ff}.brand-mark{background:linear-gradient(145deg,#132f3d,#29234b);color:#83f6e7;box-shadow:0 0 16px #5cead733}.brand:hover .brand-mark{transform:rotate(-10deg) scale(1.08)}
    .auth-neon-lines{position:absolute;z-index:1;inset:0;width:100%;height:100%;pointer-events:none;opacity:.72}.auth-neon-lines path{fill:none;stroke:#72f2e5;stroke-width:1.5;stroke-dasharray:8 14;filter:drop-shadow(0 0 6px #62e9dc);animation:auth-line-flow 22s linear infinite}.auth-neon-lines path:nth-child(2){stroke:#a78cff;stroke-dasharray:2 16;animation-duration:29s;animation-direction:reverse}@keyframes auth-line-flow{to{stroke-dashoffset:-420}}.welcome-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.24;filter:saturate(1.3) hue-rotate(215deg) brightness(.8);mix-blend-mode:screen;pointer-events:none}.welcome-art{position:absolute;z-index:1;width:95%;max-width:650px;right:-16%;bottom:7%;opacity:.18;pointer-events:none;animation:art-drift 9s ease-in-out infinite}.welcome-copy{animation:auth-rise .65s .12s both}.eyebrow{color:#82f0df}.welcome h1{color:#f3f6ff}.welcome-copy p{color:#b9c8dd}.note{background:#132036;border:1px solid #536989;color:#d9e6f8;animation:auth-rise .65s .28s both}
    .form-side{background:linear-gradient(145deg,#10192a,#0c1424);animation:auth-form .65s .15s both}.form-side h2{color:#f0f4ff}.subtitle,.login{color:#a8b7d0}.error{color:#ffc2d0;background:#351b2b;border-color:#713957}
    .error.success{background:#102e2b;border-color:#286d65;color:#adf8e8}.secondary{display:inline-flex;justify-content:center;align-items:center;min-height:44px;margin-top:12px;padding:0 16px;border:1px solid #405675;border-radius:10px;background:#111c2e;color:#8ceee2;font-weight:750;cursor:pointer;transition:transform .2s,border-color .2s,box-shadow .2s}.secondary:hover{transform:translateY(-1px);border-color:#72eadc;box-shadow:0 0 17px #62dfd522}
    .form-side label{color:#d6e0f0}.form-side input{background:#0a1220;border-color:#344461;color:#eef5ff;transition:border-color .2s,box-shadow .2s,transform .2s}.form-side input::placeholder{color:#7587a4}.form-side input:hover{border-color:#6077a0}.form-side input:focus{border-color:#71e9dc;box-shadow:0 0 0 3px #71e9dc25,0 0 20px #71e9dc1b;transform:translateY(-1px)}
    .show-password{background:#152b3c;color:#8bf4e4;transition:transform .2s,box-shadow .2s}.show-password:hover{transform:translateY(-1px);box-shadow:0 0 15px #70eadb33}.submit{background:linear-gradient(105deg,#61e9d9,#8b91ff);color:#07111d;box-shadow:0 0 23px #61e9d933;background-size:180% 180%;transition:background-position .35s,transform .2s,box-shadow .2s}.submit:hover{background-position:100% 0;transform:translateY(-2px);box-shadow:0 0 28px #61e9d966}.login a{color:#84f1e3;transition:color .2s,text-shadow .2s}.login a:hover{color:#c1fff3;text-shadow:0 0 12px #69ebdb77}
    @keyframes auth-enter{from{opacity:0;transform:translateY(16px) scale(.99)}to{opacity:1;transform:translateY(0) scale(1)}}@keyframes auth-rise{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}@keyframes auth-form{from{opacity:0;transform:translateX(14px)}to{opacity:1;transform:translateX(0)}}@keyframes art-drift{0%,100%{transform:translateY(0) rotate(-1deg)}50%{transform:translateY(-12px) rotate(1deg)}}
    @media(max-width:760px){.welcome-art{width:75%;right:-15%;bottom:-3%;opacity:.27}.welcome-copy{margin:28px 0 0}}
    @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}.welcome-video{display:none!important}}
</style>
<style>
    .strength{margin-top:9px}.strength-bar{height:7px;border-radius:99px;background:#1c2740;overflow:hidden}.strength-bar span{display:block;height:100%;width:0;border-radius:99px;transition:width .3s,background .3s}
    .strength small{display:block;margin-top:6px;font-size:13px;color:#a8b7d0}
</style>
<style>
    .auth-neon-lines path{stroke:#91adff;stroke-dasharray:6 17;animation-duration:17s;filter:drop-shadow(0 0 7px #8195ff)}
    .auth-neon-lines path:nth-child(2){stroke:#71f0c9;stroke-dasharray:3 21;animation-duration:31s;animation-direction:alternate;filter:drop-shadow(0 0 8px #5fe9c1)}
</style>
</head>
<body>
    <main class="shell">
        <section class="welcome">
            <video class="welcome-video" autoplay muted loop playsinline preload="none" poster="assets/career-journey.svg" aria-hidden="true"><source src="https://videos.pexels.com/video-files/8939684/8939684-hd_1920_1080_25fps.mp4" type="video/mp4"></video><img class="welcome-art" src="assets/career-journey.svg" alt=""><svg class="auth-neon-lines" viewBox="0 0 800 900" preserveAspectRatio="none" aria-hidden="true"><path d="M-90 170 C180 310 150 80 400 260 S620 420 890 650"/><path d="M-90 760 C150 610 310 820 500 650 S700 700 880 480"/></svg>
            <a class="brand" href="index.php"><span class="brand-mark">↗</span>CareerRoadmap</a>
            <div class="welcome-copy"><div class="eyebrow">A little step toward your future</div><h1>Your journey<br>starts here.</h1><p>Create an account to explore careers, discover your skills, and plan what comes next.</p></div>
            <p class="note">✨ Everyone is welcome. Register and log in to get started.</p>
        </section>
        <section class="form-side">
            <h2><?= $step === 'verify' ? 'Verify your email' : 'Create your account' ?></h2>
            <p class="subtitle"><?= $step === 'verify' ? 'Enter the 6-digit code we sent to your inbox.' : 'Start with your email and password. No name is needed.' ?></p>
            <?php if ($message): ?><p class="error<?= $messageType === 'success' ? ' success' : '' ?>" role="<?= $messageType === 'success' ? 'status' : 'alert' ?>"><?= registrationEscape($message) ?></p><?php endif; ?>
            <?php if ($step === 'verify'): ?>
                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= registrationEscape($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="verify_code">
                    <label for="email">Email address</label><input id="email" type="email" name="email" value="<?= registrationEscape($emailValue) ?>" readonly>
                    <label for="verification_code">Verification code</label><input id="verification_code" type="text" name="verification_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required placeholder="6-digit code">
                    <button class="submit" type="submit">Verify and create account</button>
                </form>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= registrationEscape($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="resend_code">
                    <input type="hidden" name="email" value="<?= registrationEscape($emailValue) ?>">
                    <button class="secondary" type="submit">Resend code</button>
                    <a class="secondary" href="register.php">Use a different email</a>
                </form>
            <?php else: ?>
                <form method="post" autocomplete="on">
                    <input type="hidden" name="csrf_token" value="<?= registrationEscape($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="send_code">
                    <label for="email">Email address</label><input id="email" type="email" name="email" autocomplete="email" maxlength="150" required value="<?= registrationEscape($emailValue) ?>" placeholder="you@example.com">
                    <label for="password">Password</label><div class="password-wrap"><input id="password" type="password" name="password" autocomplete="new-password" required minlength="8" placeholder="At least 8 characters"><button class="show-password" type="button" id="togglePassword">Show</button></div>
                    <?php /* [ashasuborna] Password strength meter (#3) */ ?>
                    <div class="strength" aria-live="polite"><div class="strength-bar"><span id="strengthFill"></span></div><small id="strengthText">Password strength: type a password</small></div>
                    <button class="submit" type="submit">Send verification code</button>
                </form>
            <?php endif; ?>
            <p class="login">Already have an account? <a href="login.php">Log in</a></p>
        </section>
    </main>
    <script>const togglePassword=document.getElementById('togglePassword');if(togglePassword)togglePassword.addEventListener('click',function(){const input=document.getElementById('password'),showing=input.type==='password';input.type=showing?'text':'password';this.textContent=showing?'Hide':'Show';});</script>
    <?php /* [ashasuborna] Password strength meter: scores length, upper/lower case, numbers and symbols (#3) */ ?>
    <script>
    (function () {
        const pass = document.getElementById('password'), fill = document.getElementById('strengthFill'), text = document.getElementById('strengthText');
        if (!pass || !fill || !text) return;
        const levels = [
            {label: 'Too short', color: '#ff6b81', width: 15},
            {label: 'Weak', color: '#ff8e5e', width: 35},
            {label: 'Fair', color: '#ffd166', width: 60},
            {label: 'Good', color: '#7ee0a1', width: 80},
            {label: 'Strong', color: '#48d6a8', width: 100}
        ];
        function score(p) {
            if (p.length < 8) return 0;
            let s = 1;
            if (p.length >= 12) s++;
            if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
            if (/\d/.test(p)) s++;
            if (/[^A-Za-z0-9]/.test(p)) s++;
            return Math.min(4, s);
        }
        pass.addEventListener('input', function () {
            const p = pass.value;
            if (!p) { fill.style.width = '0'; text.textContent = 'Password strength: type a password'; return; }
            const s = score(p), level = levels[s];
            fill.style.width = level.width + '%'; fill.style.background = level.color;
            text.textContent = 'Password strength: ' + level.label + (s < 3 ? ' (add capitals, numbers or symbols)' : '');
        });
    })();
    </script>
<script src="assets/page-navigation.js" defer></script></body>
</html>
