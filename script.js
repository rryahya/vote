/* ============================================================
   VoteApp — script.js  v2 ULTRA EFFECTS
   - Particle web with connecting lines
   - Magnetic card tilt on mouse move
   - Ripple on all buttons
   - Smooth cursor trail on vote page
   - OTP auto-advance
   - Results live refresh
   ============================================================ */

/* ════════════════════════════════════════════════════
   PARTICLE WEB  (dots + connecting lines)
   ════════════════════════════════════════════════════ */
(function initParticles() {
    const canvas = document.getElementById('particles-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let pts = [], W, H;
    const MAX_DIST = 130;

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }

    function makePts() {
        pts = Array.from({ length: 55 }, () => ({
            x: Math.random() * W,
            y: Math.random() * H,
            r: Math.random() * 2.2 + 0.7,
            vx: (Math.random() - .5) * 0.25,
            vy: (Math.random() - .5) * 0.25,
            o: Math.random() * 0.48 + 0.08
        }));
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);

        /* connecting lines */
        for (let i = 0; i < pts.length; i++) {
            for (let j = i + 1; j < pts.length; j++) {
                const dx = pts[i].x - pts[j].x;
                const dy = pts[i].y - pts[j].y;
                const dist = Math.sqrt(dx*dx + dy*dy);
                if (dist < MAX_DIST) {
                    const alpha = (1 - dist / MAX_DIST) * 0.12;
                    ctx.beginPath();
                    ctx.moveTo(pts[i].x, pts[i].y);
                    ctx.lineTo(pts[j].x, pts[j].y);
                    ctx.strokeStyle = `rgba(255,255,255,${alpha})`;
                    ctx.lineWidth = 0.8;
                    ctx.stroke();
                }
            }
        }

        /* dots */
        pts.forEach(p => {
            p.x += p.vx; p.y += p.vy;
            if (p.x < -8)  p.x = W + 8;
            if (p.x > W+8) p.x = -8;
            if (p.y < -8)  p.y = H + 8;
            if (p.y > H+8) p.y = -8;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255,255,255,${p.o})`;
            ctx.fill();
        });

        requestAnimationFrame(draw);
    }

    resize(); makePts(); draw();
    window.addEventListener('resize', () => { resize(); makePts(); });
})();


/* ════════════════════════════════════════════════════
   MAGNETIC CARD TILT  (auth pages)
   ════════════════════════════════════════════════════ */
(function initTilt() {
    const card = document.querySelector('.auth-card');
    if (!card) return;

    card.addEventListener('mousemove', e => {
        const rect = card.getBoundingClientRect();
        const cx = rect.left + rect.width  / 2;
        const cy = rect.top  + rect.height / 2;
        const dx = (e.clientX - cx) / (rect.width  / 2);
        const dy = (e.clientY - cy) / (rect.height / 2);
        const tiltX =  dy * 6;   // degrees
        const tiltY = -dx * 6;
        card.style.transform = `perspective(900px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(1.012)`;
        card.style.boxShadow = `${-dx*14}px ${dy*14}px 70px rgba(0,0,0,.32), 0 4px 24px rgba(0,0,0,.14), inset 0 2px 0 rgba(255,255,255,.74)`;
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = '';
        card.style.boxShadow = '';
    });
})();


/* ════════════════════════════════════════════════════
   RIPPLE on every .btn-black and .btn-vote-big
   ════════════════════════════════════════════════════ */
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-black, .btn-vote-big, .btn-confirm-vote, .btn-confirm-del, .btn-main');
    if (!btn || btn.disabled) return;
    const r = document.createElement('span');
    r.className = 'ripple';
    const rect = btn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    r.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px`;
    btn.appendChild(r);
    setTimeout(() => r.remove(), 600);
});


/* ════════════════════════════════════════════════════
   CURSOR GLOW  (vote / results pages only)
   ════════════════════════════════════════════════════ */
(function initCursorGlow() {
    if (!document.querySelector('.vote-page-bg, .results-bg')) return;
    const glow = document.createElement('div');
    glow.style.cssText = `
        position:fixed;width:200px;height:200px;border-radius:50%;
        background:radial-gradient(circle,rgba(255,255,255,0.07) 0%,transparent 70%);
        pointer-events:none;z-index:1;
        transform:translate(-50%,-50%);
        transition:left .08s ease,top .08s ease;
    `;
    document.body.appendChild(glow);
    document.addEventListener('mousemove', e => {
        glow.style.left = e.clientX + 'px';
        glow.style.top  = e.clientY + 'px';
    });
})();


/* ════════════════════════════════════════════════════
   PASSWORD VISIBILITY TOGGLE
   ════════════════════════════════════════════════════ */
function togglePwd(inputId, btn) {
    const inp = document.getElementById(inputId);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.style.opacity = inp.type === 'text' ? '0.72' : '0.36';
}


/* ════════════════════════════════════════════════════
   PASSWORD STRENGTH
   ════════════════════════════════════════════════════ */
function checkStrength(val, barId, lblId) {
    let s = 0;
    if (val.length >= 8)           s++;
    if (/[A-Z]/.test(val))         s++;
    if (/[0-9]/.test(val))         s++;
    if (/[^A-Za-z0-9]/.test(val))  s++;
    const cols = ['','#e74c3c','#e67e22','#f1c40f','#27ae60'];
    const lbls = ['','Weak','Fair','Good','Strong'];
    const bar = document.getElementById(barId);
    const lbl = document.getElementById(lblId);
    if (bar) { bar.style.width = (s * 25) + '%'; bar.style.background = cols[s] || ''; }
    if (lbl) { lbl.textContent = lbls[s] || ''; lbl.style.color = cols[s] || ''; }
}


/* ════════════════════════════════════════════════════
   OTP AUTO-ADVANCE
   ════════════════════════════════════════════════════ */
function initOTP() {
    const inputs = document.querySelectorAll('.otp-digit');
    inputs.forEach((inp, i) => {
        inp.addEventListener('input', () => {
            inp.value = inp.value.replace(/\D/g,'').slice(0,1);
            if (inp.value && i < inputs.length - 1) inputs[i+1].focus();
            const h = document.getElementById('otp_full');
            if (h) h.value = Array.from(inputs).map(x => x.value).join('');
        });
        inp.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !inp.value && i > 0) inputs[i-1].focus();
        });
        inp.addEventListener('paste', e => {
            e.preventDefault();
            const t = e.clipboardData.getData('text').replace(/\D/g,'');
            t.split('').forEach((d,j) => { if (inputs[i+j]) inputs[i+j].value = d; });
            const h = document.getElementById('otp_full');
            if (h) h.value = Array.from(inputs).map(x => x.value).join('');
        });
    });
}


/* ════════════════════════════════════════════════════
   MODAL
   ════════════════════════════════════════════════════ */
function openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});


/* ════════════════════════════════════════════════════
   VOTE CONFIRMATION
   ════════════════════════════════════════════════════ */
function confirmVote(candidateId, candidateName) {
    const ne = document.getElementById('modal-cand-name');
    const ie = document.getElementById('modal-cand-id');
    if (ne) ne.textContent = candidateName;
    if (ie) ie.value = candidateId;
    openModal('vote-confirm-modal');
}


/* ════════════════════════════════════════════════════
   TOAST
   ════════════════════════════════════════════════════ */
function showToast(msg, type='success') {
    let c = document.getElementById('toast-container');
    if (!c) { c = document.createElement('div'); c.id = 'toast-container'; document.body.appendChild(c); }
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => {
        t.style.transition = 'all .3s';
        t.style.opacity = '0'; t.style.transform = 'translateX(22px)';
        setTimeout(() => t.remove(), 320);
    }, 3500);
}


/* ════════════════════════════════════════════════════
   RESULTS AUTO-REFRESH
   ════════════════════════════════════════════════════ */
function initAutoRefresh(ajaxUrl, intervalMs) {
    let countdown = intervalMs / 1000;
    const el = document.getElementById('countdown');
    setInterval(() => {
        countdown--;
        if (el) el.textContent = countdown;
        if (countdown <= 0) {
            countdown = intervalMs / 1000;
            fetch(ajaxUrl).then(r => r.json()).then(data => {
                renderResultBars(data);
            }).catch(() => {});
        }
    }, 1000);
}

function renderResultBars(data) {
    const container = document.getElementById('results-container');
    if (!container || !data.results) return;
    const total = data.total || 1;
    container.innerHTML = '';
    data.results.forEach((item, i) => {
        const pct = total > 0 ? Math.round((item.total / total) * 100) : 0;
        const isTop = i === 0 && item.total > 0;
        const div = document.createElement('div');
        div.className = 'result-card' + (isTop ? ' leader' : '');
        div.style.animationDelay = (i * 0.08) + 's';
        div.innerHTML = `
            <div class="r-rank">${isTop ? '🏆' : i + 1}</div>
            <div class="r-avatar"><img src="${item.photo||'images/candidats/default.png'}" alt="" onerror="this.parentNode.innerHTML='<span class=no-ph>👤</span>'"></div>
            <div class="r-info">
                <div class="r-name">${item.prenom} ${item.nom}</div>
                <div class="r-bar-bg"><div class="r-bar-fill" style="width:${pct}%"></div></div>
            </div>
            <div class="r-count">${item.total}<span>${pct}%</span></div>`;
        container.appendChild(div);
    });
    const tv = document.getElementById('stat-total');
    if (tv) tv.textContent = data.total;
}


/* ════════════════════════════════════════════════════
   SCROLL REVEAL  (subtle fade-in on scroll)
   ════════════════════════════════════════════════════ */
(function initReveal() {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.style.opacity = '1';
                e.target.style.transform = 'translateY(0)';
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.result-card, .stat-glass, .ar-bar-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(18px)';
        el.style.transition = 'opacity .45s ease, transform .45s ease';
        observer.observe(el);
    });
})();


/* ════════════════════════════════════════════════════
   DOMContentLoaded bootstraps
   ════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    initOTP();

    /* Auto-dismiss alerts */
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'all .4s';
            el.style.opacity = '0';
            el.style.maxHeight = '0';
            el.style.padding = '0';
            el.style.marginBottom = '0';
            setTimeout(() => el.remove(), 420);
        }, 5000);
    });

    /* Input focus glow on auth card */
    document.querySelectorAll('.input-pill input').forEach(inp => {
        inp.addEventListener('focus', () => {
            inp.closest('.input-pill').style.background = 'rgba(255,255,255,0.97)';
        });
        inp.addEventListener('blur', () => {
            inp.closest('.input-pill').style.background = '';
        });
    });
});
