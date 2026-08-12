<?php

session_start();

require_once "../config/database.php";

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $login_error = "Please enter your email address and password.";

    } else {

        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, email, password, role, status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {

            // Create the login session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["first_name"] = $user["first_name"];
            $_SESSION["last_name"] = $user["last_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            // Redirect resident to resident dashboard
            if ($user["role"] === "resident") {

                header("Location: ../resident/dashboard.php");
                exit;

            } else {

                // Temporary fallback for other roles
                header("Location: ../index.php");
                exit;
            }

        } else {

            $login_error = "Invalid email address or password.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sign In - Tanza Public Service Portal</title>

    <style>
      @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap');

      :root{
        --ink:#10233F;
        --bay:#1B4E7E;
        --bay-deep:#123A5F;
        --bay-light:#EAF1F8;
        --gold:#E3A23C;
        --gold-deep:#C6822A;
        --paper:#FAF7F1;
        --white:#FFFFFF;
        --slate:#3A3F47;
        --muted:#5B616B;
        --success:#3F8F5F;
        --success-bg:#E8F3EC;
        --alert:#A6301E;
        --alert-bg:#FBEAE6;
        --line:#D8D2C2;
        --line-soft:#E9E4D8;
        --radius:14px;
        --focus-ring:#8A5A17;
      }

      /* High-contrast overrides (toggled via body.high-contrast) */
      body.high-contrast{
        --paper:#FFFFFF;
        --slate:#000000;
        --muted:#1A1A1A;
        --line:#000000;
        --line-soft:#333333;
        --bay-light:#D9E8F5;
        --alert:#7A1F12;
        --alert-bg:#FBEAE6;
      }
      body.high-contrast .field input{
        border-width:2px;
      }
      body.high-contrast .brand-overlay{
        background:rgba(6,20,38,.86);
      }

      *{box-sizing:border-box;}
      html,body{margin:0;padding:0;}
      html{font-size:100%;} /* 1rem = 16px baseline; JS scales this for text-size control */
      body{
        background:var(--paper);
        color:var(--slate);
        font-family:'Inter',sans-serif;
        -webkit-font-smoothing:antialiased;
        min-height:100vh;
        overflow-x:hidden;
        font-size:1rem;
        line-height:1.5;
      }
      h1,h2,h3{font-family:'Fraunces',serif;}
      .mono{font-family:'IBM Plex Mono',monospace;}

      /* Respect reduced-motion preference: no transitions/animation by default */
      *{transition:none;}
      @media (prefers-reduced-motion: no-preference){
        .btn-signin, .toggle-pw, .field input, .a11y-btn, .field-row a, .signup-line a,
        .back-home a, .help-link{
          transition:background .15s ease, border-color .15s ease, transform .15s ease, box-shadow .15s ease, color .15s ease;
        }
      }

      /* Strong, visible focus for keyboard users everywhere */
      a:focus-visible,
      button:focus-visible,
      input:focus-visible,
      [tabindex]:focus-visible{
        outline:3px solid var(--focus-ring);
        outline-offset:2px;
        border-radius:6px;
      }

      .skip-link{
        position:absolute;left:-9999px;top:0;
        background:var(--ink);color:var(--white);
        padding:12px 20px;border-radius:8px;font-weight:700;
        z-index:200;
      }
      .skip-link:focus{left:16px;top:16px;}

      /* ================= PAGE SHELL ================= */
      .login-shell{
        display:flex;
        min-height:100vh;
      }

      /* ---------- LEFT: Branding panel (photo background) ---------- */
      .brand-panel{
        flex:0 0 38%;
        max-width:38%;
        position:relative;
        overflow:hidden;
        background-image: url('../assets/images/tanza-municipal-hall.jpg');
        background-size:cover;
        background-position:center;
        background-repeat:no-repeat;
        min-height:100vh;
      }

      /* Navy overlay (built from the dashboard's --ink) so photo stays readable
         without hiding it entirely */
      .brand-overlay{
        position:absolute;
        inset:0;
        background:linear-gradient(180deg, rgba(16,35,63,.72) 0%, rgba(16,35,63,.58) 45%, rgba(16,35,63,.80) 100%);
      }

      .brand-content{
        position:relative;
        z-index:2;
        height:100%;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        text-align:center;
        padding:40px 32px;
      }

      /* Centered logo + branding treated as one group */
      .brand-crest{
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:0;
      }
      .seal-img {
        width: 180px !important;
        height: 180px !important;
        max-width: 180px !important;
        max-height: 180px !important;
        object-fit: contain;
        flex-shrink: 0;
        margin: 0 auto 20px;
        display: block;
        filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.4));
      }
      .brand-crest .t{
        font-family:'Fraunces',serif;
        font-size:clamp(1.3rem, 2.2vw, 1.7rem);
        font-weight:600;
        line-height:1.3;
        color:var(--white);
        max-width:340px;
      }
      .brand-crest .s{
        font-size:clamp(0.9rem, 1.1vw, 1rem);
        font-weight:500;
        color:#D6E1EE;
        margin-top:8px;
      }

      /* ---------- RIGHT: Login form (UNCHANGED) ---------- */
      .form-panel{
        flex:1 1 62%;
        background:var(--white);
        display:flex;
        align-items:center;
        justify-content:center;
        padding:44px 32px;
        position:relative;
      }

      .login-card{
        width:100%;
        max-width:440px;
      }

      .a11y-bar{
        display:flex;justify-content:flex-end;margin-bottom:18px;position:relative;
      }
      .a11y-btn{
        display:inline-flex;align-items:center;gap:8px;
        background:var(--bay-light);border:1px solid var(--line);color:var(--ink);
        font-size:0.9rem;font-weight:600;
        padding:10px 14px;border-radius:10px;cursor:pointer;
        min-height:44px;
      }
      .a11y-btn:hover{background:#DDEAF6;}
      .a11y-panel{
        display:none;
        position:absolute;top:calc(100% + 8px);right:0;z-index:40;
        background:var(--white);border:1px solid var(--line);border-radius:12px;
        box-shadow:0 16px 36px -18px rgba(16,35,63,.35);
        padding:14px;width:240px;
      }
      .a11y-panel.open{display:block;}
      .a11y-panel .a11y-title{
        font-size:0.85rem;font-weight:700;color:var(--ink);margin:0 0 10px;
      }
      .a11y-row{display:flex;gap:8px;margin-bottom:8px;}
      .a11y-row button{
        flex:1;min-height:44px;
        border:1px solid var(--line);background:var(--paper);color:var(--ink);
        border-radius:8px;font-size:0.9rem;font-weight:600;cursor:pointer;
      }
      .a11y-row button:hover{background:var(--bay-light);}
      .a11y-panel .full-row button{width:100%;}

      .login-card h1{
        font-size:1.9rem;font-weight:600;color:var(--ink);margin:0 0 8px;
      }
      .login-card .subtitle{
        font-size:1.05rem;color:var(--slate);margin:0 0 28px;line-height:1.55;
      }

      .alert-box{
        display:flex;align-items:flex-start;gap:10px;
        background:var(--alert-bg);border:1px solid rgba(166,48,30,.35);color:var(--alert);
        font-size:1rem;line-height:1.55;font-weight:600;
        border-radius:10px;
        padding:14px 16px;margin-bottom:20px;
      }
      .alert-box .ic{flex-shrink:0;font-size:1.1rem;line-height:1;}

      .field{margin-bottom:20px;}
      .field label{
        display:block;font-size:1rem;font-weight:700;color:var(--ink);margin-bottom:8px;
      }
      .field .req{color:var(--alert);}
      .field input{
        width:100%;
        border:1.5px solid var(--line);
        border-radius:10px;
        padding:14px 16px;
        font-size:1.05rem;
        font-family:'Inter',sans-serif;
        color:var(--slate);
        background:var(--paper);
        min-height:52px;
      }
      .field input:focus{
        border-color:var(--bay);
        background:var(--white);
      }
      .field input::placeholder{color:#6B7280;}
      .field-error{
        display:flex;align-items:center;gap:6px;
        font-size:0.9rem;font-weight:600;color:var(--alert);margin-top:7px;
      }

      .password-wrap{position:relative;display:flex;align-items:stretch;}
      .password-wrap input{padding-right:58px;}
      .toggle-pw{
        position:absolute;top:50%;right:6px;transform:translateY(-50%);
        background:var(--bay-light);border:1px solid var(--line);cursor:pointer;
        color:var(--ink);font-size:1rem;line-height:1;
        min-width:44px;min-height:44px;
        display:flex;align-items:center;justify-content:center;
        border-radius:8px;
      }
      .toggle-pw:hover{background:#DDEAF6;}

      .field-row{
        display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;
        margin-bottom:26px;font-size:1rem;
      }
      .remember{
        display:flex;align-items:center;gap:10px;color:var(--slate);
        cursor:pointer;padding:6px 4px;min-height:44px;font-weight:500;
      }
      .remember input{
        width:22px;height:22px;accent-color:var(--bay);cursor:pointer;flex-shrink:0;
      }
      .field-row a{
        color:var(--bay-deep);font-weight:700;text-decoration:underline;
        padding:6px 4px;min-height:44px;display:inline-flex;align-items:center;
      }
      .field-row a:hover{color:var(--ink);}

      .btn-signin{
        width:100%;
        display:flex;align-items:center;justify-content:center;gap:9px;
        background:var(--gold-deep);
        color:var(--white);
        border:none;
        font-family:'Inter',sans-serif;
        font-weight:800;font-size:1.15rem;
        padding:16px 20px;
        min-height:56px;
        border-radius:10px;
        cursor:pointer;
      }
      .btn-signin:hover{background:#A96D22;}

      .signup-line{
        text-align:center;
        font-size:1rem;
        color:var(--slate);
        margin:24px 0 0;
      }
      .signup-line a{
        color:var(--bay-deep);font-weight:700;text-decoration:underline;
        padding:4px 2px;
      }
      .signup-line a:hover{color:var(--ink);}

      .help-box{
        margin-top:26px;
        border-top:1px solid var(--line-soft);
        padding-top:20px;
        text-align:center;
      }
      .help-box .q{font-size:0.98rem;font-weight:700;color:var(--ink);margin:0 0 4px;}
      .help-box .a{font-size:0.95rem;color:var(--slate);margin:0;line-height:1.55;}

      .back-home{
        text-align:center;
        margin-top:16px;
      }
      .back-home a{
        font-size:0.95rem;color:var(--bay-deep);text-decoration:underline;font-weight:600;
        padding:4px 6px;
      }

      .form-footnote{
        text-align:center;
        font-size:0.85rem;
        color:var(--muted);
        margin-top:28px;
        line-height:1.6;
      }

      /* ---------- Responsive ---------- */
      @media(max-width:1020px){
        .brand-panel{flex:0 0 42%;max-width:42%;}
        .brand-content{padding:32px 30px;}
      }

      @media(max-width:760px){
        .login-shell{flex-direction:column;}
        .brand-panel{
          flex:0 0 auto;
          max-width:100%;
          width:100%;
          min-height:220px;
          height:220px;
        }
        .brand-content{padding:20px 22px;}
        .seal-img{width:76px;height:76px;margin-bottom:14px;}
        .brand-crest .t{font-size:1.05rem;max-width:280px;}
        .brand-crest .s{font-size:0.85rem;margin-top:6px;}
        .form-panel{padding:28px 18px 40px;}
        .a11y-panel{right:0;left:auto;}
      }

      @media(max-width:420px){
        .brand-panel{min-height:200px;height:200px;}
        .seal-img{width:64px;height:64px;margin-bottom:12px;}
        .login-card h1{font-size:1.6rem;}
      }
    </style>

</head>

<body>

<a href="#login-form" class="skip-link">Skip to sign in form</a>

<div class="login-shell">

    <!-- ============ LEFT: BRANDING (photo panel) ============ -->
    <div class="brand-panel" role="img" aria-label="Tanza Municipal Hall, Tanza, Cavite">

        <div class="brand-overlay" aria-hidden="true"></div>

        <div class="brand-content">
            <div class="brand-crest">
                <img src="../assets/images/tanza-logo.png" alt="Municipality of Tanza Logo"
                class="seal-img">
                <div>
                    <div class="t">Tanza Public Service Portal</div>
                    <div class="s">Municipality of Tanza, Cavite</div>
                </div>
            </div>
        </div>

    </div>

    <!-- ============ RIGHT: LOGIN FORM (UNCHANGED) ============ -->
    <div class="form-panel">

        <div class="login-card">

            <div class="a11y-bar">
                <button
                    type="button"
                    class="a11y-btn"
                    id="a11yToggle"
                    aria-expanded="false"
                    aria-controls="a11yPanel"
                >
                    <span aria-hidden="true">⚙</span> Accessibility Options
                </button>
                <div class="a11y-panel" id="a11yPanel" role="region" aria-label="Accessibility options">
                    <p class="a11y-title">Text &amp; Display</p>
                    <div class="a11y-row">
                        <button type="button" id="decreaseText">A−</button>
                        <button type="button" id="increaseText">A+</button>
                    </div>
                    <div class="a11y-row full-row">
                        <button type="button" id="toggleContrast">High Contrast</button>
                    </div>
                    <div class="a11y-row full-row">
                        <button type="button" id="resetA11y">Reset to Default</button>
                    </div>
                </div>
            </div>

            <h1 id="login-form">Sign In</h1>
            <p class="subtitle">Sign in to access your municipal services.</p>

            <?php if ($login_error): ?>

                <div class="alert-box" role="alert">
                    <span class="ic" aria-hidden="true">⚠</span>
                    <span><?php echo htmlspecialchars($login_error); ?></span>
                </div>
                
            <?php endif; ?>

            <form method="POST" novalidate id="loginForm">

                <div class="field">
                    <label for="email">Email Address <span class="req" aria-hidden="true">*</span></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="your@email.com"
                        required
                        aria-required="true"
                        aria-describedby="emailError"
                    >
                    <div class="field-error" id="emailError" role="alert" style="display:none;">
                        <span aria-hidden="true">⚠</span>
                        <span>Please enter your email address.</span>
                    </div>
                </div>

                <div class="field">
                    <label for="password">Password <span class="req" aria-hidden="true">*</span></label>
                    <div class="password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            aria-required="true"
                            aria-describedby="passwordError"
                        >
                        <button
                            type="button"
                            class="toggle-pw"
                            id="togglePw"
                            aria-label="Show password"
                            aria-pressed="false"
                        ><span aria-hidden="true">👁</span></button>
                    </div>
                    <div class="field-error" id="passwordError" role="alert" style="display:none;">
                        <span aria-hidden="true">⚠</span>
                        <span>Please enter your password.</span>
                    </div>
                </div>

                <div class="field-row">
                    <label class="remember" for="remember">
                        <input type="checkbox" id="remember" name="remember">
                        Remember Me
                    </label>
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-signin">
                    Sign In
                </button>

            </form>

            <p class="signup-line">
                Don't have an account? <a href="register.php">Register Here.</a>
            </p>

            <div class="help-box">
                <p class="q">Need help signing in?</p>
                <p class="a">Contact the Municipal Public Information Office for assistance.</p>
            </div>

            <div class="back-home">
                <a href="../index.php">Back to Home</a>
            </div>

            <p class="form-footnote">
                This is an official website of the Municipality of Tanza, Cavite.<br>
                All transactions are secured and confidential.
            </p>

        </div>

    </div>

</div>

<script>
  /* ---------- Password show/hide ---------- */
  const togglePw = document.getElementById('togglePw');
  const pwInput = document.getElementById('password');

  togglePw.addEventListener('click', () => {
    const isHidden = pwInput.type === 'password';
    pwInput.type = isHidden ? 'text' : 'password';
    togglePw.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    togglePw.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
  });

  /* ---------- Simple, friendly inline validation ---------- */
  const form = document.getElementById('loginForm');
  const emailInput = document.getElementById('email');
  const emailError = document.getElementById('emailError');
  const passwordError = document.getElementById('passwordError');

  form.addEventListener('submit', (e) => {
    let hasError = false;

    if (!emailInput.value.trim()) {
      emailError.style.display = 'flex';
      emailInput.setAttribute('aria-invalid', 'true');
      hasError = true;
    } else {
      emailError.style.display = 'none';
      emailInput.removeAttribute('aria-invalid');
    }

    if (!pwInput.value) {
      passwordError.style.display = 'flex';
      pwInput.setAttribute('aria-invalid', 'true');
      hasError = true;
    } else {
      passwordError.style.display = 'none';
      pwInput.removeAttribute('aria-invalid');
    }

    if (hasError) {
      e.preventDefault();
      (emailError.style.display === 'flex' ? emailInput : pwInput).focus();
    }
  });

  /* ---------- Accessibility Options panel ---------- */
  const a11yToggle = document.getElementById('a11yToggle');
  const a11yPanel = document.getElementById('a11yPanel');
  const decreaseText = document.getElementById('decreaseText');
  const increaseText = document.getElementById('increaseText');
  const toggleContrast = document.getElementById('toggleContrast');
  const resetA11y = document.getElementById('resetA11y');

  const TEXT_STEPS = [100, 112, 125]; // percent
  let textStepIndex = parseInt(localStorage.getItem('tanza_text_step') || '0', 10);
  let highContrast = localStorage.getItem('tanza_high_contrast') === '1';

  function applyA11y(){
    document.documentElement.style.fontSize = TEXT_STEPS[textStepIndex] + '%';
    document.body.classList.toggle('high-contrast', highContrast);
  }
  applyA11y();

  a11yToggle.addEventListener('click', () => {
    const open = a11yPanel.classList.toggle('open');
    a11yToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  document.addEventListener('click', (e) => {
    if (!a11yPanel.contains(e.target) && e.target !== a11yToggle) {
      a11yPanel.classList.remove('open');
      a11yToggle.setAttribute('aria-expanded', 'false');
    }
  });

  increaseText.addEventListener('click', () => {
    textStepIndex = Math.min(textStepIndex + 1, TEXT_STEPS.length - 1);
    localStorage.setItem('tanza_text_step', textStepIndex);
    applyA11y();
  });

  decreaseText.addEventListener('click', () => {
    textStepIndex = Math.max(textStepIndex - 1, 0);
    localStorage.setItem('tanza_text_step', textStepIndex);
    applyA11y();
  });

  toggleContrast.addEventListener('click', () => {
    highContrast = !highContrast;
    localStorage.setItem('tanza_high_contrast', highContrast ? '1' : '0');
    applyA11y();
  });

  resetA11y.addEventListener('click', () => {
    textStepIndex = 0;
    highContrast = false;
    localStorage.removeItem('tanza_text_step');
    localStorage.removeItem('tanza_high_contrast');
    applyA11y();
  });
</script>

</body>

</html>