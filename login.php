<?php include 'includes/header.php'; ?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="auth-card">
        <h2 class="text-center mb-4">Login to BookMyTicket</h2>
        <form id="login-form">
            <div class="form-group mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" id="email" class="form-control" required placeholder="name@example.com">
            </div>
            <div class="form-group mb-4">
                <label class="form-label">Password</label>
                <input type="password" id="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">Login</button>
        </form>

        <div id="loginError" class="alert alert-danger d-none mt-3 small text-center" role="alert"></div>

        <p class="text-center mt-4 text-muted small">
            Don't have an account? <a href="register.php" class="text-danger fw-bold text-decoration-none">Sign Up</a>
        </p>
    </div>
</div>

<script type="module">
    import { auth, db, doc, getDoc, signInWithEmailAndPassword, sendEmailVerification } from './assets/js/firebase-config.js';

    document.getElementById('login-form').onsubmit = async (e) => {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('loginError');

        errorDiv.classList.add('d-none');

        try {
            const cred = await signInWithEmailAndPassword(auth, email, password);

            if (!cred.user.emailVerified) {
                await sendEmailVerification(cred.user).catch(() => {});
                window.location.href = 'verify_email.php';
                return;
            }

            const userDoc = await getDoc(doc(db, 'Users', cred.user.uid));
            const isAdmin = userDoc.exists() && (userDoc.data().role === 'admin' || userDoc.data().isAdmin === true);
            if (isAdmin) {
                window.location.href = 'admin/index.php';
                return;
            }

            const urlParams = new URLSearchParams(window.location.search);
            const redirect = urlParams.get('redirect') || 'index.php';
            window.location.href = redirect;
        } catch (error) {
            errorDiv.textContent = "Login Error: " + error.message;
            errorDiv.classList.remove('d-none');
        }
    };
</script>

<?php include 'includes/footer.php'; ?>
