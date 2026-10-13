package com.example.bookmyticket.adapter

import android.graphics.Color
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.databinding.ItemAdminBookingBinding
import com.example.bookmyticket.models.Booking

class AdminBookingAdapter(
    private var bookings: List<Booking>,
    private val onCancelClick: (String) -> Unit
) : RecyclerView.Adapter<AdminBookingAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemAdminBookingBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemAdminBookingBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val booking = bookings[position]
        holder.binding.tvMovieTitle.text = booking.movieTitle
        holder.binding.tvStatus.text = booking.status
        holder.binding.tvUserEmail.text = holder.itemView.context.getString(com.example.bookmyticket.R.string.user_id_format, booking.userId)
        holder.binding.tvBookingDetails.text = "Showtime: ${booking.timing} | Seats: ${booking.seats.joinToString()}"
        holder.binding.tvPrice.text = holder.itemView.context.getString(com.example.bookmyticket.R.string.price_format_total, booking.totalPrice)

        if (booking.status == "Cancelled") {
            holder.binding.tvStatus.setBackgroundResource(com.example.bookmyticket.R.drawable.bg_chip)
            holder.binding.tvStatus.setTextColor(Color.WHITE)
            holder.binding.tvStatus.alpha = 0.6f
            holder.binding.btnCancel.visibility = View.GONE
        } else {
            holder.binding.tvStatus.setBackgroundResource(com.example.bookmyticket.R.drawable.bg_chip)
            holder.binding.tvStatus.setTextColor(Color.WHITE)
            holder.binding.tvStatus.alpha = 1.0f
            holder.binding.btnCancel.visibility = if (booking.status == "Upcoming") View.VISIBLE else View.GONE
        }

        holder.binding.btnCancel.setOnClickListener {
            onCancelClick(booking.bookingId)
        }
    }

    override fun getItemCount() = bookings.size

    fun updateData(newBookings: List<Booking>) {
        bookings = newBookings
        notifyDataSetChanged()
    }
}