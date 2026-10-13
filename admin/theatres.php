<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Theatres - Admin Dashboard</title>
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
                <li><a href="movies.php"><i class="fa-solid fa-film"></i> Movies</a></li>
                <li><a href="bookings.php"><i class="fa-solid fa-ticket"></i> Bookings</a></li>
                <li><a href="showtimes.php"><i class="fa-solid fa-film"></i> Showtimes</a></li>
                <li><a href="theatres.php" class="active"><i class="fa-solid fa-location-dot"></i> Theatres</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users"></i> Users</a></li>
                <li><a href="../index.php"><i class="fa-solid fa-house"></i> View Website</a></li>
            </ul>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <div>
                <h1><i class="fa-solid fa-location-dot"></i> Manage Theatres</h1>
                <p class="admin-subtitle">Add theatres and they will sync automatically across the website and app</p>
            </div>
        </div>

        <div class="admin-card">
            <h2 class="admin-card-title"><i class="fa-solid fa-plus"></i> Add New Theatre</h2>
            <div style="display:flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
                <button type="button" id="import-surat-cinemas" class="btn btn-primary">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Import Surat Cinema List
                </button>
                <button type="button" id="clear-theatres" class="btn btn-danger">
                    <i class="fa-solid fa-trash"></i> Clear Current Theatre List
                </button>
            </div>
            <form id="theatre-form" class="form-grid">
                <div class="form-group">
                    <label for="theatre-name">Theatre Name</label>
                    <input id="theatre-name" type="text" class="form-input" placeholder="PVR Cinemas" required>
                </div>
                <div class="form-group">
                    <label for="theatre-city">City</label>
                    <input id="theatre-city" type="text" class="form-input" placeholder="Surat" required>
                </div>
                <div class="form-group">
                    <label for="theatre-area">Area / Locality</label>
                    <input id="theatre-area" type="text" class="form-input" placeholder="Adajan, Athwa, VR Mall">
                </div>
                <div class="form-group">
                    <label for="theatre-address">Address</label>
                    <input id="theatre-address" type="text" class="form-input" placeholder="VR Mall, Surat" required>
                </div>
                <div class="form-group">
                    <label for="theatre-lat">Latitude</label>
                    <input id="theatre-lat" type="number" step="any" class="form-input" placeholder="21.1700" required>
                </div>
                <div class="form-group">
                    <label for="theatre-lng">Longitude</label>
                    <input id="theatre-lng" type="number" step="any" class="form-input" placeholder="72.8300" required>
                </div>
                <div class="form-group">
                    <label for="theatre-times">Show Times</label>
                    <input id="theatre-times" type="text" class="form-input" placeholder="10:00 AM, 01:30 PM, 04:45 PM">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fa fa-plus"></i> Save Theatre
                    </button>
                </div>
            </form>
            <p id="form-status" class="form-status"></p>
        </div>

        <h2 class="section-heading"><i class="fa-solid fa-list"></i> Stored Theatres</h2>
        <div class="data-table-container">
            <table class="data-table" style="min-width: 800px;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>City</th>
                        <th>Area</th>
                        <th>Address</th>
                        <th>Coordinates</th>
                        <th>Times</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="theatres-body">
                    <tr><td colspan="7"><div class="loading-cell">Loading theatres...</div></td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <script type="module">
        import { auth, db, doc, getDoc, setDoc, deleteDoc, collection, onSnapshot, onAuthStateChanged, serverTimestamp } from '../assets/js/firebase-config.js';

        const tbody = document.getElementById('theatres-body');
        const formStatus = document.getElementById('form-status');
        let allTheatres = [];

        function slugify(value) {
            return String(value || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') || `theatre-${Date.now()}`;
        }

        function parseTimes(value) {
            if (!value) return [];
            if (Array.isArray(value)) return value.filter(Boolean);
            return String(value).split(',').map(item => item.trim()).filter(Boolean);
        }

        async function saveTheatre(data) {
            const area = (data.area || data.city || '').trim();
            const id = slugify(`${data.name}-${data.city}-${area}`);
            const payload = {
                id,
                name: data.name,
                city: data.city,
                area,
                address: data.address,
                lat: Number(data.lat),
                lng: Number(data.lng),
                times: parseTimes(data.times),
                active: true,
                updatedAt: serverTimestamp()
            };
            await setDoc(doc(db, 'theatres', id), payload);
        }

        function render() {
            tbody.innerHTML = '';
            if (!allTheatres.length) {
                tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-location-dot"></i><p>No theatres added yet.</p></div></td></tr>';
                return;
            }

            allTheatres.forEach(theatre => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${theatre.name || '—'}</strong></td>
                    <td>${theatre.city || '—'}</td>
                    <td>${theatre.area || theatre.city || '—'}</td>
                    <td>${theatre.address || '—'}</td>
                    <td>${Number(theatre.lat || 0).toFixed(4)}, ${Number(theatre.lng || 0).toFixed(4)}</td>
                    <td>${(theatre.times || []).join(', ') || '—'}</td>
                    <td><button class="btn btn-danger btn-sm del-btn" data-id="${theatre.id}"><i class="fa-solid fa-trash"></i> Delete</button></td>
                `;
                tbody.appendChild(tr);
            });

            tbody.querySelectorAll('.del-btn').forEach(btn => {
                btn.onclick = async () => {
                    if (!confirm('Delete this theatre?')) return;
                    await deleteDoc(doc(db, 'theatres', btn.dataset.id));
                };
            });
        }

        async function importSuratCinemaList() {
            try {
                formStatus.style.color = '#fbbf24';
                formStatus.innerText = 'Importing verified Surat theatre list to Firestore...';

                const response = await fetch('../assets/data/surat_cinema_locations.json');
                if (!response.ok) throw new Error('Could not read the verified cinema data file.');

                const cinemas = await response.json();
                if (!Array.isArray(cinemas) || !cinemas.length) throw new Error('The verified cinema list is empty.');

                for (const cinema of cinemas) {
                    const cityName = (cinema.city || 'Surat').trim() || 'Surat';
                    const areaName = (cinema.area || cinema.city || 'Surat').trim() || 'Surat';
                    const theatreId = slugify(`${cinema.name}-${cityName}-${areaName}`);
                    const payload = {
                        id: theatreId,
                        name: cinema.name,
                        city: cityName,
                        area: areaName,
                        address: cinema.address || `${areaName}, ${cityName}`,
                        lat: Number(cinema.lat || 0),
                        lng: Number(cinema.lng || 0),
                        times: Array.isArray(cinema.times) ? cinema.times : parseTimes(cinema.times),
                        active: cinema.active !== false,
                        updatedAt: serverTimestamp()
                    };
                    await setDoc(doc(db, 'theatres', theatreId), payload);
                }

                formStatus.style.color = '#10b981';
                formStatus.innerText = `✓ Imported ${cinemas.length} verified cinema entries successfully. The website and app are now synced from Firestore.`;
            } catch (error) {
                formStatus.style.color = '#e50914';
                formStatus.innerText = 'Import failed: ' + error.message;
            }
        }

        async function clearTheatres() {
            if (!allTheatres.length) {
                formStatus.style.color = '#fbbf24';
                formStatus.innerText = 'There are no theatres to clear.';
                return;
            }

            if (!confirm('This will delete every theatre in Firestore. Continue?')) return;

            try {
                formStatus.style.color = '#fbbf24';
                formStatus.innerText = 'Deleting theatre records...';

                for (const theatre of allTheatres) {
                    await deleteDoc(doc(db, 'theatres', theatre.id));
                }

                formStatus.style.color = '#10b981';
                formStatus.innerText = '✓ All theatres were cleared from Firestore.';
            } catch (error) {
                formStatus.style.color = '#e50914';
                formStatus.innerText = 'Delete failed: ' + error.message;
            }
        }

        document.getElementById('import-surat-cinemas').onclick = async () => {
            await importSuratCinemaList();
        };

        document.getElementById('clear-theatres').onclick = async () => {
            await clearTheatres();
        };

        document.getElementById('theatre-form').onsubmit = async (event) => {
            event.preventDefault();
            const payload = {
                name: document.getElementById('theatre-name').value.trim(),
                city: document.getElementById('theatre-city').value.trim(),
                area: document.getElementById('theatre-area').value.trim(),
                address: document.getElementById('theatre-address').value.trim(),
                lat: document.getElementById('theatre-lat').value,
                lng: document.getElementById('theatre-lng').value,
                times: document.getElementById('theatre-times').value
            };

            if (!payload.name || !payload.city || !payload.address || !payload.lat || !payload.lng) {
                formStatus.style.color = '#e50914';
                formStatus.innerText = 'Please fill all mandatory fields.';
                return;
            }

            try {
                await saveTheatre(payload);
                formStatus.style.color = '#10b981';
                formStatus.innerText = '✓ Theatre saved successfully. It will update the website and app in real time.';
                document.getElementById('theatre-form').reset();
            } catch (error) {
                formStatus.style.color = '#e50914';
                formStatus.innerText = 'Error: ' + error.message;
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

            onSnapshot(collection(db, 'theatres'), (snap) => {
                allTheatres = snap.docs.map(docSnap => ({ id: docSnap.id, ...docSnap.data() }));
                render();
            }, (error) => {
                tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><p>Could not load theatres: ' + error.message + '</p></div></td></tr>';
            });
        });
    </script>
</body>
</html>
