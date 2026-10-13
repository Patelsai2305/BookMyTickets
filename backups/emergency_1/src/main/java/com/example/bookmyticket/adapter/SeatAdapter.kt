package com.example.bookmyticket.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.R
import com.example.bookmyticket.databinding.ItemSeatBinding
import com.example.bookmyticket.models.Seat

class SeatAdapter(
    private val seats: List<Seat>,
    private val onSeatClick: (Seat) -> Unit
) : RecyclerView.Adapter<SeatAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemSeatBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemSeatBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val seat = seats[position]

        holder.binding.tvSeatLabel.text = seat.name

        val backgroundRes = when {
            seat.isBooked -> R.drawable.bg_seat_booked
            seat.isSelected -> R.drawable.bg_seat_selected
            seat.type == "VIP" -> R.drawable.bg_seat_vip
            seat.type == "Premium" -> R.drawable.bg_seat_premium
            else -> R.drawable.bg_seat_normal
        }

        holder.binding.tvSeatLabel.setBackgroundResource(backgroundRes)
        
        // Disable click for booked seats
        holder.itemView.isEnabled = !seat.isBooked
        holder.itemView.alpha = if (seat.isBooked) 0.5f else 1.0f

        holder.itemView.setOnClickListener { onSeatClick(seat) }
    }

    override fun getItemCount() = seats.size
}