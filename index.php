<?php
require_once 'config/tmdb.php';
$tmdb = new TMDB();

$genreId = $_GET['genre'] ?? null;
$searchQuery = $_GET['search'] ?? null;

if ($searchQuery) {
    $nowPlaying = $tmdb->searchMovie($searchQuery);
    $upcoming = ['results' => []];
} elseif ($genreId) {
    $nowPlaying = $tmdb->getMoviesByGenre($genreId);
    $upcoming = ['results' => []];
} else {
    $nowPlaying = $tmdb->getNowPlaying();
    $upcoming = $tmdb->getUpcoming();
}

$genres = $tmdb->getGenres()['genres'] ?? [];

include 'includes/header.php';
?>

<main>
    <!-- Hero Banner Carousel -->
    <section class="hero">
        <div class="hero-backdrop-layer" id="hero-bg"></div>
        <?php $heroMovies = array_slice($nowPlaying['results'], 0, 5); ?>
        <div class="hero-content">
            <p id="hero-tag" style="color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; margin-bottom: 0.5rem;">Featured Movies</p>
            <h1 id="hero-title">Experience Cinema Like Never Before</h1>
            <p id="hero-overview">Real-time ticket booking synced across Web & Android. Zero wait times, instant seat reservations.</p>
            <div id="hero-actions" style="margin-bottom: 2rem; display: none; gap: 1rem;">
                <a id="hero-book-btn" class="btn btn-primary" href="#"><i class="fa-solid fa-ticket"></i> Book Now</a>
                <a id="hero-info-btn" class="btn btn-outline" href="#"><i class="fa-solid fa-circle-info"></i> Details</a>
            </div>
            <form action="index.php" method="GET" class="search-bar">
                <i class="fa-solid fa-magnifying-glass" style="color: var(--text-muted); margin-right: 0.5rem;"></i>
                <input type="text" name="search" placeholder="Search movies, actors, or genres..." value="<?= htmlspecialchars($searchQuery ?? '') ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <?php if (count($heroMovies) > 1): ?>
            <div class="hero-dots" id="hero-dots" style="display: flex; gap: 0.5rem; margin-top: 1.5rem;"></div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (count($heroMovies) > 0 && !$searchQuery && !$genreId): ?>
    <script type="module">
        const heroMovies = <?= json_encode(array_map(fn($m) => [
            'id' => $m['id'],
            'title' => $m['title'] ?? '',
            'overview' => $m['overview'] ?? '',
            'backdrop' => !empty($m['backdrop_path']) ? 'https://image.tmdb.org/t/p/original' . $m['backdrop_path'] : null
        ], $heroMovies)) ?>;

        const bgLayer = document.getElementById('hero-bg');
        const titleEl = document.getElementById('hero-title');
        const overviewEl = document.getElementById('hero-overview');
        const actionsEl = document.getElementById('hero-actions');
        const dotsEl = document.getElementById('hero-dots');
        let current = 0;
        let timer = null;

        heroMovies.forEach((_, i) => {
            const dot = document.createElement('span');
            dot.style.cssText = `width: 10px; height: 10px; border-radius: 50%; cursor: pointer; transition: background 0.3s; background: ${i === 0 ? 'var(--accent)' : 'rgba(255,255,255,0.3)'};`;
            // Hover / single tap: update banner details WITHOUT navigating
            dot.onmouseenter = () => showSlide(i);
            dot.onclick = () => showSlide(i);
            dotsEl.appendChild(dot);
        });

        // Double-click anywhere on the hero: navigate to the movie details page
        document.querySelector('.hero').addEventListener('dblclick', () => {
            const m = heroMovies[current];
            if (m && m.id) window.location.href = `movie_details.php?id=${m.id}`;
        });

        function showSlide(i) {
            current = i;
            const m = heroMovies[i];
            bgLayer.style.backgroundImage = m.backdrop ? `url('${m.backdrop}')` : 'none';
            titleEl.innerText = m.title;
            overviewEl.innerText = m.overview.length > 180 ? m.overview.slice(0, 180) + '\u2026' : m.overview;
            actionsEl.style.display = 'flex';
            document.getElementById('hero-book-btn').href = `theatres.php?movieId=${m.id}&title=${encodeURIComponent(m.title)}`;
            document.getElementById('hero-info-btn').href = `movie_details.php?id=${m.id}`;
            [...dotsEl.children].forEach((d, j) => d.style.background = j === i ? 'var(--accent)' : 'rgba(255,255,255,0.3)');
        }

        function restart() {
            clearInterval(timer);
            timer = setInterval(() => showSlide((current + 1) % heroMovies.length), 6000);
        }

        showSlide(0);
        restart();
    </script>
    <?php endif; ?>

    <!-- Genre Chips -->
    <div class="genre-container">
        <a href="index.php" class="genre-chip <?= !$genreId ? 'active' : '' ?>">All Genres</a>
        <?php foreach (array_slice($genres, 0, 10) as $g): ?>
            <a href="index.php?genre=<?= $g['id'] ?>" class="genre-chip <?= $genreId == $g['id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($g['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Now Showing from Firestore 'Movies' collection (synced with the Android app) -->
    <section class="movies-section" id="firestore-movies-section" style="display: none;">
        <div class="section-title"><h2>Now Playing <span style="font-size:0.75rem; color:var(--text-muted);">• Live from App</span></h2></div>
        <div class="movie-grid" id="firestore-movies-grid"></div>
    </section>

    <!-- Now Showing Section (TMDB) -->
    <section class="movies-section">
        <div class="section-title">
            <h2><?= $searchQuery ? 'Search Results' : ($genreId ? 'Filtered Movies' : 'Now Showing') ?></h2>
        </div>

        <?php if (empty($nowPlaying['results'])): ?>
            <div style="padding: 3rem 5%; text-align: center; color: var(--text-muted);">
                <h3>No movies found matching your criteria.</h3>
                <a href="index.php" class="btn btn-outline" style="margin-top: 1rem;">View All Movies</a>
            </div>
        <?php else: ?>
            <div class="movie-grid">
                <?php foreach (array_slice($nowPlaying['results'], 0, 12) as $movie): ?>
                    <a href="movie_details.php?id=<?= $movie['id'] ?>" class="movie-card">
                        <div class="poster-wrapper">
                            <img src="<?= $tmdb->getImageUrl($movie['poster_path']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" loading="lazy">
                        </div>
                        <div class="movie-info">
                            <div class="movie-title"><?= htmlspecialchars($movie['title']) ?></div>
                            <div class="movie-meta">
                                <span><i class="fa-solid fa-star" style="color: #f59e0b;"></i> <?= number_format($movie['vote_average'] ?? 0, 1) ?></span>
                                <span><?= isset($movie['release_date']) ? substr($movie['release_date'], 0, 4) : 'N/A' ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Upcoming Releases Section -->
    <?php if (!$searchQuery && !$genreId && !empty($upcoming['results'])): ?>
        <section class="movies-section">
            <div class="section-title">
                <h2>Upcoming Releases</h2>
            </div>
            <div class="movie-grid">
                <?php foreach (array_slice($upcoming['results'], 0, 6) as $movie): ?>
                    <a href="movie_details.php?id=<?= $movie['id'] ?>" class="movie-card">
                        <div class="poster-wrapper">
                            <img src="<?= $tmdb->getImageUrl($movie['poster_path']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" loading="lazy">
                        </div>
                        <div class="movie-info">
                            <div class="movie-title"><?= htmlspecialchars($movie['title']) ?></div>
                            <div class="movie-meta">
                                <span><i class="fa-solid fa-calendar-days"></i> <?= htmlspecialchars($movie['release_date'] ?? 'Coming Soon') ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>

<script type="module">
    import { db, collection, getDocs, onSnapshot } from './assets/js/firebase-config.js';
    import { getHybridImageUrl } from './assets/js/image-utils.js';
    import { normalizeMovie, isUpcomingMovie } from './assets/js/movie-model.js';

    // Firebase movie catalogue: Web + Android share Movies/movies.
    // Each collection is loaded independently so one failed/empty collection
    // cannot prevent the other collection from appearing.
    const grid = document.getElementById('firestore-movies-grid');
    const section = document.getElementById('firestore-movies-section');
    const getImageUrl = path => getHybridImageUrl(path, 'w500', 'assets/img/no-poster.png');

    const moviesById = new Map();
    const collectionState = { Movies: false, movies: false };
    const collectionErrors = [];

    function movieKey(movie, docId) {
        return String(movie.tmdbId || movie.id || movie.movieId || docId || movie.title || movie.name || '').trim();
    }

    function renderMovies() {
        const movies = [...moviesById.values()]
            .filter(m => !isUpcomingMovie(m))
            .sort((a, b) => String(a.title || '').localeCompare(String(b.title || '')));

        if (!movies.length) {
            // Keep the section hidden when there really are no Firestore movies.
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        grid.innerHTML = '';

        movies.forEach(m => {
            const title = m.title || m.name || 'Untitled Movie';
            const poster = m.poster || m.posterPath || m.poster_path || '';
            const id = movieKey(m, '');

            const card = document.createElement('a');
            card.href = `movie_details.php?id=${encodeURIComponent(id)}`;
            card.className = 'movie-card';

            const posterWrapper = document.createElement('div');
            posterWrapper.className = 'poster-wrapper';
            const img = document.createElement('img');
            img.src = getImageUrl(poster);
            img.alt = title;
            img.loading = 'lazy';
            img.onerror = () => {
                img.onerror = null;
                img.src = 'assets/img/no-poster.png';
            };
            posterWrapper.appendChild(img);

            const info = document.createElement('div');
            info.className = 'movie-info';
            const titleDiv = document.createElement('div');
            titleDiv.className = 'movie-title';
            titleDiv.textContent = title;
            const metaDiv = document.createElement('div');
            metaDiv.className = 'movie-meta';
            const metaSpan = document.createElement('span');
            metaSpan.textContent = 'App Listing';
            metaDiv.appendChild(metaSpan);
            info.appendChild(titleDiv);
            info.appendChild(metaDiv);

            card.appendChild(posterWrapper);
            card.appendChild(info);
            grid.appendChild(card);
        });
    }

    function mergeSnapshot(collectionName, snap) {
        collectionState[collectionName] = true;
        snap.docs.forEach(d => {
            try {
                const movie = normalizeMovie(d.id, d.data());
                const key = movieKey(movie, d.id);
                if (!key) return;
                // Prefer the newest/complete record but preserve the document ID.
                moviesById.set(key, movie);
            } catch (e) {
                console.warn(`Could not normalize movie ${collectionName}/${d.id}:`, e);
            }
        });
        renderMovies();
    }

    function showCollectionError(collectionName, error) {
        console.error(`Error loading ${collectionName}:`, error);
        collectionErrors.push(`${collectionName}: ${error?.code || error?.message || error}`);

        // If both sources failed, show a useful message instead of silently doing nothing.
        if (collectionErrors.length >= 2 && moviesById.size === 0) {
            section.style.display = 'block';
            grid.innerHTML = `
                <div style="grid-column:1/-1;padding:2rem;text-align:center;color:var(--text-muted);">
                    <h3>Unable to load app movies</h3>
                    <p>Please check your Firebase connection/rules and try again.</p>
                    <button type="button" class="btn btn-outline" id="retry-firestore-movies" style="margin-top:1rem;">Retry</button>
                </div>`;
            document.getElementById('retry-firestore-movies')?.addEventListener('click', loadMoviesOnce);
        }
    }

    // One-shot fallback/retry. This is useful if the browser temporarily blocks
    // the Firestore connection or the first request fails.
    let retryTimer = null;
    async function loadMoviesOnce() {
        clearTimeout(retryTimer);
        collectionErrors.length = 0;
        try {
            const results = await Promise.allSettled([
                getDocs(collection(db, 'Movies')),
                getDocs(collection(db, 'movies'))
            ]);
            if (results[0].status === 'fulfilled') mergeSnapshot('Movies', results[0].value);
            else showCollectionError('Movies', results[0].reason);
            if (results[1].status === 'fulfilled') mergeSnapshot('movies', results[1].value);
            else showCollectionError('movies', results[1].reason);
        } catch (error) {
            showCollectionError('Firestore', error);
        }
    }

    // Real-time listeners keep Web synchronized with Android/admin changes.
    // If a listener fails, the independent listener can still display movies,
    // and a one-shot retry is scheduled.
    function watchCollection(collectionName) {
        return onSnapshot(
            collection(db, collectionName),
            snap => mergeSnapshot(collectionName, snap),
            error => {
                showCollectionError(collectionName, error);
                clearTimeout(retryTimer);
                retryTimer = setTimeout(loadMoviesOnce, 3000);
            }
        );
    }

    // Start immediately; no page refresh should be required.
    loadMoviesOnce();
    watchCollection('Movies');
    watchCollection('movies');
</script>
