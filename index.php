
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareerRoadmap</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:Inter,"Segoe UI",Arial,sans-serif;background:linear-gradient(135deg,#faf6ff,#fff8fb 55%,#f1f7ff);color:#382d48;min-height:100vh}
        nav{background:#ffffffd9;backdrop-filter:blur(12px);border-bottom:1px solid #eee7f4;padding:16px max(22px,calc((100% - 1120px)/2));display:flex;justify-content:space-between;align-items:center;gap:20px}
        nav h2{margin:0;color:#57367b}nav div{display:flex;gap:22px}nav a{color:#684492;text-decoration:none;font-weight:650;margin:0}nav a:hover{text-decoration:underline}
        .hero{max-width:1120px;margin:55px auto;padding:80px 54px;text-align:left;border-radius:30px;background:linear-gradient(125deg,#f1e7ff,#fff0f6 70%,#edf7ff);border:1px solid #eee2f5;position:relative;overflow:hidden;box-shadow:0 16px 45px #4c2f6d0d}
        .hero:after{content:"✿";position:absolute;right:9%;top:12%;color:#d7bdea;font-size:120px;transform:rotate(12deg)}
        .hero h1{font-size:clamp(38px,6vw,62px);line-height:1.08;color:#4c3264;max-width:700px;margin:0 0 18px;position:relative;z-index:1}
        .hero p{font-size:18px;color:#766d80;line-height:1.7;max-width:640px;position:relative;z-index:1}
        .btn{display:inline-block;background:#7550a5;color:white;padding:13px 22px;border-radius:24px;text-decoration:none;margin:14px 10px 0 0;font-weight:700;box-shadow:0 7px 16px #68409e25}.btn.secondary{background:#fff;color:#684492;border:1px solid #e8dcef}
        @media(max-width:700px){nav{padding:14px 20px;align-items:flex-start}nav div{gap:12px;flex-wrap:wrap;justify-content:flex-end}.hero{margin:24px 14px;padding:52px 26px}.hero:after{right:3%;font-size:75px;opacity:.55}.hero p{font-size:16px}}
    </style>
<link rel="stylesheet" href="assets/cute-theme.css">
</head>
<body>

<nav>
    <h2>CareerRoadmap</h2>
    <div>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </div>
</nav>

<section class="hero">
    <h1>Discover Your Future Career</h1>

    <p>
        Explore your interests, discover career opportunities
        and build your skills with CareerExplorer.
    </p>

    <a href="register.php" class="btn">Get Started</a>
    <a href="login.php" class="btn secondary">Login</a>
</section>

</body>
</html>
