<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Admin</title>
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
                            <span class="title-line">Booking</span>
                            <span class="title-line title-line-highlight">Details</span>
                        </h1>
                    </div>
                    <p class="metric-subheading">Every booking, amount and ticket status</p>
                    <div class="metric-back-wrap">
                        <a href="index.php" class="metric-reference-back"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
                    </div>

                    <div class="metric-detail-list">
                        <div class="metric-detail-item"><span class="label">Total bookings</span><span id="summary-total" class="value">0</span></div>
                        <div class="metric-detail-item"><span class="label">Completed</span><span id="summary-completed" class="value small">0</span></div>
                        <div class="metric-detail-item"><span class="label">Cancelled</span><span id="summary-cancelled" class="value">0</span></div>
                    </div>
                </div>

                <div class="revenue-mini-card bookings">
                    <div class="mini-label">Booking Details</div>
                    <div class="mini-amount"><span class="mini-icon"><i class="fa-solid fa-ticket"></i></span><span id="summary-top-total">0</span></div>
                </div>

                <div class="revenue-chart-wrap">
                    <div class="chart-header">
                        <span>Booking trend</span>
                        <span class="chip">Live</span>
                    </div>
                    <div id="revenue-chart" class="revenue-chart" aria-live="polite"></div>
                </div>
            </div>

            <div class="metric-summary-grid" aria-label="Booking summary cards">
                <div class="metric-summary-card bookings">
                    <div class="metric-summary-label">Total Bookings</div>
                    <div id="card-total" class="metric-summary-value">0</div>
                </div>
                <div class="metric-summary-card bookings">
                    <div class="metric-summary-label">Completed</div>
                    <div id="card-completed" class="metric-summary-value">0</div>
                </div>
                <div class="metric-summary-card refund">
                    <div class="metric-summary-label">Cancelled</div>
                    <div id="card-cancelled" class="metric-summary-value">0</div>
                </div>
            </div>

            <div class="data-table-container">
                <div class="table-header">
                    <h3><i class="fa-solid fa-list"></i> Recent booking activity</h3>
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
        const completedEl = document.getElementById('summary-completed');
        const cancelledEl = document.getElementById('summary-cancelled');
        const cardTotalEl = document.getElementById('card-total');
        const cardCompletedEl = document.getElementById('card-completed');
        const cardCancelledEl = document.getElementById('card-cancelled');
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

        function renderChart(rows) {
            const completed = rows.filter((row) => row.status === 'Completed').length;
            const cancelled = rows.filter((row) => row.status === 'Cancelled').length;
            const chartData = [
                { label: 'Done', value: completed },
                { label: 'Cancel', value: cancelled },
                { label: 'Total', value: rows.length }
            ];
            const maxValue = Math.max(...chartData.map((item) => Number(item.value || 0)), 1);
            const hasValues = chartData.some((item) => Number(item.value) > 0);
            if (!hasValues) {
                revenueChart.innerHTML = '<div class="metric-list-empty" style="width:100%;display:flex;align-items:center;justify-content:center;min-height:150px;">No booking data available.</div>';
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
                tableBody.innerHTML = '<tr><td colspan="6"><div class="metric-list-empty">No bookings found.</div></td></tr>';
                totalEl.textContent = '0';
                topTotalEl.textContent = '0';
                cardTotalEl.textContent = '0';
                completedEl.textContent = '0';
                cardCompletedEl.textContent = '0';
                cancelledEl.textContent = '0';
                cardCancelledEl.textContent = '0';
                renderChart([]);
                return;
            }

            totalEl.textContent = String(rows.length);
            topTotalEl.textContent = String(rows.length);
            cardTotalEl.textContent = String(rows.length);
            const completedCount = rows.filter((row) => row.status === 'Completed').length;
            const cancelledCount = rows.filter((row) => row.status === 'Cancelled').length;
            completedEl.textContent = String(completedCount);
            cardCompletedEl.textContent = String(completedCount);
            cancelledEl.textContent = String(cancelledCount);
            cardCancelledEl.textContent = String(cancelledCount);
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
                    .sort((a, b) => Number((b.createdAt && b.createdAt.seconds) || 0) - Number((a.createdAt && a.createdAt.seconds) || 0))
                    .map((booking) => {
                        const displayStatus = getBookingStatus(booking);
                        return {
                            movie: booking.movieTitle || 'Movie',
                            user: booking.userName || booking.userEmail || 'Unknown user',
                            email: booking.userEmail || 'Unknown user',
                            theatre: booking.theatreName || booking.theatreId || 'Theatre',
                            date: booking.date || booking.showDate || 'No date',
                            status: displayStatus,
                            amount: Number(booking.totalPrice ?? booking.totalAmount ?? 0),
                            amountText: money(booking.totalPrice ?? booking.totalAmount ?? 0)
                        };
                    });

                renderRows(allRows);
            });
        });

        searchInput.addEventListener('input', applySearch);
    </script>
</body>
</html>
