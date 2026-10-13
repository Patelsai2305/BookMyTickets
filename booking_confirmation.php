<?php
$page_title = "Booking Confirmed - BookMyTicket";
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <!-- Loading State -->
            <div id="loadingConfirmation" class="text-center py-5">
                <div class="spinner-border text-danger" role="status"></div>
                <p class="mt-2 text-muted">Retrieving confirmation...</p>
            </div>

            <!-- Confirmation Card -->
            <div id="confirmationCard" class="confirmation-card d-none text-center">
                <div class="text-success mb-3">
                    <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                </div>
                <h3 class="fw-bold text-white mb-1">Booking Confirmed!</h3>
                <p class="text-muted small mb-4">Booking ID: <span id="confBookingId" class="text-white fw-bold"></span></p>

                <div class="confirmation-details text-start">
                    <h5 id="confMovieTitle" class="fw-bold text-danger mb-2"></h5>
                    <div class="text-secondary small mb-1">
                        <i class="bi bi-building me-2"></i><span id="confTheatre" class="text-white"></span>
                    </div>
                    <div class="text-secondary small mb-1">
                        <i class="bi bi-calendar-event me-2"></i><span id="confDate" class="text-white"></span>
                        | <i class="bi bi-clock me-1"></i><span id="confTime" class="text-white"></span>
                    </div>
                    <div class="text-secondary small mb-1">
                        <i class="bi bi-ticket-perforated me-2"></i>Seats: <span id="confSeats" class="badge bg-danger fs-6"></span>
                    </div>
                    <div class="text-secondary small mt-3 pt-2 border-top border-secondary">
                        Total Amount: <span id="confTotal" class="text-white fw-bold fs-6"></span>
                    </div>
                </div>

                <div class="d-flex gap-3 mt-4">
                    <a href="tickets.php" class="btn btn-danger w-50 py-2 fw-bold">View Tickets</a>
                    <a href="index.php" class="btn btn-outline-secondary w-50 py-2">Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module">
import { db, doc, getDoc } from './assets/js/firebase-config.js';

document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const bookingId = urlParams.get('bookingId');
    const loading = document.getElementById('loadingConfirmation');
    const card = document.getElementById('confirmationCard');

    if (!bookingId) {
        window.location.href = 'index.php';
        return;
    }

    try {
        const docSnap = await getDoc(doc(db, 'bookingticket_app_web', bookingId));
        if (!docSnap.exists()) {
            loading.innerHTML = '<div class="alert alert-warning text-center">Booking not found.</div>';
            return;
        }

        const b = docSnap.data();
        document.getElementById('confBookingId').textContent = b.bookingId || docSnap.id;
        document.getElementById('confMovieTitle').textContent = b.movieTitle || 'Movie Ticket';
        document.getElementById('confTheatre').textContent = b.theatreName || '';
        document.getElementById('confDate').textContent = b.date || '';
        document.getElementById('confTime').textContent = b.timing || '';
        document.getElementById('confSeats').textContent = Array.isArray(b.seats) ? b.seats.join(', ') : (b.seats || 'N/A');
        document.getElementById('confTotal').textContent = `₹${b.totalPrice || 0}`;

        loading.classList.add('d-none');
        card.classList.remove('d-none');
    } catch (err) {
        console.error('Confirmation fetch error:', err);
        loading.innerHTML = `<div class="alert alert-danger text-center">Error: ${err.message}</div>`;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
