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

    private val bookingsUpperCollection = firestore.collection("Bookings")
    private val bookingsLowerCollection = firestore.collection("bookings")

    fun getBookingsFlow(): Flow<List<Booking>> = callbackFlow {
        val bookingsMap = mutableMapOf<String, Booking>()

        fun emitMergedList() {
            trySend(bookingsMap.values.toList().sortedByDescending { it.createdAt })
        }

        val listenerMain = bookingsCollection.addSnapshotListener { snapshot, error ->
            if (error == null) {
                snapshot?.documents?.forEach { doc ->
                    try {
                        doc.toObject(Booking::class.java)?.apply { 
                            this.docId = doc.id
                            this.collectionName = "bookingticket_app_web"
                            if (bookingId.isEmpty()) bookingId = doc.id
                            bookingsMap[doc.id] = this
                        }
                    } catch (e: Exception) {}
                }
                emitMergedList()
            }
        }
        val listenerUpper = bookingsUpperCollection.addSnapshotListener { snapshot, error ->
            if (error == null) {
                snapshot?.documents?.forEach { doc ->
                    try {
                        doc.toObject(Booking::class.java)?.apply { 
                            this.docId = doc.id
                            this.collectionName = "Bookings"
                            if (bookingId.isEmpty()) bookingId = doc.id
                            bookingsMap[doc.id] = this
                        }
                    } catch (e: Exception) {}
                }
                emitMergedList()
            }
        }
        val listenerLower = bookingsLowerCollection.addSnapshotListener { snapshot, error ->
            if (error == null) {
                snapshot?.documents?.forEach { doc ->
                    try {
                        doc.toObject(Booking::class.java)?.apply { 
                            this.docId = doc.id
                            this.collectionName = "bookings"
                            if (bookingId.isEmpty()) bookingId = doc.id
                            bookingsMap[doc.id] = this
                        }
                    } catch (e: Exception) {}
                }
                emitMergedList()
            }
        }

        awaitClose { 
            listenerMain.remove()
            listenerUpper.remove()
            listenerLower.remove()
        }
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

    suspend fun updateBookingStatus(booking: Booking? = null, bookingId: String? = null, status: String): Boolean {
        return try {
            if (booking != null && booking.docId.isNotEmpty()) {
                firestore.collection(booking.collectionName).document(booking.docId)
                    .update("status", status).await()
                return true
            }

            val idToSearch = bookingId ?: booking?.bookingId ?: return false
            val collections = listOf("bookingticket_app_web", "Bookings", "bookings")
            
            for (collName in collections) {
                // Try App-specific field
                val queryApp = firestore.collection(collName)
                    .whereEqualTo("Booking ID", idToSearch).get().await()
                if (!queryApp.isEmpty) {
                    val docId = queryApp.documents[0].id
                    firestore.collection(collName).document(docId).update("status", status).await()
                    return true
                }

                // Try Web-specific field
                val queryWeb = firestore.collection(collName)
                    .whereEqualTo("bookingId", idToSearch).get().await()
                if (!queryWeb.isEmpty) {
                    val docId = queryWeb.documents[0].id
                    firestore.collection(collName).document(docId).update("status", status).await()
                    return true
                }
                
                // Also try document ID direct match
                try {
                    val doc = firestore.collection(collName).document(idToSearch).get().await()
                    if (doc.exists()) {
                        firestore.collection(collName).document(idToSearch).update("status", status).await()
                        return true
                    }
                } catch (e: Exception) {}
            }
            false
        } catch (e: Exception) {
            false
        }
    }

    suspend fun getUsersBookings(userId: String): List<Booking> {
        val allResults = mutableListOf<Booking>()
        val collections = listOf("bookingticket_app_web", "Bookings", "bookings")
        
        for (collName in collections) {
            try {
                val snapshot = firestore.collection(collName)
                    .whereEqualTo("userId", userId)
                    .get()
                    .await()
                
                snapshot.documents.forEach { doc ->
                    doc.toObject(Booking::class.java)?.apply {
                        this.docId = doc.id
                        this.collectionName = collName
                        if (bookingId.isEmpty()) bookingId = doc.id
                        allResults.add(this)
                    }
                }
            } catch (e: Exception) {
                android.util.Log.e("BookingRepository", "Error fetching from $collName: ${e.message}")
            }
        }
        return allResults.sortedByDescending { it.createdAt }
    }
}