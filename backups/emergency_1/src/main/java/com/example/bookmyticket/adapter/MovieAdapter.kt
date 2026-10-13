package com.example.bookmyticket.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.databinding.ItemMovieBinding
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.utils.ImageUtils

class MovieAdapter(
    private val movies: List<Movie>,
    private val onItemClick: (Int) -> Unit
) : RecyclerView.Adapter<MovieAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemMovieBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemMovieBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val movie = movies[position]
        holder.binding.movieTitle.text = movie.getDisplayTitle()
        
        ImageUtils.loadImage(holder.binding.moviePoster, movie.posterUrl)

        holder.itemView.setOnClickListener {
            onItemClick(position)
        }
    }

    override fun getItemCount() = movies.size
}