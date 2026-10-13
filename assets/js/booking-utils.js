export function bookingMatchesShow(data, show) {
    const movie = data.movieId ?? data.movieID ?? data.tmdbId;
    const theatre = data.theatreId ?? data.theatreID ?? data.theatreName ?? data.theater ?? data.theatre;
    const date = data.date ?? data.showDate ?? data.show_date;
    const time = data.timing ?? data.showTime ?? data.show_time;
    const status = String(data.status ?? data.paymentStatus ?? '').toUpperCase();
    return String(movie) === String(show.movieId)
        && (String(theatre) === String(show.theatreId) || String(data.theatreName || '') === String(show.theatreName || ''))
        && String(date) === String(show.date)
        && String(time) === String(show.time)
        && status !== 'CANCELLED'
        && status !== 'REFUNDED';
}

export function getBookedSeats(data) {
    const seats = data.seats ?? data.selectedSeats ?? data.bookedSeats ?? [];
    return Array.isArray(seats) ? seats : String(seats).split(',').map(s => s.trim()).filter(Boolean);
}
