<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') { header('Location: ../login.php'); exit(); }
require_once '../config/db.php';

$error = '';
$grouped = [];
$check = $conn->query("SHOW COLUMNS FROM questions LIKE 'status'");
if (!$check || $check->num_rows === 0) {
    $error = 'Quiz questions are not configured yet. Please contact the administrator.';
} else {
    $labelCheck = $conn->query("SHOW COLUMNS FROM options LIKE 'option_label'");
    if (!$labelCheck || $labelCheck->num_rows === 0) $error = 'Quiz options are not configured yet. Please contact the administrator.';
    $sql = "SELECT q.question_id,q.question_text,o.option_label,o.option_text FROM questions q JOIN options o ON o.question_id=q.question_id WHERE q.status='approved' ORDER BY q.question_id,o.option_id";
    $result = $error ? false : $conn->query($sql);
    if ($result) while ($row = $result->fetch_assoc()) {
        $qid = (int)$row['question_id'];
        if (!isset($grouped[$qid])) $grouped[$qid] = ['question'=>$row['question_text'],'options'=>[]];
        $grouped[$qid]['options'][] = ['label'=>$row['option_label'] ?? '', 'text'=>$row['option_text']];
    }
    $grouped = array_values(array_filter($grouped, fn($q) => count($q['options']) >= 2));
    if (count($grouped) > 0) {
        $freshQuiz = !isset($_GET['step']) && $_SERVER['REQUEST_METHOD'] !== 'POST';
        if ($freshQuiz || empty($_SESSION['quiz_questions'])) {
            $_SESSION['quiz_questions'] = $grouped;
            $_SESSION['quiz_answers'] = [];
            unset($_SESSION['quiz_result_report'], $_SESSION['quiz_result_text'], $_SESSION['quiz_result_saved']);
        } else {
            // Keep the quiz snapshot stable while the student moves between steps.
            $grouped = $_SESSION['quiz_questions'];
        }
    } else $error = 'No approved quiz questions are available yet. Please check back later.';
}

$total = count($grouped);
$step = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT);
if ($step === false || $step === null) $step = 0;
$step = max(0, min($step, max(0, $total - 1)));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $postedStep = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);
    if ($postedStep !== false && $postedStep !== null && isset($_POST['answers'][$postedStep])) {
        $_SESSION['quiz_answers'][$postedStep] = (string)$_POST['answers'][$postedStep];
    }
    $direction = $_POST['direction'] ?? 'next';
    if ($direction === 'back') {
        header('Location: quiz.php?step=' . max(0, $step - 1));
        exit();
    }
    header('Location: quiz.php?step=' . min($total - 1, $step + 1));
    exit();
}

$current = $grouped[$step] ?? null;
$savedAnswer = $_SESSION['quiz_answers'][$step] ?? '';
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Career Interest Quiz</title>
    <link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
    <style>
        body{position:relative;isolation:isolate;margin:0;min-height:100vh;padding:32px 18px 132px;font-family:Inter,"Segoe UI",Arial,sans-serif;overflow-x:hidden}
        .quiz-sky{position:fixed;inset:0;z-index:-1;overflow:hidden;pointer-events:none;background:radial-gradient(ellipse at 16% 18%,#34266a88,transparent 42%),radial-gradient(ellipse at 83% 70%,#07505b66,transparent 38%),linear-gradient(145deg,#070b18,#10172c 58%,#111025)}
        .quiz-sky:before,.quiz-sky:after{content:"";position:absolute;inset:-35%;background-image:radial-gradient(2px 2px at 12% 22%,#e5faffbb 55%,transparent 65%),radial-gradient(1px 1px at 32% 75%,#fff9 55%,transparent 65%),radial-gradient(2px 2px at 71% 18%,#a9f9ffbb 55%,transparent 65%),radial-gradient(1px 1px at 84% 66%,#fff9 55%,transparent 65%),radial-gradient(1px 1px at 54% 46%,#e1d7ffbb 55%,transparent 65%);background-size:390px 310px;animation:star-drift 48s linear infinite}
        .quiz-sky:after{opacity:.45;background-size:250px 220px;animation:star-drift 72s linear reverse infinite}
        .sky-moon{position:absolute;top:12%;right:14%;width:clamp(70px,12vw,138px);aspect-ratio:1;border-radius:50%;background:radial-gradient(circle at 35% 35%,#f4f0ff,#b5c5f5 45%,#8788bd 72%,#555686);box-shadow:0 0 28px #d8d9ff88,0 0 110px #8b91ff3d;opacity:.78;animation:moon-breathe 8s ease-in-out infinite}
        .sky-orbit{position:absolute;top:calc(12% - 14px);right:calc(14% - 14px);width:clamp(98px,15vw,166px);aspect-ratio:1;border:1px solid #d7dbff42;border-radius:50%;transform:rotate(-24deg) scaleY(.55);box-shadow:0 0 22px #8194ff22}
        .sky-star{position:absolute;width:120px;height:2px;opacity:0;background:linear-gradient(90deg,transparent,#c5f7ff,#fff,transparent);filter:drop-shadow(0 0 5px #9df5ff);transform:rotate(-28deg);animation:shooting-star 9s ease-in infinite}
        .sky-star.one{top:24%;left:8%;animation-delay:1s}.sky-star.two{top:48%;right:10%;animation-delay:5s;animation-duration:11s}
        .quiz-shell{position:relative;z-index:1;width:min(760px,100%);margin:20px auto;padding:clamp(22px,5vw,42px);border:1px solid #344363;border-radius:26px;background:linear-gradient(145deg,rgba(18,25,45,.92),rgba(14,21,38,.92));box-shadow:0 20px 70px #02061188,0 0 32px #7761ff20;backdrop-filter:blur(14px);animation:neonRise .35s ease both}
        .quiz-top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:28px}.quiz-kicker{margin:0;color:#83f2e3;font-size:.82rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.quiz-top h1{margin:8px 0 0;font-size:clamp(1.5rem,4vw,2rem)}
        .step-count{white-space:nowrap;padding:9px 13px;border:1px solid #36536b;border-radius:999px;background:#102536;color:#a6e9ff;font-weight:750}
        .progress-track{height:8px;margin:0 0 30px;overflow:hidden;border-radius:999px;background:#202a43}.progress-fill{height:100%;border-radius:inherit;background:linear-gradient(90deg,#57e9d7,#9a7cff);box-shadow:0 0 16px #69e8dd77;transition:width .35s ease}
        .question-card{padding:clamp(20px,4vw,30px);border:1px solid #344363;border-radius:20px;background:linear-gradient(145deg,#141e34,#111a2e)}.question-card h2{margin:0 0 22px;font-size:clamp(1.2rem,3vw,1.55rem);line-height:1.5}
        .choice-list{display:grid;gap:12px}.choice{position:relative;display:flex;align-items:center;gap:13px;min-height:56px;padding:12px 16px;border:1px solid #344363;border-radius:15px;background:#0d1629;color:#dfe7ff;cursor:pointer;transition:border-color .18s ease,background .18s ease,transform .18s ease,box-shadow .18s ease}.choice:hover{transform:translateY(-1px);border-color:#67dcd6;background:#102335}.choice:has(input:checked){border-color:#74f0e1;background:linear-gradient(100deg,#12312f,#1d2440);box-shadow:0 0 18px #5ce7d522}.choice input{accent-color:#6de8db;width:18px;height:18px;flex:0 0 auto}.choice-label{display:grid;place-items:center;min-width:32px;height:32px;border:1px solid #39546d;border-radius:10px;color:#8ff4e6;font-weight:800}
        .quiz-actions{position:fixed;left:50%;bottom:max(14px,env(safe-area-inset-bottom));z-index:25;display:flex;justify-content:space-between;align-items:center;gap:12px;width:min(760px,calc(100vw - 36px));margin:0;padding:12px 14px;border:1px solid #405376;border-radius:18px;background:rgba(10,16,31,.9);box-shadow:0 12px 38px #0009,0 0 24px #62e8dc1a;backdrop-filter:blur(16px);transform:translateX(-50%)}.quiz-actions button{min-height:46px;padding:11px 20px;border-radius:14px}.quiz-actions .back-step{border:1px solid #3a496a;background:#18233a;color:#dbe5ff;box-shadow:none}.quiz-actions .back-step:disabled{opacity:.45;cursor:not-allowed;transform:none}.error{padding:14px;border:1px solid #713957;border-radius:12px;background:#351b2c;color:#ffc1d0}.home-row{position:fixed;top:18px;right:22px;z-index:30;margin:0}
        .analysis-overlay{position:fixed;inset:0;z-index:50;display:grid;place-items:center;padding:24px;background:rgba(4,9,20,.78);backdrop-filter:blur(10px);animation:analysis-fade .22s ease both}.analysis-overlay[hidden]{display:none}.analysis-panel{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);align-items:center;gap:clamp(20px,4vw,38px);width:min(820px,100%);padding:clamp(20px,4vw,34px);border:1px solid #385074;border-radius:24px;background:linear-gradient(145deg,#14213a,#10182b);box-shadow:0 0 38px #70eada20,0 24px 70px #0009}.analysis-visual{position:relative;min-height:230px;aspect-ratio:16/11;overflow:hidden;border:1px solid #3e5875;border-radius:18px;background:radial-gradient(ellipse at 70% 20%,#285367,#11182e 68%);box-shadow:inset 0 0 34px #0008,0 14px 36px #0005}.analysis-visual:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,#07101a00 48%,#07101acc 100%);pointer-events:none}.analysis-video{display:block;width:100%;height:100%;min-height:230px;position:absolute;inset:0;object-fit:cover;opacity:.76}.analysis-visual.video-fallback .analysis-video{display:none}.analysis-fallback-art{position:absolute;inset:0;display:grid;place-items:center;overflow:hidden}.analysis-fallback-art:before{content:"";position:absolute;width:60%;aspect-ratio:1;border:1px solid #7ef2e255;border-radius:50%;box-shadow:0 0 38px #72ecdd44,inset 0 0 30px #72ecdd22;animation:analysis-orbit 8s linear infinite}.analysis-fallback-art:after{content:"✦";position:absolute;color:#e0ffff;font-size:48px;text-shadow:0 0 24px #79f4e8;animation:analysis-float 3s ease-in-out infinite}.analysis-visual-caption{position:absolute;z-index:2;left:16px;right:16px;bottom:14px;display:flex;justify-content:space-between;align-items:center;gap:8px;color:#f4f8ff;font-size:.8rem;font-weight:750;letter-spacing:.04em}.analysis-live{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border:1px solid #b9fff355;border-radius:999px;background:#081820ad;color:#9af4e4;backdrop-filter:blur(8px)}.analysis-live:before{content:"";width:7px;height:7px;border-radius:50%;background:#79f2db;box-shadow:0 0 12px #79f2db;animation:analysis-pulse 1.2s ease-in-out infinite}.analysis-copy{min-width:0}.analysis-spinner{width:34px;height:34px;margin:0 0 18px;border:3px solid #334562;border-top-color:#71efe0;border-right-color:#a291ff;border-radius:50%;animation:analysis-spin .8s linear infinite}.analysis-panel h2{margin:0 0 9px;color:#f0f4ff;font-size:clamp(1.35rem,3vw,1.75rem);line-height:1.25}.analysis-panel p{margin:0;color:#aebbd3;line-height:1.6}.analysis-steps{display:grid;gap:9px;margin:20px 0 0;padding:0;list-style:none}.analysis-steps li{display:flex;align-items:center;gap:10px;color:#c2cee4;font-size:.92rem}.analysis-steps li:before{content:"";width:8px;height:8px;flex:0 0 auto;border:1px solid #72eadd;border-radius:50%;box-shadow:0 0 12px #72eadd77;animation:analysis-pulse 1.6s ease-in-out infinite}.analysis-steps li:nth-child(2):before{animation-delay:.25s}.analysis-steps li:nth-child(3):before{animation-delay:.5s}.analysis-panel small{display:block;margin-top:18px;color:#83e9dd;line-height:1.5}@keyframes analysis-spin{to{transform:rotate(360deg)}}@keyframes analysis-fade{from{opacity:0}to{opacity:1}}@keyframes analysis-pulse{0%,100%{opacity:.45;transform:scale(.82)}50%{opacity:1;transform:scale(1.18)}}@keyframes analysis-float{0%,100%{transform:translateY(5px) scale(.95)}50%{transform:translateY(-8px) scale(1.08)}}@keyframes analysis-orbit{to{transform:rotate(360deg)}}
        .analysis-error{margin:16px 0 0;padding:12px 14px;border:1px solid #ff9eaf70;border-radius:13px;background:#401e30;color:#ffd4df;line-height:1.5}.analysis-error[hidden]{display:none}
        @media(max-width:640px){.analysis-overlay{padding:14px}.analysis-panel{grid-template-columns:1fr;gap:18px;padding:17px;border-radius:20px}.analysis-visual{min-height:0;aspect-ratio:16/8}.analysis-video{min-height:0}.analysis-spinner{margin-bottom:12px}.analysis-steps{grid-template-columns:1fr 1fr;gap:8px;margin-top:15px}.analysis-steps li{font-size:.82rem}}
        @media(max-width:520px){body{padding:14px 14px 124px}.quiz-shell{margin:8px auto}.quiz-top{align-items:flex-start}.quiz-actions{width:calc(100vw - 20px);padding:10px;border-radius:16px}.quiz-actions button{padding:10px 13px}.choice{padding:10px 12px}.analysis-overlay{padding:10px}.analysis-panel{max-height:calc(100dvh - 20px);overflow-y:auto}.analysis-visual{aspect-ratio:16/7}.analysis-steps{grid-template-columns:1fr 1fr}}
        @keyframes star-drift{from{transform:translate3d(0,0,0)}to{transform:translate3d(70px,45px,0)}}@keyframes moon-breathe{0%,100%{transform:scale(1);filter:brightness(.94)}50%{transform:scale(1.04);filter:brightness(1.12)}}@keyframes shooting-star{0%,72%{opacity:0;transform:translate3d(-25vw,-7vh,0) rotate(-28deg)}76%{opacity:.9}88%{opacity:0;transform:translate3d(60vw,23vh,0) rotate(-28deg)}100%{opacity:0}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
    </style>
</head>
<body class="neon-app">
<div class="quiz-sky" aria-hidden="true"><span class="sky-moon"></span><span class="sky-orbit"></span><i class="sky-star one"></i><i class="sky-star two"></i></div>
<main class="quiz-shell">
    <header class="quiz-top"><div><p class="quiz-kicker">Career discovery · Quiz</p><h1>Your interests, one step at a time</h1></div><?php if (!$error): ?><span class="step-count"><?= $step + 1 ?> / <?= $total ?></span><?php endif; ?></header>
    <?php if ($error): ?><p class="error"><?= $escape($error) ?></p><?php elseif ($current): ?>
        <div class="progress-track" aria-label="Quiz progress"><div class="progress-fill" style="width:<?= (int)round((($step + 1) / $total) * 100) ?>%"></div></div>
        <form id="quizForm" method="post" action="quiz.php?step=<?= $step ?>">
            <input type="hidden" name="step" value="<?= $step ?>">
            <?php if ($step === $total - 1): foreach ($_SESSION['quiz_answers'] ?? [] as $answerIndex => $answerValue): if ((int)$answerIndex !== $step): ?><input type="hidden" name="answers[<?= (int)$answerIndex ?>]" value="<?= $escape($answerValue) ?>"><?php endif; endforeach; endif; ?>
            <section class="question-card" aria-labelledby="question-title">
                <h2 id="question-title"><?= $step + 1 ?>. <?= $escape($current['question']) ?></h2>
                <div class="choice-list">
                    <?php foreach ($current['options'] as $optionIndex => $option): $value = (string)$option['text']; ?>
                        <label class="choice"><input type="radio" name="answers[<?= $step ?>]" value="<?= $escape($value) ?>" required <?= $savedAnswer === $value ? 'checked' : '' ?>><?php if ($option['label'] !== ''): ?><span class="choice-label"><?= $escape($option['label']) ?></span><?php else: ?><span class="choice-label"><?= chr(65 + $optionIndex) ?></span><?php endif; ?><span><?= $escape($value) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </section>
            <div class="quiz-actions">
                <?php if ($step > 0): ?><button class="back-step" type="submit" name="direction" value="back" formnovalidate>← Back</button><?php else: ?><button class="back-step" type="button" disabled>← Back</button><?php endif; ?>
                <?php if ($step === $total - 1): ?><button type="submit">Finish quiz →</button><?php else: ?><button type="submit" name="direction" value="next">Next question →</button><?php endif; ?>
            </div>
        </form>
        <?php if ($step === $total - 1): ?><p class="analysis-error" id="analysisError" role="alert" hidden></p><?php endif; ?>
    <?php endif; ?>
</main>
<?php if (!$error && $current && $step === $total - 1): ?>
<div class="analysis-overlay" id="analysisOverlay" role="status" aria-live="polite" hidden>
    <div class="analysis-panel">
        <div class="analysis-visual" id="analysisVisual">
            <video class="analysis-video" id="analysisVideo" autoplay muted loop playsinline preload="metadata" aria-label="Career research and analysis video">
                <source src="https://videos.pexels.com/video-files/7308102/7308102-hd_1920_1080_24fps.mp4" type="video/mp4">
            </video>
            <div class="analysis-fallback-art" aria-hidden="true"></div>
            <div class="analysis-visual-caption"><span>YOUR NEXT CHAPTER</span><span class="analysis-live">ANALYZING</span></div>
        </div>
        <div class="analysis-copy">
            <div class="analysis-spinner" aria-hidden="true"></div>
            <h2>Preparing your career insights</h2>
            <p>Reviewing your answers and shaping a practical roadmap around your interests.</p>
            <ul class="analysis-steps" aria-hidden="true"><li>Reviewing your answers</li><li>Matching career paths</li><li>Building your roadmap</li></ul>
            <small>Your result will appear here as soon as the analysis is ready.</small>
        </div>
    </div>
</div>
<script>
const analysisVideo = document.getElementById('analysisVideo');
if (analysisVideo) {
    analysisVideo.addEventListener('error', function () {
        document.getElementById('analysisVisual').classList.add('video-fallback');
    });
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) analysisVideo.pause();
}
document.getElementById('quizForm').addEventListener('submit', function (event) {
    if (event.submitter && event.submitter.name === 'direction' && event.submitter.value === 'back') return;
    event.preventDefault();
    if (this.dataset.submitting === '1') return;
    this.dataset.submitting = '1';
    const overlay = document.getElementById('analysisOverlay');
    const errorBox = document.getElementById('analysisError');
    const finishButton = this.querySelector('button[type="submit"]:not([name="direction"])');
    const answers = {};
    for (const [key, value] of new FormData(this).entries()) {
        const match = key.match(/^answers\[(\d+)\]$/);
        if (match) answers[match[1]] = value;
    }
    errorBox.hidden = true;
    overlay.hidden = false;
    if (finishButton) { finishButton.disabled = true; finishButton.setAttribute('aria-busy', 'true'); }

    (async () => {
        try {
            const response = await fetch('quiz_result.php?stream=1', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/x-ndjson'},
                body: JSON.stringify({answers})
            });
            if (!response.ok || !response.body) throw new Error('Could not start your career analysis. Please try again.');
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let pending = '';
            let reportReady = false;
            const readEvent = (line) => {
                if (!line.trim()) return;
                const result = JSON.parse(line);
                if (result.error) throw new Error(result.error);
                if (result.recommendation || result.text) reportReady = true;
            };
            while (true) {
                const {value, done} = await reader.read();
                pending += decoder.decode(value || new Uint8Array(), {stream: !done});
                const lines = pending.split('\n');
                pending = lines.pop();
                for (const line of lines) readEvent(line);
                if (done) break;
            }
            if (pending) readEvent(pending);
            if (!reportReady) throw new Error('The analysis returned no result. Please try again.');
            window.location.replace('quiz_result.php?ready=1');
        } catch (error) {
            overlay.hidden = true;
            errorBox.textContent = error.message || 'Could not prepare your result. Please try again.';
            errorBox.hidden = false;
            this.dataset.submitting = '';
            if (finishButton) { finishButton.disabled = false; finishButton.removeAttribute('aria-busy'); }
        }
    })();
});
</script>
<?php endif; ?>
<div class="home-row"><a href="dashboard.php" class="home-button">⌂ Home</a></div>
<script src="../assets/page-navigation.js" defer></script></body>
</html>
