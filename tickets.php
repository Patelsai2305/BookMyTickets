<?php
$page_title = "My Tickets - BookMyTicket";
include 'includes/header.php';
?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-danger"><i class="bi bi-ticket-detailed me-2"></i>My Tickets</h2>
    </div>
    <div id="loadingTickets" class="text-center py-5">
        <div class="spinner-border text-danger" role="status"></div>
        <p class="mt-2 text-muted">Loading your tickets...</p>
    </div>
    <div id="ticketsContainer" class="row g-4 d-none"></div>
</div>
<script type="module">
import { auth, db, collection, query, where, getDocs, onAuthStateChanged, doc, getDoc, writeBatch, serverTimestamp } from './assets/js/firebase-config.js';
import { buildShowKey, buildSeatLockId } from './assets/js/seat-lock.js';
import { getHybridImageUrl } from './assets/js/image-utils.js';

const container = document.getElementById('ticketsContainer');
const loading = document.getElementById('loadingTickets');

function esc(v) {
    return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function status(v) {
    const s = String(v || 'CONFIRMED').toUpperCase();
    if (s === 'CANCELLED') return 'CANCELLED';
    if (s === 'COMPLETED') return 'COMPLETED';
    return 'CONFIRMED';
}
function millis(v) {
    if (v == null) return 0;
    if (typeof v === 'number') return v;
    if (typeof v === 'string') { const n=Date.parse(v); return Number.isNaN(n)?0:n; }
    if (typeof v.toMillis === 'function') return v.toMillis();
    if (typeof v.seconds === 'number') return v.seconds*1000;
    return 0;
}
function showStart(b) {
    if (!b.date || !(b.timing || b.showTime)) return 0;
    const t = new Date(`${b.date} ${b.timing || b.showTime}`);
    return Number.isNaN(t.getTime()) ? 0 : t.getTime();
}
function seatsOf(b) {
    if (Array.isArray(b.seats)) return b.seats.map(String).filter(Boolean);
    if (Array.isArray(b.selectedSeatsAlias)) return b.selectedSeatsAlias.map(String).filter(Boolean);
    return String(b.seats || '').split(',').map(s=>s.trim()).filter(Boolean);
}

async function getUserBookings(user) {
    const qs = [
        query(collection(db,'bookingticket_app_web'), where('userId','==',user.uid)),
        query(collection(db,'bookingticket_app_web'), where('uid','==',user.uid))
    ];
    if (user.email) qs.push(query(collection(db,'bookingticket_app_web'), where('userEmail','==',user.email)));
    const snaps = await Promise.all(qs.map(q=>getDocs(q)));
    const map = new Map();
    snaps.forEach(s => s.forEach(d => map.set(d.id,{id:d.id,...d.data()})));
    return [...map.values()].sort((a,b)=>millis(b.createdAt)-millis(a.createdAt));
}

function render(bookings) {
    if (!bookings.length) {
        container.innerHTML = `<div class="col-12 text-center py-5 text-muted"><i class="bi bi-ticket-perforated fs-1 d-block mb-3"></i><p>No tickets booked yet.</p><a href="index.php" class="btn btn-outline-danger">Book a Movie</a></div>`;
        return;
    }
    container.innerHTML = bookings.map(b => {
        const s=status(b.status), seats=seatsOf(b).join(', '), start=showStart(b);
        const canCancel = s !== 'CANCELLED' && s !== 'COMPLETED' && (!start || start > Date.now());
        const cancel = canCancel ? `<button type="button" class="btn btn-sm btn-outline-danger cancel-ticket-btn mt-2" data-booking-id="${esc(b.id)}"><i class="bi bi-x-circle me-1"></i>Cancel Ticket</button>` : '';
        const badge=s==='CANCELLED'?'bg-secondary':s==='COMPLETED'?'bg-dark':'bg-success';
        return `<div class="col-md-6 col-lg-4"><div class="generic-card h-100 shadow rounded-4 overflow-hidden"><div class="row g-0"><div class="col-4"><img src="${esc(getHybridImageUrl(b.moviePoster,'w200','assets/img/default-poster.jpg'))}" class="img-fluid h-100 w-100 object-fit-cover" alt="${esc(b.movieTitle||'Movie')}"></div><div class="col-8 p-3 d-flex flex-column justify-content-between"><div><div class="d-flex justify-content-between align-items-start gap-2"><h6 class="fw-bold text-danger mb-1">${esc(b.movieTitle||'Movie')}</h6><span class="badge ${badge}">${s}</span></div><p class="text-secondary small mb-1"><i class="bi bi-geo-alt me-1"></i>${esc(b.theatreName||'Theatre')}</p><p class="small mb-1"><i class="bi bi-calendar me-1"></i>${esc(b.date||'')} | ${esc(b.timing||b.showTime||'')}</p><p class="small mb-0"><strong class="text-white">Seats:</strong> <span class="text-danger">${esc(seats)}</span></p>${s==='CANCELLED'?'<div class="small text-secondary mt-2">Ticket cancelled</div>':''}</div><div class="mt-3 border-top border-secondary pt-2"><div class="d-flex justify-content-between align-items-center"><span class="fw-bold fs-5 text-white">₹${esc(b.totalPrice??0)}</span><span class="text-muted" style="font-size:.7rem">ID: ${esc(b.bookingId||b.id)}</span></div>${cancel}</div></div></div></div></div>`;
    }).join('');
}

async function cancelTicket(id) {
    const user=auth.currentUser;
    if(!user) return;
    if(!confirm('Are you sure you want to cancel this ticket?')) return;
    try {
        const ref=doc(db,'bookingticket_app_web',id), snap=await getDoc(ref);
        if(!snap.exists()) throw new Error('Booking not found.');
        const b=snap.data();
        if(!((b.userId||'')===user.uid || (b.uid||'')===user.uid || (b.userEmail||'')===(user.email||''))) throw new Error('You cannot cancel this ticket.');
        if(status(b.status)==='CANCELLED') throw new Error('Ticket is already cancelled.');
        const start=showStart(b); if(start && start<=Date.now()) throw new Error('This ticket cannot be cancelled because the show has started.');
        const showKey=b.showKey || b.showId || buildShowKey({movieId:b.movieId,movieTitle:b.movieTitle,theatreId:b.theatreId,theatreName:b.theatreName,date:b.date,time:b.timing||b.showTime});
        const seats=seatsOf(b);
        const batch=writeBatch(db);
        batch.update(ref,{status:'CANCELLED',cancelledAt:serverTimestamp(),cancellationReason:'Cancelled by user'});
        for(const seatId of seats){
            const lref=doc(db,'seatLocks',buildSeatLockId(showKey,seatId));
            const ls=await getDoc(lref);
            if(!ls.exists()) continue;
            const ld=ls.data();
            if(ld.userId===user.uid && String(ld.status||'').toLowerCase()==='booked') batch.update(lref,{status:'cancelled',cancelledAt:serverTimestamp(),cancellationReason:'Booking cancelled'});
        }
        await batch.commit();
        alert('Ticket cancelled successfully.');
        await load();
    } catch(e) { console.error(e); alert(e.message||'Cancellation failed.'); }
}

async function load(){
    loading.classList.remove('d-none'); container.classList.add('d-none');
    try { const u=auth.currentUser; if(!u)return; render(await getUserBookings(u)); loading.classList.add('d-none'); container.classList.remove('d-none'); }
    catch(e){ loading.innerHTML=`<div class="alert alert-danger text-center">Failed to load tickets: ${esc(e.message)}</div>`; }
}
document.addEventListener('click',e=>{const b=e.target.closest('.cancel-ticket-btn'); if(b) cancelTicket(b.dataset.bookingId);});
onAuthStateChanged(auth,u=>{if(!u){location.href='login.php';return;}load();});
</script>
<?php include 'includes/footer.php'; ?>
