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

        if (seat.isBooked) {
            holder.binding.ivSeat.setImageResource(R.drawable.ic_seat)
            holder.binding.ivSeat.setColorFilter(holder.itemView.context.getColor(R.color.gray))
        } else if (seat.isSelected) {
            holder.binding.ivSeat.setImageResource(R.drawable.ic_seat)
            holder.binding.ivSeat.setColorFilter(holder.itemView.context.getColor(R.color.primary))
        } else {
            holder.binding.ivSeat.setImageResource(R.drawable.ic_seat)
            holder.binding.ivSeat.setColorFilter(holder.itemView.context.getColor(R.color.white))
        }

        holder.itemView.setOnClickListener { onSeatClick(seat) }
    }

    override fun getItemCount() = seats.size
}