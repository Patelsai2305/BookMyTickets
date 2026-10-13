package com.example.bookmyticket.viewmodel

import androidx.lifecycle.*
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.repository.MovieRepository
import kotlinx.coroutines.launch

class MovieViewModel : ViewModel() {

    private val repository = MovieRepository()

    private val _movies = MutableLiveData<List<Movie>>()
    val movies: LiveData<List<Movie>> = _movies

    fun loadMovies() {
        viewModelScope.launch {
            val result = repository.getAllMovies()
            _movies.value = result
        }
    }
}