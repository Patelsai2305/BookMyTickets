package com.example.bookmyticket.repository

import com.example.bookmyticket.models.Movie
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore
import kotlinx.coroutines.tasks.await

class WishlistRepository {
    private val firestore = FirebaseFirestore.getInstance()
    private val auth = FirebaseAuth.getInstance()
    
    private fun getWishlistCollection() = auth.currentUser?.let {
        firestore.collection("Users").document(it.uid).collection("Wishlist")
    }

    suspend fun addToWishlist(movie: Movie): Boolean {
        return try {
            getWishlistCollection()?.document(movie.id)?.set(movie)?.await()
            true
        } catch (e: Exception) {
            false
        }
    }

    suspend fun removeFromWishlist(movieId: String): Boolean {
        return try {
            getWishlistCollection()?.document(movieId)?.delete()?.await()
            true
        } catch (e: Exception) {
            false
        }
    }

    suspend fun isInWishlist(movieId: String): Boolean {
        return try {
            val doc = getWishlistCollection()?.document(movieId)?.get()?.await()
            doc?.exists() == true
        } catch (e: Exception) {
            false
        }
    }

    suspend fun getWishlistMovies(): List<Movie> {
        return try {
            getWishlistCollection()?.get()?.await()?.toObjects(Movie::class.java) ?: emptyList()
        } catch (e: Exception) {
            emptyList()
        }
    }
}