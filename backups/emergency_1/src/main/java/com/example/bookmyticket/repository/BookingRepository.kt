package com.example.bookmyticket.repository

import com.example.bookmyticket.models.Booking
import com.google.firebase.firestore.FirebaseFirestore
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.callbackFlow
import kotlinx.coroutines.tasks.await

class BookingRepository {
    private val firestore = FirebaseFirestore.getInstance()
    private val bookingsCollection = firestore.collection("bookingticket_app_web")

    fun getBookingsFlow(): Flow<List<Booking>> = callbackFlow {
        val listener = bookingsCollection.addSnapshotListener { snapshot, error ->
            if (error != null) {
                close(error)
                return@addSnapshotListener
            }
            val bookings = snapshot?.toObjects(Booking::class.java) ?: emptyList()
            trySend(bookings)
        }
        awaitClose { listener.remove() }
    }

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
            val querySnapshot = bookingsCollection.whereEqualTo("bookingId", bookingId).get().await()
            if (!querySnapshot.isEmpty) {
                val docId = querySnapshot.documents[0].id
                bookingsCollection.document(docId).update("status", status).await()
                true
            } else {
                false
            }
        } catch (e: Exception) {
            false
        }
    }
}