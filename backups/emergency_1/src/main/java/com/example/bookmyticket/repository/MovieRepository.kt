package com.example.bookmyticket.repository

import android.content.Context
import android.net.Uri
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.network.ApiClient
import com.example.bookmyticket.utils.Constants
import com.example.bookmyticket.utils.ImageUtils
import com.google.firebase.firestore.FirebaseFirestore
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.async
import kotlinx.coroutines.coroutineScope
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.callbackFlow
import kotlinx.coroutines.tasks.await

class MovieRepository {
    private val firestore = FirebaseFirestore.getInstance()
    private val moviesCollection = firestore.collection("Movies")
    private val moviesLowerCollection = firestore.collection("movies")

    fun getMoviesFlow(): Flow<List<Movie>> = callbackFlow {
        val listener = moviesCollection.addSnapshotListener { snapshot, error ->
            if (error != null) {
                close(error)
                return@addSnapshotListener
            }
            val movies = snapshot?.documents?.mapNotNull { doc ->
                doc.toObject(Movie::class.java)?.apply { id = doc.id }
            } ?: emptyList()
            trySend(movies)
        }
        awaitClose { listener.remove() }
    }

    suspend fun getMoviesByStatus(requestedStatus: String): List<Movie> = kotlinx.coroutines.coroutineScope {
        try {
            android.util.Log.d("MovieRepository", "Fetching movies for status: $requestedStatus (Parallel)")
            
            val deferredUpper = async { fetchByStatusFromCollection(moviesCollection, requestedStatus) }
            val deferredLower = async { fetchByStatusFromCollection(moviesLowerCollection, requestedStatus) }

            val upperResult = deferredUpper.await()
            if (upperResult.isNotEmpty()) {
                android.util.Log.d("MovieRepository", "Firestore 'Movies': Found ${upperResult.size} movies for $requestedStatus")
                return@coroutineScope upperResult
            }

            val lowerResult = deferredLower.await()
            android.util.Log.d("MovieRepository", "Firestore 'movies': Found ${lowerResult.size} movies for $requestedStatus")
            lowerResult
        } catch (e: Exception) {
            val errorMsg = e.message ?: "Unknown error"
            if (errorMsg.contains("PERMISSION_DENIED", ignoreCase = true)) {
                android.util.Log.e("MovieRepository", "PERMISSION_DENIED: Check Firestore rules for Movies/movies collections.")
            } else {
                android.util.Log.e("MovieRepository", "Error fetching movies by status ($requestedStatus).", e)
            }
            emptyList()
        }
    }

    private suspend fun fetchByStatusFromCollection(collection: com.google.firebase.firestore.CollectionReference, requestedStatus: String): List<Movie> {
        // A. Try specific status field
        val snapshot = collection.whereEqualTo("status", requestedStatus).get().await()
        var list = snapshot.documents.mapNotNull { doc ->
            doc.toObject(Movie::class.java)?.apply { id = doc.id }
        }
        
        // B. Try web's 'category' field if empty
        if (list.isEmpty()) {
            val category = if (requestedStatus == "Now Playing") "now_playing" else "upcoming"
            val categorySnapshot = collection.whereEqualTo("category", category).get().await()
            list = categorySnapshot.documents.mapNotNull { doc ->
                doc.toObject(Movie::class.java)?.apply { id = doc.id }
            }
        }

        // C. Fallback: Fetch everything from this collection and filter locally (Loose match)
        if (list.isEmpty()) {
            val allSnapshot = collection.get().await()
            val all = allSnapshot.documents.mapNotNull { doc ->
                doc.toObject(Movie::class.java)?.apply { id = doc.id }
            }
            list = all.filter { 
                it.getEffectiveStatus().contains(requestedStatus, ignoreCase = true) ||
                it.status.contains(requestedStatus, ignoreCase = true) ||
                it.category.contains(requestedStatus, ignoreCase = true)
            }
        }
        return list
    }

    suspend fun getAllMovies(): List<Movie> {
        return try {
            val snapshot = moviesCollection.get().await()
            var movies = snapshot.documents.mapNotNull { doc ->
                doc.toObject(Movie::class.java)?.apply { id = doc.id }
            }
            
            // Try lowercase if empty
            if (movies.isEmpty()) {
                val lowerSnapshot = moviesLowerCollection.get().await()
                movies = lowerSnapshot.documents.mapNotNull { doc ->
                    doc.toObject(Movie::class.java)?.apply { id = doc.id }
                }
            }

            android.util.Log.d("MovieRepository", "Firestore Total: Found ${movies.size} movies")
            movies.sortedByDescending { it.createdAt }
        } catch (e: Exception) {
            android.util.Log.e("MovieRepository", "Error fetching all movies. Check Firestore rules.", e)
            emptyList()
        }
    }

    suspend fun uploadImage(context: Context, uri: Uri, folderName: String): String {
        return ImageUtils.uriToBase64(context, uri) ?: throw Exception("Failed to process image")
    }

    suspend fun addMovie(movie: Movie): Boolean {
        return try {
            val docRef = if (movie.id.isEmpty()) moviesCollection.document() else moviesCollection.document(movie.id)
            movie.id = docRef.id
            // Ensure compatibility fields are set for Web
            if (movie.name.isEmpty()) movie.name = movie.title
            if (movie.category.isEmpty()) movie.category = if (movie.status == "Now Playing") "now_playing" else "upcoming"
            
            docRef.set(movie).await()
            true
        } catch (e: Exception) {
            false
        }
    }

    suspend fun deleteMovie(movieId: String): Boolean {
        return try {
            moviesCollection.document(movieId).delete().await()
            true
        } catch (e: Exception) {
            false
        }
    }

    suspend fun searchMovies(query: String): List<Movie> {
        return try {
            val allMovies = getAllMovies()
            allMovies.filter { 
                it.title.contains(query, ignoreCase = true) || it.name.contains(query, ignoreCase = true)
            }
        } catch (e: Exception) {
            emptyList()
        }
    }

    suspend fun getNowPlayingFromTMDB(): List<Movie> {
        android.util.Log.d("MovieRepository", "Requesting TMDB Now Playing with key: ${Constants.TMDB_API_KEY.take(5)}...")
        val response = ApiClient.movieApi.getNowPlayingMovies(Constants.TMDB_API_KEY, region = null)
        if (response.isSuccessful) {
            val body = response.body()
            val movies = body?.results ?: emptyList()
            android.util.Log.d("MovieRepository", "TMDB Now Playing Success: Found ${movies.size} movies. Page: ${body?.page}")
            if (movies.isEmpty()) {
                android.util.Log.w("MovieRepository", "TMDB Now Playing returned 0 results. Check region/language.")
            }
            return formatTMDBMovies(movies)
        } else {
            val errorBody = response.errorBody()?.string()
            val message = "TMDB Error ${response.code()}: ${response.message()} - $errorBody"
            android.util.Log.e("MovieRepository", message)
            throw Exception(message)
        }
    }

    suspend fun getUpcomingFromTMDB(): List<Movie> {
        android.util.Log.d("MovieRepository", "Requesting TMDB Upcoming with key: ${Constants.TMDB_API_KEY.take(5)}...")
        val response = ApiClient.movieApi.getUpcomingMovies(Constants.TMDB_API_KEY, region = null)
        if (response.isSuccessful) {
            val body = response.body()
            val movies = body?.results ?: emptyList()
            android.util.Log.d("MovieRepository", "TMDB Upcoming Success: Found ${movies.size} movies. Page: ${body?.page}")
            return formatTMDBMovies(movies)
        } else {
            val errorBody = response.errorBody()?.string()
            val message = "TMDB Error ${response.code()}: ${response.message()} - $errorBody"
            android.util.Log.e("MovieRepository", message)
            throw Exception(message)
        }
    }

    private fun formatTMDBMovies(movies: List<Movie>): List<Movie> {
        movies.forEach {
            if (!it.posterUrl.isNullOrEmpty() && it.posterUrl!!.startsWith("/")) {
                 it.posterUrl = "https://image.tmdb.org/t/p/w500${it.posterUrl}"
            }
            if (!it.bannerUrl.isNullOrEmpty() && it.bannerUrl!!.startsWith("/")) {
                it.bannerUrl = "https://image.tmdb.org/t/p/original${it.bannerUrl}"
            }
        }
        return movies
    }

    suspend fun searchMoviesFromTMDB(query: String): List<Movie> {
        val response = ApiClient.movieApi.searchMovies(Constants.TMDB_API_KEY, query)
        if (response.isSuccessful) {
            return formatTMDBMovies(response.body()?.results ?: emptyList())
        } else {
            val errorBody = response.errorBody()?.string()
            val message = "TMDB Search Error ${response.code()}: ${response.message()} - $errorBody"
            android.util.Log.e("MovieRepository", message)
            throw Exception(message)
        }
    }
}
