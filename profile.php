<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="profile-header">
        <div class="profile-avatar">
            <i id="profile-placeholder" class="fa fa-user"></i>
            <img id="profile-image" alt="Profile photo" style="display: none;">
        </div>
        <div class="profile-info">
            <h1 id="prof-name">Loading...</h1>
            <p id="prof-email"></p>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mb-4">
        <button class="btn btn-primary px-4">My Bookings</button>
        <div class="flex-grow-1 border-bottom border-secondary"></div>
    </div>

    <div id="bookings-list" class="row g-4">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-danger" role="status"></div>
            <p class="mt-2 text-muted">Loading your tickets...</p>
        </div>
    </div>
</div>

<script type="module">
    import { auth, db, collection, query, where, getDocs, orderBy, onAuthStateChanged, doc, getDoc } from './assets/js/firebase-config.js';
    import { getHybridImageUrl } from './assets/js/image-utils.js';

    onAuthStateChanged(auth, async (user) => {
        if (!user) {
            window.location.href = 'login.php';
            return;
        }

        // Load User Data
        try {
            const userDoc = await getDoc(doc(db, "Users", user.uid));
            if (userDoc.exists()) {
                const userData = userDoc.data();
                document.getElementById('prof-name').innerText = userData.fullName || userData.name || 'User';
                document.getElementById('prof-email').innerText = userData.email || user.email;
                const profileImage = document.getElementById('profile-image');
                const profilePlaceholder = document.getElementById('profile-placeholder');
                const imageUrl = getHybridImageUrl(userData.profileImage, 'w200', '');
                if (imageUrl) {
                    profileImage.src = imageUrl;
                    profileImage.onload = () => { profileImage.style.display = 'block'; profilePlaceholder.style.display = 'none'; };
                    profileImage.onerror = () => { profileImage.removeAttribute('src'); profileImage.style.display = 'none'; profilePlaceholder.style.display = 'inline'; };
                }
            }
        } catch (err) {
            console.error("Error loading profile:", err);
        }

        // Load Bookings - simple queries only; sort locally for cross-client compatibility.
        try {
            const queries = [
                query(collection(db, "bookingticket_app_web"), where("userId", "==", user.uid)),
                query(collection(db, "bookingticket_app_web"), where("uid", "==", user.uid))
            ];
            if (user.email) {
                queries.push(query(collection(db, "bookingticket_app_web"), where("userEmail", "==", user.email)));
            }

            const snapshots = await Promise.all(queries.map(q => getDocs(q)));
            const unique = new Map();
            snapshots.forEach(snap => snap.forEach(d => unique.set(d.id, { id: d.id, ...d.data() })));
            const bookings = [...unique.values()].sort((a, b) => {
                const ms = v => typeof v === 'number' ? v : (v?.toMillis ? v.toMillis() : (v?.seconds ? v.seconds * 1000 : 0));
                return ms(b.createdAt) - ms(a.createdAt);
            });

            const list = document.getElementById('bookings-list');
            list.innerHTML = '';
            if (!bookings.length) {
                list.innerHTML = `<div class="col-12 text-center py-5 text-muted"><i class="bi bi-ticket-perforated fs-1 d-block mb-3"></i><p>No bookings found.</p><a href="index.php" class="btn btn-outline-danger">Book a Movie</a></div>`;
                return;
            }

            const getImageUrl = path => getHybridImageUrl(path, 'w200', 'assets/img/no-poster.png');
            bookings.forEach((booking) => {
                const seats = Array.isArray(booking.seats) ? booking.seats.join(', ') : (booking.seats || 'N/A');
                const status = String(booking.status || 'CONFIRMED').toUpperCase();
                const badge = status === 'CANCELLED' ? 'bg-secondary' : status === 'COMPLETED' ? 'bg-dark' : 'bg-success';
                const card = document.createElement('div');
                card.className = 'col-md-6 col-lg-4';
                card.innerHTML = `<div class="generic-card h-100 shadow rounded-4 overflow-hidden"><div class="row g-0"><div class="col-4"><img src="${getImageUrl(booking.moviePoster)}" class="img-fluid h-100 w-100 object-fit-cover" alt="${booking.movieTitle || 'Movie'}"></div><div class="col-8 p-3 d-flex flex-column justify-content-between"><div><div class="d-flex justify-content-between align-items-start"><h6 class="card-title fw-bold text-danger mb-1">${booking.movieTitle || 'Movie'}</h6><span class="badge ${badge}">${status}</span></div><p class="text-secondary small mb-1"><i class="bi bi-geo-alt me-1"></i>${booking.theatreName || 'Theatre'}</p><p class="small mb-1"><i class="bi bi-calendar me-1"></i>${booking.date || ''} | ${booking.timing || booking.showTime || ''}</p><p class="small mb-0"><strong>Seats:</strong> <span class="text-danger">${seats}</span></p></div><div class="mt-3 d-flex justify-content-between align-items-center border-top border-secondary pt-2"><span class="fw-bold fs-5 text-white">₹${booking.totalPrice || 0}</span><a href="booking_confirmation.php?bookingId=${encodeURIComponent(booking.bookingId || booking.id)}" class="btn btn-outline-danger btn-sm py-1 px-2" style="font-size:.7rem;">View Ticket</a></div></div></div></div>`;
                list.appendChild(card);
            });
        } catch (err) {
            console.error("Error loading bookings:", err);
            document.getElementById('bookings-list').innerHTML = `<div class="alert alert-danger text-center">Error loading bookings: ${err.message}</div>`;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
