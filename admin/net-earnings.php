<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Net Earnings - Admin</title>
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
                            <span class="title-line">Net</span>
                            <span class="title-line title-line-highlight"><span class="rupee-mark">₹</span>Earnings</span>
                        </h1>
                    </div>
                    <p class="metric-subheading">Gross revenue minus refund losses</p>
                    <div class="metric-back-wrap">
                        <a href="index.php" class="metric-reference-back"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
                    </div>

                    <div class="metric-detail-list">
                        <div class="metric-detail-item"><span class="label">Gross revenue</span><span id="gross-total" class="value">₹0</span></div>
                        <div class="metric-detail-item"><span class="label">Refunds</span><span id="refund-total" class="value small">₹0</span></div>
                        <div class="metric-detail-item"><span class="label">Net earnings</span><span id="net-total" class="value">₹0</span></div>
                    </div>
                </div>

                <div class="revenue-mini-card net">
                    <div class="mini-label">Net Earnings</div>
                    <div class="mini-amount"><span class="mini-icon">₹</span><span id="net-top-total">₹0</span></div>
                </div>

                <div class="revenue-chart-wrap">
                    <div class="chart-header">
                        <span>Earnings trend</span>
                        <span class="chip">Live</span>
                    </div>
                    <div id="revenue-chart" class="revenue-chart" aria-live="polite"></div>
                </div>
            </div>

            <div class="metric-summary-grid" aria-label="Net earnings summary cards">
                <div class="metric-summary-card net">
                    <div class="metric-summary-label">Gross Revenue</div>
                    <div id="card-gross" class="metric-summary-value">₹0</div>
                </div>
                <div class="metric-summary-card refund">
                    <div class="metric-summary-label">Refunds</div>
                    <div id="card-refunds" class="metric-summary-value">₹0</div>
                </div>
                <div class="metric-summary-card net">
                    <div class="metric-summary-label">Net Earnings</div>
                    <div id="card-net" class="metric-summary-value">₹0</div>
                </div>
            </div>

            <div class="data-table-container">
                <div class="table-header">
                    <h3><i class="fa-solid fa-chart-column"></i> Earnings breakdown</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Value</th>
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
        const grossEl = document.getElementById('gross-total');
        const refundEl = document.getElementById('refund-total');
        const netEl = document.getElementById('net-total');
        const netTopEl = document.getElementById('net-top-total');
        const cardGrossEl = document.getElementById('card-gross');
        const cardRefundsEl = document.getElementById('card-refunds');
        const cardNetEl = document.getElementById('card-net');
        const revenueChart = document.getElementById('revenue-chart');

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

        function bookingStatus(book) {
            const raw = String(book.status || book.paymentStatus || '').toUpperCase().trim();
            if (raw.includes('CANCEL') || raw.includes('REFUND')) return 'CANCELLED';
            if (raw.includes('COMPLETED') || raw.includes('SUCCESS') || raw.includes('PAID') || raw.includes('CONFIRM') || raw.includes('BOOKED') || raw.includes('APPROVED')) {
                return showStartMillis(book) && Date.now() > showStartMillis(book) ? 'COMPLETED' : 'UPCOMING';
            }
            return showStartMillis(book) && Date.now() > showStartMillis(book) ? 'COMPLETED' : 'UPCOMING';
        }

        function getChartData(items) {
            const gross = Number(items.find((item) => item.type === 'gross')?.amount || 0);
            const refunds = Number(items.find((item) => item.type === 'refund')?.amount || 0);
            const net = gross - refunds;
            return [
                { label: 'Gross', value: gross },
                { label: 'Refunds', value: refunds },
                { label: 'Net', value: net }
            ];
        }

        function renderChart(items) {
            const chartData = getChartData(items);
            const maxValue = Math.max(...chartData.map((item) => Number(item.value || 0)), 1);
            const hasValues = chartData.some((item) => Number(item.value) > 0);
            if (!hasValues) {
                revenueChart.innerHTML = '<div class="metric-list-empty" style="width:100%;display:flex;align-items:center;justify-content:center;min-height:150px;">No earnings data available.</div>';
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

        function render(items) {
            const gross = items.find((item) => item.type === 'gross')?.amount || 0;
            const refunds = items.find((item) => item.type === 'refund')?.amount || 0;
            const net = gross - refunds;

            grossEl.textContent = money(gross);
            refundEl.textContent = money(refunds);
            netEl.textContent = money(net);
            netTopEl.textContent = money(net);
            cardGrossEl.textContent = money(gross);
            cardRefundsEl.textContent = money(refunds);
            cardNetEl.textContent = money(net);
            renderChart(items);

            tableBody.innerHTML = items.map((item) => `
                <tr>
                    <td><strong>${escapeHtml(item.label)}</strong></td>
                    <td><span class="cell-muted">${escapeHtml(item.description)}</span></td>
                    <td><strong>${escapeHtml(item.amountText)}</strong></td>
                </tr>
            `).join('');
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
                const bookings = snap.docs.map((docSnap) => ({ id: docSnap.id, ...docSnap.data() }));
                const gross = bookings
                    .filter((booking) => bookingStatus(booking) !== 'CANCELLED')
                    .reduce((sum, booking) => sum + Number(booking.totalPrice ?? booking.totalAmount ?? 0), 0);

                const refunds = bookings
                    .filter((booking) => bookingStatus(booking) === 'CANCELLED')
                    .reduce((sum, booking) => sum + Number(booking.totalPrice ?? booking.totalAmount ?? 0), 0);

                render([
                    { label: 'Gross revenue', description: 'Completed ticket payments', amount: gross, amountText: money(gross), type: 'gross' },
                    { label: 'Refund amount', description: 'Cancelled or refunded transactions', amount: refunds, amountText: money(refunds), type: 'refund' },
                    { label: 'Net earnings', description: 'Gross revenue minus refunds', amount: gross - refunds, amountText: money(gross - refunds), type: 'net' }
                ]);
            });
        });
    </script>
</body>
</html>
