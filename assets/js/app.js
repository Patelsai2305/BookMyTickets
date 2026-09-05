// Booking seat map — real-time Firestore sync between Web and Android app.
// Seat locking uses BOTH:
//   1. screenings/{showtimeId}.bookedSeats  (shared with Android)
//   2. bookingticket_app_web/{autoId}       (web booking records, auto-ID docs)
// A seat is locked if it appears in either source.
import {
    db,
    doc,
    collection,
    query,
    where,
    getDocs,
    addDoc,
    onSnapshot,
    runTransaction,
    arrayUnion,
    serverTimestamp,
    onAuthStateChanged,
    auth
} from './firebase-config.js';

const CFG = window.BOOKING_CONFIG || {};
const SHOWTIME_ID = CFG.showtimeId || 'spider-man-brand-new-day_20260829_1730';
const PRICING = {
    vip: 450,
    premium: 300,
    normal: 200,
    ...(CFG.pricing || {})
};

const ROWS = ['A', 'B', 'C', 'D', 'E', 'F']; // 6 rows
const COLS = 8;                              // 8 seats per row
const tierOf = row => (['A', 'B'].includes(row) ? 'vip' : ['C', 'D'].includes(row) ? 'premium' : 'normal');

const grid = document.getElementById('seat-grid');
const seatCountEl = document.getElementById('seat-count');
const totalPriceEl = document.getElementById('total-price');
const confirmBtn = document.getElementById('confirm-btn');

let bookedSeats = new Set();     // merged locks from screenings + bookingticket_app_web
const selectedSeats = new Set(); // user's current selection
let currentUser = null;

const screeningRef = doc(db, 'screenings', SHOWTIME_ID);
// Web booking records — auto-ID documents in bookingticket_app_web.
const bookingsCol = collection(db, 'bookingticket_app_web');
const bookingsQuery = query(bookingsCol, where('showtimeId', '==', SHOWTIME_ID));

// ---- Render the 6x8 grid ----
ROWS.forEach(row => {
    for (let col = 1; col <= COLS; col++) {
        const label = `${row}${col}`;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `seat ${tierOf(row)}`;
        btn.textContent = label;
        btn.dataset.seat = label;
        btn.dataset.price = PRICING[tierOf(row)];
        btn.addEventListener('click', () => toggleSeat(btn, label));
        grid.appendChild(btn);
    }
});

// ---- Selection state ----
function toggleSeat(btn, label) {
    if (bookedSeats.has(label)) return;
    if (selectedSeats.has(label)) {
        selectedSeats.delete(label);
        btn.classList.remove('selected');
    } else {
        selectedSeats.add(label);
        btn.classList.add('selected');
    }
    updateSummary();
}

function updateSummary() {
    const count = selectedSeats.size;
    const total = [...selectedSeats].reduce((sum, seat) => sum + PRICING[tierOf(seat[0])], 0);
    seatCountEl.textContent = `${count} seat${count === 1 ? '' : 's'}`;
    totalPriceEl.textContent = `₹${total}`;
    confirmBtn.disabled = count === 0;
}

// ---- Real-time sync #1: screenings doc (shared with Android) ----
onSnapshot(screeningRef, (docSnap) => {
    if (docSnap.exists()) {
        mergeBooked('screenings', docSnap.data().bookedSeats || []);
    }
}, (error) => console.error('screenings listener error:', error));

// ---- Real-time sync #2: bookingticket_app_web collection (auto-ID docs) ----
onSnapshot(bookingsQuery, (snap) => {
    const seats = [];
    snap.forEach(d => {
        const data = d.data();
        // Only count active bookings; treat CANCELLED as free.
        if (String(data.status || 'CONFIRMED').toUpperCase() !== 'CANCELLED') {
            (data.seats || data.bookedSeats || data.selectedSeats || []).forEach(s => seats.push(String(s)));
        }
    });
    mergeBooked('bookingticket_app_web', seats);
}, (error) => console.error('bookingticket_app_web listener error:', error));

const sourceSeats = { screenings: [], bookingticket_app_web: [] };
function mergeBooked(source, seats) {
    sourceSeats[source] = seats.map(String);
    bookedSeats = new Set([...sourceSeats.screenings, ...sourceSeats.bookingticket_app_web]);
    // Release any of my selected seats that were locked elsewhere.
    selectedSeats.forEach(seat => {
        if (bookedSeats.has(seat)) {
            selectedSeats.delete(seat);
            grid.querySelector(`[data-seat="${seat}"]`)?.classList.remove('selected');
        }
    });
    grid.querySelectorAll('.seat').forEach(btn => {
        btn.classList.toggle('booked', bookedSeats.has(btn.dataset.seat));
    });
    updateSummary();
}

// Track the signed-in user (optional but recorded on each booking doc).
onAuthStateChanged(auth, (user) => { currentUser = user; });

// Legacy direct-confirm handler intentionally disabled.
// BookMyTicket now uses seat_selection.php -> payment.php so Web and Android
// create the same canonical booking schema and seatLocks.
confirmBtn.addEventListener('click', () => {
    if (selectedSeats.size === 0) return;
    alert('Please use the payment checkout to complete this booking.');
});
