package com.example.bookmyticket.models

import android.os.Parcelable
import com.google.firebase.firestore.PropertyName
import com.google.firebase.firestore.ServerTimestamp
import kotlinx.parcelize.Parcelize
import java.util.Date

@Parcelize
data class Booking(
    var bookingId: String = "",
    var userId: String = "",
    var userEmail: String = "", // Added for Web Sync
    
    var movieId: String = "",
    var movieTitle: String = "",
    var moviePoster: String? = "",
    var movieDuration: Int = 150,
    
    var theatreId: String = "",
    
    @get:PropertyName("theater")
    @set:PropertyName("theater")
    var theatreName: String = "",
    
    @get:PropertyName("showtimeId")
    @set:PropertyName("showtimeId")
    var showId: String = "", // Unified with Web's showtimeId
    
    var date: String = "", // YYYY-MM-DD
    var showDate: String = "", // Web Fallback (same as date)
    
    var timing: String = "",
    var showTime: String = "", // Web Fallback (same as timing)
    
    var seats: List<String> = emptyList(),

    @get:PropertyName("selectedSeats")
    @set:PropertyName("selectedSeats")
    var selectedSeatsAlias: List<String>? = null, // Alias for Web compatibility

    @get:PropertyName("total")
    @set:PropertyName("total")
    var totalPrice: Int = 0,
    
    var status: String = "Upcoming", // "Upcoming" or "Cancelled"
    var paymentStatus: String = "PAID", // Web compatibility
    
    var createdAt: Long = System.currentTimeMillis(),
    
    @ServerTimestamp
    var bookedAt: Date? = null // Real-time server timestamp for Web
) : Parcelable
