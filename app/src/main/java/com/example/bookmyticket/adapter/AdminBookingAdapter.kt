package com.example.bookmyticket.adapter

import android.graphics.Color
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.R
import com.example.bookmyticket.databinding.ItemAdminBookingBinding
import com.example.bookmyticket.models.Booking

class AdminBookingAdapter(
    private var bookings: List<Booking>,
    private val onCancelClick: (Booking) -> Unit
) : RecyclerView.Adapter<AdminBookingAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemAdminBookingBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemAdminBookingBinding.inflate(
            LayoutInflater.from(parent.context),
            parent,
            false
        )
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val booking = bookings[position]
        val context = holder.itemView.context

        // Title and Status
        holder.binding.tvMovieTitle.text = booking.movieTitle.ifEmpty { "Movie Ticket" }
        holder.binding.tvStatus.text = booking.status.ifEmpty { "Confirmed" }

        // User info fallback: prefers email, falls back to userId
        val userIdentifier = booking.userEmail.ifEmpty { booking.userId }
        holder.binding.tvUserEmail.text = try {
            context.getString(R.string.user_id_format, userIdentifier)
        } catch (e: Exception) {
            "User: $userIdentifier"
        }

        // Showtime and Seats
        val seatsFormatted = if (booking.seats.isNotEmpty()) {
            booking.seats.joinToString(", ")
        } else {
            "None"
        }
        val showTimeFormatted = if (booking.date.isNotEmpty()) {
            "${booking.date} | ${booking.timing}"
        } else {
            booking.timing
        }
        holder.binding.tvBookingDetails.text = "Showtime: $showTimeFormatted | Seats: $seatsFormatted"

        // Total Price formatting
        holder.binding.tvPrice.text = try {
            context.getString(R.string.price_format_total, booking.totalPrice.toLong())
        } catch (e: Exception) {
            "₹ ${booking.totalPrice.toInt()}"
        }

        // Status Styling & Cancel Button Visibility
        val isCancelled = booking.status.equals("Cancelled", ignoreCase = true)
        val canCancel = !isCancelled && (
                booking.status.equals("Confirmed", ignoreCase = true) ||
                        booking.status.equals("Upcoming", ignoreCase = true)
                )

        holder.binding.tvStatus.setBackgroundResource(R.drawable.bg_chip)
        holder.binding.tvStatus.setTextColor(Color.WHITE)

        if (isCancelled) {
            holder.binding.tvStatus.alpha = 0.5f
            holder.binding.btnCancel.visibility = View.GONE
        } else {
            holder.binding.tvStatus.alpha = 1.0f
            holder.binding.btnCancel.visibility = if (canCancel) View.VISIBLE else View.GONE
        }

        holder.binding.btnCancel.setOnClickListener {
            onCancelClick(booking)
        }
    }

    override fun getItemCount(): Int = bookings.size

    fun updateData(newBookings: List<Booking>) {
        this.bookings = newBookings
        notifyDataSetChanged()
    }
}