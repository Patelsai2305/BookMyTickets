<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gross Revenue Details - Admin</title>
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
                            <span class="title-line">Gross</span>
                            <span class="title-line title-line-highlight"><span class="rupee-mark">₹</span>Revenue</span>
                            <span class="title-line">Details</span>
                        </h1>
                    </div>
                    <p class="metric-subheading">Live booking revenue from successful transactions</p>
                    <div class="metric-back-wrap">
                        <a href="index.php" class="metric-reference-back"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
                    </div>

                    <div class="metric-detail-list">
                        <div class="metric-detail-item"><span class="label">Gross revenue</span><span id="summary-total" class="value">₹0</span></div>
                        <div class="metric-detail-item"><span class="label">Bookings</span><span id="summary-count" class="value small">0</span></div>
                        <div class="metric-detail-item"><span class="label">Avg. order</span><span id="summary-average" class="value">₹0</span></div>
                    </div>
                </div>

                <div class="revenue-mini-card">
                    <div class="mini-label">Gross Revenue</div>
                    <div class="mini-amount"><span class="mini-icon">₹</span><span id="summary-top-total">₹0</span></div>
                </div>

                <div class="revenue-chart-wrap">
                    <div class="chart-header">
                        <span>Revenue trend</span>
                        <span class="chip">Last 7 days</span>
                    </div>
                    <div id="revenue-chart" class="revenue-chart" aria-live="polite"></div>
                </div>
            </div>

            <div class="metric-summary-grid" aria-label="Gross revenue summary cards">
                <div class="metric-summary-card">
                    <div class="metric-summary-label">Gross Revenue</div>
                    <div id="card-total" class="metric-summary-value">₹0</div>
                </div>
                <div class="metric-summary-card">
                    <div class="metric-summary-label">Successful Bookings</div>
                    <div id="card-count" class="metric-summary-value">0</div>
                </div>
                <div class="metric-summary-card">
                    <div class="metric-summary-label">Avg. Order Value</div>
                    <div id="card-average" class="metric-summary-value">₹0</div>
                </div>
            </div>

            <div class="data-table-container">
                <div class="table-header">
                    <h3><i class="fa-solid fa-circle-check"></i> Successful bookings</h3>
                    <input id="metric-search" class="table-search" type="text" placeholder="Search movie, email or theatre...">
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Movie</th>
                            <th>Customer</th>
                            <th>Theatre</th>
                            <th>Date</th>
                            <th>Amount</th>
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
        const topTotalEl = document.getElementById('summary-top-total');
        const countEl = document.getElementById('summary-count');
        const averageEl = document.getElementById('summary-average');
        const cardTotalEl = document.getElementById('card-total');
        const cardCountEl = document.getElementById('card-count');
        const cardAverageEl = document.getElementById('card-average');
        const revenueChart = document.getElementById('revenue-chart');

        let allRows = [];

        function money(value) {
            return `₹${Number(value || 0).toLocaleString()}`;
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));
        }

        function showStartMillis(book) {
            const date = book.date || book.showDate || '';
            const time = book.timing || book.showTime || book.time || '';
            if (!date || !time) return 0;
            const value = new Date(`${date} ${time}`);
            return Number.isNaN(value.getTime()) ? 0 : value.getTime();
        }

        function getBookingStatus(book) {
            const raw = String(book.status || book.paymentStatus || 'PAID').toUpperCase().trim();
            if (raw.includes('CANCEL') || raw.includes('REFUND')) return 'Cancelled';
            if (raw.includes('COMPLETED') || raw.includes('SUCCESS') || raw.includes('PAID') || raw.includes('CONFIRM') || raw.includes('BOOKED') || raw.includes('APPROVED')) {
                return showStartMillis(book) && Date.now() > showStartMillis(book) ? 'Completed' : 'Upcoming';
            }
            return showStartMillis(book) && Date.now() > showStartMillis(book) ? 'Completed' : 'Upcoming';
        }

        function buildStatusBadge(status) {
            const s = String(status || '').toUpperCase();
            const className = s === 'CANCELLED' || s === 'REFUNDED' || s === 'CANCEL' ? 'badge-warning' : 'badge-success';
            return `<span class="badge ${className}">${escapeHtml(status || 'Upcoming')}</span>`;
        }

        function getChartData(rows) {
            const map = new Map();
            rows.forEach((row) => {
                const date = row.date || row.showDate || '';
                if (!date) return;
                const key = String(date).slice(5);
                map.set(key, (map.get(key) || 0) + Number(row.amount || 0));
            });

            const labels = Array.from(map.entries()).sort((a, b) => a[0].localeCompare(b[0])).slice(-7);
            if (!labels.length) {
                return [
                    { label: 'N/A', value: 0 },
                    { label: 'N/A', value: 0 },
                    { label: 'N/A', value: 0 },
                    { label: 'N/A', value: 0 },
                    { label: 'N/A', value: 0 },
                    { label: 'N/A', value: 0 },
                    { label: 'N/A', value: 0 }
                ];
            }

            const max = Math.max(...labels.map(([, value]) => value), 1);
            return labels.map(([label, value]) => ({ label, value, height: Math.max((value / max) * 100, 12) }));
        }

        function renderChart(rows) {
            const chartData = getChartData(rows);
            const hasValues = chartData.some((item) => Number(item.value) > 0);

            if (!hasValues || !rows.length) {
                revenueChart.innerHTML = '<div class="metric-list-empty" style="width:100%;display:flex;align-items:center;justify-content:center;min-height:150px;">No revenue data available for the selected period.</div>';
                return;
            }

            const maxValue = Math.max(...chartData.map((item) => item.value), 1);
            revenueChart.innerHTML = chartData.map((item) => `
                <div class="chart-bar-group">
                    <div class="chart-bar-wrap">
                        <div class="chart-bar" style="height:${Math.max((item.value / maxValue) * 100, 14)}%;"></div>
                    </div>
                    <span class="chart-label">${escapeHtml(item.label || '—')}</span>
                </div>
            `).join('');
        }

        function renderRows(rows) {
            if (!rows.length) {
                tableBody.innerHTML = '<tr><td colspan="6"><div class="metric-list-empty">No matching successful bookings found.</div></td></tr>';
                totalEl.textContent = '₹0';
                topTotalEl.textContent = '₹0';
                cardTotalEl.textContent = '₹0';
                countEl.textContent = '0';
                cardCountEl.textContent = '0';
                averageEl.textContent = '₹0';
                cardAverageEl.textContent = '₹0';
                renderChart([]);
                return;
            }

            const total = rows.reduce((sum, row) => sum + Number(row.amount || 0), 0);
            totalEl.textContent = money(total);
            topTotalEl.textContent = money(total);
            cardTotalEl.textContent = money(total);
            countEl.textContent = String(rows.length);
            cardCountEl.textContent = String(rows.length);
            averageEl.textContent = money(total / rows.length);
            cardAverageEl.textContent = money(total / rows.length);
            renderChart(rows);

            tableBody.innerHTML = rows.map((row) => `
                <tr>
                    <td><strong>${escapeHtml(row.movie)}</strong></td>
                    <td>
                        <span>${escapeHtml(row.user)}</span><br>
                        <span class="cell-muted">${escapeHtml(row.email)}</span>
                    </td>
                    <td><span>${escapeHtml(row.theatre)}</span></td>
                    <td><span class="cell-muted">${escapeHtml(row.date)}</span></td>
                    <td><strong>${escapeHtml(row.amountText)}</strong></td>
                    <td>${buildStatusBadge(row.status)}</td>
                </tr>
            `).join('');
        }

        function applySearch() {
            const q = searchInput.value.toLowerCase();
            const filtered = allRows.filter((row) => {
                const haystack = `${row.movie} ${row.user} ${row.email} ${row.theatre} ${row.date}`.toLowerCase();
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

            onSnapshot(collection(db, 'bookingticket_app_web'), (snap) => {
                allRows = snap.docs
                    .map((docSnap) => ({ id: docSnap.id, ...docSnap.data() }))
                    .filter((booking) => {
                        const status = String(booking.status || booking.paymentStatus || '').toUpperCase();
                        return status !== 'REFUNDED' && status !== 'CANCELLED';
                    })
                    .map((booking) => ({
                        movie: booking.movieTitle || 'Movie',
                        user: booking.userName || booking.userEmail || 'Unknown user',
                        email: booking.userEmail || 'Unknown user',
                        theatre: booking.theatreName || booking.theatreId || 'Theatre',
                        date: booking.date || booking.showDate || 'No date',
                        status: getBookingStatus(booking),
                        amount: Number(booking.totalPrice ?? booking.totalAmount ?? 0),
                        amountText: money(booking.totalPrice ?? booking.totalAmount ?? 0)
                    }))
                    .sort((a, b) => b.amount - a.amount);

                renderRows(allRows);
            });
        });

        searchInput.addEventListener('input', applySearch);
    </script>
</body>
</html>
