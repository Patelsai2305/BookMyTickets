// Movie model mapping — keeps the Web app in sync with the Android app's
// Firestore documents, which may use either Android/TMDB or Web field names.
export function normalizeMovie(id, data = {}) {
<<<<<<< HEAD
    const raw = data || {};
    const posterCandidates = [
        raw.posterUrl, raw.posterURL, raw.poster_url,
        raw.posterPath, raw.poster_path, raw.poster,
        raw.bannerUrl, raw.bannerURL, raw.banner_url,
        raw.banner, raw.image, raw.imageUrl, raw.image_url,
        raw.moviePoster, raw.posterImage, raw.poster_image,
        raw.cover, raw.coverImage, raw.cover_url
    ];
    const backdropCandidates = [
        raw.backdropUrl, raw.backdropURL, raw.backdrop_url,
        raw.backdropPath, raw.backdrop_path, raw.backdrop,
        raw.bannerUrl, raw.bannerURL, raw.banner_url,
        raw.banner, raw.backdropImage, raw.backdrop_image
    ];

    const source = raw.source || raw.editedBy || raw.updatedBy || raw.createdBy || raw.editor || raw.author || 'Unknown';
    const editedBy = raw.editedBy || raw.updatedBy || raw.createdBy || raw.editor || raw.author || raw.adminName || raw.nameBy || 'Unknown';

    return {
        id,
        // title (Android/TMDB) vs name (Web fallback)
        title: raw.title || raw.name || 'Untitled',
        // posterUrl (Android) vs posterPath / poster_path (TMDB) vs poster (Web)
        poster: posterCandidates.find(value => value !== undefined && value !== null && value !== '') || '',
        backdrop: backdropCandidates.find(value => value !== undefined && value !== null && value !== '') || '',
        // status ("Now Playing") vs category ("now_playing")
        status: raw.status || '',
        category: raw.category || raw.type || '',
        upcoming: raw.upcoming === true,
        tmdbId: raw.tmdbId || raw.movieId || raw.id || id,
        source,
        editedBy,
        raw
=======
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
>>>>>>> 0e1e9cc6a83a338a3355514328ca1f09d8fcc2cb
    };
}

// True when the movie should be listed under "Upcoming" rather than "Now Playing".
export function isUpcomingMovie(movie) {
    return movie.upcoming
        || movie.category.toLowerCase().includes('upcoming')
        || movie.status.toLowerCase().includes('upcoming');
}
