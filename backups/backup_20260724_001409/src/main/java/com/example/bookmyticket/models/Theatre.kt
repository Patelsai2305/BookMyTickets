package com.example.bookmyticket.models

import android.os.Parcelable
import kotlinx.parcelize.Parcelize

@Parcelize
data class Theatre(
    val id: String = "",
    val name: String = "",
    val location: String = "",
    val distance: String = "",
    val showTimings: List<String> = emptyList(),
    val latitude: Double = 0.0,
    val longitude: Double = 0.0
) : Parcelable