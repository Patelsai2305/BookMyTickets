<?php include 'includes/header.php'; ?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="auth-card">
        <h2 class="text-center mb-4">Join BookMyTicket</h2>
        <form id="register-form">
            <div class="form-group mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" id="name" class="form-control" required placeholder="John Doe">
            </div>
            <div class="form-group mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" id="email" class="form-control" required placeholder="name@example.com">
            </div>
            <div class="form-group mb-4">
                <label class="form-label">Password</label>
                <input type="password" id="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">Create Account</button>
        </form>

        <div id="registerError" class="alert alert-danger d-none mt-3 small text-center" role="alert"></div>

        <p class="text-center mt-4 text-muted small">
            Already have an account? <a href="login.php" class="text-danger fw-bold text-decoration-none">Login</a>
        </p>
    </div>
</div>

<script type="module">
    import { auth, db, createUserWithEmailAndPassword, sendEmailVerification, doc, setDoc, serverTimestamp } from './assets/js/firebase-config.js';

    document.getElementById('register-form').onsubmit = async (e) => {
        e.preventDefault();
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('registerError');

        errorDiv.classList.add('d-none');

        try {
            const userCredential = await createUserWithEmailAndPassword(auth, email, password);
            const user = userCredential.user;

            await setDoc(doc(db, "Users", user.uid), {
                uid: user.uid,
                fullName: name,
                email,
                role: 'user',
                createdAt: serverTimestamp()
            });

            await sendEmailVerification(user);
            window.location.href = 'verify_email.php';
        } catch (error) {
            errorDiv.textContent = "Registration Error: " + error.message;
            errorDiv.classList.remove('d-none');
        }
    };
</script>

<?php include 'includes/footer.php'; ?>
