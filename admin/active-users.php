<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Users - Admin</title>
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
                <li><a href="movies.php"><i class="fa-solid fa-film"></i> Movies</a></li>
                <li><a href="bookings.php"><i class="fa-solid fa-ticket"></i> Bookings</a></li>
                <li><a href="showtimes.php"><i class="fa-solid fa-film"></i> Showtimes</a></li>
                <li><a href="theatres.php"><i class="fa-solid fa-location-dot"></i> Theatres</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users"></i> Users</a></li>
                <li><a href="../index.php"><i class="fa-solid fa-house"></i> View Website</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        <section class="metric-page metric-reference-layout">
            <div class="revenue-hero-shell">
                <div class="revenue-overview-panel">
                    <div class="revenue-header-row">
                        <h1 class="metric-big-title">
                            <span class="title-line">Active</span>
                            <span class="title-line title-line-highlight">Users</span>
                        </h1>
                    </div>
                    <p class="metric-subheading">Registered customers and admin accounts</p>
                    <div class="metric-back-wrap">
                        <a href="index.php" class="metric-reference-back"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
                    </div>

                    <div class="metric-detail-list">
                        <div class="metric-detail-item"><span class="label">Total users</span><span id="summary-total" class="value">0</span></div>
                        <div class="metric-detail-item"><span class="label">Admins</span><span id="summary-admins" class="value small">0</span></div>
                        <div class="metric-detail-item"><span class="label">Customers</span><span id="summary-customers" class="value">0</span></div>
                    </div>
                </div>

                <div class="revenue-mini-card users">
                    <div class="mini-label">Active Users</div>
                    <div class="mini-amount"><span class="mini-icon"><i class="fa-solid fa-users"></i></span><span id="mini-total">0</span></div>
                </div>

                <div class="revenue-chart-wrap">
                    <div class="chart-header">
                        <span>User mix</span>
                        <span class="chip">Live</span>
                    </div>
                    <div id="revenue-chart" class="revenue-chart" aria-live="polite"></div>
                </div>
            </div>

            <div class="metric-summary-grid">
                <div class="metric-summary-card users">
                    <div class="metric-summary-label">Total users</div>
                    <div id="card-total" class="metric-summary-value">0</div>
                </div>
                <div class="metric-summary-card users">
                    <div class="metric-summary-label">Admins</div>
                    <div id="card-admins" class="metric-summary-value">0</div>
                </div>
                <div class="metric-summary-card users">
                    <div class="metric-summary-label">Customers</div>
                    <div id="card-customers" class="metric-summary-value">0</div>
                </div>
            </div>

            <div class="data-table-container">
                <div class="table-header">
                    <h3><i class="fa-solid fa-user-group"></i> User directory</h3>
                    <input id="metric-search" class="table-search" type="text" placeholder="Search name or email...">
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="metric-table-body"></tbody>
                </table>
            </div>
        </section>
    </main>

    <script type="module">
        import { auth, db, collection, onSnapshot, onAuthStateChanged, doc, getDoc } from '../assets/js/firebase-config.js';

        const tableBody = document.getElementById('metric-table-body');
        const searchInput = document.getElementById('metric-search');
        const totalEl = document.getElementById('summary-total');
        const adminsEl = document.getElementById('summary-admins');
        const customersEl = document.getElementById('summary-customers');
        const miniTotalEl = document.getElementById('mini-total');
        const cardTotalEl = document.getElementById('card-total');
        const cardAdminsEl = document.getElementById('card-admins');
        const cardCustomersEl = document.getElementById('card-customers');
        const revenueChart = document.getElementById('revenue-chart');

        let allRows = [];

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));
        }

        function roleBadge(role) {
            const className = role === 'Admin' ? 'badge-admin' : 'badge-user';
            return `<span class="badge ${className}">${escapeHtml(role)}</span>`;
        }

        function statusBadge(status) {
            const className = status === 'Active' ? 'badge-success' : 'badge-user';
            return `<span class="badge ${className}">${escapeHtml(status)}</span>`;
        }

        function renderChart(rows) {
            const admins = rows.filter((row) => row.role === 'Admin').length;
            const customers = rows.filter((row) => row.role !== 'Admin').length;
            const chartData = [
                { label: 'Admins', value: admins },
                { label: 'Customers', value: customers }
            ];
            const maxValue = Math.max(...chartData.map((item) => Number(item.value || 0)), 1);
            const hasValues = chartData.some((item) => Number(item.value) > 0);

            if (!hasValues || !rows.length) {
                revenueChart.innerHTML = '<div class="metric-list-empty" style="width:100%;display:flex;align-items:center;justify-content:center;min-height:150px;">No user data available.</div>';
                return;
            }

            revenueChart.innerHTML = chartData.map((item) => `
                <div class="chart-bar-group">
                    <div class="chart-bar-wrap">
                        <div class="chart-bar" style="height:${Math.max((Number(item.value) / maxValue) * 100, 14)}%;"></div>
                    </div>
                    <span class="chart-label">${escapeHtml(item.label || '—')}</span>
                </div>
            `).join('');
        }

        function renderRows(rows) {
            if (!rows.length) {
                tableBody.innerHTML = '<tr><td colspan="4"><div class="metric-list-empty">No users found.</div></td></tr>';
                totalEl.textContent = '0';
                adminsEl.textContent = '0';
                customersEl.textContent = '0';
                miniTotalEl.textContent = '0';
                cardTotalEl.textContent = '0';
                cardAdminsEl.textContent = '0';
                cardCustomersEl.textContent = '0';
                renderChart([]);
                return;
            }

            const adminCount = rows.filter((row) => row.role === 'Admin').length;
            const customerCount = rows.filter((row) => row.role !== 'Admin').length;

            totalEl.textContent = String(rows.length);
            adminsEl.textContent = String(adminCount);
            customersEl.textContent = String(customerCount);
            miniTotalEl.textContent = String(rows.length);
            cardTotalEl.textContent = String(rows.length);
            cardAdminsEl.textContent = String(adminCount);
            cardCustomersEl.textContent = String(customerCount);
            renderChart(rows);

            tableBody.innerHTML = rows.map((row) => `
                <tr>
                    <td><strong>${escapeHtml(row.name)}</strong></td>
                    <td><span class="cell-muted">${escapeHtml(row.email)}</span></td>
                    <td>${roleBadge(row.role)}</td>
                    <td>${statusBadge(row.status)}</td>
                </tr>
            `).join('');
        }

        function applySearch() {
            const q = searchInput.value.toLowerCase();
            const filtered = allRows.filter((row) => {
                const haystack = `${row.name} ${row.email}`.toLowerCase();
                return haystack.includes(q);
            });
            renderRows(filtered);
        }

        onAuthStateChanged(auth, async (user) => {
            if (!user) {
                window.location.href = '../login.php';
                return;
            }

            const userDoc = await getDoc(doc(db, 'Users', user.uid));
            const data = userDoc.data() || {};

            if (!userDoc.exists() || (data.role !== 'admin' && data.isAdmin !== true && data.isAdmin !== 'true')) {
                window.location.href = '../index.php';
                return;
            }

            onSnapshot(collection(db, 'Users'), (snap) => {
                allRows = snap.docs
                    .map((docSnap) => ({ id: docSnap.id, ...docSnap.data() }))
                    .map((u) => ({
                        name: u.fullName || u.name || 'Unknown user',
                        email: u.email || u.userEmail || 'No email',
                        role: u.role === 'admin' || u.isAdmin === true || u.isAdmin === 'true' ? 'Admin' : 'Customer',
                        status: u.isAdmin ? 'Active' : 'Online'
                    }))
                    .sort((a, b) => a.role.localeCompare(b.role));

                renderRows(allRows);
            });
        });

        searchInput.addEventListener('input', applySearch);
    </script>
</body>
</html>
