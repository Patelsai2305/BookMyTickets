<?php
// Admin guard is enforced client-side via Firebase Auth + role check (see script below).
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - BookMyTicket Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <aside class="admin-sidebar">
        <h2 class="admin-brand"><i class="fa-solid fa-user-shield"></i> BMT Admin</h2>
        <nav>
            <ul class="admin-nav">
                <li><a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
                <li><a href="bookings.php"><i class="fa-solid fa-ticket"></i> Bookings</a></li>
                <li><a href="showtimes.php"><i class="fa-solid fa-film"></i> Showtimes</a></li>
                <li><a href="users.php" class="active"><i class="fa-solid fa-users"></i> Users</a></li>
                <li><a href="../index.php"><i class="fa-solid fa-house"></i> View Website</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <div>
                <h1><i class="fa-solid fa-users"></i> Users</h1>
                <p class="admin-subtitle">Manage registered user accounts</p>
            </div>
            <div class="admin-header-right">
                <input type="text" id="user-search" class="table-search" placeholder="Search by name or email...">
            </div>
        </div>

        <div class="data-table-container">
            <div class="table-header">
                <h3>Registered Users</h3>
            </div>
            <table class="data-table" style="min-width: 700px;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <tr><td colspan="6"><div class="loading-cell">Loading users...</div></td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <script type="module">
        import { auth, db, collection, getDocs, doc, getDoc, updateDoc, onAuthStateChanged } from '../assets/js/firebase-config.js';

        const tbody = document.getElementById('users-table-body');
        const searchInput = document.getElementById('user-search');
        let allUsers = [];

        onAuthStateChanged(auth, async (user) => {
            if (!user) { window.location.href = '../login.php?redirect=' + encodeURIComponent(window.location.href); return; }
            const userDoc = await getDoc(doc(db, "Users", user.uid));
            if (!userDoc.exists() || (userDoc.data().role !== 'admin' && userDoc.data().isAdmin !== true)) {
                window.location.href = '../index.php';
                return;
            }
            loadUsers();
        });

        async function loadUsers() {
            const querySnapshot = await getDocs(collection(db, "Users"));
            allUsers = querySnapshot.docs.map(d => ({ id: d.id, ...d.data() }));
            renderUsers(allUsers);
        }

        function renderUsers(users) {
            if (users.length === 0) {
                tbody.innerHTML = '';
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 6;
                td.innerHTML = '<div class="empty-state"><i class="fa-solid fa-user-slash"></i><p>No users found.</p></div>';
                tr.appendChild(td);
                tbody.appendChild(tr);
                return;
            }
            tbody.innerHTML = '';
            users.forEach(u => {
                const joined = u.createdAt ? new Date(u.createdAt.seconds * 1000).toLocaleDateString() : '—';
                const isAdmin = u.role === 'admin' || u.isAdmin === true;
                const tr = document.createElement('tr');

                const tdName = document.createElement('td');
                const strong = document.createElement('strong');
                strong.textContent = u.fullName || u.name || '—';
                tdName.appendChild(strong);

                const tdEmail = document.createElement('td');
                tdEmail.textContent = u.email || '—';

                const tdPhone = document.createElement('td');
                const phoneSpan = document.createElement('span');
                phoneSpan.className = 'cell-muted';
                phoneSpan.textContent = u.phone || '—';
                tdPhone.appendChild(phoneSpan);

                const tdJoined = document.createElement('td');
                tdJoined.className = 'cell-muted';
                tdJoined.textContent = joined;

                const tdRole = document.createElement('td');
                const roleBadge = document.createElement('span');
                roleBadge.className = 'badge ' + (isAdmin ? 'badge-admin' : 'badge-user');
                roleBadge.textContent = isAdmin ? 'ADMIN' : 'USER';
                tdRole.appendChild(roleBadge);

                const tdActions = document.createElement('td');
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-outline btn-sm role-toggle';
                toggleBtn.dataset.id = u.id;
                toggleBtn.dataset.role = u.role || 'user';
                toggleBtn.textContent = isAdmin ? 'Demote to User' : 'Promote to Admin';
                tdActions.appendChild(toggleBtn);

                tr.appendChild(tdName);
                tr.appendChild(tdEmail);
                tr.appendChild(tdPhone);
                tr.appendChild(tdJoined);
                tr.appendChild(tdRole);
                tr.appendChild(tdActions);
                tbody.appendChild(tr);
            });

            tbody.querySelectorAll('.role-toggle').forEach(btn => {
                btn.onclick = async () => {
                    const id = btn.dataset.id;
                    const newRole = btn.dataset.role === 'admin' ? 'user' : 'admin';
                    if (!confirm(`Change this user's role to "${newRole}"?`)) return;
                    btn.disabled = true;
                    try {
                        await updateDoc(doc(db, "Users", id), { role: newRole });
                        const u = allUsers.find(x => x.id === id);
                        if (u) u.role = newRole;
                        renderUsers(allUsers);
                    } catch (err) {
                        alert('Failed to update role: ' + err.message);
                        btn.disabled = false;
                    }
                };
            });
        }

        searchInput.oninput = (e) => {
            const term = e.target.value.toLowerCase();
            renderUsers(allUsers.filter(u =>
                (u.name || '').toLowerCase().includes(term) ||
                (u.email || '').toLowerCase().includes(term)
            ));
        };
    </script>
</body>
</html>
