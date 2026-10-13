package com.example.bookmyticket.repository

import com.example.bookmyticket.models.Booking
import com.google.firebase.firestore.FirebaseFirestore
import kotlinx.coroutines.tasks.await

class BookingRepository {
    private val firestore = FirebaseFirestore.getInstance()
    private val bookingsCollection = firestore.collection("Bookings")

    suspend fun getAllBookings(): List<Booking> {
        return try {
            bookingsCollection.orderBy("createdAt", com.google.firebase.firestore.Query.Direction.DESCENDING)
                .get()
                .await()
                .toObjects(Booking::class.java)
        } catch (e: Exception) {
            bookingsCollection.get().await().toObjects(Booking::class.java)
        }
    }

    suspend fun updateBookingStatus(bookingId: String, status: String): Boolean {
        return try {
            bookingsCollection.document(bookingId).update("status", status).await()
            true
        } catch (e: Exception) {
            false
        }
    }
}