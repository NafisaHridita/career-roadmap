<?php
session_start();
$loggedIn = isset($_SESSION['user_id']);
$startUrl = !$loggedIn ? 'register.php' : (($_SESSION['role'] ?? '') === 'student' ? 'student/quiz.php' : 'admin/dashboard.php');
$startLabel = !$loggedIn ? 'Start your career test' : ((($_SESSION['role'] ?? '') === 'student') ? 'Start your career test' : 'Open dashboard');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080d18">
    <title>CareerRoadmap | Find a path that fits you</title>
    <style>
        :root{color-scheme:dark;--ink:#f6f5f1;--muted:#c3c1bf;--gold:#ded15c;--line:#454545;--surface:#191919}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;min-height:100vh;background:#111;color:var(--ink);font-family:Inter,"Segoe UI",Arial,sans-serif}
        a{color:inherit}.site-header{height:82px;display:flex;align-items:center;gap:clamp(24px,4vw,64px);padding:0 clamp(22px,4.5vw,82px);border-bottom:1px solid #ffffff12;background:#151515}
        .brand{display:inline-flex;align-items:center;gap:10px;text-decoration:none;font-size:23px;font-weight:800;letter-spacing:-1.2px;white-space:nowrap}.brand-mark{display:grid;place-items:center;width:35px;height:35px;border:1px solid #d9cc55;border-radius:11px;color:var(--gold);font-size:18px;box-shadow:0 0 18px #ded15c22}
        .main-nav{display:flex;align-items:center;gap:clamp(18px,3vw,48px);flex:1}.main-nav a,.login-link{color:#e5e3e0;text-decoration:none;font-size:15px;font-weight:600;transition:color .18s}.main-nav a:hover,.login-link:hover{color:var(--gold)}.header-actions{display:flex;align-items:center;gap:18px;white-space:nowrap}.header-register{display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border:1px solid #66eadb88;border-radius:9px;background:linear-gradient(145deg,#102238,#151933);color:#8bf3e5;text-decoration:none;font-size:14px;font-weight:800;box-shadow:0 0 17px #62e9db15;transition:transform .2s,border-color .2s,box-shadow .2s,background .2s}.header-register:hover{transform:translateY(-2px);border-color:#8ffff0;background:linear-gradient(110deg,#123347,#242049);box-shadow:0 0 23px #66eadb44}.header-cta{padding:12px 19px;border-radius:9px;background:#f5f5f3;color:#151515;text-decoration:none;font-weight:750;transition:transform .18s,background .18s,box-shadow .18s}.header-cta:hover,.primary-cta:hover{transform:translateY(-2px);background:#fff;box-shadow:0 9px 28px #0005}
        .hero{position:relative;isolation:isolate;min-height:calc(100svh - 82px);overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr) minmax(440px,.98fr);align-items:center;gap:35px;padding:60px clamp(22px,4.5vw,82px) 45px;background:radial-gradient(ellipse at 83% 65%,#2b2927 0,transparent 35%),#363636}
        .hero-copy{position:relative;z-index:2;max-width:800px;padding-bottom:20px}.eyebrow{display:inline-flex;align-items:center;gap:10px;margin-bottom:20px;color:#e4dc84;text-transform:uppercase;letter-spacing:.17em;font-size:12px;font-weight:800}.eyebrow:before{content:"";width:24px;height:1px;background:var(--gold)}
        h1{max-width:760px;margin:0;font-size:clamp(54px,7.4vw,112px);line-height:.98;letter-spacing:-.065em;font-weight:650}.hero-copy h1 span{color:#b9b5ad}.intro{max-width:650px;margin:28px 0 29px;color:#e1dfdc;font-size:clamp(17px,1.55vw,22px);line-height:1.58;letter-spacing:-.015em}.primary-cta{display:inline-flex;align-items:center;justify-content:center;gap:14px;min-width:310px;min-height:60px;padding:16px 25px;border-radius:10px;background:#f7f7f5;color:#161616;text-decoration:none;font-size:17px;font-weight:750;transition:transform .2s,box-shadow .2s}.primary-cta span{font-size:21px;transition:transform .2s}.primary-cta:hover span{transform:translateX(4px)}
        .hero-visual{position:relative;z-index:1;min-height:590px;display:grid;place-items:center}.orbit{position:absolute;width:min(45vw,650px);aspect-ratio:1;border:1px solid #ded15c88;border-radius:50%;transform:rotate(-19deg) scaleY(.58);animation:orbit-drift 11s ease-in-out infinite}.orbit:before,.orbit:after{content:"";position:absolute;inset:9%;border:1px solid #d5c85244;border-radius:50%}.orbit:after{inset:19%;border-color:#ffffff1a}.planet{position:absolute;width:clamp(235px,26vw,380px);aspect-ratio:1;border-radius:50%;background:linear-gradient(145deg,#aba399,#635d56);opacity:.76;right:3%;top:13%;filter:drop-shadow(0 0 36px #0005)}
        .preview-board{position:absolute;right:-2%;top:16%;width:min(100%,650px);height:390px;padding:27px;border:1px solid #85817b88;border-radius:21px;background:repeating-linear-gradient(155deg,transparent 0 16px,#ffffff08 17px 18px),linear-gradient(145deg,#222 10%,#191919 68%);box-shadow:0 26px 75px #0006;transform:perspective(1000px) rotateY(-7deg);opacity:.76}.board-top{display:flex;justify-content:space-between;align-items:center;color:#c4c0b9;font-weight:800;font-size:18px}.board-dots{display:flex;gap:8px}.board-dots i{width:9px;height:9px;background:#79756f;border-radius:50%}.board-line{height:1px;background:#ffffff21;margin:20px 0}.board-label{color:#a9a59f;font-size:12px}.board-question{font-size:22px;max-width:420px;line-height:1.35;margin:12px 0 20px}.ghost-options{display:grid;grid-template-columns:1fr 1fr;gap:12px}.ghost-option{height:54px;border:1px solid #66645f;border-radius:12px;background:#ffffff07}.ghost-option:before{content:"";display:block;width:22px;height:22px;margin:15px;border:1px solid #85817b;border-radius:7px}.board-footer{position:absolute;bottom:24px;left:27px;right:27px;display:flex;align-items:center;gap:14px;color:#ada9a3;font-size:12px}.board-progress{height:5px;flex:1;border-radius:9px;background:#424242;overflow:hidden}.board-progress:after{content:"";display:block;width:62%;height:100%;background:var(--gold);border-radius:9px}
        .quiz-float{position:relative;z-index:3;width:min(360px,80%);margin:125px 10% 0 0;padding:24px 23px 21px;border:1px solid #e9e6df;border-radius:23px;background:#171717;box-shadow:0 24px 70px #0009;animation:float-in .75s cubic-bezier(.2,.75,.25,1) both}.float-brand{display:flex;align-items:center;justify-content:center;gap:8px;padding-bottom:17px;border-bottom:1px solid #ffffff1b;font-size:17px;font-weight:800}.float-brand b{color:var(--gold);font-size:20px}.float-step{display:flex;justify-content:space-between;margin:17px 0 6px;color:#aaa69e;font-size:12px}.float-question{margin:0 0 16px;font-size:18px;line-height:1.4}.float-answer{display:flex;align-items:center;gap:11px;margin:9px 0;padding:10px;border:1px solid #555;border-radius:8px;color:#dedbd5;font-size:13px}.float-answer span{display:grid;place-items:center;width:24px;height:24px;border:1px solid #9c9891;border-radius:6px;color:#f4f1eb;font-weight:800}.float-answer.selected{border-color:#d8ce69;background:#d8ce6912}.float-bottom{height:5px;margin-top:17px;background:#424242;border-radius:9px}.float-bottom:after{content:"";display:block;width:45%;height:100%;border-radius:inherit;background:linear-gradient(90deg,#ded15c,#f1e7a3)}
        .numbers{position:relative;z-index:2;display:grid;grid-template-columns:minmax(205px,1.15fr) repeat(3,minmax(85px,.5fr));align-items:center;gap:22px;max-width:760px;margin-top:54px}.numbers-title{grid-column:1/-1;margin:0;color:var(--gold);font-size:14px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.rating{min-height:78px;padding-left:19px;border-left:2px solid #eee}.rating small,.stat small{display:block;color:#c5c2bd;font-size:12px}.stars{margin:6px 0;color:#f3f1ea;font-size:23px;letter-spacing:2px}.rating p{margin:4px 0 0;color:#c5c2bd;font-size:12px}.stat strong{display:block;margin-bottom:5px;font-size:24px;font-weight:650;letter-spacing:-.04em}.stat small{line-height:1.45}
        .how{padding:90px clamp(22px,8vw,140px);background:#111}.how-head{max-width:720px;margin:0 auto 38px;text-align:center}.how-head small{color:var(--gold);font-weight:800;text-transform:uppercase;letter-spacing:.16em}.how-head h2{margin:12px 0;font-size:clamp(32px,5vw,54px);letter-spacing:-.04em}.how-head p{color:#aaa7a1;line-height:1.7}.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;max-width:1160px;margin:auto}.step-card{padding:25px;border:1px solid #383838;border-radius:17px;background:linear-gradient(145deg,#1c1c1c,#151515);transition:transform .2s,border-color .2s}.step-card:hover{transform:translateY(-5px);border-color:#9f9548}.step-no{color:var(--gold);font-size:13px;font-weight:800;letter-spacing:.12em}.step-card h3{margin:18px 0 9px;font-size:20px}.step-card p{margin:0;color:#aaa7a1;line-height:1.65}.site-footer{display:flex;justify-content:space-between;gap:20px;padding:25px clamp(22px,4.5vw,82px);border-top:1px solid #ffffff18;color:#a9a6a0;font-size:13px}.site-footer a{color:#e6df8d;text-decoration:none}
        @keyframes float-in{from{opacity:0;transform:translateY(22px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}@keyframes orbit-drift{0%,100%{transform:rotate(-19deg) scaleY(.58) translateY(0)}50%{transform:rotate(-13deg) scaleY(.58) translateY(-13px)}}
        @media(max-width:1050px){.site-header{gap:24px}.main-nav{gap:20px}.hero{grid-template-columns:1fr minmax(350px,.8fr);padding-top:48px}.hero-visual{min-height:510px}.preview-board{right:-8%;height:340px}.numbers{grid-template-columns:1fr repeat(3,.55fr);gap:14px}}
        @media(max-width:760px){.site-header{height:auto;min-height:72px;flex-wrap:wrap;gap:12px;padding:15px 20px}.brand{font-size:20px}.main-nav{order:3;flex-basis:100%;overflow:auto;gap:22px;padding:7px 0 2px}.main-nav a{font-size:13px;white-space:nowrap}.header-actions{margin-left:auto;gap:10px}.login-link{font-size:13px}.header-register{padding:9px 11px;font-size:13px}.header-cta{padding:10px 13px;font-size:13px}.hero{min-height:auto;grid-template-columns:1fr;gap:0;padding:58px 22px 45px}.hero-copy{padding:0}.hero-copy h1{font-size:clamp(52px,13vw,86px)}.intro{margin:20px 0 23px}.primary-cta{width:100%;min-width:0}.hero-visual{min-height:410px;margin-top:8px}.planet{width:255px;right:0;top:5%}.orbit{width:90vw}.preview-board{top:15%;right:-4%;width:100%;height:260px;padding:18px}.board-question{font-size:17px}.ghost-option{height:38px}.ghost-option:before{margin:8px}.board-footer{bottom:15px;left:18px;right:18px}.quiz-float{width:min(330px,87%);margin:100px 9% 0 0;padding:19px}.float-answer{margin:7px 0;padding:8px}.numbers{grid-template-columns:repeat(3,1fr);gap:18px 11px;margin-top:32px}.numbers-title{grid-column:1/-1}.rating{grid-column:1/-1;min-height:0}.stat strong{font-size:20px}.stat small{font-size:11px}.how{padding:66px 20px}.steps{grid-template-columns:1fr}.site-footer{flex-direction:column}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
    </style>
    <style>
        :root{--ink:#f2f6ff;--muted:#aab9d1;--gold:#72f2df;--line:#293b58;--surface:#111a2c}
        body{background:#080d18;color:#f2f6ff}
        .site-header{background:rgba(8,13,25,.94);border-bottom-color:#7896c01c;box-shadow:0 8px 35px #02061155}
        .brand-mark{border-color:#5de8d977;color:#85f8e8;box-shadow:0 0 18px #58eedc35;background:linear-gradient(145deg,#122538,#1c1c3c)}
        .main-nav a,.login-link{color:#cbd5e8}.main-nav a:hover,.login-link:hover{color:#8af8e7}
        .header-cta{background:linear-gradient(110deg,#65eadb,#9d91ff);color:#09121c;box-shadow:0 0 20px #64e9df2e}
        .header-cta:hover,.primary-cta:hover{background:linear-gradient(110deg,#a0fff0,#c6bdff);box-shadow:0 0 27px #6decdc61}
        .hero{background:radial-gradient(ellipse at 77% 24%,#172a4a 0,transparent 34%),radial-gradient(ellipse at 91% 82%,#211b43 0,transparent 31%),linear-gradient(125deg,#0b1221,#0e1628 57%,#101529)}
        .hero:before{content:"";position:absolute;inset:0;z-index:-1;pointer-events:none;opacity:.19;background-image:linear-gradient(#7894bd12 1px,transparent 1px),linear-gradient(90deg,#7894bd12 1px,transparent 1px);background-size:52px 52px;mask-image:linear-gradient(90deg,transparent 15%,#000 100%)}
        .eyebrow,.numbers-title,.how-head small,.step-no{color:#79efdf}
        /* Editorial display heading: expressive, but still clear and professional. */
        .hero-copy h1{max-width:800px;color:#f1f5ff;font-family:"Palatino Linotype","Book Antiqua",Georgia,serif;font-size:clamp(58px,7.1vw,108px);font-weight:600;line-height:.99;letter-spacing:-.055em;text-wrap:balance;text-shadow:0 8px 35px #02061170}
        .hero-copy h1 span{display:inline-block;padding:.02em .08em .09em 0;font-family:"Palatino Linotype","Book Antiqua",Georgia,serif;font-style:italic;font-weight:600;letter-spacing:-.06em;background:linear-gradient(100deg,#88f5e6 5%,#adb0ff 80%);-webkit-background-clip:text;background-clip:text;color:transparent;filter:drop-shadow(0 0 22px #6cebdd25)}
        .hero-copy .eyebrow{padding:9px 13px;border:1px solid #5de8d944;border-radius:999px;background:linear-gradient(100deg,#102538a6,#211c43a6);box-shadow:0 0 24px #58eedc12}
        .hero-copy .eyebrow:before{width:18px;height:2px;box-shadow:0 0 9px #72f2df}
        .hero-copy .intro{max-width:610px}
        .intro{color:#b9c6dc}.primary-cta{background:linear-gradient(110deg,#66eadb,#a092ff);color:#0a1220;box-shadow:0 0 25px #65e8dc38;font-weight:800}
        .orbit{border-color:#72eede88;filter:drop-shadow(0 0 12px #5de4da35)}.orbit:before{border-color:#8a83f34d}.orbit:after{border-color:#ffffff20}
        .planet{background:radial-gradient(circle at 32% 30%,#7aecd9,#5653a5 54%,#28254d);opacity:.42;filter:drop-shadow(0 0 36px #5c78ff55)}
        .preview-board{border-color:#5b7293a8;background:repeating-linear-gradient(155deg,transparent 0 16px,#9cb7e00a 17px 18px),linear-gradient(145deg,#111c30ed,#0c1424f2 68%);box-shadow:0 22px 65px #02061199,0 0 32px #668bff16}
        .board-top{color:#dce7fa}.board-line{background:#98b7e026}.board-label{color:#8cf3e3}.ghost-option{border-color:#415372;background:#a4bbdf08}.ghost-option:before{border-color:#627895}.board-footer{color:#aebbd2}.board-progress{background:#24324a}.board-progress:after{background:linear-gradient(90deg,#60e9dc,#a18aff);box-shadow:0 0 12px #78eee477}
        .quiz-float{border-color:#647ba0;background:linear-gradient(145deg,#121e33,#0d1525);box-shadow:0 22px 65px #020611bd,0 0 35px #668bff20}
        .float-brand{border-bottom-color:#8da5cb24}.float-brand b{color:#80f4e4;text-shadow:0 0 15px #66e8db77}.float-step{color:#8fa4c0}.float-answer{border-color:#3b4c69;color:#dbe6fa;background:#ffffff04}.float-answer span{border-color:#627895;color:#a9f8eb}.float-answer.selected{border-color:#76eee2;background:linear-gradient(100deg,#59e6d818,#9585ff1b);box-shadow:0 0 18px #67eddf19}.float-bottom{background:#26344e}.float-bottom:after{background:linear-gradient(90deg,#62ebd9,#9b8cff);box-shadow:0 0 12px #75eede66}
        .rating{border-left-color:#73efdf}.rating small,.stat small,.rating p{color:#9eafc9}.stars{color:#82f1e4}.stat strong{color:#eef3ff}.how{background:linear-gradient(180deg,#080d18,#0a1020)}.how-head p,.step-card p{color:#9eacc4}.how-head h2{color:#f0f4ff}.step-card{border-color:#293854;background:linear-gradient(145deg,#121d31,#0c1424);box-shadow:0 12px 34px #02061142}.step-card:hover{border-color:#56cfc7;box-shadow:0 0 24px #56dfd522,0 16px 35px #02061170}.site-footer{border-top-color:#8195bc20;color:#93a3bc}.site-footer a{color:#8af0e3}
        .hero-illustration{position:absolute;z-index:1;width:min(100%,750px);max-height:590px;object-fit:contain;opacity:.24;pointer-events:none;animation:art-float 8s ease-in-out infinite}
        .hero-video{position:absolute;z-index:0;top:0;right:0;width:58%;height:100%;object-fit:cover;opacity:0;filter:brightness(.72) saturate(1.18);mask-image:linear-gradient(90deg,transparent,#000 17%);pointer-events:none}
        .hero-video--one{animation:hero-clip-one 36s ease-in-out infinite}.hero-video--two{animation:hero-clip-two 36s ease-in-out infinite}.hero-video--three{animation:hero-clip-three 36s ease-in-out infinite}
        @keyframes hero-clip-one{0%,27%,100%{opacity:.72}34%,96%{opacity:0}}
        @keyframes hero-clip-two{0%,29%,62%,100%{opacity:0}36%,55%{opacity:.68}69%{opacity:0}}
        @keyframes hero-clip-three{0%,62%,100%{opacity:0}70%,93%{opacity:.7}}
        .hero:after{content:"";position:absolute;z-index:1;top:0;right:0;width:58%;height:100%;pointer-events:none;background:linear-gradient(90deg,#0b1221 0%,#0b122144 32%,#0b122166 100%)}
        .hero-copy,.hero-visual{z-index:3}.orbit,.planet{z-index:1}.preview-board{z-index:2}.quiz-float{z-index:3}
        .hero-lines{position:absolute;z-index:2;inset:0 0 0 auto;width:60%;height:100%;overflow:visible;pointer-events:none}
        .hero-lines path{fill:none;stroke:#77f2e5;stroke-width:2;stroke-dasharray:10 18;opacity:.8;filter:drop-shadow(0 0 7px #61e9db);animation:neon-line-flow 18s linear infinite}
        .hero-lines path:nth-child(2){stroke:#a89aff;stroke-dasharray:3 19;animation-duration:25s;animation-direction:reverse;filter:drop-shadow(0 0 7px #9785ff)}
        .hero-lines path:nth-child(3){stroke:#8edfff;stroke-width:1;stroke-dasharray:2 24;animation-duration:32s;opacity:.55}
        @keyframes neon-line-flow{to{stroke-dashoffset:-420}}
        @keyframes art-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
        @media(max-width:760px){.hero-video{width:100%;height:100%;mask-image:linear-gradient(180deg,#000,transparent 92%)}.hero:after{width:100%;opacity:.88}.hero-lines{width:100%;opacity:.65}}
        @media(prefers-reduced-motion:reduce){.hero:before{mask-image:none}.hero-video{display:none}.hero-lines path{animation:none;opacity:.35}}
    </style>

</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php"><span class="brand-mark">↗</span>CareerRoadmap</a>
    <nav class="main-nav" aria-label="Main navigation">
        <a href="#career-test">Career Test</a>
        <a href="career/career_list.php">Careers</a>
        <a href="#how-it-works">How it works</a>
    </nav>
    <div class="header-actions">
        <?php if ($loggedIn): ?><a class="login-link" href="<?= ($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php' ?>">My dashboard</a><?php else: ?><a class="login-link" href="login.php">Log in</a><a class="header-register" href="register.php">Register</a><?php endif; ?>
        <a class="header-cta" href="<?= htmlspecialchars($startUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($startLabel, ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</header>

<main>
    <section class="hero" id="career-test">
        <video class="hero-video hero-video--one" autoplay muted loop playsinline preload="metadata" poster="assets/career-journey.svg" aria-hidden="true"><source src="https://videos.pexels.com/video-files/35797466/15177768_1920_1080_24fps.mp4" type="video/mp4"></video>
        <video class="hero-video hero-video--two" autoplay muted loop playsinline preload="metadata" poster="assets/career-journey.svg" aria-hidden="true"><source src="https://videos.pexels.com/video-files/36103513/15311089_1920_1080_24fps.mp4" type="video/mp4"></video>
        <video class="hero-video hero-video--three" autoplay muted loop playsinline preload="metadata" poster="assets/career-journey.svg" aria-hidden="true"><source src="https://videos.pexels.com/video-files/35252878/14935890_1920_1080_24fps.mp4" type="video/mp4"></video>
        <svg class="hero-lines" viewBox="0 0 900 900" preserveAspectRatio="none" aria-hidden="true"><path d="M-80 720 C180 580 220 880 440 650 S710 450 990 180"/><path d="M-40 340 C180 180 300 480 520 310 S760 230 980 80"/><path d="M-50 820 C190 680 310 760 520 590 S760 590 980 430"/></svg>
        <div class="hero-copy">
            <div class="eyebrow">A clearer path starts here</div>
            <h1>Find a career<br><span>that feels like you.</span></h1>
            <p class="intro">Discover what motivates you, explore career possibilities, and get practical next steps for building the skills you need.</p>
            <a class="primary-cta" href="<?= htmlspecialchars($startUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($startLabel, ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">→</span></a>

            <div class="numbers" aria-label="Quiz features">
                <p class="numbers-title">Built around your choices</p>
                <div class="rating"><small>YOUR PACE</small><div class="stars" aria-hidden="true">01 — 02 — 03</div><p>One clear question at a time</p></div>
                <div class="stat"><strong>Back</strong><small>Revisit and edit<br>your answers</small></div>
                <div class="stat"><strong>Explore</strong><small>Learn about<br>career paths</small></div>
                <div class="stat"><strong>Plan</strong><small>See skills and<br>learning steps</small></div>
            </div>
        </div>

        <div class="hero-visual" aria-label="Preview of the career quiz">
            <img class="hero-illustration" src="assets/career-journey.svg" alt="Illustration of a learning path leading to a career roadmap"><div class="orbit"></div><div class="planet"></div>
            <div class="preview-board" aria-hidden="true"><div class="board-top"><span>CareerRoadmap</span><span class="board-dots"><i></i><i></i><i></i></span></div><div class="board-line"></div><span class="board-label">YOUR INTERESTS</span><p class="board-question">What kind of work gives you energy?</p><div class="ghost-options"><div class="ghost-option"></div><div class="ghost-option"></div><div class="ghost-option"></div><div class="ghost-option"></div></div><div class="board-footer"><span>Question 2 of 8</span><div class="board-progress"></div></div></div>
            <div class="quiz-float"><div class="float-brand"><b>✳</b> CareerRoadmap</div><div class="float-step"><span>CAREER INTEREST QUIZ</span><span>02 / 08</span></div><h2 class="float-question">Which activity sounds most rewarding?</h2><div class="float-answer"><span>A</span> Solving a tricky problem</div><div class="float-answer selected"><span>B</span> Helping someone learn</div><div class="float-answer"><span>C</span> Making something creative</div><div class="float-answer"><span>D</span> Organizing a team project</div><div class="float-bottom"></div></div>
        </div>
    </section>

    <section class="how" id="how-it-works">
        <div class="how-head"><small>Your next steps</small><h2>From curiosity to a plan.</h2><p>Take a short interest quiz, explore the careers that connect with your answers, and build a learning path at your own pace.</p></div>
        <div class="steps"><article class="step-card"><span class="step-no">01 · DISCOVER</span><h3>Answer one question at a time</h3><p>Move through the quiz at your own pace. Go back whenever you want to change an answer.</p></article><article class="step-card"><span class="step-no">02 · EXPLORE</span><h3>See career possibilities</h3><p>Review career ideas connected to your interests, with details about what each path involves.</p></article><article class="step-card"><span class="step-no">03 · BUILD</span><h3>Make your next move</h3><p>Explore useful skills and a learning roadmap to help turn an idea into an achievable next step.</p></article></div>
    </section>
</main>
<footer class="site-footer"><span>© <?= date('Y') ?> CareerRoadmap</span><span><a href="login.php">Log in</a> · <a href="register.php">Create an account</a></span></footer>
<script src="assets/page-navigation.js" defer></script></body>
</html>
