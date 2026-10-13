package com.example.bookmyticket.adapter

import android.graphics.Bitmap
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.example.bookmyticket.databinding.ItemTicketBinding
import com.example.bookmyticket.models.Booking
import com.google.zxing.BarcodeFormat
import com.journeyapps.barcodescanner.BarcodeEncoder

class TicketAdapter(
    private val ticketList: List<Booking>,
    private val onCancelClick: (String) -> Unit
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
        holder.binding.tvTiming.text = booking.timing
        holder.binding.tvSeats.text = "Seats: ${booking.seats.joinToString(", ")}"
        holder.binding.tvBookingId.text = "ID: ${booking.bookingId}"
        holder.binding.tvStatus.text = booking.status

        if (booking.status == "Cancelled") {
            holder.binding.tvStatus.setTextColor(android.graphics.Color.RED)
            holder.binding.btnCancel.visibility = View.GONE
        } else {
            holder.binding.tvStatus.setTextColor(android.graphics.Color.parseColor("#22C55E")) // Green
            holder.binding.btnCancel.visibility = if (booking.status == "Upcoming") View.VISIBLE else View.GONE
        }

        holder.binding.btnCancel.setOnClickListener {
            onCancelClick(booking.bookingId)
        }

        Glide.with(holder.itemView.context)
            .load(booking.moviePoster)
            .into(holder.binding.ivMoviePoster)

        try {
            val barcodeEncoder = BarcodeEncoder()
            val bitmap: Bitmap = barcodeEncoder.encodeBitmap(booking.bookingId, BarcodeFormat.QR_CODE, 200, 200)
            holder.binding.ivQRCode.setImageBitmap(bitmap)
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    override fun getItemCount(): Int = ticketList.size
}