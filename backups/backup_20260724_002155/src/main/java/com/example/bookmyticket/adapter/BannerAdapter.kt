package com.example.bookmyticket.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.example.bookmyticket.databinding.ItemBannerBinding
import com.example.bookmyticket.models.Movie

class BannerAdapter(
    private val movies: List<Movie>,
    private val onBannerClick: (Movie) -> Unit
) : RecyclerView.Adapter<BannerAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemBannerBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemBannerBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val movie = movies[position]
        
        Glide.with(holder.itemView.context)
            .load(movie.bannerUrl)
            .into(holder.binding.bannerImage)

        holder.itemView.setOnClickListener {
            onBannerClick(movie)
        }
    }

    override fun getItemCount() = movies.size
}