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
import com.example.bookmyticket.utils.SeatLock
import com.example.bookmyticket.utils.SystemUtils
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore

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

    // Deterministic cross-platform key for this exact showtime — computed the
    // same way as the website's showKey and used to query /seatLocks.
    private var showKey: String = ""

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

        // Prefer the web-compatible movieId field (kept in sync by the admin
        // panel), then the TMDB numeric ID, then fall back to the raw
        // Firestore doc ID. See the note in PaymentActivity/README about
        // movieId not always matching web's TMDB ID today.
        val movieIdForKey = movie?.movieId?.takeIf { it.isNotBlank() }
            ?: movie?.tmdbId?.takeIf { it != 0 }?.toString()
            ?: movie?.id

        showKey = SeatLock.buildShowKey(
            movieId = movieIdForKey,
            movieTitle = movie?.title,
            theatreId = theatre?.id,
            theatreName = theatre?.name,
            date = dbDate,
            time = timing
        )
        android.util.Log.d("SeatSelection", "Show key: $showKey")

        setupSeatGrid()
        startRealTimeListener()

        binding.btnConfirmBooking.setOnClickListener {
            if (selectedSeats.isEmpty()) {
                Toast.makeText(this, "Please select at least one seat", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            goToPayment(movie, theatre, timing, dbDate, movieIdForKey)
        }
    }

    private fun getSeatType(row: String): String {
        return when (row) {
            "A", "B" -> "VIP"
            "C", "D" -> "Premium"
            else -> "Normal"
        }
    }

    private fun getSeatPrice(type: String): Int {
        return when (type) {
            "VIP" -> vipPrice
            "Premium" -> premiumPrice
            else -> normalPrice
        }
    }

    private fun startRealTimeListener() {
        binding.progressBar.visibility = View.VISIBLE
        val currentUserId = auth.currentUser?.uid

        db.collection("seatLocks")
            .whereEqualTo("showKey", showKey)
            .addSnapshotListener { snapshot, error ->
                binding.progressBar.visibility = View.GONE
                if (error != null) {
                    android.util.Log.e("SeatSelection", "Seat lock sync error.", error)
                    return@addSnapshotListener
                }

                val documents = snapshot?.documents ?: emptyList()
                
                // Partition locks into "mine" and "others"
                val myLocks = documents.filter { it.getString("userId") == currentUserId }
                val otherLocks = documents.filter { it.getString("userId") != currentUserId }

                // 1. Update booked seats (taken by others)
                bookedSeatIds.clear()
                bookedSeatIds.addAll(otherLocks.mapNotNull { it.getString("seatId") })

                // 2. Sync local selection with Firebase (enables multi-device sync)
                val cloudSelectedIds = myLocks.mapNotNull { it.getString("seatId") }.toSet()
                
                // Remove local selections that are no longer in the cloud
                selectedSeats.removeAll { it.name !in cloudSelectedIds }
                
                // Add cloud selections that aren't in local list yet
                cloudSelectedIds.forEach { seatId ->
                    if (selectedSeats.none { it.name == seatId }) {
                        val row = seatId.take(1)
                        val type = getSeatType(row)
                        val price = getSeatPrice(type)
                        selectedSeats.add(Seat(
                            name = seatId, 
                            row = row, 
                            column = seatId.drop(1).toIntOrNull() ?: 0, 
                            isSelected = true,
                            type = type,
                            price = price
                        ))
                    }
                }

                // 3. Handle conflicts: if a seat we think we have is now booked by someone else
                val conflicts = selectedSeats.filter { it.name in bookedSeatIds }
                if (conflicts.isNotEmpty()) {
                    selectedSeats.removeAll(conflicts)
                    conflicts.forEach { SeatLock.releaseTempLock(currentUserId ?: "", showKey, it.name) }
                    Toast.makeText(this, "Some selected seats were just taken by others.", Toast.LENGTH_SHORT).show()
                }

                updateBottomBar()
                setupSeatGrid()
            }
    }

    private fun setupSeatGrid() {
        allSeats.clear()

        val rows = listOf("A", "B", "C", "D", "E", "F")
        for (row in rows) {
            for (col in 1..8) {
                val seatId = "$row$col"
                val type = getSeatType(row)
                val price = getSeatPrice(type)

                val isSelected = selectedSeats.any { it.name == seatId }
                val isBooked = bookedSeatIds.contains(seatId)

                allSeats.add(Seat(seatId, row, col, isSelected = isSelected, isBooked = isBooked, type = type, price = price))
            }
        }

        binding.rvSeats.layoutManager = GridLayoutManager(this, 8)
        binding.rvSeats.isNestedScrollingEnabled = false
        binding.rvSeats.adapter = SeatAdapter(allSeats) { seat ->
            if (seat.isBooked) return@SeatAdapter

            val userId = auth.currentUser?.uid ?: return@SeatAdapter

            seat.isSelected = !seat.isSelected
            if (seat.isSelected) {
                selectedSeats.add(seat)
                SeatLock.createTempLock(userId, showKey, seat.name)
            } else {
                selectedSeats.removeAll { it.name == seat.name }
                SeatLock.releaseTempLock(userId, showKey, seat.name)
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

    // Pay-then-Book: nothing is written to Firestore here. Seat locks and the
    // booking record are created together, atomically, only after a
    // successful payment in PaymentActivity — this prevents "ghost" locks
    // from someone who picks seats and then abandons checkout.
    private fun goToPayment(movie: Movie?, theatre: Theatre?, timing: String, date: String, movieIdForKey: String?) {
        val user = auth.currentUser
        if (user == null) {
            Toast.makeText(this, "Please sign in to book tickets", Toast.LENGTH_LONG).show()
            startActivity(Intent(this, LoginActivity::class.java))
            return
        }

        val booking = Booking(
            userId = user.uid,
            userEmail = user.email ?: "",
            movieId = movieIdForKey ?: (movie?.id ?: "movie"),
            movieTitle = movie?.title ?: "Movie",
            theatreId = theatre?.id ?: "theatre",
            theatreName = theatre?.name ?: "Theatre",
            date = date,
            timing = timing,
            seats = selectedSeats.map { it.name },
            totalPrice = selectedSeats.sumOf { it.price }.toDouble(),
            moviePoster = movie?.posterUrl ?: "",
            movieDuration = movie?.duration ?: 0,
            status = "CONFIRMED",
            showId = showKey
        )

        val intent = Intent(this, PaymentActivity::class.java)
        intent.putExtra("booking", booking)
        startActivity(intent)
    }
}
