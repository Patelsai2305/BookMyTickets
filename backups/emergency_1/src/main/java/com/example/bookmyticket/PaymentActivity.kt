package com.example.bookmyticket

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.example.bookmyticket.databinding.ActivityPaymentBinding
import com.example.bookmyticket.models.Booking
import com.example.bookmyticket.utils.SystemUtils
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore
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
            .joinToString("");
        
        val bookingId = "BMT$randomString"
        
        // Universal ID Format Sync: movie-slug_date_time24h
        val movieSlug = (booking.movieTitle)
            .lowercase()
            .replace(Regex("[^a-z0-9\\s]"), "")
            .trim()
            .replace("\\s+".toRegex(), "-")
            
        val formattedDate = booking.date.replace("-", "")
        
        val formattedTime = try {
            val inputFormat = java.text.SimpleDateFormat("hh:mm a", java.util.Locale.getDefault())
            val outputFormat = java.text.SimpleDateFormat("HHmm", java.util.Locale.getDefault())
            val dateObj = inputFormat.parse(booking.timing)
            outputFormat.format(dateObj!!)
        } catch (e: Exception) {
            booking.timing.replace(":", "").replace(" ", "").lowercase()
        }

        val showtimeId = "${movieSlug}_${formattedDate}_${formattedTime}"

        val finalBooking = booking.copy(
            bookingId = bookingId,
            userEmail = auth.currentUser?.email ?: "guest@example.com",
            showId = showtimeId,
            showDate = booking.date,
            showTime = booking.timing,
            status = "Upcoming",
            paymentStatus = "PAID",
            createdAt = System.currentTimeMillis(),
            selectedSeatsAlias = booking.seats // Sync both fields for Web
        )

        // Conflict Check: Query using Web's 'showtimeId' key
        db.collection("bookingticket_app_web")
            .whereEqualTo("showtimeId", showtimeId)
            .get()
            .addOnSuccessListener { querySnapshot ->
                val allBookedSeats = mutableSetOf<String>()
                for (doc in querySnapshot.documents) {
                    val seats = doc.get("seats") as? List<String>
                    seats?.let { allBookedSeats.addAll(it) }
                }

                val conflicts = booking.seats.filter { it in allBookedSeats }
                if (conflicts.isNotEmpty()) {
                    binding.progressBar.visibility = View.GONE
                    binding.btnPay.isEnabled = true
                    Toast.makeText(this, "Seats ${conflicts.joinToString()} are already booked.", Toast.LENGTH_LONG).show()
                } else {
                    // No conflicts, save booking
                    saveBooking(finalBooking)
                }
            }
            .addOnFailureListener { e ->
                binding.progressBar.visibility = View.GONE
                binding.btnPay.isEnabled = true
                Toast.makeText(this, "Failed to verify seat availability: ${e.message}", Toast.LENGTH_SHORT).show()
            }
    }

    private fun saveBooking(booking: Booking) {
        db.collection("bookingticket_app_web")
            .add(booking)
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
