package com.example.bookmyticket.viewmodel

import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.models.Theatre
import com.example.bookmyticket.repository.MovieRepository
import com.example.bookmyticket.repository.TheatreRepository
import kotlinx.coroutines.launch

class HomeViewModel : ViewModel() {

    private val repository = MovieRepository()

    val nowPlayingMovies = MutableLiveData<List<Movie>>()
    val upcomingMovies = MutableLiveData<List<Movie>>()
    val searchResults = MutableLiveData<List<Movie>>()
    val theatreResults = MutableLiveData<List<Theatre>>()
    val error = MutableLiveData<String>()

    fun loadMovies() {
        viewModelScope.launch {
            try {
                val nowPlaying = repository.getMoviesByStatus("Now Playing")
                val upcoming = repository.getMoviesByStatus("Upcoming")
                
                nowPlayingMovies.postValue(nowPlaying)
                upcomingMovies.postValue(upcoming)
            } catch (e: Exception) {
                error.postValue("Failed to fetch movies: ${e.message}")
            }
        }
    }

    fun searchMovies(query: String) {
        viewModelScope.launch {
            val movies = repository.searchMovies(query)
            searchResults.postValue(movies)
            
            val theatres = TheatreRepository.getStaticTheatres().filter { 
                it.name.contains(query, ignoreCase = true) || it.location.contains(query, ignoreCase = true)
            }
            theatreResults.postValue(theatres)
        }
    }
}