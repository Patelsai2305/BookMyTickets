<?php
require_once 'config/tmdb.php';
$tmdb = new TMDB();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$movieId = $_GET['id'];
$movie = $tmdb->getMovieDetails($movieId);

// Fallback values to prevent "Undefined array key" warnings
$movieTitle = $movie['title'] ?? 'Unknown Movie';
$movieOverview = $movie['overview'] ?? 'No overview available.';
$movieTagline = $movie['tagline'] ?? '';
$movieRuntime = $movie['runtime'] ?? 0;
$movieReleaseDate = $movie['release_date'] ?? 'N/A';
$movieVote = $movie['vote_average'] ?? 0;
$moviePosterPath = $movie['poster_path'] ?? '';
$movieBackdropPath = $movie['backdrop_path'] ?? '';

// Find YouTube trailer if available
$trailerKey = null;
if (!empty($movie['videos']['results'])) {
    foreach ($movie['videos']['results'] as $video) {
        if ($video['site'] === 'YouTube' && ($video['type'] === 'Trailer' || $video['type'] === 'Teaser')) {
            $trailerKey = $video['key'];
            break;
        }
    }
}

include 'includes/header.php';
?>

<style>
    .backdrop {
        position: relative;
        height: 60vh;
        background: linear-gradient(to right, #121212 30%, rgba(18, 18, 18, 0.6) 70%, transparent 100%),
                    linear-gradient(to bottom, transparent 60%, #121212 100%),
                    url('<?= $tmdb->getBackdropUrl($movieBackdropPath) ?>');
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: center;
        padding: 0 5%;
    }
    .movie-detail-container {
        display: flex;
        gap: 3rem;
        margin-top: -120px;
        padding: 0 5% 4rem;
        position: relative;
        z-index: 10;
    }
    .poster-img {
        width: 280px;
        border-radius: 12px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.7);
        border: 1px solid var(--border-color);
        flex-shrink: 0;
    }
    .detail-info { flex: 1; padding-top: 130px; }
    .genre-badge {
        display: inline-block;
        padding: 0.3rem 1rem;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 20px;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
    }
    .cast-scroll {
        display: flex;
        gap: 1.5rem;
        overflow-x: auto;
        padding: 1rem 0;
        scrollbar-width: none;
    }
    .cast-scroll::-webkit-scrollbar { display: none; }
    .cast-card { flex: 0 0 110px; text-align: center; }
    .cast-card img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 0.5rem; border: 2px solid var(--border-color); }
    .booking-bar {
        position: sticky;
        bottom: 0;
        background: rgba(20, 27, 45, 0.95);
        backdrop-filter: blur(12px);
        padding: 1.2rem 5%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid var(--border-color);
        z-index: 100;
    }
</style>

<div class="backdrop">
    <div class="hero-text" style="max-width: 700px;">
        <h1 style="font-size: 3.2rem; line-height: 1.1; font-weight: 800;"><?= htmlspecialchars($movieTitle) ?></h1>
        <?php if ($movieTagline): ?>
            <p style="font-size: 1.2rem; color: var(--text-muted); margin-top: 0.5rem; font-style: italic;"><?= htmlspecialchars($movieTagline) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="movie-detail-container">
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <img src="<?= $tmdb->getImageUrl($moviePosterPath) ?>" class="poster-img" alt="<?= htmlspecialchars($movieTitle) ?>">

        <!-- Wishlist Button -->
        <button id="wishlist-btn" class="btn btn-secondary" style="width: 100%;">
            <i class="fa-regular fa-heart" id="wishlist-icon"></i> <span id="wishlist-text">Add to Wishlist</span>
        </button>

        <?php if ($trailerKey): ?>
            <button id="open-trailer-btn" class="btn btn-outline" style="width: 100%;">
                <i class="fa-solid fa-play"></i> Watch Trailer
            </button>
        <?php endif; ?>
    </div>

    <div class="detail-info">
        <div style="margin-bottom: 1.5rem;">
            <?php foreach ($movie['genres'] ?? [] as $genre): ?>
                <span class="genre-badge"><?= htmlspecialchars($genre['name']) ?></span>
            <?php endforeach; ?>
        </div>

        <h2 style="margin-bottom: 0.5rem;">Synopsis</h2>
        <p style="font-size: 1.05rem; color: var(--text-muted); margin-bottom: 2.5rem; line-height: 1.8;"><?= htmlspecialchars($movieOverview) ?></p>

        <h3 style="margin-bottom: 1rem;">Top Cast</h3>
        <div class="cast-scroll">
            <?php foreach (array_slice($movie['credits']['cast'] ?? [], 0, 10) as $cast): ?>
                <div class="cast-card">
                    <img src="<?= $tmdb->getImageUrl($cast['profile_path']) ?>" alt="<?= htmlspecialchars($cast['name']) ?>">
                    <div style="font-size: 0.85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($cast['name']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($cast['character']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Trailer Modal -->
<?php if ($trailerKey): ?>
<div class="modal" id="trailer-modal">
    <div class="modal-content">
        <span class="modal-close" id="close-modal">&times;</span>
        <div class="video-container">
            <iframe id="trailer-iframe" src="" allowfullscreen></iframe>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="booking-bar">
    <div>
        <span style="font-size: 1.25rem; font-weight: 700;"><i class="fa-solid fa-star" style="color: #f59e0b;"></i> <?= number_format($movieVote, 1) ?>/10</span>
        <span style="margin-left: 2rem; color: var(--text-muted);"><?= $movieRuntime ?> mins • <?= $movieReleaseDate ?></span>
    </div>
    <a href="theatres.php?movie_id=<?= $movieId ?>&title=<?= urlencode($movieTitle) ?>&poster=<?= urlencode($moviePosterPath) ?>" class="btn btn-primary" style="padding: 0.9rem 2.5rem; font-size: 1.05rem;">
        <i class="fa-solid fa-ticket"></i> Book Tickets
    </a>
</div>

<script type="module">
    import { auth, db, doc, getDoc, setDoc, deleteDoc, onAuthStateChanged, serverTimestamp } from './assets/js/firebase-config.js';

    const movieId = "<?= $movieId ?>";
    const movieTitle = <?= json_encode($movieTitle) ?>;
    const posterPath = <?= json_encode($moviePosterPath) ?>;
    const trailerKey = <?= json_encode($trailerKey) ?>;

    // Wishlist Logic
    const wishlistBtn = document.getElementById('wishlist-btn');
    const wishlistIcon = document.getElementById('wishlist-icon');
    const wishlistText = document.getElementById('wishlist-text');
    let currentUser = null;
    let isWishlisted = false;

    onAuthStateChanged(auth, async (user) => {
        currentUser = user;
        if (user) {
            const wishRef = doc(db, `wishlist/${user.uid}/movies/${movieId}`);
            const wishDoc = await getDoc(wishRef);
            if (wishDoc.exists()) {
                isWishlisted = true;
                updateWishlistUI();
            }
        }
    });

    wishlistBtn.onclick = async () => {
        if (!currentUser) {
            alert("Please sign in to add movies to your wishlist.");
            window.location.href = "login.php";
            return;
        }

        const wishRef = doc(db, `wishlist/${currentUser.uid}/movies/${movieId}`);
        try {
            if (isWishlisted) {
                await deleteDoc(wishRef);
                isWishlisted = false;
            } else {
                await setDoc(wishRef, {
                    movieId: movieId,
                    title: movieTitle,
                    posterPath: posterPath,
                    addedAt: serverTimestamp()
                });
                isWishlisted = true;
            }
            updateWishlistUI();
        } catch (err) {
            console.error("Wishlist error:", err);
        }
    };

    function updateWishlistUI() {
        if (isWishlisted) {
            wishlistIcon.className = "fa-solid fa-heart";
            wishlistIcon.style.color = "#e50914";
            wishlistText.innerText = "In Wishlist";
        } else {
            wishlistIcon.className = "fa-regular fa-heart";
            wishlistIcon.style.color = "inherit";
            wishlistText.innerText = "Add to Wishlist";
        }
    }

    // Modal Trailer Logic
    if (trailerKey) {
        const modal = document.getElementById('trailer-modal');
        const openBtn = document.getElementById('open-trailer-btn');
        const closeBtn = document.getElementById('close-modal');
        const iframe = document.getElementById('trailer-iframe');

        openBtn.onclick = () => {
            iframe.src = `https://www.youtube.com/embed/${trailerKey}?autoplay=1`;
            modal.classList.add('active');
        };

        const closeModal = () => {
            iframe.src = '';
            modal.classList.remove('active');
        };

        closeBtn.onclick = closeModal;
        modal.onclick = (e) => { if (e.target === modal) closeModal(); };
    }
</script>

<?php include 'includes/footer.php'; ?>
