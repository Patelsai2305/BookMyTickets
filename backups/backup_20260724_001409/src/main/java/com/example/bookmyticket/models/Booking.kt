package com.example.bookmyticket.models

import android.os.Parcelable
import kotlinx.parcelize.Parcelize

@Parcelize
data class Booking(
    val userId: String = "",
    val movieTitle: String = "",
    val theatreName: String = "",
    val timing: String = "",
    val seats: List<String> = emptyList(),
    val totalPrice: Int = 0,
    val bookingId: String = "",
    val moviePoster: String? = "",
    val status: String = "Upcoming", // Upcoming, Completed, Cancelled
    val createdAt: Long = System.currentTimeMillis()
) : Parcelable