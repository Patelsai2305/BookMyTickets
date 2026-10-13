<?php
$page_title = "My Wishlist - BookMyTicket";
include 'includes/header.php';
?>
<div class="container my-5" style="padding: 0 5%; max-width: 1400px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-danger"><i class="fa-solid fa-heart me-2"></i>My Wishlist</h2>
    </div>

    <div id="loadingWishlist" class="text-center py-5">
        <div class="spinner-border text-danger" role="status"></div>
        <p class="mt-2 text-muted">Loading your wishlist...</p>
    </div>

    <div id="wishlistContainer" class="row g-4 d-none"></div>
</div>

<script type="module">
import { auth, db, collection, getDocs, onAuthStateChanged, doc, deleteDoc } from './assets/js/firebase-config.js';
import { getHybridImageUrl } from './assets/js/image-utils.js';

const container = document.getElementById('wishlistContainer');
const loading = document.getElementById('loadingWishlist');

function esc(value) {
    return String(value ?? '').replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function millis(value) {
    if (value == null) return 0;
    if (typeof value === 'number') return value;
    if (typeof value === 'string') {
        const parsed = Date.parse(value);
        return Number.isNaN(parsed) ? 0 : parsed;
    }
    if (typeof value.toMillis === 'function') return value.toMillis();
    if (typeof value.seconds === 'number') return value.seconds * 1000;
    return 0;
}

async function loadWishlist(user) {
    loading.classList.remove('d-none');
    container.classList.add('d-none');

    try {
        const primarySnap = await getDocs(collection(db, 'Users', user.uid, 'Wishlist'));
        const legacySnap = await getDocs(collection(db, 'wishlist', user.uid, 'movies'));

        const map = new Map();

        primarySnap.docs.forEach((docSnap) => {
            map.set(String(docSnap.id), { id: docSnap.id, ...docSnap.data() });
        });

        legacySnap.docs.forEach((docSnap) => {
            const key = String(docSnap.id);
            if (!map.has(key)) {
                map.set(key, { id: docSnap.id, ...docSnap.data() });
            }
        });

        const movies = [...map.values()].sort((a, b) => (millis(b.addedAt) || 0) - (millis(a.addedAt) || 0));

        if (!movies.length) {
            container.innerHTML = `
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fa-regular fa-heart fs-1 d-block mb-3"></i>
                    <p>Your wishlist is empty.</p>
                    <a href="index.php" class="btn btn-outline">Explore movies</a>
                </div>
            `;
            loading.classList.add('d-none');
            container.classList.remove('d-none');
            return;
        }

        container.innerHTML = movies.map((movie) => {
            const title = movie.title || movie.name || 'Untitled Movie';
            const poster = getHybridImageUrl(movie.posterUrl || movie.posterPath || movie.poster || movie.image || '', 'w500', 'assets/img/no-poster.png');
            const movieId = encodeURIComponent(String(movie.movieId || movie.id));

            return `
                <div class="col-md-6 col-lg-4">
                    <div class="generic-card h-100 shadow rounded-4 overflow-hidden" style="background: rgba(20,27,45,0.96); border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; overflow: hidden;">
                        <div class="position-relative">
                            <img src="${esc(poster)}" class="img-fluid w-100" alt="${esc(title)}" style="height: 320px; object-fit: cover;">
                        </div>
                        <div class="p-3 d-flex flex-column justify-content-between h-100">
                            <div>
                                <h5 class="fw-bold text-white mb-2">${esc(title)}</h5>
                                <p class="text-muted small mb-0">Saved to your wishlist</p>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <a href="movie_details.php?id=${movieId}" class="btn btn-primary btn-sm flex-fill">
                                    <i class="fa-solid fa-film me-1"></i> Details
                                </a>
                                <button type="button" class="btn btn-outline btn-sm remove-wishlist-btn" data-movie-id="${esc(movie.movieId || movie.id)}">
                                    <i class="fa-solid fa-trash me-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        loading.classList.add('d-none');
        container.classList.remove('d-none');
    } catch (error) {
        console.error('Failed to load wishlist:', error);
        loading.innerHTML = `<div class="alert alert-danger text-center">Unable to load your wishlist right now.</div>`;
    }
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest('.remove-wishlist-btn');
    if (!button || !auth.currentUser) return;

    const movieId = button.dataset.movieId;
    if (!movieId) return;

    const confirmed = window.confirm('Remove this movie from your wishlist?');
    if (!confirmed) return;

    try {
        await deleteDoc(doc(db, 'Users', auth.currentUser.uid, 'Wishlist', movieId));
        await deleteDoc(doc(db, 'wishlist', auth.currentUser.uid, 'movies', movieId));
        await loadWishlist(auth.currentUser);
    } catch (error) {
        console.error('Failed to remove movie from wishlist:', error);
        alert('Could not remove this movie from your wishlist.');
    }
});

onAuthStateChanged(auth, (user) => {
    if (!user) {
        window.location.href = 'login.php';
        return;
    }

    loadWishlist(user);
});
</script>

<?php include 'includes/footer.php'; ?>
