<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 5rem 5%; display: flex; justify-content: center;">
    <div class="auth-card" style="background: var(--card-dark); padding: 3rem; border-radius: 12px; width: 100%; max-width: 420px; border: 1px solid var(--border-color);">
        <h2 style="margin-bottom: 0.5rem; text-align: center;">Reset Password</h2>
        <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">
            Enter your email address and we'll send you a password reset link.
        </p>

        <div id="alert-msg" style="display: none; padding: 0.8rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; text-align: center;"></div>

        <form id="forgot-form">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="email" class="form-input" required placeholder="name@example.com">
            </div>
            <button type="submit" id="submit-btn" class="btn btn-primary" style="width: 100%; padding: 0.9rem; margin-top: 0.5rem;">Send Reset Link</button>
        </form>

        <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem;">
            <a href="login.php" style="color: var(--accent);"><i class="fa fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</div>

<script type="module">
    import { auth, sendPasswordResetEmail } from './assets/js/firebase-config.js';

    const forgotForm = document.getElementById('forgot-form');
    const alertMsg = document.getElementById('alert-msg');
    const submitBtn = document.getElementById('submit-btn');

    forgotForm.onsubmit = async (e) => {
        e.preventDefault();
        const email = document.getElementById('email').value.trim();

        submitBtn.disabled = true;
        submitBtn.innerText = "Sending...";

        try {
            await sendPasswordResetEmail(auth, email);
            alertMsg.style.display = 'block';
            alertMsg.style.background = 'rgba(16, 185, 129, 0.15)';
            alertMsg.style.color = '#10b981';
            alertMsg.innerText = 'Password reset link sent! Check your inbox.';
            forgotForm.reset();
        } catch (error) {
            alertMsg.style.display = 'block';
            alertMsg.style.background = 'rgba(229, 9, 20, 0.15)';
            alertMsg.style.color = '#e50914';
            alertMsg.innerText = error.message;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = "Send Reset Link";
        }
    };
</script>

<?php include 'includes/footer.php'; ?>
