package com.example.bookmyticket.adapter

import android.graphics.Color
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.databinding.ItemAdminPaymentBinding
import com.example.bookmyticket.models.Booking
import java.text.SimpleDateFormat
import java.util.*

class AdminPaymentAdapter(
    private var payments: List<Booking>
) : RecyclerView.Adapter<AdminPaymentAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemAdminPaymentBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemAdminPaymentBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val payment = payments[position]
        holder.binding.tvBookingId.text = "ID: #${payment.bookingId}"
        
        val sdf = SimpleDateFormat("dd MMM yyyy, HH:mm", Locale.getDefault())
        holder.binding.tvDate.text = sdf.format(Date(payment.createdAt))
        
        holder.binding.tvUser.text = "User ID: ${payment.userId}"
        holder.binding.tvAmount.text = holder.itemView.context.getString(com.example.bookmyticket.R.string.amount_paid_format, payment.totalPrice.toLong())

        if (payment.status == "Cancelled") {
            holder.binding.tvStatus.text = "Refunded"
            holder.binding.tvStatus.setTextColor(Color.parseColor("#E50914")) // Primary Red
            holder.binding.tvAmount.setTextColor(Color.parseColor("#E50914"))
        } else {
            holder.binding.tvStatus.text = "Success"
            holder.binding.tvStatus.setTextColor(Color.parseColor("#22C55E")) // Success Green
            holder.binding.tvAmount.setTextColor(Color.parseColor("#E50914")) // Keep primary for success? User said red amount for refund.
        }
    }

    override fun getItemCount() = payments.size

    fun updateData(newPayments: List<Booking>) {
        payments = newPayments
        notifyDataSetChanged()
    }
}