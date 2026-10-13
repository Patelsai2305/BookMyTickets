package com.example.bookmyticket.network

import com.example.bookmyticket.models.MovieResponse
import retrofit2.Response
import retrofit2.http.GET
import retrofit2.http.Query

interface MovieApi {

        @GET("movie/now_playing")
        suspend fun getNowPlayingMovies(
            @Query("api_key") apiKey: String,
            @Query("region") region: String? = null,
            @Query("language") language: String? = "en-US",
            @Query("page") page: Int = 1
        ): Response<MovieResponse>

        @GET("movie/popular")
        suspend fun getPopularMovies(
            @Query("api_key") apiKey: String,
            @Query("region") region: String? = null,
            @Query("language") language: String? = "en-US",
            @Query("page") page: Int = 1
        ): Response<MovieResponse>

        @GET("movie/upcoming")
        suspend fun getUpcomingMovies(
            @Query("api_key") apiKey: String,
            @Query("region") region: String? = null,
            @Query("language") language: String? = "en-US",
            @Query("page") page: Int = 1
        ): Response<MovieResponse>

        @GET("movie/top_rated")
        suspend fun getTopRatedMovies(
            @Query("api_key") apiKey: String,
            @Query("language") language: String = "en-US",
            @Query("page") page: Int = 1
        ): Response<MovieResponse>

        @GET("search/movie")
        suspend fun searchMovies(
            @Query("api_key") apiKey: String,
            @Query("query") query: String,
            @Query("page") page: Int = 1
        ): Response<MovieResponse>
    }
