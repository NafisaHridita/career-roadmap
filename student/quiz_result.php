<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../login.php');
    exit();
}
require_once '../config/db.php';
require_once '../includes/module_helpers.php';
require_once '../ai/generate_recommendation.php';

ini_set('max_execution_time', '210');

if (($_GET['stream'] ?? '') === '1') {
    header('Content-Type: application/x-ndjson; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $emit = static function (array $event): void {
        echo json_encode($event, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n";
        if (ob_get_level() > 0) @ob_flush();
        flush();
    };

    $payload = json_decode((string)file_get_contents('php://input'), true);
    $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
    $questions = $_SESSION['quiz_questions'] ?? [];
    if (!$answers || !$questions) {
        $emit(['error' => 'Quiz answers are missing. Please start the quiz again.']);
        exit;
    }

    $answerText = '';
    foreach ($questions as $index => $question) {
        $answerText .= 'Question: ' . (string)($question['question'] ?? '') . "\n";
        $answerText .= 'Student answer: ' . (string)($answers[$index] ?? 'Not answered') . "\n\n";
    }

    // [ashasuborna] Send the admin's career list to the AI (#30)
    $careerCatalog = [];
    if (ensureCareerTables($conn)) {
        $careerRows = $conn->query('SELECT career_name, description, required_skills FROM careers ORDER BY career_name');
        if ($careerRows) while ($career = $careerRows->fetch_assoc()) $careerCatalog[] = $career;
    }

    $recommendation = careerAiGenerateRecommendation($answerText, $careerCatalog);
    if (!$recommendation['ok']) {
        $emit(['error' => $recommendation['error']]);
        exit;
    }

    $rawResult = $recommendation['text'];
    $report = json_decode($rawResult, true);
    $hasStructuredReport = is_array($report)
        && is_array($report['careers'] ?? null)
        && count($report['careers']) === 5
        && is_array($report['roadmap']['stages'] ?? null)
        && count($report['roadmap']['stages']) === 4;

    if ($hasStructuredReport) {
        $_SESSION['quiz_result_report'] = $report;
        unset($_SESSION['quiz_result_text']);
        $historyLines = [(string)($report['overview'] ?? ''), '', 'CAREER OPTIONS'];
        foreach ($report['careers'] as $career) {
            $historyLines[] = '';
            $historyLines[] = (string)($career['name'] ?? 'Career') . ' — ' . (string)($career['fit_level'] ?? 'Explore');
            $historyLines[] = 'Why it may fit: ' . (string)($career['evidence'] ?? '');
            $historyLines[] = 'Day-to-day work: ' . (string)($career['day_to_day'] ?? '');
            $historyLines[] = 'Skills to build: ' . implode(', ', (array)($career['skills_to_build'] ?? []));
            $historyLines[] = 'First step: ' . (string)($career['first_step'] ?? '');
            $historyLines[] = 'Reality check: ' . (string)($career['reality_check'] ?? '');
        }
        $historyLines[] = '';
        $historyLines[] = '12-WEEK STARTER ROADMAP';
        foreach ($report['roadmap']['stages'] as $stage) {
            $historyLines[] = '';
            $historyLines[] = (string)($stage['timeframe'] ?? '') . ': ' . (string)($stage['title'] ?? '');
            $historyLines[] = (string)($stage['focus'] ?? '');
            foreach ((array)($stage['actions'] ?? []) as $action) $historyLines[] = '- ' . (string)$action;
            $historyLines[] = 'Milestone: ' . (string)($stage['milestone'] ?? '');
        }
        $historyLines[] = '';
        $historyLines[] = 'REALITY CHECK: ' . (string)($report['reality_check'] ?? '');
        $savedText = trim(implode("\n", $historyLines));
        $emit(['recommendation' => $report]);
    } else {
        $savedText = $rawResult;
        $_SESSION['quiz_result_text'] = $rawResult;
        unset($_SESSION['quiz_result_report']);
        $emit(['text' => $rawResult]);
    }

    $saved = false;
    if (ensureRecommendationTable($conn)) {
        $userId = (int)$_SESSION['user_id'];
        $save = $conn->prepare('INSERT INTO career_recommendations (user_id, result_text) VALUES (?, ?)');
        if ($save) {
            $save->bind_param('is', $userId, $savedText);
            $saved = $save->execute();
            $save->close();
        }
    }
    $_SESSION['quiz_result_saved'] = $saved;
    $emit(['done' => true, 'saved' => $saved]);
    exit;
}

$initialReport = is_array($_SESSION['quiz_result_report'] ?? null) ? $_SESSION['quiz_result_report'] : null;
$initialResultText = (string)($_SESSION['quiz_result_text'] ?? '');
$resultWasSaved = !empty($_SESSION['quiz_result_saved']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Career Constellation</title>
    <link rel="stylesheet" href="../assets/cute-theme.css?v=20261115-motion3">
    <style>
        :root{color-scheme:dark;--ink:#f5f2ff;--muted:#b7bdd4;--line:#ffffff20;--panel:#111a33;--mint:#8df1dd;--violet:#bb9bff;--peach:#ffb6a3}
        *{box-sizing:border-box}body.roadmap-result-page{margin:0;min-height:100vh;padding:22px clamp(14px,4vw,42px) 50px;background:radial-gradient(ellipse at 78% 6%,#4a367b55,transparent 32%),radial-gradient(ellipse at 10% 45%,#11665e2e,transparent 33%),linear-gradient(145deg,#080e20,#0b1225 52%,#11132b);color:var(--ink);font:16px/1.6 Inter,"Segoe UI",Arial,sans-serif;overflow-x:hidden}
        .universe{max-width:1180px;margin:auto}.report-top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:0 0 24px}.home-link{display:inline-flex;align-items:center;gap:8px;padding:9px 15px;border:1px solid var(--line);border-radius:999px;color:var(--ink);text-decoration:none;background:#ffffff09;transition:transform .2s,border-color .2s}.home-link:hover{transform:translateY(-2px);border-color:var(--mint)}.top-note{margin:0;color:#abb5d3;font-size:.78rem;letter-spacing:.13em;text-transform:uppercase}
        .hero{position:relative;display:grid;grid-template-columns:1.04fr .96fr;align-items:center;gap:20px;min-height:390px;padding:clamp(24px,5vw,58px);border:1px solid #d8d7ff24;border-radius:32px;background:linear-gradient(115deg,#121d38ed,#1c2140d9 55%,#32264bdb);box-shadow:0 26px 80px #03071266;overflow:hidden}.hero:before{content:"";position:absolute;width:420px;height:420px;right:11%;top:-59%;border-radius:50%;border:1px solid #b69dff36;box-shadow:0 0 0 32px #b69dff08,0 0 0 68px #b69dff06;pointer-events:none}.hero-copy,.hero-scene{position:relative;z-index:1}.eyebrow{display:flex;align-items:center;gap:9px;margin:0 0 17px;color:var(--mint);font-size:.75rem;font-weight:800;letter-spacing:.18em}.eyebrow i{width:8px;height:8px;border-radius:50%;background:var(--mint);box-shadow:0 0 15px var(--mint)}.hero h1{max-width:570px;margin:0;font-size:clamp(2.35rem,5vw,4.35rem);line-height:1.02;letter-spacing:-.055em}.hero h1 em{font-style:normal;color:var(--peach)}.hero-copy>p:not(.eyebrow){max-width:500px;color:#c2c9e0;margin:19px 0 21px}.hero-tags{display:flex;flex-wrap:wrap;gap:9px}.hero-tags span{padding:7px 11px;border:1px solid #ffffff20;border-radius:999px;background:#ffffff08;color:#e3e6f5;font-size:.82rem}.hero-scene{min-width:0;animation:scene-float 6s ease-in-out infinite}.hero-scene svg{display:block;width:100%;max-height:315px;filter:drop-shadow(0 18px 35px #04071170)}.scene-caption{position:absolute;right:5%;bottom:2%;padding:8px 12px;border:1px solid #ffffff20;border-radius:14px;background:#121a35d9;color:#cdd4ec;font-size:.8rem;backdrop-filter:blur(12px)}
        .career-film{display:grid;grid-template-columns:1.05fr .95fr;align-items:center;gap:clamp(20px,4vw,38px);margin-top:18px;padding:clamp(16px,3vw,26px);border:1px solid #ffffff20;border-radius:25px;background:linear-gradient(120deg,#151f39ed,#11192ff2);box-shadow:0 18px 52px #03071238}.video-frame{position:relative;min-height:220px;aspect-ratio:16/9;overflow:hidden;border:1px solid #ffffff22;border-radius:18px;background:radial-gradient(ellipse at 70% 20%,#285367,#11182e 68%);box-shadow:inset 0 0 28px #0007,0 12px 32px #0005}.career-video{display:block;width:100%;height:100%;min-height:220px;position:absolute;inset:0;object-fit:cover}.video-frame.video-fallback .career-video{display:none}.video-fallback-art{position:absolute;inset:0;display:none;place-items:center;color:#abf4e9;font-size:3rem;background:radial-gradient(circle,#354b71,#11182e 68%)}.video-frame.video-fallback .video-fallback-art{display:grid}.video-frame:after{content:"";position:absolute;inset:45% 0 0;background:linear-gradient(transparent,#080f20a8);pointer-events:none}.film-tag{position:absolute;z-index:2;left:13px;top:13px;padding:7px 10px;border:1px solid #ffffff35;border-radius:999px;background:#10192bcc;color:#d6fff6;font-size:.7rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;backdrop-filter:blur(8px)}.film-caption{position:absolute;z-index:2;left:15px;right:15px;bottom:12px;display:flex;justify-content:space-between;gap:10px;color:#f1f5ff;font-size:.8rem}.film-caption a{color:#9ef3e5;text-decoration:none}.film-copy .eyebrow{margin-bottom:10px}.film-copy h2{margin:0;font-size:clamp(1.45rem,3vw,2.1rem);line-height:1.12;letter-spacing:-.035em}.film-copy>p:not(.eyebrow){margin:12px 0 18px;color:#c2c9e0}.mini-route{display:flex;align-items:center;flex-wrap:wrap;gap:8px}.mini-route span{padding:7px 10px;border:1px solid #ffffff20;border-radius:12px;background:#ffffff09;color:#e5e9f6;font-size:.78rem}.mini-route b{color:var(--mint);font-size:.9rem}
        .result-area{margin-top:28px}.overview-card,.career-card,.roadmap-panel,.reality-card{border:1px solid var(--line);border-radius:24px;background:linear-gradient(145deg,#131d36ed,#0f172bfa);box-shadow:0 18px 52px #03071238}.error-card{padding:18px 20px;border:1px solid #ff9eaf70;border-radius:16px;background:#401e30;color:#ffd4df}
        .overview-card{display:grid;grid-template-columns:auto 1fr;gap:16px;align-items:start;padding:22px 24px;margin-bottom:20px}.overview-spark{display:grid;place-items:center;width:42px;height:42px;border-radius:14px;background:#8df1dd18;color:var(--mint);font-size:1.35rem}.overview-card p{margin:0;color:#dce2f3}.section-heading{display:flex;align-items:end;justify-content:space-between;gap:18px;margin:38px 2px 15px}.section-heading h2{margin:0;font-size:clamp(1.5rem,3vw,2rem);letter-spacing:-.035em}.section-heading p{margin:0;color:var(--muted);font-size:.9rem}.career-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.career-card{position:relative;padding:21px;overflow:hidden;transition:transform .28s,border-color .28s,box-shadow .28s}.career-card:hover{transform:translateY(-5px);border-color:#8df1dd70;box-shadow:0 24px 55px #02071380}.career-card:after{content:"";position:absolute;width:130px;height:130px;right:-72px;top:-72px;border:1px solid #c4b6ff30;border-radius:50%;box-shadow:0 0 0 18px #c4b6ff08}.career-title-row{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.career-title-row h3{margin:0;font-size:1.2rem}.fit-badge{flex:none;padding:5px 9px;border:1px solid #8df1dd4b;border-radius:999px;background:#8df1dd12;color:var(--mint);font-size:.72rem}.career-card p{margin:13px 0 0;color:#c3cbe0;font-size:.94rem}.career-card strong{color:#f3efff}.skill-pills{display:flex;flex-wrap:wrap;gap:7px;margin-top:14px}.skill-pill{padding:5px 9px;border-radius:9px;background:#bb9bff16;color:#d9caff;font-size:.76rem}.first-step{margin-top:15px;padding:12px 13px;border-left:2px solid var(--peach);border-radius:0 12px 12px 0;background:#ffb6a30c;color:#e7d9de;font-size:.9rem}.reality-small{color:#aeb7d0!important;font-size:.84rem!important}
        .roadmap-panel{padding:clamp(18px,3vw,30px);margin-top:22px}.roadmap-intro{display:flex;align-items:flex-start;gap:12px}.roadmap-intro .map-icon{display:grid;place-items:center;width:42px;height:42px;flex:none;border-radius:14px;background:#bb9bff18;color:var(--violet);font-size:1.2rem}.roadmap-intro h2{margin:0 0 5px;font-size:1.6rem}.roadmap-intro p{margin:0;color:var(--muted)}.route-diagram{display:block;width:100%;height:auto;margin:20px 0 5px;overflow:visible}.route-path{fill:none;stroke:#7ce7dc;stroke-width:5;stroke-linecap:round;stroke-dasharray:10 11;animation:path-flow 14s linear infinite}.route-node{fill:#131c34;stroke:#8df1dd;stroke-width:4}.route-number{fill:#f5f2ff;font:700 14px Inter,Arial,sans-serif;text-anchor:middle;dominant-baseline:central}.route-caption{fill:#aeb9d4;font:12px Inter,Arial,sans-serif;text-anchor:middle}.stage-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.stage-card{position:relative;padding:19px;border:1px solid #ffffff1a;border-radius:19px;background:linear-gradient(140deg,#18233e,#121a30);transition:transform .3s,border-color .3s}.stage-card:hover{transform:translateY(-4px);border-color:#bb9bff75}.stage-kicker{display:flex;align-items:center;gap:9px;margin:0 0 9px;color:var(--mint);font-size:.75rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.stage-dot{width:28px;height:28px;display:grid;place-items:center;border-radius:9px;background:#8df1dd18;color:var(--mint)}.stage-card h3{margin:0;font-size:1.12rem}.stage-focus{margin:7px 0 11px;color:#c1cae0;font-size:.91rem}.stage-card ul{margin:0;padding-left:19px;color:#e4e7f2}.stage-card li{padding-left:3px;margin:6px 0;font-size:.9rem}.milestone{margin-top:13px;padding-top:12px;border-top:1px solid #ffffff16;color:#e9d9ff;font-size:.86rem}.video-link{display:inline-flex;align-items:center;gap:9px;margin-top:14px;padding:8px 11px;border:1px solid #ffb6a34a;border-radius:12px;background:#ffb6a30b;color:#ffd1c2;text-decoration:none;font-size:.82rem;transition:background .2s,transform .2s}.video-link:hover{transform:translateY(-2px);background:#ffb6a31b}.play-icon{display:grid;place-items:center;width:24px;height:24px;border-radius:50%;background:#ffb6a322;color:#ffb6a3;font-size:.65rem}.reality-card{display:flex;gap:13px;padding:19px 21px;margin-top:18px;color:#d0d6e9}.reality-card .reality-icon{flex:none;color:var(--peach);font-size:1.2rem}.reality-card p{margin:0}.save-status{min-height:24px;margin:12px 4px;color:#aab6d0;font-size:.86rem}.footer-note{margin-top:21px;color:#8792b1;font-size:.78rem;text-align:center}
        .reveal{opacity:0;transform:translateY(15px);transition:opacity .55s ease,transform .55s ease}.reveal.in-view{opacity:1;transform:none}.hidden{display:none!important}@keyframes path-flow{to{stroke-dashoffset:-168}}@keyframes scene-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-9px)}}@keyframes twinkle{0%,100%{opacity:.4}50%{opacity:1}}
        @media(max-width:760px){.hero{grid-template-columns:1fr;gap:6px}.hero-scene{width:min(480px,100%);margin:0 auto}.scene-caption{right:0;bottom:0}.career-film{grid-template-columns:1fr}.video-frame,.career-video{min-height:0}.career-grid,.stage-grid{grid-template-columns:1fr}.section-heading{align-items:flex-start;flex-direction:column}.top-note{font-size:.65rem}.report-top{align-items:flex-start}.route-diagram{margin-top:15px}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}.reveal{opacity:1;transform:none}}
    </style>
</head>
<body class="neon-app roadmap-result-page">
<main class="universe">
    <nav class="report-top"><a class="home-link" href="dashboard.php">⌂ <span>Student home</span></a><p class="top-note">Career constellation · AI guided</p></nav>
    <section class="hero">
        <div class="hero-copy"><p class="eyebrow"><i></i> YOUR CAREER MAP</p><h1>Find a path that feels <em>like you.</em></h1><p>A thoughtful starting point built from your quiz answers. Explore the options, check the day-to-day work, and take one small step before making a big decision.</p><div class="hero-tags"><span>5 paths to explore</span><span>12-week starter roadmap</span><span>Made to be adjusted</span></div></div>
        <div class="hero-scene" aria-label="Anime-inspired student looking at a glowing path through the stars" role="img">
            <svg viewBox="0 0 620 360" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <defs><linearGradient id="skyGlow" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#8d83ff" stop-opacity=".68"/><stop offset="1" stop-color="#70e9d3" stop-opacity=".12"/></linearGradient><linearGradient id="island" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#6de0ce"/><stop offset="1" stop-color="#48537d"/></linearGradient></defs>
                <circle cx="431" cy="119" r="89" fill="url(#skyGlow)"/><circle cx="431" cy="119" r="63" fill="#ffe4c4" opacity=".88"/>
                <g fill="#fff" class="stars"><circle cx="78" cy="65" r="2.4"/><circle cx="165" cy="105" r="1.8"/><circle cx="254" cy="45" r="2"/><circle cx="553" cy="83" r="2.3"/><circle cx="513" cy="185" r="1.7"/><circle cx="116" cy="178" r="1.6"/><circle cx="327" cy="82" r="1.5"/></g>
                <path d="M26 280 C110 215 173 244 250 194 S388 191 594 76" fill="none" stroke="#b69dff" stroke-opacity=".45" stroke-width="2" stroke-dasharray="4 10"/>
                <path d="M0 286 Q100 252 190 280 T380 271 T620 257 V360 H0Z" fill="#273451"/><path d="M0 311 Q140 269 248 312 T465 298 T620 306 V360 H0Z" fill="#172640"/>
                <path d="M194 258 Q291 220 386 260 Q336 285 290 300 Q233 289 194 258Z" fill="url(#island)"/><path d="M225 272 Q289 292 358 270 L331 328 Q285 345 263 328Z" fill="#38456d"/>
                <path d="M211 254 Q239 230 257 248 M335 250 Q354 230 375 252" fill="none" stroke="#8df1dd" stroke-width="4" stroke-linecap="round"/>
                <g class="student"><path d="M275 248 Q273 214 290 202 Q313 198 326 215 L330 249Z" fill="#392e59"/><path d="M286 218 Q282 181 302 169 Q326 173 327 202 L319 222Z" fill="#f2bca8"/><path d="M281 192 Q281 165 302 163 Q330 163 332 189 Q318 177 306 180 Q296 194 281 192Z" fill="#27233f"/><path d="M280 238 Q301 222 328 242 L340 274 L272 276Z" fill="#f39b92"/><path d="M284 272 L278 297 M326 272 L333 296" stroke="#e9b9a9" stroke-width="9" stroke-linecap="round"/><path d="M280 296 L267 299 M331 296 L344 299" stroke="#a9b6e2" stroke-width="8" stroke-linecap="round"/><path d="M291 203 Q298 207 305 203" fill="none" stroke="#533c50" stroke-width="2" stroke-linecap="round"/><circle cx="315" cy="199" r="2" fill="#39405d"/><path d="M328 237 Q346 229 351 242 L348 265" fill="none" stroke="#f7c79b" stroke-width="5" stroke-linecap="round"/></g>
                <g fill="#8df1dd"><circle cx="237" cy="224" r="3" style="animation:twinkle 2s infinite"/><circle cx="379" cy="225" r="2.4" style="animation:twinkle 2.6s infinite"/><circle cx="402" cy="207" r="2" style="animation:twinkle 2.2s infinite"/></g>
            </svg><div class="scene-caption">Small steps. Real progress.</div>
        </div>
    </section>

    <section class="result-area" id="careerResult" aria-live="polite"></section>
    <section class="career-film reveal" aria-labelledby="careerFilmTitle">
        <div class="video-frame" id="careerVideoFrame">
            <video class="career-video" id="careerResearchVideo" autoplay muted loop playsinline controls preload="metadata" aria-label="A person using a laptop for research and planning">
                <source src="https://videos.pexels.com/video-files/7308102/7308102-hd_1920_1080_24fps.mp4" type="video/mp4">
            </video>
            <div class="video-fallback-art" aria-hidden="true">✦</div>
            <span class="film-tag">Explore real work</span>
            <div class="film-caption"><span>Research · Plan · Try</span><a href="https://www.pexels.com/video/a-person-using-a-laptop-for-research-and-calculations-7308093/" target="_blank" rel="noopener noreferrer">Video source ↗</a></div>
        </div>
        <div class="film-copy">
            <p class="eyebrow"><i></i> BEFORE YOU CHOOSE</p>
            <h2 id="careerFilmTitle">Picture the work, then test the path.</h2>
            <p>Use each career suggestion as a starting point. Watch a beginner lesson, try a small task, and notice what you enjoy doing.</p>
            <div class="mini-route" aria-label="Explore, try a task, then reflect"><span>Explore</span><b>→</b><span>Try one task</span><b>→</b><span>Reflect</span></div>
        </div>
    </section>
    <p class="save-status" id="resultStatus" aria-live="polite"></p>
    <p class="footer-note">A quiz can suggest a direction; trying real tasks and talking with people in a field will teach you more.</p>
</main>
<script>
const resultBox=document.getElementById('careerResult');
const statusBox=document.getElementById('resultStatus');
const careerResearchVideo=document.getElementById('careerResearchVideo');
if(careerResearchVideo)careerResearchVideo.addEventListener('error',()=>document.getElementById('careerVideoFrame').classList.add('video-fallback'));
const initialRecommendation=<?=json_encode($initialReport, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?: 'null'?>;
const initialResultText=<?=json_encode($initialResultText, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?: '""'?>;
const resultWasSaved=<?=json_encode($resultWasSaved)?>;
const makeElement=(tag,className,text)=>{const node=document.createElement(tag);if(className)node.className=className;if(text!==undefined)node.textContent=String(text);return node;};
function observeReveals(){if(!('IntersectionObserver' in window)||matchMedia('(prefers-reduced-motion: reduce)').matches){document.querySelectorAll('.reveal').forEach(node=>node.classList.add('in-view'));return;}const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('in-view');observer.unobserve(entry.target)}}),{threshold:.08});document.querySelectorAll('.reveal').forEach(node=>observer.observe(node));}
function renderRecommendation(data){
  resultBox.replaceChildren();
  const overview=makeElement('section','overview-card reveal');overview.append(makeElement('div','overview-spark','✦'),makeElement('p','',data.overview||'Use these suggestions as options to investigate, not a prediction.'));resultBox.append(overview);
  const careerHead=makeElement('div','section-heading reveal');careerHead.append(makeElement('h2','',"Five paths worth exploring"),makeElement('p','','Fit labels are prompts for exploration, not test scores.'));resultBox.append(careerHead);
  const careerGrid=makeElement('div','career-grid');
  (Array.isArray(data.careers)?data.careers:[]).forEach((career,index)=>{const card=makeElement('article','career-card reveal');const titleRow=makeElement('div','career-title-row');titleRow.append(makeElement('h3','',career.name||'Career option'),makeElement('span','fit-badge',career.fit_level||'Explore'));card.append(titleRow);card.append(makeElement('p','',career.evidence||''));card.append(makeElement('p','',career.day_to_day||''));const pills=makeElement('div','skill-pills');(Array.isArray(career.skills_to_build)?career.skills_to_build:[]).forEach(skill=>pills.append(makeElement('span','skill-pill',skill)));card.append(pills);card.append(makeElement('div','first-step','Try this first: '+(career.first_step||'')));if(career.reality_check)card.append(makeElement('p','reality-small','Reality check: '+career.reality_check));careerGrid.append(card)});
  resultBox.append(careerGrid);
  const roadmap=data.roadmap||{};const roadmapPanel=makeElement('section','roadmap-panel reveal');const intro=makeElement('div','roadmap-intro');intro.append(makeElement('div','map-icon','↗'));const introText=makeElement('div');introText.append(makeElement('h2','','A practical 12-week route'),makeElement('p','',roadmap.goal||'A starter plan that you can adapt to your schedule.'));intro.append(introText);roadmapPanel.append(intro);
  const stages=Array.isArray(roadmap.stages)?roadmap.stages:[];const labels=['Explore','Learn','Build','Review'];const x=[85,365,645,925];const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox','0 0 1010 112');svg.setAttribute('class','route-diagram');svg.setAttribute('role','img');svg.setAttribute('aria-label','Four connected roadmap stages');const path=document.createElementNS('http://www.w3.org/2000/svg','path');path.setAttribute('d','M'+x[0]+' 40 C190 40 215 70 '+x[1]+' 70 S480 40 '+x[2]+' 40 S760 70 '+x[3]+' 70');path.setAttribute('class','route-path');svg.append(path);stages.forEach((stage,index)=>{const cx=x[index]||85;const cy=index%2===0?40:70;const circle=document.createElementNS('http://www.w3.org/2000/svg','circle');circle.setAttribute('cx',cx);circle.setAttribute('cy',cy);circle.setAttribute('r','18');circle.setAttribute('class','route-node');svg.append(circle);const number=document.createElementNS('http://www.w3.org/2000/svg','text');number.setAttribute('x',cx);number.setAttribute('y',cy);number.setAttribute('class','route-number');number.textContent=String(index+1);svg.append(number);const caption=document.createElementNS('http://www.w3.org/2000/svg','text');caption.setAttribute('x',cx);caption.setAttribute('y','108');caption.setAttribute('class','route-caption');caption.textContent=labels[index]||'Stage '+(index+1);svg.append(caption)});roadmapPanel.append(svg);
  const stageGrid=makeElement('div','stage-grid');stages.forEach((stage,index)=>{const card=makeElement('article','stage-card reveal');const kicker=makeElement('p','stage-kicker');kicker.append(makeElement('span','stage-dot',String(index+1)),document.createTextNode(' '+(stage.timeframe||'Stage '+(index+1))));card.append(kicker);card.append(makeElement('h3','',stage.title||'Learning stage'));card.append(makeElement('p','stage-focus',stage.focus||''));const list=makeElement('ul');(Array.isArray(stage.actions)?stage.actions:[]).forEach(action=>list.append(makeElement('li','',action)));card.append(list);card.append(makeElement('div','milestone','Milestone: '+(stage.milestone||'Complete one practical task.')));const query=String(stage.video_search||`${stage.title||'career'} beginner tutorial`).slice(0,180);const video=makeElement('a','video-link');video.href='https://www.youtube.com/results?search_query='+encodeURIComponent(query);video.target='_blank';video.rel='noopener noreferrer';video.append(makeElement('span','play-icon','▶'),makeElement('span','','Find video lessons'));card.append(video);stageGrid.append(card)});roadmapPanel.append(stageGrid);resultBox.append(roadmapPanel);
  const reality=makeElement('aside','reality-card reveal');reality.append(makeElement('span','reality-icon','✦'),makeElement('p','',data.reality_check||'Check local entry requirements and try a small project before investing heavily in a path.'));resultBox.append(reality);observeReveals();
}
if(initialRecommendation){renderRecommendation(initialRecommendation);statusBox.textContent=resultWasSaved?'Saved to your recommendation history.':'Your result is ready, but could not be saved to history.';}
else if(initialResultText){const rawResult=makeElement('article','reality-card',initialResultText);rawResult.style.whiteSpace='pre-wrap';resultBox.append(rawResult);statusBox.textContent=resultWasSaved?'Saved to your recommendation history.':'Your result is ready, but could not be saved to history.';}
else{const emptyState=makeElement('article','error-card');emptyState.append(makeElement('p','','No completed quiz result was found. Start the quiz to generate your career map.'));const retry=makeElement('a','video-link','Start the quiz');retry.href='quiz.php';emptyState.append(retry);resultBox.append(emptyState);}
</script>
<script src="../assets/page-navigation.js" defer></script></body>
</html>
