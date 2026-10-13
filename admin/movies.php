<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies - Admin Dashboard</title>
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
                <li><a href="movies.php" class="active"><i class="fa-solid fa-film"></i> Movies</a></li>
                <li><a href="bookings.php"><i class="fa-solid fa-ticket"></i> Bookings</a></li>
                <li><a href="showtimes.php"><i class="fa-solid fa-film"></i> Showtimes</a></li>
                <li><a href="theatres.php"><i class="fa-solid fa-location-dot"></i> Theatres</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users"></i> Users</a></li>
                <li><a href="../index.php"><i class="fa-solid fa-house"></i> View Website</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <div>
                <h1><i class="fa-solid fa-film"></i> Manage Movies</h1>
                <p class="admin-subtitle">Add movies to Firebase and sync them automatically across the website and app</p>
            </div>
        </div>

        <div class="admin-card">
            <h2 class="admin-card-title"><i class="fa-solid fa-plus"></i> Add New Movie</h2>
            <form id="movie-form" class="form-grid">
                <div class="form-group">
                    <label for="movie-title">Movie Title</label>
                    <input id="movie-title" type="text" class="form-input" placeholder="The Batman" required>
                </div>
                <div class="form-group">
                    <label for="movie-status">Status</label>
                    <select id="movie-status" class="form-input" required>
                        <option value="Now Showing">Now Showing</option>
                        <option value="Upcoming">Upcoming</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="movie-release-date">Release Date</label>
                    <input id="movie-release-date" type="date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="movie-category">Category</label>
                    <input id="movie-category" type="text" class="form-input" placeholder="Action, Drama, Thriller">
                </div>
                <div class="form-group">
                    <label for="movie-runtime">Runtime (Minutes)</label>
                    <input id="movie-runtime" type="number" min="0" class="form-input" placeholder="120">
                </div>
                <div class="form-group">
                    <label for="movie-rating">Rating</label>
                    <input id="movie-rating" type="number" min="0" max="10" step="0.1" class="form-input" placeholder="8.4">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="movie-description">Description</label>
                    <textarea id="movie-description" class="form-input" rows="4" placeholder="Short description or overview of the movie..."></textarea>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="movie-poster-upload">Poster Image Upload</label>
                    <input id="movie-poster-upload" type="file" accept="image/*" class="form-input">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="movie-poster">Poster URL / Base64</label>
                    <input id="movie-poster" type="text" class="form-input" placeholder="Paste a URL or raw image string /9j/...; upload fills this automatically">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="movie-banner-upload">Banner Image Upload</label>
                    <input id="movie-banner-upload" type="file" accept="image/*" class="form-input">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="movie-banner">Banner / Image URL String</label>
                    <input id="movie-banner" type="text" class="form-input" placeholder="Paste the raw image string or banner URL here /9j/...; upload fills this automatically">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="movie-backdrop-upload">Backdrop Image Upload</label>
                    <input id="movie-backdrop-upload" type="file" accept="image/*" class="form-input">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="movie-backdrop">Backdrop URL / Base64</label>
                    <input id="movie-backdrop" type="text" class="form-input" placeholder="Paste a URL or raw image string; upload fills this automatically">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Save Movie to Firebase
                    </button>
                </div>
            </form>
            <p id="form-status" class="form-status"></p>
        </div>

        <h2 class="section-heading"><i class="fa-solid fa-list"></i> Firebase Movies</h2>
        <div class="data-table-container">
            <table class="data-table" style="min-width: 1100px;">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Release</th>
                        <th>Category</th>
                        <th>Source</th>
                        <th>Edited By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="movies-body">
                    <tr><td colspan="8"><div class="loading-cell">Loading movies...</div></td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <script type="module">
        import { auth, db, doc, getDoc, getDocs, setDoc, deleteDoc, collection, onSnapshot, onAuthStateChanged, serverTimestamp } from '../assets/js/firebase-config.js';

        const tbody = document.getElementById('movies-body');
        const formStatus = document.getElementById('form-status');
        const moviesById = new Map();
        const ignoredMovieIds = new Set();

        function slugify(value) {
            return String(value || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') || `movie-${Date.now()}`;
        }

        function fileToBase64Payload(file) {
            return new Promise((resolve, reject) => {
                if (!file) {
                    reject(new Error('No file selected.'));
                    return;
                }

                const reader = new FileReader();
                reader.onload = () => {
                    const result = String(reader.result || '');
                    const cleaned = result.includes(',') ? result.split(',')[1] : result;
                    resolve(cleaned || '');
                };
                reader.onerror = () => reject(new Error('Could not read the selected image file.'));
                reader.readAsDataURL(file);
            });
        }

        async function handleImageUpload(event, targetFieldId) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;

            try {
                const payload = await fileToBase64Payload(file);
                const field = document.getElementById(targetFieldId);
                if (field) field.value = payload;
                formStatus.style.color = '#10b981';
                formStatus.innerText = `✓ ${file.name} converted to Base64 and filled automatically.`;
            } catch (error) {
                formStatus.style.color = '#e50914';
                formStatus.innerText = 'Image upload failed: ' + error.message;
            }
        }

        document.getElementById('movie-poster-upload').addEventListener('change', (event) => handleImageUpload(event, 'movie-poster'));
        document.getElementById('movie-banner-upload').addEventListener('change', (event) => handleImageUpload(event, 'movie-banner'));
        document.getElementById('movie-backdrop-upload').addEventListener('change', (event) => handleImageUpload(event, 'movie-backdrop'));

        function normalizeMovieDocument(data = {}) {
            const title = data.title || data.name || 'Untitled Movie';
            const status = data.status || 'Now Showing';
            const releaseDate = data.release_date || data.releaseDate || '';
            const posterUrl = data.posterUrl || data.posterURL || data.poster_path || data.posterPath || data.poster || '';
            const backdropUrl = data.backdropUrl || data.backdropURL || data.backdrop_path || data.backdropPath || data.backdrop || '';
            const description = data.description || data.overview || '';
            const category = data.category || data.type || 'General';
            const bannerUrl = data.bannerUrl || data.bannerURL || data.banner_url || data.banner || data.image || data.imageUrl || data.image_url || data.poster || '';
            const source = data.source || data.editedBy || data.updatedBy || data.createdBy || data.editor || 'Web';
            const editedBy = data.editedBy || data.updatedBy || data.createdBy || data.editor || data.adminName || data.nameBy || 'Admin';

            return {
                id: String(data.id || slugify(title)),
                title,
                name: title,
                status,
                category,
                type: category,
                upcoming: status === 'Upcoming',
                description,
                overview: description,
                release_date: releaseDate,
                releaseDate,
                posterUrl,
                poster_path: posterUrl,
                posterPath: posterUrl,
                bannerUrl,
                bannerURL: bannerUrl,
                banner_url: bannerUrl,
                banner: bannerUrl,
                image: bannerUrl,
                imageUrl: bannerUrl,
                image_url: bannerUrl,
                backdropUrl,
                backdrop_path: backdropUrl,
                backdropPath: backdropUrl,
                vote_average: Number(data.vote_average ?? data.voteAverage ?? 0),
                voteAverage: Number(data.vote_average ?? data.voteAverage ?? 0),
                runtime: Number(data.runtime ?? 0),
                tmdbId: data.tmdbId || data.movieId || '',
                source,
                editedBy,
                active: data.active !== false,
                updatedAt: serverTimestamp()
            };
        }

        async function refreshMoviesFromFirebase() {
            moviesById.clear();

            const [moviesResult, appMoviesResult] = await Promise.allSettled([
                getDocs(collection(db, 'Movies'), { source: 'server' }),
                getDocs(collection(db, 'movies'), { source: 'server' })
            ]);

            if (moviesResult.status === 'fulfilled') {
                mergeMovieMap('Movies', moviesResult.value);
            }
            if (appMoviesResult.status === 'fulfilled') {
                mergeMovieMap('movies', appMoviesResult.value);
            }
        }

        function renderMovies() {
            tbody.innerHTML = '';
            const movies = [...moviesById.values()].sort((a, b) => String(a.title).localeCompare(String(b.title)));

            if (!movies.length) {
                tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-film"></i><p>No movies added yet.</p></div></td></tr>';
                return;
            }

            movies.forEach((movie) => {
                const tr = document.createElement('tr');
                const poster = movie.posterUrl || movie.poster_path || movie.posterPath || '../assets/img/no-poster.png';
                const id = movie.id || slugify(movie.title || 'movie');
                const sourceLabel = movie.source || movie.editedBy || 'Web';
                const editorLabel = movie.editedBy || movie.updatedBy || movie.createdBy || movie.editor || 'Admin';

                tr.innerHTML = `
                    <td><img src="${poster}" alt="${movie.title}" style="width: 54px; height: 72px; object-fit: cover; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);"></td>
                    <td><strong>${movie.title || '—'}</strong></td>
                    <td><span class="badge ${movie.status === 'Upcoming' ? 'badge-warning' : 'badge-success'}">${movie.status || 'Now Showing'}</span></td>
                    <td>${movie.release_date || movie.releaseDate || '—'}</td>
                    <td>${movie.category || '—'}</td>
                    <td>${sourceLabel}</td>
                    <td>${editorLabel}</td>
                    <td><button class="btn btn-danger btn-sm del-btn" data-id="${id}"><i class="fa-solid fa-trash"></i> Delete</button></td>
                `;

                tbody.appendChild(tr);
            });

            tbody.querySelectorAll('.del-btn').forEach((button) => {
                button.onclick = async () => {
                    const id = button.dataset.id;
                    if (!confirm('Delete this movie from Firebase?')) return;

                    try {
                        await deleteDoc(doc(db, 'Movies', id));
                        await deleteDoc(doc(db, 'movies', id));
                        ignoredMovieIds.add(String(id));
                        moviesById.delete(String(id));
                        renderMovies();
                        await refreshMoviesFromFirebase();
                        ignoredMovieIds.delete(String(id));
                        formStatus.style.color = '#10b981';
                        formStatus.innerText = '✓ Movie removed from Firebase.';
                    } catch (error) {
                        formStatus.style.color = '#e50914';
                        formStatus.innerText = 'Delete failed: ' + error.message;
                    }
                };
            });
        }

        function mergeMovieMap(collectionName, snap) {
            const liveIds = new Set();
            snap.docs.forEach((docSnap) => {
                const movieId = String(docSnap.id);
                if (ignoredMovieIds.has(movieId)) {
                    return;
                }
                const data = docSnap.data() || {};
                const movie = normalizeMovieDocument({ ...data, id: docSnap.id });
                moviesById.set(movieId, movie);
                liveIds.add(movieId);
            });

            [...moviesById.keys()].forEach((id) => {
                if (!liveIds.has(id) && !ignoredMovieIds.has(id)) {
                    moviesById.delete(id);
                }
            });

            renderMovies();
        }

        async function saveMovie(payload) {
            const title = payload.title.trim();
            const releaseDate = payload.releaseDate || '';
            const status = payload.status || 'Now Showing';
            const id = slugify(title);

            const movieData = normalizeMovieDocument({
                id,
                title,
                name: title,
                status,
                category: payload.category || 'General',
                type: payload.category || 'General',
                upcoming: status === 'Upcoming',
                description: payload.description || '',
                overview: payload.description || '',
                release_date: releaseDate,
                releaseDate,
                posterUrl: payload.poster || '',
                poster_path: payload.poster || '',
                posterPath: payload.poster || '',
                bannerUrl: payload.banner || payload.poster || '',
                bannerURL: payload.banner || payload.poster || '',
                banner_url: payload.banner || payload.poster || '',
                banner: payload.banner || payload.poster || '',
                image: payload.banner || payload.poster || '',
                imageUrl: payload.banner || payload.poster || '',
                image_url: payload.banner || payload.poster || '',
                backdropUrl: payload.backdrop || '',
                backdrop_path: payload.backdrop || '',
                backdropPath: payload.backdrop || '',
                vote_average: Number(payload.rating || 0),
                voteAverage: Number(payload.rating || 0),
                runtime: Number(payload.runtime || 0),
                source: 'Web',
                editedBy: 'Admin',
                createdBy: 'Admin',
                updatedBy: 'Admin',
                active: true,
                addedAt: serverTimestamp(),
                updatedAt: serverTimestamp()
            });

            await setDoc(doc(db, 'Movies', id), movieData);
            await setDoc(doc(db, 'movies', id), movieData);
        }

        document.getElementById('movie-form').onsubmit = async (event) => {
            event.preventDefault();

            const form = {
                title: document.getElementById('movie-title').value.trim(),
                status: document.getElementById('movie-status').value,
                releaseDate: document.getElementById('movie-release-date').value,
                category: document.getElementById('movie-category').value.trim(),
                runtime: document.getElementById('movie-runtime').value,
                rating: document.getElementById('movie-rating').value,
                description: document.getElementById('movie-description').value.trim(),
                poster: document.getElementById('movie-poster').value.trim(),
                banner: document.getElementById('movie-banner').value.trim(),
                backdrop: document.getElementById('movie-backdrop').value.trim()
            };

            if (!form.title || !form.releaseDate) {
                formStatus.style.color = '#e50914';
                formStatus.innerText = 'Please enter the movie title and release date.';
                return;
            }

            try {
                await saveMovie(form);
                await refreshMoviesFromFirebase();
                formStatus.style.color = '#10b981';
                formStatus.innerText = '✓ Movie saved to Firebase. The website and app will sync it automatically.';
                document.getElementById('movie-form').reset();
                document.getElementById('movie-status').value = 'Now Showing';
            } catch (error) {
                formStatus.style.color = '#e50914';
                formStatus.innerText = 'Save failed: ' + error.message;
            }
        };

        onAuthStateChanged(auth, async (user) => {
            if (!user) {
                window.location.href = '../login.php?redirect=' + encodeURIComponent(window.location.href);
                return;
            }

            const userDoc = await getDoc(doc(db, 'Users', user.uid));
            const userData = userDoc.exists() ? userDoc.data() : {};
            const isAdmin = userData.role === 'admin' || userData.isAdmin === true || userData.isAdmin === 'true';
            if (!userDoc.exists() || !isAdmin) {
                window.location.href = '../index.php';
                return;
            }

            await refreshMoviesFromFirebase();
        });
    </script>
</body>
</html>
