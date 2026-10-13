package com.example.bookmyticket.models

import android.os.Parcelable
import com.google.firebase.firestore.Exclude
import com.google.firebase.firestore.PropertyName
import com.google.firebase.firestore.ServerTimestamp
import kotlinx.parcelize.Parcelize
import kotlinx.parcelize.RawValue

import java.util.Date

@Parcelize
data class User(
    var uid: String = "",

    @get:PropertyName("name")
    @set:PropertyName("name")
    var fullName: String = "",

    var email: String = "",
    var phone: String = "",
    var role: String = "user", // "admin" or "user"
    
    @get:PropertyName("isAdmin")
    @set:PropertyName("isAdmin")
    var isAdmin: Boolean = false, // Added for Web Sync
    
    var profileImage: String = "",
    var nationality: String = "",
    
    @get:PropertyName("created_at")
    @set:PropertyName("created_at")
    var createdAt: @RawValue Any? = System.currentTimeMillis(),
    
    @ServerTimestamp
    var registeredAt: Date? = null // Server timestamp for Web
) : Parcelable {

    @get:Exclude
    val createdAtLong: Long
        get() = when (val value = createdAt) {
            is Long -> value
            is Double -> value.toLong()
            is String -> value.toLongOrNull() ?: 0L
            else -> 0L
        }
    
    @Exclude
    fun isUserAdmin(): Boolean {
        if (role == "admin") return true
        if (isAdmin) return true
        
        // Handle string "true" case matching Firestore rules
        val isAdminStr = when (val value = isAdmin) {
            is String -> value
            else -> value.toString()
        }
        return isAdminStr.equals("true", ignoreCase = true)
    }

    @Exclude
    fun updateAdminStatus() {
        isAdmin = (role == "admin")
    }
}
