<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>All Bookings - Admin Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css"><link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<aside class="admin-sidebar"><h2 class="admin-brand"><i class="fa-solid fa-user-shield"></i> BMT Admin</h2><nav><ul class="admin-nav"><li><a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li><li><a href="bookings.php" class="active"><i class="fa-solid fa-ticket"></i> Bookings</a></li><li><a href="showtimes.php"><i class="fa-solid fa-film"></i> Showtimes</a></li><li><a href="users.php"><i class="fa-solid fa-users"></i> Users</a></li><li><a href="../index.php"><i class="fa-solid fa-house"></i> View Website</a></li></ul></nav></aside>
<main class="admin-content"><div class="admin-header"><div><h1><i class="fa-solid fa-ticket"></i> Bookings</h1><p class="admin-subtitle">Real-time Web + Android bookings</p></div><input type="text" id="booking-search" class="table-search" placeholder="Search by email, movie, booking ID..."></div>
<div class="data-table-container"><div class="table-header"><h3>All Bookings</h3></div><table class="data-table"><thead><tr><th>Booking ID</th><th>Customer</th><th>Movie / Theatre</th><th>Seats</th><th>Total</th><th>Status</th><th>Action</th><th>Created</th></tr></thead><tbody id="bookings-table-body"><tr><td colspan="8"><div class="loading-cell">Checking admin access...</div></td></tr></tbody></table></div></main>
<script type="module">
import { auth,db,onAuthStateChanged,collection,onSnapshot,doc,getDoc,getDocs,writeBatch,serverTimestamp } from '../assets/js/firebase-config.js';
import { buildShowKey,buildSeatLockId } from '../assets/js/seat-lock.js';
const tbody=document.getElementById('bookings-table-body'), search=document.getElementById('booking-search'); let all=[];
function isAdmin(d){return d && (d.role==='admin'||d.isAdmin===true||d.isAdmin==='true');}
function seatsOf(b){return Array.isArray(b.seats)?b.seats.map(String):String(b.seats||b.selectedSeats||'').split(',').map(s=>s.trim()).filter(Boolean);}
function millis(v){return typeof v==='number'?v:(v?.toMillis?v.toMillis():(v?.seconds?v.seconds*1000:0));}
async function cancel(id){
 const ref=doc(db,'bookingticket_app_web',id), snap=await getDoc(ref); if(!snap.exists())throw new Error('Booking not found.'); const b=snap.data();
 if(String(b.status||'').toUpperCase()==='CANCELLED')return;
 const key=b.showKey||b.showId||buildShowKey({movieId:b.movieId,movieTitle:b.movieTitle,theatreId:b.theatreId,theatreName:b.theatreName,date:b.date,time:b.timing||b.showTime}); const batch=writeBatch(db);
 batch.update(ref,{status:'CANCELLED',cancelledAt:serverTimestamp(),cancellationReason:'Cancelled by admin'});
 for(const seat of seatsOf(b)){const lr=doc(db,'seatLocks',buildSeatLockId(key,seat));const ls=await getDoc(lr);if(ls.exists()&&String(ls.data().status||'').toLowerCase()==='booked')batch.update(lr,{status:'cancelled',cancelledAt:serverTimestamp(),cancellationReason:'Booking cancelled by admin'});}
 await batch.commit();
}
function render(list){tbody.innerHTML=''; if(!list.length){tbody.innerHTML='<tr><td colspan="8" class="text-center py-4">No bookings found.</td></tr>';return;} list.forEach(b=>{const tr=document.createElement('tr'),s=String(b.status||'CONFIRMED').toUpperCase(), seats=seatsOf(b).join(', '); tr.innerHTML=`<td class="cell-mono">${b.bookingId||b.id||'—'}</td><td>${b.userEmail||b.userId||'—'}</td><td><strong>${b.movieTitle||'—'}</strong><br><span class="cell-secondary">${b.theatreName||b.theatreId||''} • ${b.date||''} ${b.timing||b.showTime||''}</span></td><td>${seats||'—'}</td><td>₹${b.totalPrice??b.totalAmount??0}</td><td><span class="badge ${s==='CANCELLED'?'badge-warning':'badge-success'}">${s}</span></td><td>${s==='CANCELLED'?'<span class="cell-muted">Cancelled</span>':`<button class="btn btn-sm btn-danger admin-cancel" data-id="${b.id}">Cancel</button>`}</td><td class="cell-muted">${millis(b.createdAt)?new Date(millis(b.createdAt)).toLocaleString():'—'}</td>`;tbody.appendChild(tr);});}
async function start(){const u=auth.currentUser;if(!u){location.href='../login.php';return;}const us=await getDoc(doc(db,'Users',u.uid));if(!us.exists()||!isAdmin(us.data())){location.href='../index.php';return;}onSnapshot(collection(db,'bookingticket_app_web'),snap=>{all=snap.docs.map(d=>({id:d.id,...d.data()})).sort((a,b)=>millis(b.createdAt)-millis(a.createdAt));render(all);},e=>{tbody.innerHTML=`<tr><td colspan="8" class="text-danger">${e.message}</td></tr>`;});}
search.addEventListener('input',()=>{const q=search.value.toLowerCase();render(all.filter(b=>[b.userEmail,b.movieTitle,b.bookingId,b.userId].some(x=>String(x||'').toLowerCase().includes(q))));});
tbody.addEventListener('click',async e=>{const btn=e.target.closest('.admin-cancel');if(!btn)return;if(!confirm('Cancel this booking?'))return;btn.disabled=true;try{await cancel(btn.dataset.id);}catch(err){alert(err.message||'Cancellation failed.');}finally{btn.disabled=false;}});
onAuthStateChanged(auth,()=>{if(auth.currentUser)start();});
</script></body></html>
