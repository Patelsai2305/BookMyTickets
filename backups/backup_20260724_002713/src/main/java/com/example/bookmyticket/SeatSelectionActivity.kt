package com.example.bookmyticket

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.GridLayoutManager
import com.example.bookmyticket.adapter.SeatAdapter
import com.example.bookmyticket.databinding.ActivitySeatSelectionBinding
import com.example.bookmyticket.models.Booking
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.models.Seat
import com.example.bookmyticket.utils.SystemUtils
import com.example.bookmyticket.models.Theatre
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore
import java.util.UUID

class SeatSelectionActivity : AppCompatActivity() {

    private lateinit var binding: ActivitySeatSelectionBinding
    private val selectedSeats = mutableListOf<Seat>()
    
    private val vipPrice = 450
    private val premiumPrice = 300
    private val normalPrice = 200

    private val db = FirebaseFirestore.getInstance()
    private val auth = FirebaseAuth.getInstance()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        SystemUtils.setImmersiveMode(this)
        binding = ActivitySeatSelectionBinding.inflate(layoutInflater)
        setContentView(binding.root)

        val movie = intent.getParcelableExtra<Movie>("movie")
        val theatre = intent.getParcelableExtra<Theatre>("theatre")
        val timing = intent.getStringExtra("timing")

        binding.toolbar.setNavigationOnClickListener { finish() }
        binding.tvMovieTitle.text = movie?.title
        binding.tvTheatreInfo.text = "${theatre?.name} | $timing"

        setupSeats()

        binding.btnConfirmBooking.setOnClickListener {
            if (selectedSeats.isEmpty()) {
                Toast.makeText(this, "Please select at least one seat", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            confirmBooking(movie, theatre, timing)
        }
    }

    private fun setupSeats() {
        val seats = mutableListOf<Seat>()
        
        // VIP Seats (Rows A-B)
        for (row in listOf("A", "B")) {
            for (i in 1..8) {
                seats.add(Seat("$row$i", row, i, isBooked = (Math.random() > 0.8), type = "VIP", price = vipPrice))
            }
        }
        
        // Premium Seats (Rows C-D)
        for (row in listOf("C", "D")) {
            for (i in 1..8) {
                seats.add(Seat("$row$i", row, i, isBooked = (Math.random() > 0.8), type = "Premium", price = premiumPrice))
            }
        }
        
        // Normal Seats (Rows E-F)
        for (row in listOf("E", "F")) {
            for (i in 1..8) {
                seats.add(Seat("$row$i", row, i, isBooked = (Math.random() > 0.8), type = "Normal", price = normalPrice))
            }
        }

        binding.rvSeats.layoutManager = GridLayoutManager(this, 8)
        binding.rvSeats.adapter = SeatAdapter(seats) { seat ->
            if (seat.isBooked) return@SeatAdapter
            
            seat.isSelected = !seat.isSelected
            if (seat.isSelected) {
                selectedSeats.add(seat)
            } else {
                selectedSeats.remove(seat)
            }
            updateBottomBar()
            binding.rvSeats.adapter?.notifyDataSetChanged()
        }
    }

    private fun updateBottomBar() {
        val totalPrice = selectedSeats.sumOf { it.price }
        binding.tvSelectedSeats.text = "${selectedSeats.size} Seats: ${selectedSeats.joinToString { it.name }}"
        binding.tvTotalPrice.text = "₹ $totalPrice"
    }

    private fun confirmBooking(movie: Movie?, theatre: Theatre?, timing: String?) {
        val bookingId = UUID.randomUUID().toString().substring(0, 8).uppercase()
        val userId = auth.currentUser?.uid ?: "guest"
        val totalPrice = selectedSeats.sumOf { it.price }

        val booking = Booking(
            userId = userId,
            movieTitle = movie?.title ?: "Unknown Movie",
            theatreName = theatre?.name ?: "Unknown Theatre",
            timing = timing ?: "",
            seats = selectedSeats.map { it.name },
            totalPrice = totalPrice,
            bookingId = bookingId,
            moviePoster = movie?.posterUrl,
            status = "Upcoming"
        )

        db.collection("Bookings")
            .document(bookingId)
            .set(booking)
            .addOnSuccessListener {
                val intent = Intent(this, BookingConfirmationActivity::class.java)
                intent.putExtra("booking", booking)
                startActivity(intent)
                finish()
            }
            .addOnFailureListener {
                Toast.makeText(this, "Booking failed: ${it.message}", Toast.LENGTH_SHORT).show()
            }
    }
}