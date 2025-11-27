<?php
require_once '../includes/auth_model.php';

$message = '';
$tokenCreated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username_or_email']);

    // Call model function that inserts into passwordreset
    $token = createPasswordResetToken($usernameOrEmail);

    if (!$token) {
        $message = "<p class='error-msg'>⚠️ No account found with that username or email.</p>";
    } else {
        $tokenCreated = true;

        // Build reset link
        $resetLink = "http://localhost/user_registration_system/user_code/reset_password.php?token=" . urlencode($token);

        // Show reset link inside the card
      $message = '
    <div class="success-box">
        <p class="success-msg">✅ Reset link generated!</p>
        <p>Use the link below to reset your password. This link is valid for 30 minutes.</p>

        <div class="link-box">
            <a href="' . $resetLink . '" target="_blank"
               style="color:#007bff; text-decoration:underline; font-weight:600;">
               ' . $resetLink . '
            </a>
        </div>

        <p style="margin-top:10px; font-size:13px; color:#555;">
            Tip: In real deployment this link would be emailed to the user.
        </p>
    </div>
';

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="../assets/css/auth-style.css">
  <style>
    :root {
      --bg-light: #f5f7ff;
      --bg-card: #ffffff;
      --bg-gradient: linear-gradient(135deg, #182848, #4b6cb7);
      --text-main: #182848;
      --text-muted: #6c7a96;
      --accent: #4b6cb7;
      --accent-soft: rgba(75,108,183,0.15);
      --danger: #e74c3c;
      --success: #27ae60;
    }
    body.dark-mode {
      --bg-light: #050816;
      --bg-card: #111827;
      --bg-gradient: radial-gradient(circle at top, #4b6cb7 0, #050816 40%, #020617 100%);
      --text-main: #e5e7eb;
      --text-muted: #9ca3af;
      --accent: #60a5fa;
      --accent-soft: rgba(96,165,250,0.15);
    }
.link-box {
    padding: 10px;
    background: #f5f7ff;
    border-radius: 8px;
    margin: 10px 0;
    word-break: break-all; /* Ensures long token links don't break layout */
}

.success-box {
    padding: 15px;
    background: #e8fff2;
    border: 1px solid #a3e4c4;
    border-radius: 10px;
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
      max-width: 460px;
      padding: 20px;
    }

    .auth-card {
      background: var(--bg-card);
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0,0,0,0.30);
      padding: 30px 28px 26px;
      position: relative;
      overflow: hidden;
    }

    .auth-card::before {
      content: "";
      position: absolute;
      inset: -40%;
      background: radial-gradient(circle at top, rgba(76,175,255,0.15), transparent 60%);
      opacity: 0.5;
      pointer-events: none;
    }

    .auth-header {
      position: relative;
      z-index: 1;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 10px;
    }

    .auth-header-text h2 {
      margin: 0 0 6px;
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: 0.02em;
    }

    .auth-header-text p {
      margin: 0;
      font-size: 0.9rem;
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

    .auth-body {
      position: relative;
      z-index: 1;
      margin-top: 14px;
    }

    .field-group {
      margin-bottom: 18px;
      text-align: left;
    }
    .field-label {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-muted);
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .field-label small {
      font-weight: 400;
      font-size: 0.75rem;
      opacity: 0.9;
    }

    .field-input {
      width: 100%;
      border-radius: 12px;
      border: 1px solid rgba(148,163,184,0.6);
      padding: 10px 12px;
      font-size: 0.95rem;
      outline: none;
      background: rgba(248,250,252,0.9);
      transition: border 0.2s, box-shadow 0.2s, background 0.2s;
    }

    body.dark-mode .field-input {
      background: #020617;
      border-color: rgba(148,163,184,0.3);
      color: #e5e7eb;
    }

    .field-input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 1px var(--accent-soft);
      background: #ffffff;
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
      margin-top: 16px;
      font-size: 0.82rem;
      color: var(--text-muted);
      text-align: center;
    }
    .footer-links a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
    }
    .footer-links a:hover {
      text-decoration: underline;
    }

    /* Message cards */
    .message-container {
      margin-top: 16px;
    }
    .message-card {
      border-radius: 14px;
      padding: 12px 14px;
      font-size: 0.87rem;
      text-align: left;
      margin-top: 8px;
    }
    .message-card h3 {
      margin: 0 0 6px;
      font-size: 0.95rem;
      font-weight: 600;
    }
    .message-card.success {
      background: rgba(22, 163, 74, 0.06);
      border: 1px solid rgba(22, 163, 74, 0.4);
      color: #14532d;
    }
    .message-card.error {
      background: rgba(220, 38, 38, 0.06);
      border: 1px solid rgba(220, 38, 38, 0.45);
      color: #7f1d1d;
    }

    .reset-link-box {
      background: #0f172a;
      color: #e5e7eb;
      font-family: "JetBrains Mono", "Fira Code", monospace;
      font-size: 0.8rem;
      border-radius: 10px;
      padding: 8px 10px;
      margin-top: 8px;
      overflow-x: auto;
      word-break: break-all;
    }

    .hint {
      font-size: 0.78rem;
      margin-top: 6px;
      color: #64748b;
    }

    .error-msg {
      color: #b91c1c;
      font-size: 0.85rem;
      margin-top: 6px;
      text-align: left;
    }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-header">
        <div class="auth-header-text">
          <h2>🔐 Forgot Password?</h2>
          <p>Enter your username or email to request a reset link.</p>
        </div>
        <button type="button" class="mode-toggle-btn" id="modeToggle">
          <span class="icon" id="modeIcon">🌙</span>
          <span id="modeText">Dark</span>
        </button>
      </div>

      <div class="auth-body">
        <?php if ($message): ?>
          <div class="message-container">
            <?= $message ?>
          </div>
        <?php endif; ?>

        <?php if (!$tokenCreated): ?>
          <form method="POST" autocomplete="off">
            <div class="field-group">
              <div class="field-label">
                <span>Username or Email</span>
                <small>Required</small>
              </div>
              <input
                type="text"
                name="username_or_email"
                class="field-input"
                placeholder="Enter here..."
                required
              >
            </div>

            <button type="submit" class="primary-btn">
              <span>Send Reset Link</span> <span>➡</span>
            </button>
          </form>
        <?php endif; ?>

        <div class="footer-links">
          <a href="login.php">⬅ Back to Login</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Simple dark mode toggle for this page
    const toggleBtn = document.getElementById('modeToggle');
    const modeIcon = document.getElementById('modeIcon');
    const modeText = document.getElementById('modeText');

    toggleBtn.addEventListener('click', () => {
      document.body.classList.toggle('dark-mode');
      const dark = document.body.classList.contains('dark-mode');
      modeIcon.textContent = dark ? '☀️' : '🌙';
      modeText.textContent = dark ? 'Light' : 'Dark';
    });
  </script>
</body>
</html>
