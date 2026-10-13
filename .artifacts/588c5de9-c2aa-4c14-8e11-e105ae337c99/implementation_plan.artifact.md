# Implementation Plan - TMDB API Integration & Global Fixes

The user wants to fully integrate the provided TMDB API key to ensure the app displays real movie data on the Home screen and to fix any remaining bugs.

## User Review Required

> [!IMPORTANT]
> - **Primary Data Source**: The Home screen will now fetch real-time data from TMDB (Now Playing and Upcoming) if the local database (Firestore) is empty or as the primary source.
> - **Restore Points**: A restore point will be created before these changes.

## Proposed Changes

### [Repositories]
#### [MODIFY] [MovieRepository.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/repository/MovieRepository.kt)
- Add `getNowPlayingFromTMDB()` and `getUpcomingFromTMDB()` methods.
- These will use the `ApiClient.movieApi` and the provided `TMDB_API_KEY`.
- Ensure image URLs are correctly formatted (prepending the TMDB image base URL).

### [ViewModels]
#### [MODIFY] [HomeViewModel.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/viewmodel/HomeViewModel.kt)
- Update `loadMovies()` to fetch from TMDB.
- Optionally, combine Firestore movies with TMDB movies.

### [User Interface]
#### [MODIFY] [HomeFragment.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/HomeFragment.kt)
- Ensure the progress bar is shown while fetching from the network.
- Handle potential network errors gracefully.

### [Fixes]
- Double-check all Firestore collection references for consistency (`Movies`, `Bookings`, `Users`).
- Ensure all screens maintain the Immersive Mode.

## Verification Plan

### Manual Verification
1. **Home Screen**: Open the app and verify that "Popular Movies" and "Upcoming Movies" are populated with real movie posters and titles from the internet.
2. **Admin Dashboard**: Verify that the "Total Movies" count still reflects the movies added to Firestore manually.
3. **Immersive Mode**: Swipe from the edge to ensure the system bars appear and then hide.
