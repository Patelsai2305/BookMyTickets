<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookMyTicket - Online Cinema Tickets</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-dark">
    <header>
        <a href="index.php" class="logo">
            <i class="fa-solid fa-ticket" style="color: var(--accent);"></i> BOOKMYTICKET
        </a>
        <nav>
            <ul>
                <li><a href="index.php" class="nav-link">Movies</a></li>
                <li><a href="theatres.php" class="nav-link">Theatres</a></li>
                <li id="tickets-link" style="display:none;"><a href="tickets.php" class="nav-link">Tickets & History</a></li>
                <li id="admin-link" style="display:none;"><a href="admin/index.php" class="nav-link" style="color: var(--accent);"><i class="fa-solid fa-user-shield"></i> Admin Panel</a></li>
            </ul>
        </nav>
        <div class="user-actions" id="auth-actions">
            <a href="login.php" class="btn btn-outline">Sign In</a>
            <a href="register.php" class="btn btn-primary">Sign Up</a>
        </div>
        <div class="user-actions" id="user-actions" style="display:none;">
            <a href="profile.php" class="nav-link" style="display: flex; align-items: center; gap: 0.5rem;">
                <span id="header-avatar" style="width: 1.3rem; height: 1.3rem; display: inline-flex; align-items: center; justify-content: center; color: var(--accent);">
                    <i id="header-avatar-placeholder" class="fa-solid fa-circle-user"></i>
                    <img id="header-avatar-image" alt="Profile photo" style="display: none; width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </span>
                <span id="user-name">Profile</span>
            </a>
            <button id="logout-btn" class="btn btn-outline" style="padding: 0.4rem 0.9rem; font-size: 0.85rem;">Logout</button>
        </div>
    </header>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script type="module">
        import { auth, db, doc, getDoc, onAuthStateChanged, signOut } from './assets/js/firebase-config.js';
        import { getHybridImageUrl } from './assets/js/image-utils.js';

        onAuthStateChanged(auth, async (user) => {
            const authActions = document.getElementById('auth-actions');
            const userActions = document.getElementById('user-actions');
            const adminLink = document.getElementById('admin-link');
            const userNameSpan = document.getElementById('user-name');

            if (user) {
                if (!user.emailVerified && !window.location.pathname.endsWith('verify_email.php')) {
                    window.location.href = 'verify_email.php';
                    return;
                }

                authActions.style.display = 'none';
                userActions.style.display = 'flex';
                document.getElementById('tickets-link').style.display = 'block';

                try {
                    const userDoc = await getDoc(doc(db, "Users", user.uid));
                    if (userDoc.exists()) {
                        const userData = userDoc.data();
                        userNameSpan.innerText = userData.fullName || userData.name || user.email.split('@')[0];
                        const avatarUrl = getHybridImageUrl(userData.profileImage, 'w200', '');
                        if (avatarUrl) {
                            const avatarImage = document.getElementById('header-avatar-image');
                            const avatarPlaceholder = document.getElementById('header-avatar-placeholder');
                            avatarImage.src = avatarUrl;
                            avatarImage.onload = () => { avatarImage.style.display = 'block'; avatarPlaceholder.style.display = 'none'; };
                            avatarImage.onerror = () => { avatarImage.removeAttribute('src'); avatarImage.style.display = 'none'; avatarPlaceholder.style.display = 'inline'; };
                        }
                        if (userData.role === 'admin' || userData.isAdmin === true) {
                            adminLink.style.display = 'block';
                        }
                    } else {
                        userNameSpan.innerText = user.email.split('@')[0];
                    }
                } catch (err) {
                    console.error("Error checking user role:", err);
                }
            } else {
                authActions.style.display = 'flex';
                userActions.style.display = 'none';
                adminLink.style.display = 'none';
                document.getElementById('tickets-link').style.display = 'none';
            }
        });

        document.getElementById('logout-btn')?.addEventListener('click', () => {
            signOut(auth).then(() => {
                window.location.href = 'index.php';
            });
        });
    </script>