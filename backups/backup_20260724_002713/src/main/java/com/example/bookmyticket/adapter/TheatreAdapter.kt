package com.example.bookmyticket.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.databinding.ItemTheatreBinding
import com.example.bookmyticket.models.Theatre

class TheatreAdapter(
    private val theatreList: List<Theatre>,
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

        holder.binding.rvShowTimings.layoutManager =
            LinearLayoutManager(holder.itemView.context, LinearLayoutManager.HORIZONTAL, false)
        
        holder.binding.rvShowTimings.adapter = ShowTimingAdapter(theatre.showTimings) { timing ->
            onTimingClick(theatre, timing)
        }
    }

    override fun getItemCount(): Int = theatreList.size
}