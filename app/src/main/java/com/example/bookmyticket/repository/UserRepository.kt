package com.example.bookmyticket.repository

import com.example.bookmyticket.models.User
import com.google.firebase.firestore.FirebaseFirestore
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.callbackFlow
import kotlinx.coroutines.tasks.await

class UserRepository {
    private val firestore = FirebaseFirestore.getInstance()
    private val usersCollection = firestore.collection("Users")

    private val usersLowerCollection = firestore.collection("users")

    fun getUsersFlow(): Flow<List<User>> = callbackFlow {
        val usersMap = mutableMapOf<String, User>()

        fun emitMergedList() {
            trySend(usersMap.values.toList())
        }

        val listenerUpper = usersCollection.addSnapshotListener { snapshot, error ->
            if (error == null) {
                snapshot?.documents?.forEach { doc ->
                    try {
                        doc.toObject(User::class.java)?.apply { 
                            uid = doc.id
                            usersMap[uid] = this
                        }
                    } catch (e: Exception) {}
                }
                emitMergedList()
            }
        }
        val listenerLower = usersLowerCollection.addSnapshotListener { snapshot, error ->
            if (error == null) {
                snapshot?.documents?.forEach { doc ->
                    try {
                        doc.toObject(User::class.java)?.apply { 
                            uid = doc.id
                            usersMap[uid] = this
                        }
                    } catch (e: Exception) {}
                }
                emitMergedList()
            }
        }
        awaitClose { 
            listenerUpper.remove()
            listenerLower.remove()
        }
    }

    suspend fun getAllUsers(): List<User> {
        return try {
            val upper = usersCollection.get().await().toObjects(User::class.java)
            val lower = usersLowerCollection.get().await().toObjects(User::class.java)
            (upper + lower).distinctBy { it.uid }
        } catch (e: Exception) {
            emptyList()
        }
    }

    suspend fun updateUserRole(userId: String, newRole: String): Boolean {
        return try {
            val isAdmin = newRole == "admin"
            val updates = mapOf(
                "role" to newRole,
                "isAdmin" to isAdmin
            )
            
            // Update across both collections for full sync
            usersCollection.document(userId).update(updates).await()
            try {
                usersLowerCollection.document(userId).update(updates).await()
            } catch (e: Exception) {} // Might not exist in lower
            
            true
        } catch (e: Exception) {
            false
        }
    }
}