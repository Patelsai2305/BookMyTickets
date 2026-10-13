package com.example.bookmyticket.adapter

import android.graphics.Bitmap
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.databinding.ItemTicketBinding
import com.example.bookmyticket.models.Booking
import com.example.bookmyticket.utils.ImageUtils
import com.google.zxing.BarcodeFormat
import com.journeyapps.barcodescanner.BarcodeEncoder

class TicketAdapter(
    private val ticketList: List<Booking>,
    private val onCancelClick: (Booking) -> Unit
) : RecyclerView.Adapter<TicketAdapter.TicketViewHolder>() {

    class TicketViewHolder(val binding: ItemTicketBinding) :
        RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): TicketViewHolder {
        val binding = ItemTicketBinding.inflate(
            LayoutInflater.from(parent.context),
            parent,
            false
        )
        return TicketViewHolder(binding)
    }

    override fun onBindViewHolder(holder: TicketViewHolder, position: Int) {
        val booking = ticketList[position]
        holder.binding.tvMovieTitle.text = booking.movieTitle
        holder.binding.tvTheatreName.text = booking.theatreName
        holder.binding.tvTiming.text = "${booking.date} | ${booking.timing}"
        holder.binding.tvSeats.text = "Seats: ${booking.seats.joinToString(", ")}"
        holder.binding.tvBookingId.text = "ID: ${booking.bookingId}"
        
        updateStatusDisplay(holder, booking)

        holder.binding.btnCancel.setOnClickListener {
            onCancelClick(booking)
        }

        ImageUtils.loadImage(holder.binding.ivMoviePoster, booking.moviePoster)

        try {
            val barcodeEncoder = BarcodeEncoder()
            val bitmap: Bitmap = barcodeEncoder.encodeBitmap(booking.bookingId, BarcodeFormat.QR_CODE, 200, 200)
            holder.binding.ivQRCode.setImageBitmap(bitmap)
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    private fun updateStatusDisplay(holder: TicketViewHolder, booking: Booking) {
        val currentTime = System.currentTimeMillis()
        // Match Booking model: YYYY-MM-DD and timing format
        val sdf = java.text.SimpleDateFormat("yyyy-MM-dd hh:mm a", java.util.Locale.getDefault())
        
        var displayStatus = booking.status
        var statusColor = android.graphics.Color.parseColor("#22C55E") // Default Green
        var startTime = 0L

        try {
            val dateTimeString = "${booking.date} ${booking.timing}"
            startTime = sdf.parse(dateTimeString)?.time ?: 0L
            val endTime = startTime + (booking.movieDuration * 60 * 1000)

            if (booking.status.equals("Upcoming", ignoreCase = true)) {
                if (currentTime in startTime..endTime) {
                    displayStatus = "🔥 NOW PLAYING"
                    statusColor = android.graphics.Color.parseColor("#E50914") // Netflix Red
                } else if (currentTime < startTime) {
                    val diff = startTime - currentTime
                    val hours = diff / (1000 * 60 * 60)
                    val minutes = (diff / (1000 * 60)) % 60
                    
                    if (hours < 24) {
                        displayStatus = if (hours > 0) {
                            "Starts in ${hours}h ${minutes}m"
                        } else {
                            "Starts in ${minutes}m"
                        }
                    }
                }
            } else if (booking.status.equals("Cancelled", ignoreCase = true)) {
                statusColor = android.graphics.Color.RED
            }
        } catch (e: Exception) {
            android.util.Log.e("TicketAdapter", "Error parsing date: ${booking.date} ${booking.timing}", e)
        }

        holder.binding.tvStatus.text = displayStatus
        holder.binding.tvStatus.setTextColor(statusColor)
        
        // Safety check for cancel button visibility using already parsed startTime
        holder.binding.btnCancel.visibility = if (booking.status.equals("Upcoming", ignoreCase = true) && currentTime < startTime && startTime != 0L) {
            View.VISIBLE 
        } else {
            View.GONE
        }
    }

    override fun getItemCount(): Int = ticketList.size
}