<?php
// ============================================================
//  login.php — Full glass design (Doc 1) + MySQL/PDO backend (Doc 2)
// ============================================================
require_once 'config.php';

// Already logged in → redirect
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin-users.php' : 'vote.php'));
    exit;
}

$err = '';
$tab = (isset($_GET['tab']) && $_GET['tab'] === 'admin') ? 'admin' : 'user';

// ── USER LOGIN ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_user'])) {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $err = 'Please fill in all fields.';
        $tab = 'user';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM etudiants WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user['mot_de_passe'])) {
            $err = 'Invalid email or password.';
            $tab = 'user';
        } else {
            $_SESSION['user_id'] = $user['id_etudiant'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['prenom']  = $user['prenom'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['a_vote']  = $user['a_vote'];
            header('Location: vote.php'); exit;
        }
    }
}

// ── ADMIN LOGIN ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_admin'])) {
    $tab     = 'admin';
    $u       = trim($_POST['auser'] ?? '');
    $p       = $_POST['apass']  ?? '';
    $granted = false;

    // Master hardcoded admin (username + password)
    if ($u === 'admin' && $p === 'Admin@2025!') {
        $_SESSION['user_id'] = 0;
        $_SESSION['nom']     = 'Admin';
        $_SESSION['prenom']  = 'System';
        $_SESSION['email']   = 'admin@vote.com';
        $_SESSION['role']    = 'admin';
        $_SESSION['a_vote']  = 0;
        $granted = true;
    }

    // DB admin: any user with role=admin using email + password
    if (!$granted) {
        $em   = strtolower($u);
        $stmt = $pdo->prepare('SELECT * FROM etudiants WHERE email = ? AND role = ?');
        $stmt->execute([$em, 'admin']);
        $user = $stmt->fetch();

        if ($user && password_verify($p, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id_etudiant'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['prenom']  = $user['prenom'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = 'admin';
            $_SESSION['a_vote']  = $user['a_vote'];
            $granted = true;
        }
    }

    if ($granted) {
        header('Location: admin-users.php'); exit;
    } else {
        $err = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IAM.VOTE — Log In</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
<style>
/* ── Reset ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden;font-family:'DM Sans',sans-serif;background:#6b6b6b;color:#1a1a1a}

/* ── Animated background blobs ── */
.blob{position:fixed;border-radius:50%;filter:blur(90px);opacity:.38;animation:drift 14s ease-in-out infinite alternate;pointer-events:none}
.b1{width:560px;height:560px;background:#909090;top:-170px;left:-150px}
.b2{width:420px;height:420px;background:#d8d8d8;bottom:-110px;right:-90px;animation-delay:-5s}
.b3{width:300px;height:300px;background:#b4b4b4;top:46%;left:54%;animation-delay:-9s}
@keyframes drift{from{transform:translate(0,0) scale(1)}to{transform:translate(36px,22px) scale(1.1)}}

/* ── Particle canvas ── */
canvas{position:fixed;inset:0;pointer-events:none;z-index:0}

/* ── Center wrapper ── */
.wrap{height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;z-index:1}

/* ── Glass card — large + shimmer ── */
.card{
    background:linear-gradient(155deg,rgba(215,215,215,.52) 0%,rgba(185,188,193,.56) 100%);
    border:1px solid rgba(255,255,255,.58);
    border-radius:32px;
    backdrop-filter:blur(32px) saturate(160%);
    -webkit-backdrop-filter:blur(32px) saturate(160%);
    box-shadow:0 28px 80px rgba(0,0,0,.32),inset 0 1px 0 rgba(255,255,255,.75);
    width:100%;max-width:500px;
    padding:52px 48px 44px;
    position:relative;overflow:hidden;
    animation:cardIn .6s cubic-bezier(.22,1,.36,1);
    transition:transform .12s ease,box-shadow .12s ease;
}
/* Inner highlight dome */
.card::before{
    content:'';position:absolute;top:0;left:0;right:0;height:54%;
    border-radius:32px 32px 62% 62%;
    background:radial-gradient(ellipse at 40% 0%,rgba(255,255,255,.52),transparent 68%);
    pointer-events:none;z-index:0;
}
/* Shimmer sweep animation */
.card::after{
    content:'';position:absolute;inset:0;border-radius:inherit;
    background:linear-gradient(115deg,transparent 0%,rgba(255,255,255,0) 36%,rgba(255,255,255,.22) 50%,rgba(255,255,255,0) 64%,transparent 100%);
    background-size:220% 220%;
    animation:shimmer 5s ease-in-out infinite;
    pointer-events:none;z-index:0;
}
@keyframes shimmer{0%,100%{background-position:220% center;opacity:0}42%,58%{background-position:-220% center;opacity:1}}
@keyframes cardIn{from{opacity:0;transform:translateY(26px) scale(.97)}to{opacity:1;transform:none}}

/* ── Title ── */
h1{
    font-family:'Bebas Neue',sans-serif;
    font-size:2.2rem;text-align:center;
    margin-bottom:26px;letter-spacing:.06em;
    position:relative;z-index:1;
    text-shadow:0 1px 0 rgba(255,255,255,.55);
}

/* ── Tab bar ── */
.tabs{
    display:flex;
    background:rgba(0,0,0,.10);
    border:1px solid rgba(255,255,255,.3);
    border-radius:50px;padding:4px;
    margin-bottom:28px;
    position:relative;z-index:1;
}
.tab{
    flex:1;padding:11px 0;border:none;border-radius:50px;
    cursor:pointer;font-family:'DM Sans',sans-serif;
    font-size:.86rem;font-weight:600;
    background:transparent;color:rgba(30,30,30,.56);
    transition:all .22s ease;
}
.tab.on{background:#fff;color:#1a1a1a;box-shadow:0 2px 12px rgba(0,0,0,.14)}
.panel{display:none;position:relative;z-index:1}
.panel.on{display:block}

/* ── Input fields ── */
.field{position:relative;margin-bottom:13px}
.ficon{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:rgba(40,40,40,.45);pointer-events:none;display:flex;align-items:center}
.fsep{position:absolute;left:40px;top:50%;transform:translateY(-50%);width:1px;height:20px;background:rgba(0,0,0,.14)}
.field input{
    width:100%;
    padding:14px 46px 14px 54px;
    background:rgba(255,255,255,.84);
    border:1.5px solid rgba(180,182,188,.52);
    border-radius:50px;
    font-family:'DM Sans',sans-serif;font-size:.94rem;color:#1a1a1a;
    outline:none;
    transition:border-color .22s,box-shadow .22s,background .22s,transform .15s;
    -webkit-appearance:none;
}
.field input::placeholder{color:rgba(50,50,50,.38)}
.field input:focus{
    background:rgba(255,255,255,.97);
    border-color:rgba(40,40,45,.42);
    box-shadow:0 0 0 4px rgba(50,50,55,.08);
    transform:translateY(-1px);
}
.eye{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(50,50,50,.42);display:flex;align-items:center;padding:4px;transition:color .2s}
.eye:hover{color:#1a1a1a}

/* ── Remember / Forgot row ── */
.row{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;padding:0 4px}
.row label{display:flex;align-items:center;gap:7px;font-size:.82rem;color:rgba(40,40,40,.65);cursor:pointer}
.row label input{accent-color:#1a1a1a}
.row a{font-size:.82rem;color:rgba(40,40,40,.65);text-decoration:underline;text-underline-offset:2px;transition:color .2s}
.row a:hover{color:#000}

/* ── Buttons ── */
.btn{
    display:block;width:100%;padding:15px;
    background:#1a1a1a;color:#fff;
    border:none;border-radius:50px;
    font-family:'DM Sans',sans-serif;font-size:.96rem;font-weight:700;
    cursor:pointer;text-align:center;
    transition:transform .18s,box-shadow .18s,background .18s;
    position:relative;overflow:hidden;
    box-shadow:0 6px 22px rgba(0,0,0,.32);
}
.btn:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(0,0,0,.42);background:#000}
.btn:active{transform:scale(.98)}
/* Ripple */
.btn .rpl{position:absolute;border-radius:50%;background:rgba(255,255,255,.26);transform:scale(0);animation:rplA .55s ease-out;pointer-events:none}
@keyframes rplA{to{transform:scale(4);opacity:0}}
.btn.blue{background:linear-gradient(135deg,#4a6cf7,#6a8af9)}
.btn.blue:hover{background:linear-gradient(135deg,#3a5ce7,#5a7ae9)}

/* ── Error banner ── */
.err{background:rgba(220,32,32,.12);color:#c0392b;border:1px solid rgba(220,32,32,.24);border-radius:14px;padding:11px 15px;font-size:.87rem;margin-bottom:16px;text-align:center;position:relative;z-index:1;animation:cardIn .3s ease}
.suc{background:rgba(39,174,96,.12);color:#1e8449;border:1px solid rgba(39,174,96,.24);border-radius:14px;padding:11px 15px;font-size:.87rem;margin-bottom:16px;text-align:center;position:relative;z-index:1}

/* ── Sign up link ── */
.switch{text-align:center;margin-top:18px;font-size:.84rem;color:rgba(40,40,40,.65);position:relative;z-index:1}
.switch a{color:#1a1a1a;font-weight:700;text-decoration:underline;text-underline-offset:2px;transition:color .2s}
.switch a:hover{color:#444}

/* ── Admin panel extras ── */
.adm-ico{width:54px;height:54px;border-radius:50%;background:rgba(74,108,247,.12);border:2px solid rgba(74,108,247,.28);display:flex;align-items:center;justify-content:center;margin:0 auto 12px}
.hint{text-align:center;margin-top:12px;font-size:.76rem;color:rgba(40,40,40,.6);background:rgba(0,0,0,.06);border-radius:11px;padding:9px 13px;position:relative;z-index:1}
.hint strong{color:#1a1a1a}

/* ── Cursor glow ── */
#cursor-glow{position:fixed;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.07),transparent 70%);pointer-events:none;z-index:0;transform:translate(-50%,-50%);transition:left .08s,top .08s}
</style>
</head>
<body>

<!-- Background blobs -->
<div class="blob b1"></div>
<div class="blob b2"></div>
<div class="blob b3"></div>

<!-- Particle canvas -->
<canvas id="c"></canvas>

<!-- Cursor glow -->
<div id="cursor-glow"></div>

<div class="wrap">
<div class="card" id="card">

    <h1>Log In</h1>

    <!-- ── Alerts ── -->
    <?php if ($err): ?>
    <div class="err"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['registered'])): ?>
    <div class="suc">Account created! You can now log in.</div>
    <?php endif; ?>
    <?php if (isset($_GET['reset'])): ?>
    <div class="suc">Password reset successfully! Please log in.</div>
    <?php endif; ?>

    <!-- ── Tab Bar ── -->
    <div class="tabs">
        <button class="tab <?= $tab==='user'?'on':'' ?>" onclick="switchTab('user',this)">User Login</button>
        <button class="tab <?= $tab==='admin'?'on':'' ?>" onclick="switchTab('admin',this)">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:4px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Admin
        </button>
    </div>

    <!-- ══ USER PANEL ══ -->
    <div class="panel <?= $tab==='user'?'on':'' ?>" id="p-user">
        <form method="POST" novalidate>
            <!-- Email -->
            <div class="field">
                <span class="ficon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/></svg>
                </span>
                <div class="fsep"></div>
                <input type="email" name="email" placeholder="Email address"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <!-- Password -->
            <div class="field">
                <span class="ficon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <div class="fsep"></div>
                <input type="password" id="upw" name="password" placeholder="Password" required>
                <button type="button" class="eye" onclick="toggleEye('upw',this)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <!-- Remember / Forgot -->
            <div class="row">
                <label><input type="checkbox" name="remember"> Remember me</label>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
            <button type="submit" name="do_user" class="btn" onclick="addRipple(event,this)">Log In</button>
        </form>
        <div class="switch">No account? <a href="register.php">Sign up</a></div>
    </div>

    <!-- ══ ADMIN PANEL ══ -->
    <div class="panel <?= $tab==='admin'?'on':'' ?>" id="p-admin">
        <div class="adm-ico">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4a6cf7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <p style="text-align:center;font-size:.84rem;color:rgba(40,40,40,.6);margin-bottom:18px;position:relative;z-index:1">Admin Panel Access</p>
        <form method="POST" novalidate>
            <!-- Username or email -->
            <div class="field">
                <span class="ficon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <div class="fsep"></div>
                <input type="text" name="auser" placeholder="Username or admin email" autocomplete="off">
            </div>
            <!-- Admin password -->
            <div class="field">
                <span class="ficon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <div class="fsep"></div>
                <input type="password" id="apw" name="apass" placeholder="Password">
                <button type="button" class="eye" onclick="toggleEye('apw',this)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <button type="submit" name="do_admin" class="btn blue" style="margin-top:6px" onclick="addRipple(event,this)">
                Enter Admin Panel
            </button>
        </form>
        <div class="hint">
            Master: <strong>yahye@iam.com</strong> / <strong>46545644</strong><br>
            Or use your <strong>admin email + password</strong> from the database
        </div>
    </div>

</div>
</div>

<script>
/* ─── Particle web with connecting lines ─────────────────── */
(function(){
    var c=document.getElementById('c'),x=c.getContext('2d'),W,H,P=[];
    var MAX=115;
    function resize(){W=c.width=innerWidth;H=c.height=innerHeight}
    resize();window.addEventListener('resize',resize);
    function Pt(){
        this.x=Math.random()*W;this.y=Math.random()*H;
        this.vx=(Math.random()-.5)*.28;this.vy=(Math.random()-.5)*.28;
        this.r=Math.random()*2+.6;this.a=Math.random()*.45+.08;
    }
    Pt.prototype.u=function(){
        this.x+=this.vx;this.y+=this.vy;
        if(this.x<-10)this.x=W+10;if(this.x>W+10)this.x=-10;
        if(this.y<-10)this.y=H+10;if(this.y>H+10)this.y=-10;
    };
    for(var i=0;i<55;i++)P.push(new Pt());
    (function loop(){
        x.clearRect(0,0,W,H);
        /* connecting lines */
        for(var i=0;i<P.length;i++){
            for(var j=i+1;j<P.length;j++){
                var dx=P[i].x-P[j].x,dy=P[i].y-P[j].y,d=Math.sqrt(dx*dx+dy*dy);
                if(d<MAX){
                    x.beginPath();x.moveTo(P[i].x,P[i].y);x.lineTo(P[j].x,P[j].y);
                    x.strokeStyle='rgba(255,255,255,'+(1-d/MAX)*.11+')';
                    x.lineWidth=.7;x.stroke();
                }
            }
        }
        /* dots */
        P.forEach(function(p){
            p.u();
            x.beginPath();x.arc(p.x,p.y,p.r,0,Math.PI*2);
            x.fillStyle='rgba(255,255,255,'+p.a+')';x.fill();
        });
        requestAnimationFrame(loop);
    })();
})();

/* ─── Eye / password toggle ─────────────────────────────── */
function toggleEye(id,btn){
    var inp=document.getElementById(id),isPass=inp.type==='password';
    inp.type=isPass?'text':'password';
    btn.style.color=isPass?'#1a1a1a':'rgba(50,50,50,.42)';
    btn.innerHTML=isPass
        ?'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/></svg>'
        :'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}

/* ─── Tab switcher ───────────────────────────────────────── */
function switchTab(name,btn){
    document.querySelectorAll('.tab').forEach(function(b){b.classList.remove('on')});
    document.querySelectorAll('.panel').forEach(function(p){p.classList.remove('on')});
    btn.classList.add('on');
    document.getElementById('p-'+name).classList.add('on');
}

/* ─── Button ripple ──────────────────────────────────────── */
function addRipple(e,btn){
    var r=document.createElement('span'),rect=btn.getBoundingClientRect();
    var sz=Math.max(rect.width,rect.height);
    r.className='rpl';
    r.style.cssText='width:'+sz+'px;height:'+sz+'px;left:'+(e.clientX-rect.left-sz/2)+'px;top:'+(e.clientY-rect.top-sz/2)+'px';
    btn.appendChild(r);
    setTimeout(function(){r.remove()},600);
}

/* ─── 3D magnetic card tilt ──────────────────────────────── */
(function(){
    var card=document.getElementById('card');
    document.addEventListener('mousemove',function(e){
        var dx=(e.clientX-innerWidth/2)/(innerWidth/2);
        var dy=(e.clientY-innerHeight/2)/(innerHeight/2);
        card.style.transform='perspective(920px) rotateY('+(dx*4)+'deg) rotateX('+(-dy*4)+'deg) scale(1.006)';
        card.style.boxShadow=''+(-dx*16)+'px '+(dy*16)+'px 72px rgba(0,0,0,.3),inset 0 1px 0 rgba(255,255,255,.75)';
    });
    document.addEventListener('mouseleave',function(){
        card.style.transform='';card.style.boxShadow='';
    });
})();

/* ─── Cursor glow ────────────────────────────────────────── */
(function(){
    var g=document.getElementById('cursor-glow');
    document.addEventListener('mousemove',function(e){
        g.style.left=e.clientX+'px';g.style.top=e.clientY+'px';
    });
})();

/* ─── Auto-dismiss success alerts after 5s ───────────────── */
document.querySelectorAll('.suc').forEach(function(el){
    setTimeout(function(){
        el.style.transition='all .4s';el.style.opacity='0';
        el.style.maxHeight='0';el.style.padding='0';el.style.marginBottom='0';
        setTimeout(function(){el.remove()},420);
    },5000);
});
</script>
</body>
</html>
