package com.example.bookmyticket.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.databinding.ItemShowTimingBinding

class ShowTimingAdapter(
    private val timings: List<String>,
    private val onTimingClick: (String) -> Unit
) : RecyclerView.Adapter<ShowTimingAdapter.TimingViewHolder>() {

    class TimingViewHolder(val binding: ItemShowTimingBinding) :
        RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): TimingViewHolder {
        val binding = ItemShowTimingBinding.inflate(
            LayoutInflater.from(parent.context),
            parent,
            false
        )
        return TimingViewHolder(binding)
    }

    override fun onBindViewHolder(holder: TimingViewHolder, position: Int) {
        val timing = timings[position]
        holder.binding.tvShowTiming.text = timing
        holder.itemView.setOnClickListener {
            onTimingClick(timing)
        }
    }

    override fun getItemCount(): Int = timings.size
}