<?php
$page_title = "Manage Bookings - Admin";
include 'includes/header.php';
?>

<div class="container-fluid my-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-danger"><i class="bi bi-card-checklist me-2"></i>All Bookings</h3>
        <button class="btn btn-outline-light btn-sm" onclick="window.dispatchEvent(new CustomEvent('refreshBookings'))"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
    </div>

    <div class="card bg-dark text-white border-secondary rounded-4 shadow">
        <div class="table-responsive p-3">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-danger">
                        <th>Booking ID</th>
                        <th>User Email</th>
                        <th>Movie</th>
                        <th>Theatre</th>
                        <th>Show Time</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="adminBookingsTable">
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">Loading bookings...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="module">
import {
    auth,
    db,
    onAuthStateChanged,
    collection,
    query,
    orderBy,
    getDocs
} from './assets/js/firebase-config.js';

document.addEventListener('DOMContentLoaded', () => {
    onAuthStateChanged(auth, (user) => {
        if (!user) {
            window.location.href = 'login.php';
            return;
        }
        fetchBookings();
    });
});

async function fetchBookings() {
    const tableBody = document.getElementById('adminBookingsTable');
    try {
        const q = query(collection(db, 'bookingticket_app_web'), orderBy('createdAt', 'desc'));
        const snapshot = await getDocs(q);

        if (snapshot.empty) {
            tableBody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No bookings found.</td></tr>';
            return;
        }

        let rows = '';
        snapshot.forEach((docSnap) => {
            const b = docSnap.data();
            const seats = Array.isArray(b.seats) ? b.seats.join(', ') : (b.seats || '');
            const dateStr = b.createdAt ? new Date(b.createdAt).toLocaleDateString() : (b.date || '-');

            rows += `
                <tr>
                    <td class="fw-bold text-danger">${b.bookingId || docSnap.id}</td>
                    <td>${b.userEmail || b.userId || 'N/A'}</td>
                    <td>${b.movieTitle || 'N/A'}</td>
                    <td>${b.theatreName || 'N/A'}</td>
                    <td>${b.timing || 'N/A'}</td>
                    <td><span class="badge bg-danger">${seats}</span></td>
                    <td class="fw-bold">₹${b.totalPrice || 0}</td>
                    <td><span class="badge ${b.status === 'Cancelled' ? 'bg-secondary' : 'bg-success'}">${b.status || 'Confirmed'}</span></td>
                    <td class="small text-secondary">${dateStr}</td>
                </tr>
            `;
        });
        tableBody.innerHTML = rows;
    } catch (err) {
        console.error('Error fetching admin bookings:', err);
        tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load: ${err.message}</td></tr>`;
    }
}

// Allow the refresh button to work with the module function
window.addEventListener('refreshBookings', () => {
    fetchBookings();
});
</script>

<?php include 'includes/footer.php'; ?>
