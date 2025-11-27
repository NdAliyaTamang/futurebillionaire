<?php
require_once '../includes/auth_model.php';

$message = '';
$success = false;
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $message = "<p class='error-msg'>⚠️ Passwords do not match.</p>";
    } elseif (strlen($newPassword) < 8) {
        $message = "<p class='error-msg'>⚠️ Password must be at least 8 characters.</p>";
    } else {
        $reset = resetPassword($token, $newPassword);
        if ($reset) {
            $success = true;
            $message = "<p class='success-msg'>✅ Your password has been successfully reset. 
                        You can now <a href='login.php'>log in here</a>.</p>";
        } else {
            $message = "<p class='error-msg'>⚠️ Invalid or expired reset link. Please request a new one.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="../assets/css/auth-style.css">
  <style>
    :root {
      --bg-gradient: radial-gradient(circle at top, #4b6cb7 0, #182848 40%, #020617 100%);
      --card-bg: #ffffff;
      --card-border: rgba(148,163,184,0.35);
      --text-main: #0f172a;
      --text-muted: #6b7280;
      --accent: #4b6cb7;
      --accent-soft: rgba(56,189,248,0.25);
      --danger: #ef4444;
      --success: #22c55e;
      --warning: #eab308;
    }

    body.dark-mode {
      --bg-gradient: radial-gradient(circle at top, #1d4ed8 0, #020617 40%, #020617 100%);
      --card-bg: #020617;
      --card-border: rgba(55,65,81,0.7);
      --text-main: #e5e7eb;
      --text-muted: #9ca3af;
      --accent: #60a5fa;
      --accent-soft: rgba(96,165,250,0.35);
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: var(--bg-gradient);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-main);
    }

    .auth-wrapper {
      width: 100%;
      max-width: 500px;
      padding: 20px;
    }

    .auth-card {
      position: relative;
      background: var(--card-bg);
      border-radius: 22px;
      border: 1px solid var(--card-border);
      box-shadow: 0 24px 60px rgba(15,23,42,0.55);
      padding: 26px 26px 24px;
      overflow: hidden;
    }

    .auth-card::before {
      content: "";
      position: absolute;
      inset: -40%;
      background:
        radial-gradient(circle at 0 0, rgba(56,189,248,0.20), transparent 60%),
        radial-gradient(circle at 100% 0, rgba(129,140,248,0.18), transparent 60%);
      opacity: 0.5;
      pointer-events: none;
    }

    .auth-inner {
      position: relative;
      z-index: 1;
    }

    .auth-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 6px;
    }

    .auth-title-group h2 {
      margin: 0;
      font-size: 1.55rem;
      font-weight: 700;
      letter-spacing: 0.02em;
    }
    .auth-title-group p {
      margin: 4px 0 0;
      font-size: 0.88rem;
      color: var(--text-muted);
    }

    .mode-toggle-btn {
      border: none;
      border-radius: 999px;
      padding: 6px 12px;
      font-size: 0.75rem;
      background: rgba(15,23,42,0.04);
      color: var(--text-muted);
      display: inline-flex;
      align-items: center;
      gap: 4px;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
    }
    .mode-toggle-btn span.icon {
      font-size: 0.95rem;
    }
    .mode-toggle-btn:hover {
      background: rgba(15,23,42,0.08);
      transform: translateY(-1px);
    }

    .message-box {
      margin-top: 10px;
      font-size: 0.88rem;
    }
    .success-msg, .error-msg {
      padding: 10px 12px;
      border-radius: 12px;
      text-align: left;
    }
    .success-msg {
      background: rgba(22,163,74,0.06);
      border: 1px solid rgba(22,163,74,0.5);
      color: #14532d;
    }
    .success-msg a {
      color: #16a34a;
      font-weight: 600;
      text-decoration: none;
    }
    .success-msg a:hover {
      text-decoration: underline;
    }
    .error-msg {
      background: rgba(220,38,38,0.06);
      border: 1px solid rgba(220,38,38,0.5);
      color: #7f1d1d;
    }

    .form-body {
      margin-top: 10px;
    }

    .field-group {
      margin-bottom: 16px;
      text-align: left;
    }

    .field-label {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-muted);
      margin-bottom: 6px;
    }

    .field-input {
      width: 100%;
      border-radius: 12px;
      border: 1px solid rgba(148,163,184,0.6);
      padding: 10px 12px;
      font-size: 0.95rem;
      outline: none;
      background: rgba(248,250,252,0.95);
      transition: border 0.2s, box-shadow 0.2s, background 0.2s;
    }
    body.dark-mode .field-input {
      background: #020617;
      border-color: rgba(148,163,184,0.4);
      color: #e5e7eb;
    }
    .field-input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 1px var(--accent-soft);
      background: #ffffff;
    }

    /* Password strength widget */
    .password-widget {
      background: rgba(15,23,42,0.02);
      border-radius: 14px;
      padding: 10px 12px;
      border: 1px dashed rgba(148,163,184,0.5);
      margin-top: 4px;
    }

    .strength-bar {
      height: 7px;
      border-radius: 999px;
      background: rgba(148,163,184,0.3);
      overflow: hidden;
      margin-bottom: 6px;
    }

    .strength-fill {
      height: 100%;
      width: 0%;
      border-radius: 999px;
      background: linear-gradient(90deg, #f97316, #22c55e);
      transition: width 0.2s ease-out, background 0.2s ease-out;
    }

    .strength-label {
      font-size: 0.75rem;
      color: var(--text-muted);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .strength-label span.status {
      font-weight: 600;
    }

    .requirements-list {
      list-style: none;
      padding-left: 0;
      margin: 8px 0 0;
      font-size: 0.78rem;
      color: var(--text-muted);
    }
    .requirements-list li {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 2px;
    }
    .req-icon {
      width: 16px;
      height: 16px;
      border-radius: 999px;
      font-size: 0.7rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #fee2e2;
      color: #b91c1c;
    }
    .requirements-list li.met .req-icon {
      background: #dcfce7;
      color: #15803d;
    }

    .match-msg {
      font-size: 0.8rem;
      margin-top: 3px;
      min-height: 16px;
    }
    .match-msg.ok {
      color: #15803d;
    }
    .match-msg.bad {
      color: #b91c1c;
    }

    .primary-btn {
      width: 100%;
      border: none;
      border-radius: 999px;
      padding: 11px 14px;
      font-size: 0.95rem;
      font-weight: 600;
      letter-spacing: 0.02em;
      background: linear-gradient(135deg, #4b6cb7, #182848);
      color: #ffffff;
      cursor: pointer;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      gap: 6px;
      transition: transform 0.15s ease-out, box-shadow 0.15s ease-out, filter 0.15s;
      margin-top: 4px;
    }
    .primary-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 24px rgba(15,23,42,0.35);
      filter: brightness(1.03);
    }
    .primary-btn:active {
      transform: translateY(0);
      box-shadow: none;
      filter: brightness(0.97);
    }

    .footer-links {
      margin-top: 14px;
      font-size: 0.82rem;
      text-align: center;
      color: var(--text-muted);
    }
    .footer-links a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
    }
    .footer-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-inner">
        <div class="auth-header">
          <div class="auth-title-group">
            <h2>Reset Your Password 🔐</h2>
            <p>Choose a strong password to keep your account secure.</p>
          </div>
          <button type="button" class="mode-toggle-btn" id="modeToggle">
            <span class="icon" id="modeIcon">🌙</span>
            <span id="modeText">Dark</span>
          </button>
        </div>

        <div class="message-box">
          <?= $message ?>
        </div>

        <?php if (!$success): ?>
          <div class="form-body">
            <form method="POST" autocomplete="off">
              <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

              <div class="field-group">
                <div class="field-label">
                  <span>New Password</span>
                  <small>At least 8 characters</small>
                </div>
                <input
                  type="password"
                  name="new_password"
                  id="new_password"
                  class="field-input"
                  placeholder="Enter new password"
                  required
                >

                <div class="password-widget">
                  <div class="strength-bar">
                    <div class="strength-fill" id="strengthFill"></div>
                  </div>
                  <div class="strength-label">
                    <span>Strength</span>
                    <span class="status" id="strengthStatus">Too weak</span>
                  </div>
                  <ul class="requirements-list">
                    <li id="req-length">
                      <span class="req-icon">✖</span>
                      <span>At least 8 characters</span>
                    </li>
                    <li id="req-upper">
                      <span class="req-icon">✖</span>
                      <span>At least one uppercase letter (A–Z)</span>
                    </li>
                    <li id="req-number">
                      <span class="req-icon">✖</span>
                      <span>At least one number (0–9)</span>
                    </li>
                    <li id="req-special">
                      <span class="req-icon">✖</span>
                      <span>At least one special character (!@#$…)</span>
                    </li>
                  </ul>
                </div>
              </div>

              <div class="field-group">
                <div class="field-label">
                  <span>Confirm Password</span>
                  <small>Repeat the same password</small>
                </div>
                <input
                  type="password"
                  name="confirm_password"
                  id="confirm_password"
                  class="field-input"
                  placeholder="Re-type new password"
                  required
                >
                <div class="match-msg" id="matchMsg"></div>
              </div>

              <button type="submit" class="primary-btn">
                <span>Save New Password</span> <span>✅</span>
              </button>
            </form>
          </div>
        <?php endif; ?>

        <div class="footer-links">
          <a href="login.php">⬅ Back to Login</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Dark mode toggle
    const toggleBtn = document.getElementById('modeToggle');
    const modeIcon = document.getElementById('modeIcon');
    const modeText = document.getElementById('modeText');

    toggleBtn.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode');
      const dark = document.body.classList.contains('dark-mode');
      modeIcon.textContent = dark ? '☀️' : '🌙';
      modeText.textContent = dark ? 'Light' : 'Dark';
    });

    // Password strength + requirements
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthStatus = document.getElementById('strengthStatus');
    const matchMsg = document.getElementById('matchMsg');

    const reqLength = document.getElementById('req-length');
    const reqUpper = document.getElementById('req-upper');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');

    function updateRequirements(pw) {
      const hasLength = pw.length >= 8;
      const hasUpper = /[A-Z]/.test(pw);
      const hasNumber = /[0-9]/.test(pw);
      const hasSpecial = /[^A-Za-z0-9]/.test(pw);

      toggleReq(reqLength, hasLength);
      toggleReq(reqUpper, hasUpper);
      toggleReq(reqNumber, hasNumber);
      toggleReq(reqSpecial, hasSpecial);

      let score = 0;
      if (hasLength) score++;
      if (hasUpper) score++;
      if (hasNumber) score++;
      if (hasSpecial) score++;

      let width = 0;
      let statusText = "Too weak";
      let gradient = "linear-gradient(90deg, #ef4444, #f97316)";

      if (score === 1) {
        width = 25;
        statusText = "Very weak";
      } else if (score === 2) {
        width = 45;
        statusText = "Weak";
        gradient = "linear-gradient(90deg, #f97316, #eab308)";
      } else if (score === 3) {
        width = 70;
        statusText = "Good";
        gradient = "linear-gradient(90deg, #eab308, #22c55e)";
      } else if (score === 4) {
        width = 100;
        statusText = "Strong";
        gradient = "linear-gradient(90deg, #22c55e, #16a34a)";
      }

      strengthFill.style.width = width + "%";
      strengthFill.style.background = gradient;
      strengthStatus.textContent = statusText;
    }

    function toggleReq(element, met) {
      if (!element) return;
      const icon = element.querySelector('.req-icon');
      if (met) {
        element.classList.add('met');
        if (icon) icon.textContent = '✔';
      } else {
        element.classList.remove('met');
        if (icon) icon.textContent = '✖';
      }
    }

    function updateMatch() {
      const pw = newPassword.value;
      const cpw = confirmPassword.value;

      if (!cpw) {
        matchMsg.textContent = "";
        matchMsg.className = "match-msg";
        return;
      }

      if (pw === cpw) {
        matchMsg.textContent = "✅ Passwords match.";
        matchMsg.className = "match-msg ok";
      } else {
        matchMsg.textContent = "⚠️ Passwords do not match.";
        matchMsg.className = "match-msg bad";
      }
    }

    if (newPassword) {
      newPassword.addEventListener('input', () => {
        updateRequirements(newPassword.value);
        updateMatch();
      });
    }
    if (confirmPassword) {
      confirmPassword.addEventListener('input', updateMatch);
    }
  </script>
</body>
</html>
