package com.example.bookmyticket.models

import android.os.Parcelable
import kotlinx.parcelize.Parcelize

@Parcelize
data class Movie(
    var id: String = "",
    var title: String = "",
    var description: String = "", // Aligned with Firestore "description"
    var posterUrl: String = "",
    var bannerUrl: String = "",
    var rating: Double = 0.0,     // Aligned with Firestore "rating" (number)
    var releaseDate: String = "",
    var genre: String = "",
    var trailerUrl: String = "",
    var cast: String = "",
    var director: String = "",
    var producer: String = "",
    var status: String = "",
    var duration: Int = 0,        // Aligned with Firestore "duration" (number)
    var language: String = "",
    var createdAt: Long = System.currentTimeMillis()
) : Parcelable