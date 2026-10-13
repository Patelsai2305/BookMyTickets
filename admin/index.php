<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BookMyTicket</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <aside class="admin-sidebar">
        <h2 class="admin-brand"><i class="fa-solid fa-user-shield"></i> BMT Admin</h2>
        <nav>
            <ul class="admin-nav">
                <li><a href="index.php" class="active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
<<<<<<< HEAD
                <li><a href="movies.php"><i class="fa-solid fa-film"></i> Movies</a></li>
                <li><a href="bookings.php"><i class="fa-solid fa-ticket"></i> Bookings</a></li>
                <li><a href="showtimes.php"><i class="fa-solid fa-film"></i> Showtimes</a></li>
                <li><a href="theatres.php"><i class="fa-solid fa-location-dot"></i> Theatres</a></li>
=======
                <li><a href="bookings.php"><i class="fa-solid fa-ticket"></i> Bookings</a></li>
                <li><a href="showtimes.php"><i class="fa-solid fa-film"></i> Showtimes</a></li>
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
                <li><a href="users.php"><i class="fa-solid fa-users"></i> Users</a></li>
                <li><a href="../index.php"><i class="fa-solid fa-house"></i> View Website</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <div>
                <h1><i class="fa-solid fa-gauge-high"></i> Dashboard</h1>
                <p class="admin-subtitle">Real-time statistics overview</p>
            </div>
        </div>

<<<<<<< HEAD
        <div class="finance-hero">
            <div>
                <div class="eyebrow">Revenue overview</div>
                <h2 class="finance-hero-title">Gross Revenue & Earnings</h2>
            </div>
        </div>

        <div class="stats-grid">
            <a href="gross-revenue.php" class="stat-card accent clickable" aria-label="Open Gross Revenue details">
=======
        <div class="stats-grid">
            <div class="stat-card accent">
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
                <div class="stat-info">
                    <p>Gross Revenue</p>
                    <h2 id="stat-revenue">₹...</h2>
                </div>
                <div class="stat-icon icon-accent"><i class="fa-solid fa-indian-rupee-sign"></i></div>
<<<<<<< HEAD
            </a>
            <a href="total-refunds.php" class="stat-card warning clickable" aria-label="Open Total Refunds details">
=======
            </div>
            <div class="stat-card warning">
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
                <div class="stat-info">
                    <p>Total Refunds</p>
                    <h2 id="stat-refunds">₹...</h2>
                </div>
                <div class="stat-icon icon-warning"><i class="fa-solid fa-rotate-left"></i></div>
<<<<<<< HEAD
            </a>
            <a href="net-earnings.php" class="stat-card success clickable" aria-label="Open Net Earnings details">
                <div class="stat-info">
                    <p>Net Earnings</p>
                    <h2 id="stat-net">₹...</h2>
                </div>
                <div class="stat-icon icon-success"><i class="fa-solid fa-chart-line"></i></div>
            </a>
            <a href="booking-details.php" class="stat-card cyan clickable" aria-label="Open Total Bookings details">
=======
            </div>
            <div class="stat-card success">
                <div class="stat-info">
                    <p>Net Earnings (Revenue − Refunds)</p>
                    <h2 id="stat-net">₹...</h2>
                </div>
                <div class="stat-icon icon-success"><i class="fa-solid fa-chart-line"></i></div>
            </div>
            <div class="stat-card success">
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
                <div class="stat-info">
                    <p>Total Bookings</p>
                    <h2 id="stat-bookings">...</h2>
                </div>
<<<<<<< HEAD
                <div class="stat-icon icon-cyan"><i class="fa-solid fa-circle-check"></i></div>
            </a>
            <a href="active-users.php" class="stat-card purple clickable" aria-label="Open Active Users details">
=======
                <div class="stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="stat-card warning">
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
                <div class="stat-info">
                    <p>Active Users</p>
                    <h2 id="stat-users">...</h2>
                </div>
<<<<<<< HEAD
                <div class="stat-icon icon-purple"><i class="fa-solid fa-users"></i></div>
            </a>
=======
                <div class="stat-icon icon-warning"><i class="fa-solid fa-users"></i></div>
            </div>
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
        </div>

        <h2 class="section-heading"><i class="fa-solid fa-clock-rotate-left"></i> Recent Activity</h2>
        <div class="data-table-container">
            <div class="table-header">
                <h3>Recent Web & Mobile Bookings</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Movie</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="recent-bookings-body">
                    <tr><td colspan="5"><div class="loading-cell">Loading recent bookings...</div></td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <script type="module">
        import { auth, db, collection, query, orderBy, limit, onSnapshot, onAuthStateChanged, doc, getDoc } from '../assets/js/firebase-config.js';

<<<<<<< HEAD
        let bookingsData = [];
        let usersData = [];

        function money(value) {
            return `₹${Number(value || 0).toLocaleString()}`;
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

=======
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
        onAuthStateChanged(auth, async (user) => {
            if (!user) { window.location.href = '../login.php'; return; }
            const userDoc = await getDoc(doc(db, "Users", user.uid));
            const d = userDoc.data() || {};
<<<<<<< HEAD
            const isAdmin = d.role === 'admin' || d.isAdmin === true || d.isAdmin === 'true';
            if (!userDoc.exists() || !isAdmin) {
=======
            if (!userDoc.exists() || (d.role !== 'admin' && d.isAdmin !== true && d.isAdmin !== 'true')) {
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
                window.location.href = '../index.php';
                return;
            }
            loadStats();
        });

        function loadStats() {
            let bookingsSnap;
            let usersSnap;
            const renderStats = () => {
                if (!bookingsSnap || !usersSnap) return;
<<<<<<< HEAD
                bookingsData = [];
                usersData = [];
                bookingsSnap.forEach(doc => bookingsData.push({ id: doc.id, ...doc.data() }));
                usersSnap.forEach(doc => usersData.push({ id: doc.id, ...doc.data() }));

                let grossRevenue = 0;
                let totalRefunds = 0;
                bookingsData.forEach((b) => {
                    const amount = Number(b.totalPrice ?? b.totalAmount ?? 0);
                    const status = bookingStatus(b);
                    if (status === 'CANCELLED') totalRefunds += amount;
                    else grossRevenue += amount;
                });

                document.getElementById('stat-revenue').innerText = money(grossRevenue);
                document.getElementById('stat-refunds').innerText = money(totalRefunds);
                document.getElementById('stat-net').innerText = money(grossRevenue - totalRefunds);
                document.getElementById('stat-bookings').innerText = bookingsData.length;
                document.getElementById('stat-users').innerText = usersData.length;
=======
                let grossRevenue = 0;
                let totalRefunds = 0;
                bookingsSnap.forEach(doc => {
                    const b = doc.data();
                    const amount = Number(b.totalPrice ?? b.totalAmount ?? 0);
                    const status = (b.status || b.paymentStatus || '').toUpperCase();
                    if (status === 'REFUNDED' || status === 'CANCELLED') totalRefunds += amount;
                    else grossRevenue += amount;
                });
                document.getElementById('stat-revenue').innerText = `₹${grossRevenue.toLocaleString()}`;
                document.getElementById('stat-refunds').innerText = `₹${totalRefunds.toLocaleString()}`;
                document.getElementById('stat-net').innerText = `₹${(grossRevenue - totalRefunds).toLocaleString()}`;
                document.getElementById('stat-bookings').innerText = bookingsSnap.size;
                document.getElementById('stat-users').innerText = usersSnap.size;
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
            };
            onSnapshot(collection(db, "bookingticket_app_web"), snap => { bookingsSnap = snap; renderStats(); });
            onSnapshot(collection(db, "Users"), snap => { usersSnap = snap; renderStats(); });

            const q = query(collection(db, "bookingticket_app_web"), orderBy("createdAt", "desc"), limit(5));
            onSnapshot(q, recentSnap => {
                const tbody = document.getElementById('recent-bookings-body');
                tbody.innerHTML = '';
                if (recentSnap.empty) {
                    const tr = document.createElement('tr');
                    const td = document.createElement('td');
                    td.colSpan = 5;
                    td.innerHTML = '<div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No bookings yet.</p></div>';
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                    return;
                }
                recentSnap.forEach(doc => {
                    const b = doc.data();
<<<<<<< HEAD
                    const status = bookingStatus(b);
=======
                    const status = (b.status || b.paymentStatus || '—').toUpperCase();
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
                    const seats = (b.seats || b.selectedSeats || []).join(', ');

                    const tr = document.createElement('tr');

                    const tdUser = document.createElement('td');
                    tdUser.textContent = b.userEmail || '—';

                    const tdMovie = document.createElement('td');
                    tdMovie.textContent = b.movieTitle || '—';

                    const tdSeats = document.createElement('td');
                    const spanSeats = document.createElement('span');
                    spanSeats.className = 'cell-muted';
                    spanSeats.textContent = seats || '—';
                    tdSeats.appendChild(spanSeats);

                    const tdAmount = document.createElement('td');
<<<<<<< HEAD
                    tdAmount.textContent = money(b.totalPrice ?? b.totalAmount);

                    const tdStatus = document.createElement('td');
                    const badge = document.createElement('span');
                    const cancelled = status === 'CANCELLED';
                    badge.className = 'badge ' + (cancelled ? 'badge-warning' : 'badge-success');
                    badge.textContent = status === 'COMPLETED' ? 'Completed' : status === 'CANCELLED' ? 'Cancelled' : 'Upcoming';
=======
                    tdAmount.textContent = `₹${b.totalPrice ?? b.totalAmount}`;

                    const tdStatus = document.createElement('td');
                    const badge = document.createElement('span');
                    const cancelled = status === 'REFUNDED' || status === 'CANCELLED';
                    badge.className = 'badge ' + (cancelled ? 'badge-warning' : 'badge-success');
                    badge.textContent = status;
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
                    tdStatus.appendChild(badge);

                    tr.appendChild(tdUser);
                    tr.appendChild(tdMovie);
                    tr.appendChild(tdSeats);
                    tr.appendChild(tdAmount);
                    tr.appendChild(tdStatus);
                    tbody.appendChild(tr);
                });
            });
        }

    </script>
</body>
</html>
