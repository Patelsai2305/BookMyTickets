package com.example.bookmyticket.models

import android.os.Parcelable
import com.google.firebase.firestore.Exclude
import com.google.firebase.firestore.PropertyName
import kotlinx.parcelize.Parcelize

@Parcelize
data class Booking(
    @get:PropertyName("Booking ID")
    @set:PropertyName("Booking ID")
    var bookingId: String = "",

    var userId: String = "",
    var uid: String = "",
    var userEmail: String = "",
    var userName: String = "",
    var movieId: String = "",
    var movieTitle: String = "",
    var moviePoster: String = "",
    var movieDuration: Int = 0,
    var theatreId: String = "",
    var theatreName: String = "",
    var showId: String = "",
    var timing: String = "",
    var date: String = "",
    
    @get:PropertyName("showDate")
    @set:PropertyName("showDate")
    var showDate: String = "",

    @get:PropertyName("showTime")
    @set:PropertyName("showTime")
    var showTime: String = "",

    var seats: List<String> = emptyList(),
    var selectedSeatsAlias: List<String> = emptyList(),
    var totalPrice: Double = 0.0,

    @get:PropertyName("status")
    @set:PropertyName("status")
    var status: String = "Confirmed",

    var paymentStatus: String = "PENDING",
    var createdAt: Long = 0L,

    @Exclude
    var docId: String = "",
    
    @Exclude
    var collectionName: String = "bookingticket_app_web"
) : Parcelable {

    @get:PropertyName("bookingId")
    @set:PropertyName("bookingId")
    var bookingIdWeb: String
        @Exclude get() = bookingId
        set(value) { if (bookingId.isEmpty()) bookingId = value }

    // Logic to ensure fields stay in sync during initialization/deserialization
    init {
        if (showDate.isEmpty()) showDate = date
        if (showTime.isEmpty()) showTime = timing
        if (date.isEmpty()) date = showDate
        if (timing.isEmpty()) timing = showTime
    }

    // Ensure status is always consistent (lowercase for web, title case for app)
    fun getNormalizedStatus(): String = status.replaceFirstChar { it.uppercase() }
}
