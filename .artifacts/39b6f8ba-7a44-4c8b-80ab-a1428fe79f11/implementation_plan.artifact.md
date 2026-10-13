# Implementation Plan - Fix Missing Movies in App

The user reported they cannot see any movies in the app. This plan focuses on making the movie loading logic more robust by handling field name mismatches between the Android app and the web version, and improving error visibility.

## User Review Required

> [!IMPORTANT]
> **Field Name Mismatch**: The Android app currently expects a `title` field in Firestore, while the web version might be using `name`. I will update the code to check both fields to ensure movies always show up.
> **Loose Filtering**: I will modify the database query to be less strict about the movie "status" to ensure that even movies with slightly different status strings (like "now_playing" vs "Now Playing") are displayed.

## Proposed Changes

### 1. Model & Data Integrity
#### [MODIFY] [Movie.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/models/Movie.kt)
- Add a helper method `getDisplayTitle()` that returns `title` or `name` if one is empty.
- Add `poster_path` and `backdrop_path` fields specifically for TMDB mapping to prevent collisions with Firestore's `posterUrl`.

### 2. Robust Loading Logic
#### [MODIFY] [MovieRepository.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/repository/MovieRepository.kt)
- Update `getMoviesByStatus` to fetch a broader set of data and filter locally.
- Implement a "Fail-Safe" fetch: If a filtered query returns zero results, try fetching the entire collection as a backup.
- Improve `formatTMDBMovies` to handle both local and global image paths more reliably.

#### [MODIFY] [HomeViewModel.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/viewmodel/HomeViewModel.kt)
- Update the merging logic to use the new `getDisplayTitle()` for the `distinctBy` filter.
- Add detailed logging to help identify which source (Firestore or TMDB) is failing.

### 3. UI Feedback
#### [MODIFY] [HomeFragment.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/HomeFragment.kt)
- Ensure the Progress Bar is hidden only after data is actually processed.
- Show a more specific empty state message if movies are truly missing.

## Verification Plan

### Manual Verification
1. **Empty State Check**: Verify that if no movies are found, a clear message is displayed.
2. **Sync Check**: Add a movie on the web with only the `name` field populated. Verify it appears in the Android app.
3. **TMDB Check**: Verify that TMDB movies (like popular blockbusters) show up even if Firestore is empty.
