package com.example.bookmyticket.adapter

import android.graphics.Color
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.R
import com.example.bookmyticket.databinding.ItemDateBinding

data class DateModel(
    val day: String,
    val date: String,
    val month: String,
    val fullDate: String,
    var isSelected: Boolean = false
)

class DateAdapter(
    private val dates: List<DateModel>,
    private val onDateSelected: (DateModel) -> Unit
) : RecyclerView.Adapter<DateAdapter.DateViewHolder>() {

    private var selectedPosition = 0

    inner class DateViewHolder(val binding: ItemDateBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): DateViewHolder {
        val binding = ItemDateBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return DateViewHolder(binding)
    }

    override fun onBindViewHolder(holder: DateViewHolder, position: Int) {
        val dateModel = dates[position]
        holder.binding.apply {
            tvDay.text = dateModel.day
            tvDate.text = dateModel.date
            tvMonth.text = dateModel.month

            if (position == selectedPosition) {
                dateContainer.setBackgroundResource(R.drawable.bg_date_selected)
                tvDay.setTextColor(Color.WHITE)
                tvDate.setTextColor(Color.WHITE)
                tvMonth.setTextColor(Color.WHITE)
            } else {
                dateContainer.setBackgroundResource(R.drawable.bg_date_unselected)
                tvDay.setTextColor(ContextCompat.getColor(root.context, R.color.white_alpha))
                tvDate.setTextColor(Color.WHITE)
                tvMonth.setTextColor(ContextCompat.getColor(root.context, R.color.white_alpha))
            }

            root.setOnClickListener {
                val previousPosition = selectedPosition
                selectedPosition = holder.adapterPosition
                notifyItemChanged(previousPosition)
                notifyItemChanged(selectedPosition)
                onDateSelected(dateModel)
            }
        }
    }

    override fun getItemCount(): Int = dates.size
}