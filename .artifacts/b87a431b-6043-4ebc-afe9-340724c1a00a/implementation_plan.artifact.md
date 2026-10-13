# Fix Build Errors and Deprecations in AddEditMovieActivity

The project has multiple build errors in `AddEditMovieActivity.kt` due to missing methods/properties in `AdminViewModel`, an `ImageButton` being treated as a text-capable button, and several deprecated APIs.

## Proposed Changes

### [AdminViewModel.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/viewmodel/AdminViewModel.kt)

- Implement missing `LiveData` and methods required by `AddEditMovieActivity`:
    - `tmdbSearchResults: LiveData<List<Movie>>`
    - `saveStatus: LiveData<SaveStatus>`
    - `SaveStatus` sealed class (Loading, Success, Error).
    - `searchTMDB(query: String)`: Calls `MovieRepository.searchMoviesFromTMDB`.
    - `saveMovieWithImages(context: Context, movie: Movie, posterUri: Uri?, bannerUri: Uri?)`: Handles image uploads (Base64) and Firestore save.

### [activity_add_edit_movie.xml](file:///D:/BookMyTicket/app/src/main/res/layout/activity_add_edit_movie.xml)

- Change `btnSearchTMDB` from `ImageButton` to `com.google.android.material.button.MaterialButton` to support the `text` property (e.g., "Searching...").
- Adjust layout properties for the new button.

### [AddEditMovieActivity.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/AddEditMovieActivity.kt)

- **Fix Deprecations**:
    - Replace `ProgressDialog` with a custom loading dialog using `AlertDialog` or use a View-based `ProgressBar` (I will implement a simple `AlertDialog` with a `ProgressBar` to minimize layout changes).
    - Update `getParcelableExtra` to use the non-deprecated version for API 33+.
- **Fix Unresolved References**:
    - Ensure `binding.btnSearchTMDB.text` works after XML change.
    - Explicitly import `Movie` if needed, although it seems to be in the same package or already imported.
    - Fix observer syntax to help compiler with type inference if necessary.

## Verification Plan

### Automated Tests
- Run `./gradlew :app:compileDebugKotlin` to verify all build errors are resolved.

### Manual Verification
- Verify Movie Search (TMDB integration).
- Verify Movie Saving (Firestore integration).
- Verify UI doesn't crash on image picking or saving.
- Check that the loading indicator appears correctly.