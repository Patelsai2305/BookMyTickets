<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 5rem 5%; display: flex; justify-content: center;">
    <div class="auth-card" style="background: var(--card-dark); padding: 3rem; border-radius: 12px; width: 100%; max-width: 480px; text-align: center; border: 1px solid var(--border-color);">
        <div style="font-size: 4rem; color: var(--accent); margin-bottom: 1rem;">
            <i class="fa-solid fa-envelope-circle-check"></i>
        </div>
        <h2 style="margin-bottom: 0.75rem;">Verify Your Email</h2>
        <p style="color: var(--text-muted); margin-bottom: 0.5rem;">
            We sent a verification link to:
        </p>
        <p id="user-email" style="font-weight: 700; margin-bottom: 1.5rem;">Loading...</p>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">
            Open the email and click the verification link to activate your account.<br>
            <strong style="color: var(--warning);">Tip: also check your Spam / Promotions folder.</strong>
        </p>

        <div id="status-msg" style="display: none; padding: 0.8rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem;"></div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <button id="check-btn" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                <i class="fa fa-rotate"></i> I've Clicked the Link — Continue
            </button>
            <button id="resend-btn" class="btn btn-outline" style="width: 100%; padding: 0.7rem;">
                <i class="fa fa-paper-plane"></i> Resend Verification Email
            </button>
            <button id="logout-btn" class="btn btn-secondary" style="width: 100%; padding: 0.7rem;">
                Use a Different Account
            </button>
        </div>

        <p id="auto-note" style="margin-top: 1.5rem; font-size: 0.8rem; color: var(--text-muted);">
            This page checks automatically every 5 seconds...
        </p>
    </div>
</div>

<script type="module">
    import { auth, sendEmailVerification, signOut, onAuthStateChanged } from './assets/js/firebase-config.js';

    const statusMsg = document.getElementById('status-msg');
    const checkBtn = document.getElementById('check-btn');
    const resendBtn = document.getElementById('resend-btn');

    function showStatus(text, ok) {
        statusMsg.style.display = 'block';
        statusMsg.style.background = ok ? 'rgba(16, 185, 129, 0.15)' : 'rgba(229, 9, 20, 0.15)';
        statusMsg.style.color = ok ? '#10b981' : '#e50914';
        statusMsg.innerText = text;
    }

    onAuthStateChanged(auth, async (user) => {
        if (!user) {
            window.location.href = 'login.php';
            return;
        }
        document.getElementById('user-email').innerText = user.email;

        // Already verified? Go straight in.
        await user.reload();
        if (auth.currentUser.emailVerified) {
            window.location.href = 'index.php';
            return;
        }

        // Auto-poll every 5 seconds so the user gets redirected right
        // after clicking the link in the other tab.
        setInterval(async () => {
            try {
                await auth.currentUser.reload();
                if (auth.currentUser.emailVerified) {
                    showStatus('Email verified! Redirecting...', true);
                    setTimeout(() => window.location.href = 'index.php', 1200);
                }
            } catch (e) { /* ignore poll errors */ }
        }, 5000);
    });

    // Manual check
    checkBtn.onclick = async () => {
        checkBtn.disabled = true;
        try {
            await auth.currentUser.reload();
            if (auth.currentUser.emailVerified) {
                showStatus('Email verified! Redirecting...', true);
                setTimeout(() => window.location.href = 'index.php', 1200);
            } else {
                showStatus('Not verified yet. Please click the link in the email first.', false);
            }
        } catch (err) {
            showStatus('Error checking status: ' + err.message, false);
        } finally {
            checkBtn.disabled = false;
        }
    };

    // Resend
    resendBtn.onclick = async () => {
        resendBtn.disabled = true;
        try {
            await sendEmailVerification(auth.currentUser);
            showStatus('Verification email sent again! Check inbox AND spam folder.', true);
        } catch (err) {
            showStatus(err.code === 'auth/too-many-requests'
                ? 'Too many emails sent. Wait a minute before retrying.'
                : 'Error: ' + err.message, false);
        } finally {
            setTimeout(() => resendBtn.disabled = false, 10000);
        }
    };

    document.getElementById('logout-btn').onclick = () => signOut(auth).then(() => window.location.href = 'login.php');
</script>

<?php include 'includes/footer.php'; ?>
