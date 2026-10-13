package com.example.bookmyticket.viewmodel

import android.content.Context
import android.net.Uri
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.repository.MovieRepository
import kotlinx.coroutines.launch

class AdminViewModel : ViewModel() {

    private val repository = MovieRepository()
    private val userRepository = com.example.bookmyticket.repository.UserRepository()
    private val bookingRepository = com.example.bookmyticket.repository.BookingRepository()

    private val _movies = MutableLiveData<List<Movie>>()
    val movies: LiveData<List<Movie>> = _movies

    private val _users = MutableLiveData<List<com.example.bookmyticket.models.User>>()
    val users: LiveData<List<com.example.bookmyticket.models.User>> = _users

    private val _bookings = MutableLiveData<List<com.example.bookmyticket.models.Booking>>()
    val bookings: LiveData<List<com.example.bookmyticket.models.Booking>> = _bookings

    private val _stats = MutableLiveData<AdminStats>()
    val stats: LiveData<AdminStats> = _stats

    data class AdminStats(
        val totalUsers: Int = 0,
        val totalMovies: Int = 0,
        val totalBookings: Int = 0,
        val totalRevenue: Int = 0
    )

    fun fetchStats() {
        viewModelScope.launch {
            val users = userRepository.getAllUsers()
            val movies = repository.getAllMovies()
            val bookings = bookingRepository.getAllBookings()
            val activeBookings = bookings.filter { it.status != "Cancelled" }
            val revenue = activeBookings.sumOf { it.totalPrice }
            
            _stats.value = AdminStats(
                totalUsers = users.size,
                totalMovies = movies.size,
                totalBookings = bookings.size,
                totalRevenue = revenue
            )
        }
    }

    fun fetchMovies() {
        viewModelScope.launch {
            _movies.value = repository.getAllMovies()
        }
    }

    fun fetchUsers() {
        viewModelScope.launch {
            _users.value = userRepository.getAllUsers()
        }
    }

    fun fetchBookings() {
        viewModelScope.launch {
            _bookings.value = bookingRepository.getAllBookings()
        }
    }

    fun cancelBooking(bookingId: String) {
        viewModelScope.launch {
            if (bookingRepository.updateBookingStatus(bookingId, "Cancelled")) {
                fetchBookings()
                fetchStats()
            }
        }
    }

    fun deleteMovie(movieId: String) {
        viewModelScope.launch {
            if (repository.deleteMovie(movieId)) {
                fetchMovies()
            }
        }
    }

    fun updateUserRole(userId: String, newRole: String) {
        viewModelScope.launch {
            if (userRepository.updateUserRole(userId, newRole)) {
                fetchUsers()
            }
        }
    }

    private val _saveStatus = MutableLiveData<SaveStatus>()
    val saveStatus: LiveData<SaveStatus> = _saveStatus

    sealed class SaveStatus {
        object Loading : SaveStatus()
        data class Success(val message: String) : SaveStatus()
        data class Error(val message: String) : SaveStatus()
    }

    fun saveMovieWithImages(
        context: Context,
        movie: Movie,
        posterUri: Uri?,
        bannerUri: Uri?
    ) {
        viewModelScope.launch {
            _saveStatus.value = SaveStatus.Loading
            try {
                // 1. Upload Poster if exists
                posterUri?.let {
                    val url = repository.uploadImage(context, it, "MoviePosters")
                    movie.posterUrl = url
                }

                // 2. Upload Banner if exists
                bannerUri?.let {
                    val url = repository.uploadImage(context, it, "MovieBanners")
                    movie.bannerUrl = url
                }

                // 3. Save to Firestore
                val success = repository.addMovie(movie)
                if (success) {
                    _saveStatus.value = SaveStatus.Success("Movie saved successfully")
                } else {
                    _saveStatus.value = SaveStatus.Error("Failed to save movie details to database")
                }
            } catch (e: Exception) {
                // This will catch "Object does not exist at location" or Rule violations
                _saveStatus.value = SaveStatus.Error(e.message ?: "An unknown error occurred during upload")
            }
        }
    }
}