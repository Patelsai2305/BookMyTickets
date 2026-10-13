package com.example.bookmyticket.viewmodel

import android.content.Context
import android.net.Uri
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.bookmyticket.models.Booking
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.models.User
import com.example.bookmyticket.repository.BookingRepository
import com.example.bookmyticket.repository.MovieRepository
import com.example.bookmyticket.repository.UserRepository
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import kotlinx.coroutines.launch

class AdminViewModel : ViewModel() {

    private val repository = MovieRepository()
    private val userRepository = UserRepository()
    private val bookingRepository = BookingRepository()

    private val _movies = MutableLiveData<List<Movie>>()
    val movies: LiveData<List<Movie>> = _movies

    private val _users = MutableLiveData<List<User>>()
    val users: LiveData<List<User>> = _users

    private val _bookings = MutableLiveData<List<Booking>>()
    val bookings: LiveData<List<Booking>> = _bookings

    private val _stats = MutableLiveData<AdminStats>()
    val stats: LiveData<AdminStats> = _stats

    private val _error = MutableLiveData<String>()
    val error: LiveData<String> = _error

    private val _tmdbSearchResults = MutableLiveData<List<Movie>>()
    val tmdbSearchResults: LiveData<List<Movie>> = _tmdbSearchResults

    data class AdminStats(
        val totalUsers: Int = 0,
        val totalMovies: Int = 0,
        val totalBookings: Int = 0,
        val totalRevenue: Int = 0,
        val totalRefunds: Int = 0,
        val netEarnings: Int = 0
    )

    init {
        startListening()
    }

    private fun startListening() {
        combine(
            userRepository.getUsersFlow(),
            repository.getMoviesFlow(),
            bookingRepository.getBookingsFlow()
        ) { users, movies, bookings ->
            _users.postValue(users)
            _movies.postValue(movies.sortedByDescending { it.createdAt })
            _bookings.postValue(bookings.sortedByDescending { it.createdAt })

            val activeBookings = bookings.filter { it.status != "Cancelled" }
            val cancelledBookings = bookings.filter { it.status == "Cancelled" }

            val revenue = activeBookings.sumOf { it.totalPrice }
            val refunds = cancelledBookings.sumOf { it.totalPrice }

            _stats.postValue(AdminStats(
                totalUsers = users.size,
                totalMovies = movies.size,
                totalBookings = bookings.size,
                totalRevenue = revenue,
                totalRefunds = refunds,
                netEarnings = revenue - refunds
            ))
        }.catch { e ->
            android.util.Log.e("AdminViewModel", "Real-time listener error: ${e.message}", e)
            if (e.message?.contains("PERMISSION_DENIED", ignoreCase = true) == true) {
                _error.postValue("Access denied: You might not have admin permissions.")
            } else {
                _error.postValue("Listener error: ${e.localizedMessage}")
            }
        }.launchIn(viewModelScope)
    }

    fun fetchStats() {
        // No-op: Stats are now real-time
    }

    fun fetchMovies() {
        // No-op: Movies are now real-time
    }

    fun fetchUsers() {
        // No-op: Users are now real-time
    }

    fun fetchBookings() {
        // No-op: Bookings are now real-time
    }

    fun searchTMDB(query: String) {
        viewModelScope.launch {
            _tmdbSearchResults.value = repository.searchMoviesFromTMDB(query)
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
                    _saveStatus.value = SaveStatus.Error("Failed to save movie details to Firestore. Check internet or permissions.")
                }
            } catch (e: Exception) {
                android.util.Log.e("AdminViewModel", "Upload/Save Error: ${e.message}", e)
                _saveStatus.value = SaveStatus.Error("Upload failed: ${e.message}")
            }
        }
    }
}
