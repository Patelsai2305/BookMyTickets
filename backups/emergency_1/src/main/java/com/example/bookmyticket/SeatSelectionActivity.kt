package com.example.bookmyticket

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.GridLayoutManager
import com.example.bookmyticket.adapter.SeatAdapter
import com.example.bookmyticket.databinding.ActivitySeatSelectionBinding
import com.example.bookmyticket.models.Booking
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.models.Seat
import com.example.bookmyticket.models.Theatre
import com.example.bookmyticket.utils.SystemUtils
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FieldValue
import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.FirebaseFirestoreException

class SeatSelectionActivity : AppCompatActivity() {

    private lateinit var binding: ActivitySeatSelectionBinding
    private val allSeats = mutableListOf<Seat>()
    private val selectedSeats = mutableListOf<Seat>()
    private val bookedSeatIds = mutableSetOf<String>()
    
    private val vipPrice = 450
    private val premiumPrice = 300
    private val normalPrice = 200

    private val db = FirebaseFirestore.getInstance()
    private val auth = FirebaseAuth.getInstance()
    
    private var screeningId: String = ""

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        SystemUtils.setImmersiveMode(this)
        binding = ActivitySeatSelectionBinding.inflate(layoutInflater)
        setContentView(binding.root)

        val movie = intent.getParcelableExtra<Movie>("movie")
        val theatre = intent.getParcelableExtra<Theatre>("theatre")
        val timing = intent.getStringExtra("timing") ?: "17:30"
        val date = intent.getStringExtra("date") ?: "2026-08-29"

        binding.toolbar.setNavigationOnClickListener { finish() }
        binding.tvMovieTitle.text = movie?.title
        binding.tvTheatreInfo.text = "${theatre?.name} | $date | $timing"

        // Normalize date to YYYY-MM-DD for consistency
        val dbDate = try {
            if (date.contains("-")) date else {
                val inputFormat = java.text.SimpleDateFormat("dd MMM yyyy", java.util.Locale.getDefault())
                val outputFormat = java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.getDefault())
                outputFormat.format(inputFormat.parse(date)!!)
            }
        } catch (e: Exception) { date }

        // Universal ID Format Sync: movie-slug_date_time24h
        val movieSlug = (movie?.title ?: "movie")
            .lowercase()
            .replace(Regex("[^a-z0-9\\s]"), "") 
            .trim()
            .replace("\\s+".toRegex(), "-")
        
        val formattedDate = dbDate.replace("-", "")
        
        val formattedTime = try {
            val inputFormat = java.text.SimpleDateFormat("hh:mm a", java.util.Locale.getDefault())
            val outputFormat = java.text.SimpleDateFormat("HHmm", java.util.Locale.getDefault())
            val dateObj = inputFormat.parse(timing)
            outputFormat.format(dateObj!!)
        } catch (ignored: Exception) {
            timing.replace(":", "").replace(" ", "").lowercase()
        }

        screeningId = "${movieSlug}_${formattedDate}_${formattedTime}"
        android.util.Log.d("SeatSelection", "Generated Screening ID: $screeningId")

        // Initial setup to show seats immediately
        setupSeatGrid()
        startRealTimeListener()

        binding.btnConfirmBooking.setOnClickListener {
            if (selectedSeats.isEmpty()) {
                Toast.makeText(this, "Please select at least one seat", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            confirmBookingWithTransaction(movie, theatre, timing, dbDate)
        }
    }

    private fun startRealTimeListener() {
        binding.progressBar.visibility = View.VISIBLE
        
        // Use 'showtimeId' to match Web platform's key
        db.collection("bookingticket_app_web")
            .whereEqualTo("showtimeId", screeningId)
            .addSnapshotListener { snapshot, error ->
                binding.progressBar.visibility = View.GONE
                
                if (error != null) {
                    android.util.Log.e("SeatSelection", "Query failed.", error)
                    setupSeatGrid()
                    return@addSnapshotListener
                }

                bookedSeatIds.clear()
                if (snapshot != null) {
                    for (doc in snapshot.documents) {
                        val seats = doc.get("seats") as? List<String>
                        seats?.let { bookedSeatIds.addAll(it) }
                    }
                }
                
                // Deselect any seats that just got booked by someone else
                val newlyBooked = selectedSeats.filter { it.name in bookedSeatIds }
                if (newlyBooked.isNotEmpty()) {
                    selectedSeats.removeAll(newlyBooked)
                    Toast.makeText(this, "Some selected seats were just booked by another user.", Toast.LENGTH_LONG).show()
                    updateBottomBar()
                }

                setupSeatGrid()
            }
    }

    private fun setupSeatGrid() {
        allSeats.clear()
        
        val rows = listOf("A", "B", "C", "D", "E", "F")
        for (row in rows) {
            for (col in 1..8) {
                val seatId = "$row$col"
                val type = when (row) {
                    "A", "B" -> "VIP"
                    "C", "D" -> "Premium"
                    else -> "Normal"
                }
                val price = when (type) {
                    "VIP" -> vipPrice
                    "Premium" -> premiumPrice
                    else -> normalPrice
                }
                
                val isSelected = selectedSeats.any { it.name == seatId }
                val isBooked = bookedSeatIds.contains(seatId)
                
                allSeats.add(Seat(seatId, row, col, isSelected = isSelected, isBooked = isBooked, type = type, price = price))
            }
        }

        binding.rvSeats.layoutManager = GridLayoutManager(this, 8)
        binding.rvSeats.isNestedScrollingEnabled = false
        binding.rvSeats.adapter = SeatAdapter(allSeats) { seat ->
            if (seat.isBooked) return@SeatAdapter
            
            seat.isSelected = !seat.isSelected
            if (seat.isSelected) {
                selectedSeats.add(seat)
            } else {
                selectedSeats.removeAll { it.name == seat.name }
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

    private fun confirmBookingWithTransaction(movie: Movie?, theatre: Theatre?, timing: String, date: String) {
        val userId = auth.currentUser?.uid ?: "guest"
        val seatNames = selectedSeats.map { it.name }
        
        binding.progressBar.visibility = View.VISIBLE
        binding.btnConfirmBooking.isEnabled = false

        val screeningRef = db.collection("screenings").document(screeningId)

        db.runTransaction { transaction ->
            val snapshot = transaction.get(screeningRef)
            val currentBooked = snapshot.get("bookedSeats") as? List<String> ?: emptyList()
            
            // 1. Check for conflicts
            val conflicts = seatNames.filter { it in currentBooked }
            if (conflicts.isNotEmpty()) {
                throw FirebaseFirestoreException(
                    "Seats ${conflicts.joinToString()} are already booked.",
                    FirebaseFirestoreException.Code.ABORTED
                )
            }

            // 2. Update booked seats
            transaction.update(screeningRef, "bookedSeats", FieldValue.arrayUnion(*seatNames.toTypedArray()))
            
            null // Return null as we don't need a result
        }.addOnSuccessListener {
            // Transaction succeeded, now navigate to payment/success
            val booking = Booking(
                userId = userId,
                userEmail = auth.currentUser?.email ?: "",
                movieId = movie?.id ?: screeningId,
                movieTitle = movie?.title ?: "Movie",
                theatreId = theatre?.id ?: "theatre",
                theatreName = theatre?.name ?: "Theatre",
                date = date,
                timing = timing,
                seats = seatNames,
                totalPrice = selectedSeats.sumOf { it.price },
                moviePoster = movie?.posterUrl,
                status = "Upcoming",
                showId = screeningId
            )
            
            val intent = Intent(this, PaymentActivity::class.java)
            intent.putExtra("booking", booking)
            startActivity(intent)
            
            binding.progressBar.visibility = View.GONE
            binding.btnConfirmBooking.isEnabled = true
        }.addOnFailureListener { e ->
            binding.progressBar.visibility = View.GONE
            binding.btnConfirmBooking.isEnabled = true
            Toast.makeText(this, "Booking Failed: ${e.message}", Toast.LENGTH_LONG).show()
        }
    }
}