package com.example.bookmyticket.models

data class Seat(
    val name: String,
    val row: String,
    val column: Int,
    var isSelected: Boolean = false,
    var isBooked: Boolean = false,
    var type: String = "Normal", // VIP, Premium, Normal
    var price: Int = 200
)