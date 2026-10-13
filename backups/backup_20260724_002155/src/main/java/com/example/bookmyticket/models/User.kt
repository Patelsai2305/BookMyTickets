package com.example.bookmyticket.models

data class User(

    var uid: String = "",

    var fullName: String = "",

    var email: String = "",

    var phone: String = "",

    var role: String = "user",

    var profileImage: String = "",

    var nationality: String = "",

    var createdAt: Long = System.currentTimeMillis()

)