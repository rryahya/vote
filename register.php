<?php
require_once 'config.php';
if (isset($_SESSION['user_id'])) { header('Location: vote.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom']    ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    $pwd    = $_POST['password']    ?? '';
    $cpwd   = $_POST['confirm']     ?? '';

    if (!$nom)    $errors[] = 'Last name is required.';
    if (!$prenom) $errors[] = 'First name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($pwd) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pwd !== $cpwd)   $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id_etudiant FROM etudiants WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already registered.';
        } else {
            $hash = password_hash($pwd, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO etudiants (nom, prenom, email, mot_de_passe) VALUES (?,?,?,?)')
                ->execute([$nom, $prenom, $email, $hash]);
            header('Location: login.php?registered=1'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VoteApp — Sign Up</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<canvas id="particles-canvas"></canvas>
<div class="aurora"><div class="abl"></div><div class="abl"></div><div class="abl"></div></div>

<div class="auth-bg">
    <div class="auth-card">
        <h1 class="auth-title">Sign Up</h1>

        <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form method="POST" novalidate>
            <div class="form-row">
                <div>
                    <label class="form-label">First Name</label>
                    <div class="input-pill">
                        <input type="text" name="prenom" placeholder="Amadou"
                               value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                    </div>
                </div>
                <div>
                    <label class="form-label">Last Name</label>
                    <div class="input-pill">
                        <input type="text" name="nom" placeholder="Diallo"
                               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top:12px">
                <div class="input-pill">
                    <svg class="pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/></svg>
                    <div class="pill-sep"></div>
                    <input type="email" name="email" placeholder="Email address"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <div class="input-pill">
                    <svg class="pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <div class="pill-sep"></div>
                    <input type="password" name="password" id="pwd" placeholder="Password (min 8)"
                           oninput="checkStrength(this.value,'sbar','slbl')" required>
                    <button type="button" class="eye-toggle" onclick="togglePwd('pwd',this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="strength-bar-wrap"><div class="strength-bar" id="sbar"></div></div>
                <div class="strength-label" id="slbl"></div>
            </div>

            <div class="form-group">
                <div class="input-pill">
                    <svg class="pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <div class="pill-sep"></div>
                    <input type="password" name="confirm" id="cpwd" placeholder="Confirm password" required>
                    <button type="button" class="eye-toggle" onclick="togglePwd('cpwd',this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-black" style="margin-top:6px">Create Account</button>
        </form>

        <div class="signup-row">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
