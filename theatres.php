<?php
$movieId = $_GET['movie_id'] ?? $_GET['movieId'] ?? '';
$movieTitle = $_GET['title'] ?? 'the Movie';
$moviePoster = $_GET['poster'] ?? '';
include 'includes/header.php';
?>

<style>
    .showtimes-page { padding: 2rem 5%; }
    .showtimes-page h2 { margin-bottom: 1rem; font-weight: 800; }
    .showtime-date-row { display: flex; gap: 0.75rem; margin-bottom: 1rem; }
    .date-choice, .time-chip { border: 1px solid var(--border-color); background: var(--card-dark); color: var(--text-main); border-radius: 8px; cursor: pointer; font: inherit; transition: all 0.2s ease; }
    .date-choice { padding: 0.65rem 1.2rem; font-weight: 600; }
    .date-choice.active, .time-chip:hover, .time-chip:focus { background: var(--accent); border-color: var(--accent); color: white; transform: translateY(-1px); }
    .showtime-hint { color: var(--text-muted); font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; }
    .theatre-list { display: grid; gap: 1.25rem; margin-top: 1.5rem; }
    .theatre-card {
        background: var(--card-dark);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .theatre-card:hover {
        border-color: var(--accent);
        background: var(--card-hover);
        transform: translateX(5px);
    }
    .theatre-header h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.25rem; }
    .theatre-header p { color: var(--text-muted); font-size: 0.85rem; }
    .time-chips { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.25rem; }
    .time-chip { min-width: 90px; padding: 0.6rem 0.75rem; text-align: center; font-weight: 600; font-size: 0.9rem; }
    .location-badge {
        font-size: 0.7rem;
        background: rgba(255,255,255,0.1);
        padding: 2px 8px;
        border-radius: 4px;
        margin-left: 8px;
        color: #cbd5e1;
    }
    @media (max-width: 600px) { .showtimes-page { padding: 1.5rem 1rem; } .theatre-card { padding: 1rem; } .time-chip { flex: 1 1 calc(50% - 0.75rem); min-width: 0; } }
</style>

<main class="container showtimes-page my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Available Showtimes for <span class="text-danger"><?= htmlspecialchars($movieTitle) ?></span></h2>
    </div>

    <div class="showtime-date-row mb-4">
        <button class="date-choice active" data-date="today">Today</button>
        <button class="date-choice" data-date="tomorrow">Tomorrow</button>
    </div>

    <!-- Hidden by default to remove "buffering" feel -->
    <div id="location-status" class="showtime-hint mb-3" style="display: none;">
        <span class="spinner-border spinner-border-sm text-danger me-2" role="status"></span>Detecting nearby theatres...
    </div>

    <div id="theatre-list" class="theatre-list"></div>
</main>

<script type="module">
const movieId = <?= json_encode((string) $movieId) ?>;
const movieTitle = <?= json_encode((string) $movieTitle) ?>;
const moviePoster = <?= json_encode((string) $moviePoster) ?>;
const fallbackTheatres = [
    { id: 'inox-vr-mall-surat', name: 'Inox: VR Mall (Surat)', address: 'VR Mall, Surat', lat: 21.1741, lng: 72.7844, times: ['10:00 AM', '01:30 PM', '04:45 PM', '09:00 PM'] },
    { id: 'cinepolis-imperial-square-surat', name: 'Cinepolis: Imperial Square (Surat)', address: 'Imperial Square, Surat', lat: 21.1764, lng: 72.8021, times: ['11:15 AM', '02:45 PM', '06:00 PM', '10:30 PM'] },
    { id: 'pvr-rahul-raj-mall-surat', name: 'PVR: Rahul Raj Mall (Surat)', address: 'Rahul Raj Mall, Surat', lat: 21.1718, lng: 72.7865, times: ['09:30 AM', '12:45 PM', '04:00 PM', '08:15 PM'] },
    { id: 'rajhans-pal-surat', name: 'Rajhans Cinemas: Pal (Surat)', address: 'Pal, Surat', lat: 21.1963, lng: 72.7811, times: ['10:30 AM', '02:00 PM', '05:30 PM', '09:45 PM'] }
];
let theatres = [...fallbackTheatres];
let selectedDate = 'today';
let userLocation = null;
const list = document.getElementById('theatre-list');
const locationStatus = document.getElementById('location-status');

function loadGoogleMaps() {
    return new Promise((resolve, reject) => {
        if (window.google?.maps?.places) return resolve();
        const script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyA6rBujIxYxb_1W2UacEVTa18EQ1sX9x3c&libraries=places';
        script.async = true;
        script.onload = () => window.google?.maps?.places ? resolve() : reject(new Error('Google Places was unavailable'));
        script.onerror = () => reject(new Error('Google Maps failed to load'));
        document.head.appendChild(script);
    });
}

function haversineKm(lat1, lng1, lat2, lng2) {
    const radius = 6371;
    const toRadians = degrees => degrees * Math.PI / 180;
    const dLat = toRadians(lat2 - lat1);
    const dLng = toRadians(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(dLng / 2) ** 2;
    return radius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function selectedDateValue() {
    const date = new Date();
    if (selectedDate === 'tomorrow') date.setDate(date.getDate() + 1);
    return date.toISOString().slice(0, 10);
}

function isFutureToday(time) {
    if (selectedDate !== 'today') return true;
    const match = time.match(/(\d+):(\d+)\s(AM|PM)/);
    if (!match) return true;
    let hours = Number(match[1]) % 12 + (match[3] === 'PM' ? 12 : 0);
    const showTime = new Date();
    showTime.setHours(hours, Number(match[2]), 0, 0);
    return showTime.getTime() >= Date.now() - 30 * 60 * 1000;
}

function renderTheatres() {
    const visible = theatres.map(theatre => ({
        ...theatre,
        distance: userLocation ? haversineKm(userLocation.lat, userLocation.lng, theatre.lat, theatre.lng) : null
    })).sort((a, b) => (a.distance ?? Infinity) - (b.distance ?? Infinity));

    list.innerHTML = '';
    visible.forEach(theatre => {
        const times = theatre.times.filter(isFutureToday);
        const card = document.createElement('article');
        card.className = 'theatre-card generic-card p-4 mb-3';
        const timingButtons = times.length ? times.map(time => `<button class="time-chip btn btn-outline-secondary" data-theatre="${theatre.id}" data-name="${theatre.name}" data-time="${time}">${time}</button>`).join('') : '<span class="showtime-hint">No remaining shows today</span>';

        let distanceHtml = '';
        if (theatre.distance !== null) {
            distanceHtml = `<span class="location-badge">${theatre.distance.toFixed(1)} km away</span>`;
        } else {
            distanceHtml = `<span class="location-badge">Nearby cinema</span>`;
        }

        card.innerHTML = `
            <div class="theatre-header d-flex justify-content-between align-items-center">
                <div style="color: white;">
                    <h3 class="h5 fw-bold mb-1" style="color: white;">${theatre.name}</h3>
                    <p class="mb-0" style="color: #cbd5e1; font-size: 0.85rem;">${theatre.address || 'Cinema'}${distanceHtml}</p>
                </div>
            </div>
            <div class="time-chips d-flex flex-wrap gap-2 mt-3">${timingButtons}</div>`;
        list.appendChild(card);
    });

    list.querySelectorAll('.time-chip').forEach(button => button.addEventListener('click', () => {
        if (!movieId) {
            locationStatus.style.display = 'block';
            locationStatus.textContent = 'This movie is missing an ID. Return to the movie details page and try again.';
            return;
        }
        const params = new URLSearchParams({ movieId, title: movieTitle, poster: moviePoster, theatreId: button.dataset.theatre, theatreName: button.dataset.name, date: selectedDateValue(), timing: button.dataset.time });
        window.location.href = `seat_selection.php?${params.toString()}`;
    }));
}

document.querySelectorAll('.date-choice').forEach(button => button.addEventListener('click', () => {
    selectedDate = button.dataset.date;
    document.querySelectorAll('.date-choice').forEach(choice => choice.classList.toggle('active', choice === button));
    renderTheatres();
}));

function useFallback(message) {
    theatres = fallbackTheatres;
    renderTheatres();
}

async function loadNearbyCinemas() {
    try {
        await loadGoogleMaps();
        const mapHost = document.createElement('div');
        const service = new google.maps.places.PlacesService(new google.maps.Map(mapHost));
        service.nearbySearch({
            location: new google.maps.LatLng(userLocation.lat, userLocation.lng),
            radius: 10000,
            type: 'movie_theater'
        }, (results, status) => {
            if (status !== google.maps.places.PlacesServiceStatus.OK || !results?.length) {
                useFallback('Showing default Surat theatres.');
                return;
            }
            theatres = results.filter(place => place.geometry?.location).map(place => ({
                id: place.place_id,
                name: place.name,
                address: place.vicinity || 'Nearby cinema',
                lat: place.geometry.location.lat(),
                lng: place.geometry.location.lng(),
                times: fallbackTheatres[0].times
            }));
            renderTheatres();
        });
    } catch (error) {
        console.warn('Google Places unavailable:', error);
        useFallback('Showing default Surat theatres.');
    }
}

// Instant render to avoid blank screen
renderTheatres();

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(position => {
        userLocation = { lat: position.coords.latitude, lng: position.coords.longitude };
        loadNearbyCinemas();
    }, () => {
        useFallback('Location unavailable. Showing Surat theatres.');
    }, { enableHighAccuracy: true, timeout: 10000 });
} else {
    useFallback('Location is unavailable in this browser. Showing Surat theatres.');
}
</script>

<?php include 'includes/footer.php'; ?>
