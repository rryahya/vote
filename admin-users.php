<?php
require_once 'config.php';
requireAdmin();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($uid === (int)$_SESSION['user_id']) {
        $error = 'You cannot modify your own account.';
    } elseif ($action === 'delete_user' && $uid > 0) {
        $pdo->prepare('DELETE FROM etudiants WHERE id_etudiant = ?')->execute([$uid]);
        $message = 'User deleted.';
    } elseif ($action === 'change_role' && $uid > 0) {
        $newRole = in_array($_POST['new_role']??'',['admin','user']) ? $_POST['new_role'] : 'user';
        $pdo->prepare('UPDATE etudiants SET role = ? WHERE id_etudiant = ?')->execute([$newRole, $uid]);
        $message = "Role updated to '$newRole'.";
    }
}

$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $pdo->prepare('SELECT * FROM etudiants WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? ORDER BY nom');
    $like = "%$search%";
    $stmt->execute([$like,$like,$like]);
} else {
    $stmt = $pdo->query('SELECT * FROM etudiants ORDER BY nom');
}
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VoteApp Admin — Users</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<canvas id="particles-canvas"></canvas>
<div id="toast-container"></div>

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
            <span style="font-size:13px;color:#2a2a2a;font-weight:500"><?= htmlspecialchars($_SESSION['prenom']) ?></span>
            <a href="admin-logout.php" class="btn-logout">Log Out</a>
        </div>
    </nav>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <span class="sidebar-section">Management</span>
            <a href="admin-users.php" class="sidebar-link active">👥 Users</a>
            <a href="admin-results.php" class="sidebar-link">📊 Results</a>
            <span class="sidebar-section">Access</span>
            <a href="results.php" class="sidebar-link">🔗 Live Results</a>
            <a href="vote.php" class="sidebar-link">🗳️ Vote Page</a>
            <a href="admin-logout.php" class="sidebar-link danger">🚪 Log Out</a>
        </aside>

        <div class="admin-content">
            <h1 class="admin-title">User Management</h1>
            <p class="admin-sub"><?= count($users) ?> account<?= count($users)!==1?'s':'' ?> found</p>

            <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="table-glass">
                <div class="table-glass-head">
                    <h3>All Users</h3>
                    <form method="GET" style="display:flex;gap:8px;align-items:center">
                        <input class="admin-search" name="q" placeholder="Search…" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn-table btn-table-role">Search</button>
                        <?php if ($search): ?>
                        <a href="admin-users.php" class="btn-table btn-table-role" style="text-decoration:none">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Voted</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="color:rgba(30,30,30,0.38)"><?= $u['id_etudiant'] ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:9px">
                                <div class="nav-avatar" style="width:30px;height:30px;font-size:12px;<?= $u['role']==='admin'?'background:#1453a3':'' ?>">
                                    <?= strtoupper(substr($u['prenom'],0,1)) ?>
                                </div>
                                <strong><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></strong>
                            </div>
                        </td>
                        <td style="color:rgba(30,30,30,0.6);font-size:12.5px"><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge <?= $u['role']==='admin'?'badge-admin':'badge-user' ?>"><?= $u['role']==='admin'?'Admin':'User' ?></span></td>
                        <td><span class="badge <?= $u['a_vote']?'badge-voted':'badge-novote' ?>"><?= $u['a_vote']?'✅ Voted':'—' ?></span></td>
                        <td>
                            <?php if ($u['id_etudiant'] != $_SESSION['user_id']): ?>
                            <div style="display:flex;gap:7px">
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?= $u['id_etudiant'] ?>">
                                    <input type="hidden" name="new_role" value="<?= $u['role']==='admin'?'user':'admin' ?>">
                                    <button type="submit" class="btn-table btn-table-role">
                                        <?= $u['role']==='admin'?'Make User':'Make Admin' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('Delete <?= addslashes($u['prenom'].' '.$u['nom']) ?>?')">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $u['id_etudiant'] ?>">
                                    <button type="submit" class="btn-table btn-table-del">Delete</button>
                                </form>
                            </div>
                            <?php else: ?>
                            <span style="font-size:11px;color:rgba(30,30,30,0.35)">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:34px;color:rgba(30,30,30,0.4)">No users found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
