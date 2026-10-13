package com.example.bookmyticket

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.example.bookmyticket.databinding.ActivityPaymentBinding
import com.example.bookmyticket.models.Booking
import com.example.bookmyticket.utils.SystemUtils
import com.example.bookmyticket.utils.SeatLock
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.WriteBatch
import java.util.UUID

class PaymentActivity : AppCompatActivity() {

    private lateinit var binding: ActivityPaymentBinding
    private val db = FirebaseFirestore.getInstance()
    private val auth = FirebaseAuth.getInstance()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        SystemUtils.setImmersiveMode(this)
        binding = ActivityPaymentBinding.inflate(layoutInflater)
        setContentView(binding.root)

        val booking = intent.getParcelableExtra<Booking>("booking") ?: return

        setupUI(booking)

        binding.toolbar.setNavigationOnClickListener { finish() }
        binding.btnPay.setOnClickListener {
            processPayment(booking)
        }
    }

    private fun setupUI(booking: Booking) {
        binding.tvMovieTitle.text = booking.movieTitle
        binding.tvTheatreInfo.text = "${booking.theatreName} | ${booking.date} | ${booking.timing}"
        binding.tvSeats.text = booking.seats.joinToString()
        binding.tvTotalPrice.text = "₹ ${booking.totalPrice}"
    }

    private fun processPayment(booking: Booking) {
        binding.progressBar.visibility = View.VISIBLE
        binding.btnPay.isEnabled = false

        val charPool : List<Char> = ('A'..'Z') + ('0'..'9')
        val randomString = (1..5)
            .map { i -> kotlin.random.Random.nextInt(0, charPool.size) }
            .map(charPool::get)
            .joinToString("")

        val bookingId = "BMT$randomString"

        // Compute the same showKey used by the website's seatLocks logic
        val showKey = SeatLock.buildShowKey(
            movieId = booking.movieId,
            movieTitle = booking.movieTitle,
            theatreId = booking.theatreId,
            theatreName = booking.theatreName,
            date = booking.date,
            time = booking.timing
        )

        val finalBooking = booking.copy(
            bookingId = bookingId,
            userEmail = auth.currentUser?.email ?: "guest@example.com",
            showId = showKey, // Use showKey for cross-platform sync
            showDate = booking.date,
            showTime = booking.timing,
            status = "Upcoming",
            paymentStatus = "PAID",
            createdAt = System.currentTimeMillis(),
            selectedSeatsAlias = booking.seats
        )

        // Conflict Check: Verify no one else took the seats while we were on this page
        val currentUserId = auth.currentUser?.uid
        db.collection("seatLocks")
            .whereEqualTo("showKey", showKey)
            .get()
            .addOnSuccessListener { snapshot ->
                val allBookedSeats = mutableSetOf<String>()
                for (doc in snapshot.documents) {
                    val seatId = doc.getString("seatId")
                    val userId = doc.getString("userId")
                    // Ignore locks that belong to the current user
                    if (seatId != null && userId != currentUserId) {
                        allBookedSeats.add(seatId)
                    }
                }

                val conflicts = booking.seats.filter { it in allBookedSeats }
                if (conflicts.isNotEmpty()) {
                    binding.progressBar.visibility = View.GONE
                    binding.btnPay.isEnabled = true
                    Toast.makeText(this, "Seats ${conflicts.joinToString()} were just booked by someone else!", Toast.LENGTH_LONG).show()
                } else {
                    // No conflicts, save booking and locks atomically
                    saveBookingAndLocks(finalBooking, showKey)
                }
            }
            .addOnFailureListener { e ->
                binding.progressBar.visibility = View.GONE
                binding.btnPay.isEnabled = true
                Toast.makeText(this, "Failed to verify availability: ${e.message}", Toast.LENGTH_SHORT).show()
            }
    }

    private fun saveBookingAndLocks(booking: Booking, showKey: String) {
        val batch = db.batch()

        // 1. Create the Booking record in bookingticket_app_web
        val bookingRef = db.collection("bookingticket_app_web").document(booking.bookingId)
        batch.set(bookingRef, booking)

        // 2. Create a lock document for each selected seat
        booking.seats.forEach { seatId ->
            val lockId = SeatLock.buildSeatLockId(showKey, seatId)
            val lockRef = db.collection("seatLocks").document(lockId)
            batch.set(lockRef, mapOf(
                "showKey" to showKey,
                "seatId" to seatId,
                "userId" to auth.currentUser?.uid,
                "bookingId" to booking.bookingId,
                "status" to "booked", // Mark as permanently booked
                "lockedAt" to System.currentTimeMillis()
            ))
        }

        batch.commit()
            .addOnSuccessListener {
                binding.progressBar.visibility = View.GONE
                val intent = Intent(this, BookingConfirmationActivity::class.java)
                intent.putExtra("booking", booking)
                startActivity(intent)
                finish()
            }
            .addOnFailureListener { e ->
                binding.progressBar.visibility = View.GONE
                binding.btnPay.isEnabled = true
                Toast.makeText(this, "Booking failed: ${e.message}", Toast.LENGTH_SHORT).show()
            }
    }
}
