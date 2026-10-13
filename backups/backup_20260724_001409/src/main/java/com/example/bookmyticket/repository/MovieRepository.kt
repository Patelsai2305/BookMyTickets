package com.example.bookmyticket.repository

import android.content.Context
import android.net.Uri
import com.example.bookmyticket.models.Movie
import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.storage.FirebaseStorage
import kotlinx.coroutines.tasks.await

class MovieRepository {
    private val firestore = FirebaseFirestore.getInstance()
    
    // Explicitly using the bucket URL to avoid "Object does not exist" errors
    private val storage = FirebaseStorage.getInstance("gs://book-my-ticket-35ee6.firebasestorage.app")
    
    // Collection name must match Firestore (case-sensitive)
    private val moviesCollection = firestore.collection("Movies")

    suspend fun getMoviesByStatus(status: String): List<Movie> {
        return try {
            moviesCollection.whereEqualTo("status", status)
                .get()
                .await()
                .toObjects(Movie::class.java)
        } catch (e: Exception) {
            emptyList()
        }
    }

    suspend fun getAllMovies(): List<Movie> {
        return try {
            moviesCollection.orderBy("createdAt", com.google.firebase.firestore.Query.Direction.DESCENDING)
                .get()
                .await()
                .toObjects(Movie::class.java)
        } catch (e: Exception) {
            // Fallback if index isn't created yet or createdAt is missing
            moviesCollection.get().await().toObjects(Movie::class.java)
        }
    }

    suspend fun uploadImage(context: Context, uri: Uri, folderName: String): String {
        return try {
            val fileName = "img_${System.currentTimeMillis()}.jpg"
            val imageRef = storage.reference.child("$folderName/$fileName")
            
            val inputStream = context.contentResolver.openInputStream(uri) 
                ?: throw Exception("Could not open the selected image.")
                
            // Perform upload
            imageRef.putStream(inputStream).await()
            
            // Get download URL
            imageRef.downloadUrl.await().toString()
        } catch (e: Exception) {
            android.util.Log.e("MovieRepository", "Upload Error: ${e.message}")
            throw e
        }
    }

    suspend fun addMovie(movie: Movie): Boolean {
        return try {
            val docRef = if (movie.id.isEmpty()) {
                moviesCollection.document()
            } else {
                moviesCollection.document(movie.id)
            }
            movie.id = docRef.id
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
            allMovies.filter { it.title.contains(query, ignoreCase = true) }
        } catch (e: Exception) {
            emptyList()
        }
    }
}