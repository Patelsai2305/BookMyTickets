package com.example.bookmyticket

import android.content.Intent
import android.graphics.Bitmap
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.example.bookmyticket.databinding.ActivityBookingConfirmationBinding
import com.example.bookmyticket.models.Booking
import com.example.bookmyticket.utils.SystemUtils
import com.google.zxing.BarcodeFormat
import com.journeyapps.barcodescanner.BarcodeEncoder

class BookingConfirmationActivity : AppCompatActivity() {

    private lateinit var binding: ActivityBookingConfirmationBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        SystemUtils.setImmersiveMode(this)
        binding = ActivityBookingConfirmationBinding.inflate(layoutInflater)
        setContentView(binding.root)

        val booking = intent.getParcelableExtra<Booking>("booking")

        if (booking != null) {
            setupUI(booking)
            generateQRCode(booking.bookingId)
        }

        binding.btnHome.setOnClickListener {
            val intent = Intent(this, HomeActivity::class.java)
            intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_NEW_TASK)
            startActivity(intent)
            finish()
        }
    }

    private fun setupUI(booking: Booking) {
        binding.tvMovieTitle.text = booking.movieTitle
        binding.tvTheatreName.text = booking.theatreName
        binding.tvTiming.text = booking.timing
        binding.tvSeats.text = "Seats: ${booking.seats.joinToString()}"
        binding.tvTotalPrice.text = "Total Paid: ₹ ${booking.totalPrice}"
        binding.tvBookingId.text = "Booking ID: ${booking.bookingId}"
    }

    private fun generateQRCode(bookingId: String) {
        try {
            val barcodeEncoder = BarcodeEncoder()
            val bitmap: Bitmap = barcodeEncoder.encodeBitmap(bookingId, BarcodeFormat.QR_CODE, 500, 500)
            binding.ivQRCode.setImageBitmap(bitmap)
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }
}