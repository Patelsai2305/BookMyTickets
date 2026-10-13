# Implementation Plan - Fetch and Import Movies from TMDB

The goal is to provide an easy way for admins to populate the movie database by fetching and importing movies directly from the TMDB API ("Now Playing" and "Upcoming").

## User Review Required

> [!IMPORTANT]
> - This feature will add a "Fetch from TMDB" option in the Admin Movies section.
> - Importing a movie will fetch its full details (including runtime/duration) and save it to Firestore.
> - Do you prefer a new Activity for browsing TMDB movies, or should I just improve the existing search in "Add Movie"? I propose a new screen for better browsing.

## Proposed Changes

### [Network Layer]

#### [MODIFY] [MovieApi.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/network/MovieApi.kt)
- Add `@GET("movie/{movie_id}")` to fetch full movie details by ID.

### [Data Layer]

#### [MODIFY] [MovieRepository.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/repository/MovieRepository.kt)
- Implement `getMovieDetailsFromTMDB(tmdbId: String)` to fetch full details (runtime, genres, etc.).
- Add helper to convert TMDB genres to a simple comma-separated string.

### [Business Logic]

#### [MODIFY] [AdminViewModel.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/viewmodel/AdminViewModel.kt)
- Add `tmdbNowPlaying` and `tmdbUpcoming` LiveData.
- Add `fetchTMDBMovies(type: String)` to load lists from TMDB.
- Add `importMovieFromTMDB(tmdbId: String)` to fetch details and save to Firestore.

### [UI Layer]

#### [NEW] [TMDBFetchActivity.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/TMDBFetchActivity.kt)
- Screen with tabs for "Now Playing" and "Upcoming".
- Search bar to search TMDB directly.
- List of movies with "Import" buttons.

#### [MODIFY] [AdminMoviesFragment.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/AdminMoviesFragment.kt)
- Add a "Fetch from TMDB" button next to the "Add" FAB or in the toolbar.

#### [NEW] [activity_tmdb_fetch.xml](file:///D:/BookMyTicket/app/src/main/res/layout/activity_tmdb_fetch.xml)
- Layout for the TMDB browsing screen.

## Verification Plan

### Automated Tests
- Verify successful build.

### Manual Verification
1. Log in as Admin.
2. Navigate to "Movies".
3. Tap "Fetch from TMDB".
4. Browse the "Now Playing" list.
5. Tap "Import" on a movie (e.g., "Deadpool & Wolverine").
6. Verify the movie appears in the main Admin Movies list.
7. Open the newly imported movie and verify it has a `duration` > 0 and a full description.
