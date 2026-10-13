package com.example.bookmyticket.viewmodel

import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.models.Theatre
import com.example.bookmyticket.repository.MovieRepository
import com.example.bookmyticket.repository.TheatreRepository
import kotlinx.coroutines.async
import kotlinx.coroutines.launch

class HomeViewModel : ViewModel() {

    private val repository = MovieRepository()

    val nowPlayingMovies = MutableLiveData<List<Movie>>()
    val upcomingMovies = MutableLiveData<List<Movie>>()
    val searchResults = MutableLiveData<List<Movie>>()
    val theatreResults = MutableLiveData<List<Theatre>>()
    val error = MutableLiveData<String>()

    private var isMoviesLoaded = false

    fun loadMovies(forceRefresh: Boolean = false) {
        if (!forceRefresh && isMoviesLoaded && !nowPlayingMovies.value.isNullOrEmpty()) {
            android.util.Log.d("HomeViewModel", "Movies already loaded, skipping network call.")
            return
        }

        viewModelScope.launch {
            try {
                val currentUser = com.google.firebase.auth.FirebaseAuth.getInstance().currentUser
                if (currentUser == null) {
                    android.util.Log.e("HomeViewModel", "User not signed in. Firestore rules will block requests.")
                    error.postValue("Please sign in to view movies.")
                    return@launch
                } else {
                    android.util.Log.d("HomeViewModel", "Loading movies for UID: ${currentUser.uid}")
                }

                // Fetch from Firestore and TMDB in parallel
                val firestoreNowPlayingDeferred = async { repository.getMoviesByStatus("Now Playing") }
                val firestoreUpcomingDeferred = async { repository.getMoviesByStatus("Upcoming") }
                val tmdbNowPlayingDeferred = async { repository.getNowPlayingFromTMDB() }
                val tmdbUpcomingDeferred = async { repository.getUpcomingFromTMDB() }

                val firestoreNowPlaying = try { firestoreNowPlayingDeferred.await() } catch (e: Exception) { 
                    android.util.Log.e("HomeViewModel", "Firestore NowPlaying failed", e); emptyList() 
                }
                val firestoreUpcoming = try { firestoreUpcomingDeferred.await() } catch (e: Exception) { 
                    android.util.Log.e("HomeViewModel", "Firestore Upcoming failed", e); emptyList() 
                }
                
                var tmdbError: String? = null
                val tmdbNowPlaying = try { 
                    tmdbNowPlayingDeferred.await() 
                } catch (e: Exception) { 
                    android.util.Log.e("HomeViewModel", "TMDB NowPlaying failed", e)
                    tmdbError = e.message
                    emptyList() 
                }
                val tmdbUpcoming = try { 
                    tmdbUpcomingDeferred.await() 
                } catch (e: Exception) { 
                    android.util.Log.e("HomeViewModel", "TMDB Upcoming failed", e)
                    if (tmdbError == null) tmdbError = e.message
                    emptyList() 
                }
                
                if (tmdbError != null) {
                    error.postValue("TMDB API Error: $tmdbError")
                }
                
                android.util.Log.d("HomeViewModel", "Counts - FS NowPlaying: ${firestoreNowPlaying.size}, FS Upcoming: ${firestoreUpcoming.size}")
                android.util.Log.d("HomeViewModel", "Counts - TMDB NowPlaying: ${tmdbNowPlaying.size}, TMDB Upcoming: ${tmdbUpcoming.size}")

                // Combine: Admin movies first, then global movies
                val combinedNowPlaying = firestoreNowPlaying + tmdbNowPlaying
                val combinedUpcoming = firestoreUpcoming + tmdbUpcoming
                
                android.util.Log.d("HomeViewModel", "Loaded Now Playing: ${firestoreNowPlaying.size} (Firestore) + ${tmdbNowPlaying.size} (TMDB)")
                android.util.Log.d("HomeViewModel", "Loaded Upcoming: ${firestoreUpcoming.size} (Firestore) + ${tmdbUpcoming.size} (TMDB)")

                // Use getDisplayTitle() for deduplication to ensure sync
                val distinctNowPlaying = combinedNowPlaying.distinctBy { it.getDisplayTitle().lowercase() }
                val distinctUpcoming = combinedUpcoming.distinctBy { it.getDisplayTitle().lowercase() }
                
                nowPlayingMovies.postValue(distinctNowPlaying)
                upcomingMovies.postValue(distinctUpcoming)
                isMoviesLoaded = true
                
                if (distinctNowPlaying.isEmpty() && distinctUpcoming.isEmpty()) {
                    android.util.Log.w("HomeViewModel", "No specific movies found, trying fallback to all movies")
                    val allMovies = repository.getAllMovies()
                    if (allMovies.isNotEmpty()) {
                        nowPlayingMovies.postValue(allMovies)
                        android.util.Log.d("HomeViewModel", "Fallback: Loaded ${allMovies.size} movies into Now Playing")
                    } else {
                        error.postValue("No movies found. Please check your internet or database rules.")
                    }
                }
            } catch (e: Exception) {
                android.util.Log.e("HomeViewModel", "Error loading movies", e)
                if (e.message?.contains("PERMISSION_DENIED", ignoreCase = true) == true) {
                    error.postValue("Database access denied. Please check your Firestore security rules.")
                } else {
                    error.postValue("Failed to fetch movies: ${e.localizedMessage}")
                }
            }
        }
    }

    fun searchMovies(query: String) {
        viewModelScope.launch {
            try {
                // Search both Firestore and TMDB in parallel
                val firestoreSearchDeferred = async { repository.searchMovies(query) }
                val tmdbSearchDeferred = async { repository.searchMoviesFromTMDB(query) }
                
                val firestoreMovies = try { firestoreSearchDeferred.await() } catch (e: Exception) { emptyList() }
                val tmdbMovies = try { tmdbSearchDeferred.await() } catch (e: Exception) { 
                    error.postValue("TMDB Search Error: ${e.message}")
                    emptyList() 
                }
                
                // Combine and deduplicate
                val combined = (firestoreMovies + tmdbMovies)
                    .distinctBy { it.getDisplayTitle().lowercase() }
                
                searchResults.postValue(combined)
                
                val theatres = TheatreRepository.getStaticTheatres().filter { 
                    it.name.contains(query, ignoreCase = true) || it.location.contains(query, ignoreCase = true)
                }
                theatreResults.postValue(theatres)
                
                if (combined.isEmpty() && theatres.isEmpty()) {
                    error.postValue("No results found for '$query'")
                }
            } catch (e: Exception) {
                error.postValue("Search failed: ${e.localizedMessage}")
            }
        }
    }
}
