package com.example.bookmyticket.repository

import com.example.bookmyticket.models.Theatre

object TheatreRepository {

    fun getStaticTheatres(): List<Theatre> {
        return listOf(
            // Surat, India Theatres
            Theatre("s1", "Inox: VR Mall", "Dumas Road, Surat", "", listOf("10:00 AM", "01:30 PM", "04:45 PM", "09:00 PM"), 21.1741, 72.7844),
            Theatre("s2", "Cinepolis: Imperial Square", "Adajan, Surat", "", listOf("11:15 AM", "02:45 PM", "06:00 PM", "10:30 PM"), 21.1764, 72.8021),
            Theatre("s3", "PVR: Rahul Raj Mall", "Piplod, Surat", "", listOf("09:30 AM", "12:45 PM", "04:00 PM", "08:15 PM"), 21.1718, 72.7865),
            Theatre("s4", "Rajhans Cinemas: Pal", "Pal, Surat", "", listOf("10:30 AM", "02:00 PM", "05:30 PM", "09:45 PM"), 21.1963, 72.7811),
            Theatre("s5", "Valentine Multiplex", "Dumas Road, Surat", "", listOf("11:00 AM", "03:00 PM", "07:00 PM", "10:00 PM"), 21.1550, 72.7668),
            
            // Bangalore Theatres
            Theatre("1", "PVR Cinemas", "Forum Mall, Bangalore", "", listOf("10:00 AM", "01:30 PM", "04:45 PM", "09:00 PM"), 12.9348, 77.6111),
            Theatre("2", "INOX", "Mantri Square, Bangalore", "", listOf("11:15 AM", "02:45 PM", "06:00 PM", "10:30 PM"), 12.9917, 77.5711),
            
            // International Theatres
            Theatre("5", "AMC Empire 25", "Times Square, New York", "", listOf("11:00 AM", "02:30 PM", "06:15 PM", "10:00 PM"), 40.7563, -73.9885),
            Theatre("7", "Odeon Luxe Leicester Square", "London, UK", "", listOf("10:30 AM", "02:00 PM", "05:45 PM", "09:30 PM"), 51.5105, -0.1302)
        )
    }
}