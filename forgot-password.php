<?php
require_once 'config.php';

$step  = (int)($_SESSION['reset_step'] ?? 1);
$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Step 1 → send OTP
    if (isset($_POST['step1'])) {
        $email = trim($_POST['email'] ?? '');
        $stmt  = $pdo->prepare('SELECT id_etudiant FROM etudiants WHERE email = ?');
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            $error = 'No account found with this email.';
        } else {
            $otp     = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', time() + 600);
            $pdo->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
            $pdo->prepare('INSERT INTO password_resets (email, otp_code, expires_at) VALUES (?,?,?)')
                ->execute([$email, $otp, $expires]);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_step']  = 2;
            $_SESSION['demo_otp']    = $otp;   // demo only — remove in production
            $msg  = 'OTP sent! <strong>(Demo: your code is ' . $otp . ')</strong>';
            $step = 2;
        }
    }

    // Step 2 → verify OTP
    elseif (isset($_POST['step2'])) {
        $otp   = trim($_POST['otp_full'] ?? '');
        $email = $_SESSION['reset_email'] ?? '';
        $stmt  = $pdo->prepare('SELECT * FROM password_resets WHERE email = ? AND otp_code = ? AND expires_at > NOW() AND verified = 0');
        $stmt->execute([$email, $otp]);
        $rec   = $stmt->fetch();
        if (!$rec) {
            $error = 'Invalid or expired OTP code.'; $step = 2;
        } else {
            $pdo->prepare('UPDATE password_resets SET verified = 1 WHERE id = ?')->execute([$rec['id']]);
            $_SESSION['reset_step'] = 3; $step = 3;
        }
    }

    // Step 3 → new password
    elseif (isset($_POST['step3'])) {
        $pwd   = $_POST['password'] ?? '';
        $cpwd  = $_POST['confirm']  ?? '';
        $email = $_SESSION['reset_email'] ?? '';
        if (strlen($pwd) < 8) { $error = 'Password must be 8+ characters.'; $step = 3; }
        elseif ($pwd !== $cpwd) { $error = 'Passwords do not match.'; $step = 3; }
        else {
            $stmt = $pdo->prepare('SELECT id FROM password_resets WHERE email = ? AND verified = 1');
            $stmt->execute([$email]);
            if (!$stmt->fetch()) { $error = 'Session expired. Start over.'; unset($_SESSION['reset_step']); $step = 1; }
            else {
                $pdo->prepare('UPDATE etudiants SET mot_de_passe = ? WHERE email = ?')
                    ->execute([password_hash($pwd, PASSWORD_DEFAULT), $email]);
                $pdo->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
                unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['demo_otp']);
                header('Location: login.php?reset=1'); exit;
            }
        }
    }
}

$subs = ['Enter your email', 'Verify OTP', 'New password', 'Done!'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VoteApp — Reset Password</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<canvas id="particles-canvas"></canvas>
<div class="aurora"><div class="abl"></div><div class="abl"></div><div class="abl"></div></div>

<div class="auth-bg">
    <div class="auth-card">
        <h1 class="auth-title">Reset Password</h1>
        <p style="text-align:center;font-size:13px;color:rgba(40,40,40,0.6);margin-bottom:20px"><?= $subs[$step-1] ?></p>

        <!-- Steps indicator -->
        <div class="steps-row">
            <?php for ($i = 1; $i <= 4; $i++):
                $cls = $step > $i ? 'done' : ($step === $i ? 'active' : '');
            ?>
            <div class="step-dot <?= $cls ?>"><?= $step > $i ? '✓' : $i ?></div>
            <?php if ($i < 4): ?>
            <div class="step-line <?= $step > $i ? 'done' : '' ?>"></div>
            <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($msg): ?>
        <div class="alert alert-success"><?= $msg ?></div>
        <?php endif; ?>

        <!-- STEP 1 -->
        <?php if ($step === 1): ?>
        <form method="POST">
            <div class="form-group">
                <div class="input-pill">
                    <svg class="pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/></svg>
                    <div class="pill-sep"></div>
                    <input type="email" name="email" placeholder="Email address" required>
                </div>
            </div>
            <button type="submit" name="step1" value="1" class="btn-black">Send OTP</button>
        </form>

        <!-- STEP 2 -->
        <?php elseif ($step === 2): ?>
        <p style="text-align:center;font-size:13px;color:rgba(40,40,40,0.65);margin-bottom:12px">
            Code sent to <strong><?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?></strong>
        </p>
        <form method="POST">
            <div class="otp-wrap">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" autocomplete="off">
                <?php endfor; ?>
            </div>
            <input type="hidden" name="otp_full" id="otp_full">
            <button type="submit" name="step2" value="1" class="btn-black">Verify Code</button>
        </form>

        <!-- STEP 3 -->
        <?php elseif ($step === 3): ?>
        <form method="POST">
            <div class="form-group">
                <div class="input-pill">
                    <svg class="pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <div class="pill-sep"></div>
                    <input type="password" name="password" id="pwd" placeholder="New password" required>
                    <button type="button" class="eye-toggle" onclick="togglePwd('pwd',this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <div class="input-pill">
                    <svg class="pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <div class="pill-sep"></div>
                    <input type="password" name="confirm" id="cpwd" placeholder="Confirm password" required>
                </div>
            </div>
            <button type="submit" name="step3" value="1" class="btn-black">Reset Password</button>
        </form>
        <?php endif; ?>

        <div style="text-align:center;margin-top:18px">
            <a href="login.php" class="forgot-link">← Back to Login</a>
        </div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
