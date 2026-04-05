<?php
require_once 'config.php';
requireAdmin();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'reset_votes') {
    try {
        $pdo->beginTransaction();
        $pdo->exec('DELETE FROM votes');
        $pdo->exec('UPDATE etudiants SET a_vote = 0');
        $pdo->commit();
        $message = 'All votes have been reset.';
    } catch (Exception $e) {
        $pdo->rollBack(); $error = 'Reset failed: ' . $e->getMessage();
    }
}

$rows  = $pdo->query('SELECT c.id_candidat, c.nom, c.prenom, c.photo, COUNT(v.id_vote) AS total FROM candidats c LEFT JOIN votes v ON c.id_candidat = v.id_candidat GROUP BY c.id_candidat ORDER BY total DESC')->fetchAll();
$total = array_sum(array_column($rows, 'total'));

$details = $pdo->query('SELECT e.nom AS en, e.prenom AS ep, e.email, c.nom AS cn, c.prenom AS cp, v.date_vote FROM votes v JOIN etudiants e ON v.id_etudiant = e.id_etudiant JOIN candidats c ON v.id_candidat = c.id_candidat ORDER BY v.date_vote DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VoteApp Admin — Results</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<canvas id="particles-canvas"></canvas>

<div class="admin-bg">
    <nav class="navbar">
        <span class="nav-logo" style="cursor:default">VoteApp
            <span style="font-size:12px;color:#1a8a6a;font-weight:700;margin-left:6px">ADMIN</span>
        </span>
        <div class="nav-links">
            <button class="nav-link" onclick="location.href='results.php'">Live Results</button>
        </div>
        <div class="nav-user">
            <div class="nav-avatar" style="background:#1453a3"><?= strtoupper(substr($_SESSION['prenom'],0,1)) ?></div>
            <a href="admin-logout.php" class="btn-logout">Log Out</a>
        </div>
    </nav>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <span class="sidebar-section">Management</span>
            <a href="admin-users.php" class="sidebar-link">👥 Users</a>
            <a href="admin-results.php" class="sidebar-link active">📊 Results</a>
            <span class="sidebar-section">Access</span>
            <a href="results.php" class="sidebar-link">🔗 Live Results</a>
            <a href="vote.php" class="sidebar-link">🗳️ Vote Page</a>
            <a href="admin-logout.php" class="sidebar-link danger">🚪 Log Out</a>
        </aside>

        <div class="admin-content">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:6px">
                <div>
                    <h1 class="admin-title">Election Results</h1>
                    <p class="admin-sub"><?= $total ?> vote<?= $total!==1?'s':'' ?> cast total</p>
                </div>
                <button class="btn-black" style="width:auto;padding:10px 22px;font-size:13px" onclick="document.getElementById('m-reset').classList.add('open')">
                    🔄 Reset All Votes
                </button>
            </div>

            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Bars -->
            <?php foreach ($rows as $i => $r):
                $pct = $total>0 ? round(($r['total']/$total)*100) : 0;
                $top = $i===0 && $r['total']>0;
            ?>
            <div class="ar-bar-item" style="animation-delay:<?= $i*.07 ?>s">
                <div class="r-rank" style="<?= $top?'color:#1a1a1a;font-size:20px':'' ?>"><?= $top?'🏆':$i+1 ?></div>
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

            <!-- Log table -->
            <div class="table-glass" style="margin-top:24px">
                <div class="table-glass-head">
                    <h3>Voting Log</h3>
                    <span style="font-size:12px;color:rgba(30,30,30,0.45)"><?= count($details) ?> entries</span>
                </div>
                <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>#</th><th>Student</th><th>Email</th><th>Voted For</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($details as $i => $d): ?>
                    <tr>
                        <td style="color:rgba(30,30,30,0.38)"><?= $i+1 ?></td>
                        <td><strong><?= htmlspecialchars($d['ep'].' '.$d['en']) ?></strong></td>
                        <td style="font-size:12.5px;color:rgba(30,30,30,0.6)"><?= htmlspecialchars($d['email']) ?></td>
                        <td><span class="badge badge-voted"><?= htmlspecialchars($d['cp'].' '.$d['cn']) ?></span></td>
                        <td style="font-size:12.5px;color:rgba(30,30,30,0.5)"><?= date('d/m/Y H:i', strtotime($d['date_vote'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($details)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:34px;color:rgba(30,30,30,0.4)">No votes yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset modal -->
<div class="modal-overlay" id="m-reset">
    <div class="modal-box">
        <h3>⚠️ Reset All Votes</h3>
        <p>This will <strong>permanently delete all votes</strong> and reset every student's voting status. Cannot be undone.</p>
        <form method="POST">
            <input type="hidden" name="action" value="reset_votes">
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('m-reset').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn-confirm-del">Yes, Reset</button>
            </div>
        </form>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
