<?php
require_once 'config.php';
requireLogin();
if ($_SESSION['role'] === 'admin') { header('Location: admin-users.php'); exit; }

$userId  = $_SESSION['user_id'];
$success = '';
$error   = '';

/* ── Handle vote submission ─────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'])) {
    $candId = (int)$_POST['candidate_id'];

    $stmt = $pdo->prepare('SELECT a_vote FROM etudiants WHERE id_etudiant = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if ($row['a_vote']) {
        $error = 'You have already voted.';
    } else {
        $stmt = $pdo->prepare('SELECT id_candidat FROM candidats WHERE id_candidat = ?');
        $stmt->execute([$candId]);
        if (!$stmt->fetch()) {
            $error = 'Invalid candidate.';
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare('INSERT INTO votes (id_etudiant, id_candidat) VALUES (?,?)')->execute([$userId, $candId]);
                $pdo->prepare('UPDATE etudiants SET a_vote = 1 WHERE id_etudiant = ?')->execute([$userId]);
                $pdo->commit();
                $_SESSION['a_vote'] = 1;
                $success = 'Your vote has been recorded!';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Could not record vote. You may have already voted.';
            }
        }
    }
}

/* ── Load data ──────────────────────────────── */
$candidates = $pdo->query('SELECT * FROM candidats ORDER BY id_candidat ASC')->fetchAll();

$stmt = $pdo->prepare('SELECT e.a_vote, v.id_candidat FROM etudiants e LEFT JOIN votes v ON e.id_etudiant = v.id_etudiant WHERE e.id_etudiant = ?');
$stmt->execute([$userId]);
$userStatus = $stmt->fetch();
$hasVoted   = (bool)($userStatus['a_vote'] ?? false);
$votedFor   = $userStatus['id_candidat'] ?? null;

$candsJson  = json_encode($candidates);
$count      = count($candidates);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VoteApp — Vote</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<canvas id="particles-canvas"></canvas>
<div id="toast-container"></div>

<div class="vote-page-bg">
    <!-- Navbar -->
    <nav class="navbar">
        <a href="vote.php" class="nav-logo">Home</a>
        <div class="nav-links">
            <button class="nav-link" onclick="location.href='results.php'">result</button>
        </div>
        <div class="nav-user">
            <div class="nav-avatar"><?= strtoupper(substr($_SESSION['prenom'], 0, 1)) ?></div>
            <span style="font-size:13px;color:#2a2a2a;font-weight:500"><?= htmlspecialchars($_SESSION['prenom']) ?></span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </nav>

    <?php if ($success): ?>
    <div style="margin:0 40px"><div class="alert alert-success"><?= htmlspecialchars($success) ?></div></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div style="margin:0 40px"><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div></div>
    <?php endif; ?>

    <?php if (empty($candidates)): ?>
    <div style="text-align:center;padding:80px 20px;color:#fff">
        <div style="font-size:52px;margin-bottom:16px">🗃️</div>
        <p style="font-size:18px;font-weight:600">No candidates available yet.</p>
    </div>
    <?php else: ?>

    <!-- ── CAROUSEL ── -->
    <div class="carousel-section" id="carousel-section">

        <!-- Speech bubble (bottom-left) -->
        <div class="speech-area" id="speech-area">
            <div class="speech-bubble">
                <div class="speech-name" id="speech-name"></div>
                <p class="speech-text" id="speech-text"></p>
            </div>
        </div>

        <!-- Carousel track -->
        <div class="carousel-track">
            <!-- Left side -->
            <button class="arrow-btn" id="btn-prev" onclick="slide(-1)" aria-label="Previous">←</button>

            <div class="cand-side" id="slot-left" onclick="slide(-1)">
                <div class="cand-side-img-wrap" id="left-img-wrap"></div>
            </div>

            <!-- Center -->
            <div class="cand-center" id="slot-center">
                <div class="cand-center-img" id="center-img-wrap"></div>
                <div class="cand-center-name" id="center-name"></div>
                <div class="cand-already-voted" id="center-voted-for"></div>
            </div>

            <!-- Right side -->
            <div class="cand-side" id="slot-right" onclick="slide(1)">
                <div class="cand-side-img-wrap" id="right-img-wrap"></div>
            </div>

            <button class="arrow-btn" id="btn-next" onclick="slide(1)" aria-label="Next">→</button>
        </div>

        <!-- Vote button (bottom-right) -->
        <div class="vote-btn-area" id="vote-btn-area"></div>

        <!-- Dots -->
        <div class="carousel-dots" id="carousel-dots"></div>
    </div>

    <!-- Vote confirm modal -->
    <div class="modal-overlay" id="vote-confirm-modal">
        <div class="modal-box">
            <h3>Confirm Your Vote</h3>
            <p>You are voting for <strong id="modal-cand-name"></strong>.<br>
               This action <strong>cannot be undone</strong> — you only vote once.</p>
            <form method="POST">
                <input type="hidden" name="candidate_id" id="modal-cand-id">
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('vote-confirm-modal')">Cancel</button>
                    <button type="submit" class="btn-confirm-vote">Confirm Vote</button>
                </div>
            </form>
        </div>
    </div>

    <?php endif; ?>
</div><!-- end vote-page-bg -->

<script src="script.js"></script>
<script>
const CANDS   = <?= $candsJson ?>;
const HAS_VOTED = <?= $hasVoted ? 'true' : 'false' ?>;
const VOTED_FOR = <?= $votedFor ? (int)$votedFor : 'null' ?>;
let cur = 0;
const N = CANDS.length;

function imgTag(photo, cls) {
    if (!photo) return '<div class="' + (cls === 'lg' ? 'no-photo-lg' : 'no-photo-sm') + '">👤</div>';
    return '<img src="' + photo + '" alt="" onerror="this.parentNode.innerHTML=\'<div class=no-photo-' + (cls==='lg'?'lg':'sm') + '>👤</div>\'">';
}

function renderCarousel() {
    const left   = (cur - 1 + N) % N;
    const right  = (cur + 1) % N;
    const cand   = CANDS[cur];
    const isVotedFor = VOTED_FOR === cand.id_candidat;

    // Left slot
    document.getElementById('left-img-wrap').innerHTML = imgTag(CANDS[left].photo, 'sm');

    // Center slot
    document.getElementById('center-img-wrap').innerHTML = imgTag(cand.photo, 'lg');
    document.getElementById('center-name').textContent = cand.prenom + ' ' + cand.nom;
    document.getElementById('center-voted-for').textContent = isVotedFor ? '✅ You voted for this candidate' : '';

    // Right slot
    document.getElementById('right-img-wrap').innerHTML = imgTag(CANDS[right].photo, 'sm');

    // Speech bubble
    document.getElementById('speech-name').textContent = cand.prenom + '\'s programme :';
    document.getElementById('speech-text').textContent = cand.programme;

    // Vote button area
    const vba = document.getElementById('vote-btn-area');
    if (isVotedFor) {
        vba.innerHTML = '<div class="voted-badge-big">✅ Your Vote</div>';
    } else if (HAS_VOTED) {
        vba.innerHTML = '<button class="btn-vote-big" disabled>Voting Closed</button>';
    } else {
        const name = cand.prenom + ' ' + cand.nom;
        vba.innerHTML = '<button class="btn-vote-big" onclick="confirmVote(' + cand.id_candidat + ',\'' + name.replace("'","\'") + '\')">vote</button>';
    }

    // Dots
    const dots = document.getElementById('carousel-dots');
    dots.innerHTML = CANDS.map((_, i) =>
        '<div class="c-dot' + (i === cur ? ' on' : '') + '" onclick="jumpTo(' + i + ')"></div>'
    ).join('');
}

function slide(dir) {
    cur = (cur + dir + N) % N;
    document.getElementById('slot-center').style.animation = 'none';
    document.getElementById('center-img-wrap').style.animation = 'none';
    void document.getElementById('center-img-wrap').offsetWidth;
    document.getElementById('center-img-wrap').style.animation = 'centerPop .4s ease';
    renderCarousel();
}

function jumpTo(i) { cur = i; renderCarousel(); }

renderCarousel();
</script>
</body>
</html>
