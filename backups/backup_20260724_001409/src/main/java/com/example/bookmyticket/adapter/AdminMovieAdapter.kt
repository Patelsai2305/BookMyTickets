package com.example.bookmyticket.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.example.bookmyticket.databinding.ItemMovieAdminBinding
import com.example.bookmyticket.models.Movie

class AdminMovieAdapter(
    private var movies: List<Movie>,
    private val onEdit: (Movie) -> Unit,
    private val onDelete: (Movie) -> Unit
) : RecyclerView.Adapter<AdminMovieAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemMovieAdminBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemMovieAdminBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val movie = movies[position]
        holder.binding.tvTitle.text = movie.title
        holder.binding.tvGenre.text = movie.genre
        
        Glide.with(holder.itemView.context)
            .load(movie.posterUrl)
            .into(holder.binding.ivPoster)

        holder.binding.btnEdit.setOnClickListener { onEdit(movie) }
        holder.binding.btnDelete.setOnClickListener { onDelete(movie) }
    }

    override fun getItemCount() = movies.size

    fun updateData(newMovies: List<Movie>) {
        movies = newMovies
        notifyDataSetChanged()
    }
}