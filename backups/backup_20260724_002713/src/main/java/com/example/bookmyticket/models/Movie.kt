package com.example.bookmyticket.models

import android.os.Parcelable
import com.google.gson.annotations.SerializedName
import kotlinx.parcelize.Parcelize

@Parcelize
data class Movie(
    var id: String = "",

    @SerializedName("title")
    var title: String = "",

    @SerializedName("overview")
    var description: String = "", // Aligned with Firestore "description"

    @SerializedName("poster_path")
    var posterUrl: String = "",

    @SerializedName("backdrop_path")
    var bannerUrl: String = "",

    @SerializedName("vote_average")
    var rating: Double = 0.0,     // Aligned with Firestore "rating" (number)

    @SerializedName("release_date")
    var releaseDate: String = "",

    var genre: String = "",
    var trailerUrl: String = "",
    var cast: String = "",
    var director: String = "",
    var producer: String = "",
    var status: String = "",

    @SerializedName("runtime")
    var duration: Int = 0,        // Aligned with Firestore "duration" (number)

    @SerializedName("original_language")
    var language: String = "",

    var createdAt: Long = System.currentTimeMillis()
) : Parcelable
