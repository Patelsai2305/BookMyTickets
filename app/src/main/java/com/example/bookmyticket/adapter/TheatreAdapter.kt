package com.example.bookmyticket.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.databinding.ItemTheatreBinding
import com.example.bookmyticket.models.Theatre

class TheatreAdapter(
    private val theatreList: List<Theatre>,
    private val selectedDate: String,
    private val onTimingClick: (Theatre, String) -> Unit
) : RecyclerView.Adapter<TheatreAdapter.TheatreViewHolder>() {

    class TheatreViewHolder(val binding: ItemTheatreBinding) :
        RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): TheatreViewHolder {
        val binding = ItemTheatreBinding.inflate(
            LayoutInflater.from(parent.context),
            parent,
            false
        )
        return TheatreViewHolder(binding)
    }

    override fun onBindViewHolder(holder: TheatreViewHolder, position: Int) {
        val theatre = theatreList[position]
        holder.binding.theatreName.text = theatre.name
        holder.binding.theatreLocation.text = theatre.location
        holder.binding.theatreDistance.text = theatre.distance

        // Filter timings if the selected date is today
        val filteredTimings = filterTimingsForToday(theatre.showTimings)

        holder.binding.rvShowTimings.layoutManager =
            LinearLayoutManager(holder.itemView.context, LinearLayoutManager.HORIZONTAL, false)
        
        holder.binding.rvShowTimings.adapter = ShowTimingAdapter(filteredTimings) { timing ->
            onTimingClick(theatre, timing)
        }
    }

    private fun filterTimingsForToday(timings: List<String>): List<String> {
        val sdf = java.text.SimpleDateFormat("dd MMM yyyy", java.util.Locale.getDefault())
        val todayStr = sdf.format(java.util.Calendar.getInstance().time)

        if (selectedDate != todayStr) return timings

        val timeSdf = java.text.SimpleDateFormat("hh:mm a", java.util.Locale.getDefault())
        val currentTimeStr = timeSdf.format(java.util.Calendar.getInstance().time)
        val currentTime = timeSdf.parse(currentTimeStr)

        return timings.filter { timing ->
            try {
                val showTime = timeSdf.parse(timing)
                showTime?.after(currentTime) ?: true
            } catch (e: Exception) {
                true
            }
        }
    }

    override fun getItemCount(): Int = theatreList.size
}