package com.example.bookmyticket.models

import android.os.Parcelable
import com.google.firebase.firestore.Exclude
import com.google.firebase.firestore.ServerTimestamp
import kotlinx.parcelize.Parcelize
import java.util.Date

@Parcelize
data class User(
    var uid: String = "",
    var fullName: String = "",
    var email: String = "",
    var phone: String = "",
    var role: String = "user", // "admin" or "user"
    
    var isAdmin: Boolean = false, // Added for Web Sync
    
    var profileImage: String = "",
    var nationality: String = "",
    
    var createdAt: Long = System.currentTimeMillis(),
    
    @ServerTimestamp
    var registeredAt: Date? = null // Server timestamp for Web
) : Parcelable {
    
    @Exclude
    fun updateAdminStatus() {
        isAdmin = (role == "admin")
    }
}
