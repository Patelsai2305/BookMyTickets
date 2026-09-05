<?php
require_once '../config/tmdb.php';
$tmdb = new TMDB();
$nowPlaying = $tmdb->getNowPlaying()['results'] ?? [];
$theatres = ['PVR Cinemas', 'INOX', 'Cinepolis', 'AMC Theatres', 'Regal Cinemas', 'Carnival Cinemas'];
$times = ['09:30 AM', '12:30 PM', '03:30 PM', '06:30 PM', '09:45 PM'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Showtimes - BookMyTicket Admin</title>
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
                <li><a href="showtimes.php" class="active"><i class="fa-solid fa-film"></i> Showtimes</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users"></i> Users</a></li>
                <li><a href="../index.php"><i class="fa-solid fa-house"></i> View Website</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <div>
                <h1><i class="fa-solid fa-film"></i> Showtimes</h1>
                <p class="admin-subtitle">Add and manage showtimes across theatres</p>
            </div>
        </div>

        <!-- Add Showtime Form -->
        <div class="admin-card">
            <h2 class="admin-card-title"><i class="fa-solid fa-plus"></i> Add New Showtime</h2>
            <form id="showtime-form" class="form-grid">
                <div class="form-group">
                    <label for="movie-select">Movie (TMDB)</label>
                    <select id="movie-select" class="form-input" required>
                        <option value="">-- Select a movie --</option>
                        <?php foreach ($nowPlaying as $m): ?>
                            <option value="<?= $m['id'] ?>" data-title="<?= htmlspecialchars($m['title']) ?>" data-poster="<?= htmlspecialchars($m['poster_path'] ?? '') ?>">
                                <?= htmlspecialchars($m['title']) ?> (<?= substr($m['release_date'] ?? '', 0, 4) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="theatre-select">Theatre</label>
                    <select id="theatre-select" class="form-input" required>
                        <?php foreach ($theatres as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="show-date">Date</label>
                    <input type="date" id="show-date" class="form-input" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label for="show-time">Time</label>
                    <select id="show-time" class="form-input" required>
                        <?php foreach ($times as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="show-price">Price (₹)</label>
                    <input type="number" id="show-price" class="form-input" required min="50" value="250">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fa fa-plus"></i> Add Showtime
                    </button>
                </div>
            </form>
            <p id="form-status" class="form-status"></p>
            <p class="form-hint">
                <i class="fa-solid fa-circle-info"></i> Each showtime creates a seat matrix of 100 seats (rows A–J, columns 1–10) — identical to the Android app schema.
            </p>
        </div>

        <!-- Existing Showtimes -->
        <h2 class="section-heading"><i class="fa-solid fa-list"></i> Existing Showtimes <span id="showtime-count" style="color: var(--admin-muted); font-weight: 500;">(0)</span></h2>
        <div class="data-table-container">
            <table class="data-table" style="min-width: 800px;">
                <thead>
                    <tr>
                        <th>Movie</th>
                        <th>Theatre</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Price</th>
                        <th>Seats Booked</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="showtimes-body">
                    <tr><td colspan="7"><div class="loading-cell">Loading showtimes...</div></td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <script type="module">
        import { auth, db, collection, getDocs, addDoc, deleteDoc, doc, getDoc, onAuthStateChanged, serverTimestamp } from '../assets/js/firebase-config.js';

        const tbody = document.getElementById('showtimes-body');
        const formStatus = document.getElementById('form-status');
        let allShowtimes = [];

        onAuthStateChanged(auth, async (user) => {
            if (!user) { window.location.href = '../login.php?redirect=' + encodeURIComponent(window.location.href); return; }
            const userDoc = await getDoc(doc(db, "Users", user.uid));
            if (!userDoc.exists() || (userDoc.data().role !== 'admin' && userDoc.data().isAdmin !== true)) {
                window.location.href = '../index.php';
                return;
            }
            loadShowtimes();
        });

        // ---- Add showtime ----
        document.getElementById('showtime-form').onsubmit = async (e) => {
            e.preventDefault();
            const sel = document.getElementById('movie-select');
            const opt = sel.options[sel.selectedIndex];

            const payload = {
                movieId: sel.value,
                movieTitle: opt.dataset.title,
                theatreName: document.getElementById('theatre-select').value,
                date: document.getElementById('show-date').value,
                time: document.getElementById('show-time').value,
                price: Number(document.getElementById('show-price').value),
                bookedSeats: [],          // fresh 100-seat matrix, matches Android schema
                createdAt: serverTimestamp()
            };

            try {
                await addDoc(collection(db, "showtimes"), payload);
                formStatus.style.color = '#10b981';
                formStatus.innerText = `✓ Showtime added: ${payload.movieTitle} at ${payload.theatreName}, ${payload.date} ${payload.time}`;
            } catch (err) {
                formStatus.style.color = '#e50914';
                formStatus.innerText = 'Error: ' + err.message;
            }
        };

        // ---- List showtimes (re-run after changes) ----
        async function loadShowtimes() {
            const snap = await getDocs(collection(db, "showtimes"));
            allShowtimes = snap.docs.map(d => ({ id: d.id, ...d.data() }))
                .sort((a, b) => (a.date + a.time).localeCompare(b.date + b.time));
            render();
        }

        function render() {
            document.getElementById('showtime-count').innerText = `(${allShowtimes.length})`;
            if (allShowtimes.length === 0) {
                tbody.innerHTML = '';
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 7;
                td.innerHTML = '<div class="empty-state"><i class="fa-solid fa-film"></i><p>No showtimes yet. Add one using the form above.</p></div>';
                tr.appendChild(td);
                tbody.appendChild(tr);
                return;
            }
            tbody.innerHTML = '';
            allShowtimes.forEach(s => {
                const booked = (s.bookedSeats || []).length;
                const tr = document.createElement('tr');

                const tdMovie = document.createElement('td');
                const strong = document.createElement('strong');
                strong.textContent = s.movieTitle || s.movieId || '—';
                tdMovie.appendChild(strong);

                const tdTheatre = document.createElement('td');
                tdTheatre.textContent = s.theatreName || '—';

                const tdDate = document.createElement('td');
                tdDate.textContent = s.date || '—';

                const tdTime = document.createElement('td');
                tdTime.textContent = s.time || '—';

                const tdPrice = document.createElement('td');
                tdPrice.textContent = `₹${s.price}`;

                const tdBooked = document.createElement('td');
                const bookedBadge = document.createElement('span');
                bookedBadge.className = 'badge ' + (booked > 0 ? 'badge-warning' : 'badge-user');
                bookedBadge.textContent = `${booked} / 100`;
                tdBooked.appendChild(bookedBadge);

                const tdActions = document.createElement('td');
                const delBtn = document.createElement('button');
                delBtn.className = 'btn btn-danger btn-sm del-btn';
                delBtn.dataset.id = s.id;
                delBtn.innerHTML = '<i class="fa-solid fa-trash"></i> Delete';
                tdActions.appendChild(delBtn);

                tr.appendChild(tdMovie);
                tr.appendChild(tdTheatre);
                tr.appendChild(tdDate);
                tr.appendChild(tdTime);
                tr.appendChild(tdPrice);
                tr.appendChild(tdBooked);
                tr.appendChild(tdActions);
                tbody.appendChild(tr);
            });

            tbody.querySelectorAll('.del-btn').forEach(btn => {
                btn.onclick = async () => {
                    if (!confirm('Delete this showtime? Existing bookings for it will remain but the show disappears from the site.')) return;
                    await deleteDoc(doc(db, "showtimes", btn.dataset.id));
                    await loadShowtimes();
                };
            });
        }
    </script>
</body>
</html>
