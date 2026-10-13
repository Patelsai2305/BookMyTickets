package com.example.bookmyticket.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.databinding.ItemBannerBinding
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.utils.ImageUtils

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
        
        ImageUtils.loadImage(holder.binding.bannerImage, movie.bannerUrl)
        holder.binding.bannerTitle.text = movie.getDisplayTitle()
        holder.binding.bannerRating.text = "⭐ ${String.format(java.util.Locale.US, "%.1f", movie.rating)}/10"
        holder.binding.bannerOverview.text = movie.description

        holder.itemView.setOnClickListener {
            onBannerClick(movie)
        }
    }

    override fun getItemCount() = movies.size
}