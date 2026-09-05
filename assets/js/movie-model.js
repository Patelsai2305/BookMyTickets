// Movie model mapping — keeps the Web app in sync with the Android app's
// Firestore documents, which may use either Android/TMDB or Web field names.
export function normalizeMovie(id, data = {}) {
    return {
        id,
        // title (Android/TMDB) vs name (Web fallback)
        title: data.title || data.name || 'Untitled',
        // posterUrl (Android) vs posterPath / poster_path (TMDB) vs poster (Web)
        poster: data.posterUrl || data.posterPath || data.poster_path || data.poster || '',
        backdrop: data.backdropUrl || data.backdrop_path || data.backdrop || '',
        // status ("Now Playing") vs category ("now_playing")
        status: data.status || '',
        category: data.category || data.type || '',
        upcoming: data.upcoming === true,
        tmdbId: data.tmdbId || data.movieId || data.id || id,
        raw: data
    };
}

// True when the movie should be listed under "Upcoming" rather than "Now Playing".
export function isUpcomingMovie(movie) {
    return movie.upcoming
        || movie.category.toLowerCase().includes('upcoming')
        || movie.status.toLowerCase().includes('upcoming');
}
