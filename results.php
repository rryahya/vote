<?php
require_once 'config.php';

/* ── AJAX endpoint ── */
if (isset($_GET['ajax'])) {
    $rows = $pdo->query(
        'SELECT c.id_candidat, c.nom, c.prenom, c.photo, COUNT(v.id_vote) AS total
         FROM candidats c LEFT JOIN votes v ON c.id_candidat = v.id_candidat
         GROUP BY c.id_candidat ORDER BY total DESC'
    )->fetchAll();
    $total = array_sum(array_column($rows, 'total'));
    header('Content-Type: application/json');
    echo json_encode(['results' => $rows, 'total' => (int)$total]);
    exit;
}

requireLogin();

$rows  = $pdo->query(
    'SELECT c.id_candidat, c.nom, c.prenom, c.photo, COUNT(v.id_vote) AS total
     FROM candidats c LEFT JOIN votes v ON c.id_candidat = v.id_candidat
     GROUP BY c.id_candidat ORDER BY total DESC'
)->fetchAll();
$total = array_sum(array_column($rows, 'total'));
$studs = $pdo->query('SELECT COUNT(*) FROM etudiants WHERE role="user"')->fetchColumn();
$voted = $pdo->query('SELECT COUNT(*) FROM etudiants WHERE a_vote=1')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VoteApp — Live Results</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<canvas id="particles-canvas"></canvas>

<div class="results-bg">
    <!-- Navbar -->
    <nav class="navbar">
        <a href="vote.php" class="nav-logo">Home</a>
        <div class="nav-links">
            <button class="nav-link" style="font-weight:700;text-decoration:underline;text-underline-offset:3px">result</button>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <button class="nav-link" onclick="location.href='admin-users.php'">Admin</button>
            <?php endif; ?>
        </div>
        <div class="nav-user">
            <div class="nav-avatar"><?= strtoupper(substr($_SESSION['prenom'], 0, 1)) ?></div>
            <a href="<?= $_SESSION['role']==='admin' ? 'admin-logout.php' : 'logout.php' ?>" class="btn-logout">Log Out</a>
        </div>
    </nav>

    <div class="results-main">
        <div class="results-header">
            <h1 class="results-title">Live Results</h1>
            <div style="display:flex;align-items:center;gap:10px">
                <div class="live-badge"><span class="live-dot"></span> Live</div>
                <div class="refresh-pill">Refreshing in <span id="countdown" style="font-weight:800;margin:0 3px">5</span>s</div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-glass"><div class="stat-val" id="stat-total"><?= $total ?></div><div class="stat-lbl">Votes Cast</div></div>
            <div class="stat-glass"><div class="stat-val"><?= count($rows) ?></div><div class="stat-lbl">Candidates</div></div>
            <div class="stat-glass"><div class="stat-val"><?= $studs ?></div><div class="stat-lbl">Students</div></div>
            <div class="stat-glass"><div class="stat-val"><?= $studs>0 ? round(($voted/$studs)*100) : 0 ?>%</div><div class="stat-lbl">Participation</div></div>
        </div>

        <!-- Results bars -->
        <div id="results-container">
            <?php foreach ($rows as $i => $r): ?>
            <?php $pct = $total > 0 ? round(($r['total']/$total)*100) : 0; $isTop = $i===0 && $r['total']>0; ?>
            <div class="result-card <?= $isTop ? 'leader' : '' ?>" style="animation-delay:<?= $i*.08 ?>s">
                <div class="r-rank"><?= $isTop ? '🏆' : $i+1 ?></div>
                <div class="r-avatar">
                    <?php if ($r['photo'] && file_exists($r['photo'])): ?>
                        <img src="<?= htmlspecialchars($r['photo']) ?>" alt="">
                    <?php else: ?><span class="no-ph">👤</span><?php endif; ?>
                </div>
                <div class="r-info">
                    <div class="r-name"><?= htmlspecialchars($r['prenom'].' '.$r['nom']) ?></div>
                    <div class="r-bar-bg"><div class="r-bar-fill" style="width:<?= $pct ?>%"></div></div>
                </div>
                <div class="r-count"><?= $r['total'] ?><span><?= $pct ?>%</span></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <div style="text-align:center;padding:50px;color:#fff;opacity:.7">No results yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="script.js"></script>
<script>initAutoRefresh('results.php?ajax=1', 5000);</script>
</body>
</html>
