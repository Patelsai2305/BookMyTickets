package com.example.bookmyticket.models

import android.os.Parcelable
import com.google.firebase.firestore.Exclude
import com.google.firebase.firestore.PropertyName
import com.google.gson.annotations.SerializedName
import kotlinx.parcelize.Parcelize

@Parcelize
data class Movie(
    @Exclude
    @get:Exclude
    @Transient
    var id: String = "", // Firestore Doc ID

    @SerializedName("title")
    @get:PropertyName("title")
    @set:PropertyName("title")
    var title: String = "",

    @get:PropertyName("name")
    @set:PropertyName("name")
    var name: String = "", // Web compatibility

    @SerializedName("id")
    @get:PropertyName("tmdbId")
    @set:PropertyName("tmdbId")
    var tmdbId: Int = 0,

    @get:PropertyName("movieId")
    @set:PropertyName("movieId")
    var movieId: String = "", // Web compatibility

    @SerializedName("overview")
    @get:PropertyName("description")
    @set:PropertyName("description")
    var description: String = "",

    @SerializedName("poster_path")
    @get:PropertyName("posterUrl")
    @set:PropertyName("posterUrl")
    var posterUrl: String? = "", // Mapped from Retrofit (TMDB) AND Firestore

    @SerializedName("backdrop_path")
    @get:PropertyName("bannerUrl")
    @set:PropertyName("bannerUrl")
    var bannerUrl: String? = "",

    @SerializedName("vote_average")
    @get:PropertyName("rating")
    @set:PropertyName("rating")
    var rating: Double = 0.0,

    @SerializedName("release_date")
    @get:PropertyName("releaseDate")
    @set:PropertyName("releaseDate")
    var releaseDate: String = "",

    @get:PropertyName("genre")
    @set:PropertyName("genre")
    var genre: String = "",

    @get:PropertyName("trailerUrl")
    @set:PropertyName("trailerUrl")
    var trailerUrl: String = "",

    @get:PropertyName("cast")
    @set:PropertyName("cast")
    var cast: String = "",

    @get:PropertyName("director")
    @set:PropertyName("director")
    var director: String = "",

    @get:PropertyName("producer")
    @set:PropertyName("producer")
    var producer: String = "",
    
    @get:PropertyName("status")
    @set:PropertyName("status")
    var status: String = "", // "Now Playing" or "Upcoming"

    @get:PropertyName("category")
    @set:PropertyName("category")
    var category: String = "", // Web: "now_playing" or "upcoming"

    @get:PropertyName("upcoming")
    @set:PropertyName("upcoming")
    var upcoming: Boolean = false, // Web compatibility

    @SerializedName("runtime")
    @get:PropertyName("duration")
    @set:PropertyName("duration")
    var duration: Int = 0,

    @SerializedName("original_language")
    @get:PropertyName("language")
    @set:PropertyName("language")
    var language: String = "",

    @get:PropertyName("createdAt")
    @set:PropertyName("createdAt")
    var createdAt: Long = System.currentTimeMillis()
) : Parcelable {

    @Exclude
    fun getUniqueId(): String {
        return when {
            id.isNotEmpty() -> id
            tmdbId != 0 -> "tmdb_$tmdbId"
            else -> getDisplayTitle()
        }
    }

    @Exclude
    fun getDisplayTitle(): String {
        return when {
            title.isNotEmpty() -> title
            name.isNotEmpty() -> name
            else -> "Unknown Movie"
        }
    }

    @Exclude
    fun getEffectiveStatus(): String {
        return when {
            status.contains("Playing", ignoreCase = true) -> "Now Playing"
            category.contains("playing", ignoreCase = true) -> "Now Playing"
            status.contains("Upcoming", ignoreCase = true) -> "Upcoming"
            category.contains("upcoming", ignoreCase = true) -> "Upcoming"
            upcoming -> "Upcoming"
            else -> status
        }
    }
}
