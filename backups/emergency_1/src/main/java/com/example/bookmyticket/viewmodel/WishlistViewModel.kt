package com.example.bookmyticket.viewmodel

import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.repository.WishlistRepository
import kotlinx.coroutines.launch

class WishlistViewModel : ViewModel() {
    private val repository = WishlistRepository()
    
    val wishlistMovies = MutableLiveData<List<Movie>>()
    val isLoading = MutableLiveData<Boolean>()
    val isFavorite = MutableLiveData<Boolean>()

    fun loadWishlist() {
        viewModelScope.launch {
            isLoading.postValue(true)
            val movies = repository.getWishlistMovies()
            wishlistMovies.postValue(movies)
            isLoading.postValue(false)
        }
    }

    fun toggleWishlist(movie: Movie) {
        viewModelScope.launch {
            val exists = repository.isInWishlist(movie.id)
            if (exists) {
                repository.removeFromWishlist(movie.id)
                isFavorite.postValue(false)
            } else {
                repository.addToWishlist(movie)
                isFavorite.postValue(true)
            }
            loadWishlist()
        }
    }

    fun checkIfFavorite(movieId: String) {
        viewModelScope.launch {
            val exists = repository.isInWishlist(movieId)
            isFavorite.postValue(exists)
        }
    }
}