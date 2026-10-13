package com.example.bookmyticket.repository

import com.example.bookmyticket.models.User
import com.google.firebase.firestore.FirebaseFirestore
import kotlinx.coroutines.tasks.await

class UserRepository {
    private val firestore = FirebaseFirestore.getInstance()
    private val usersCollection = firestore.collection("Users")

    suspend fun getAllUsers(): List<User> {
        return try {
            usersCollection.get().await().toObjects(User::class.java)
        } catch (e: Exception) {
            emptyList()
        }
    }

    suspend fun updateUserRole(userId: String, newRole: String): Boolean {
        return try {
            usersCollection.document(userId).update("role", newRole).await()
            true
        } catch (e: Exception) {
            false
        }
    }
}