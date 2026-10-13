package com.example.bookmyticket

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.view.View
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.ViewModelProvider
import com.bumptech.glide.Glide
import com.example.bookmyticket.databinding.ActivityMovieDetailsBinding
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.utils.SystemUtils
import com.example.bookmyticket.viewmodel.WishlistViewModel

class MovieDetailsActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMovieDetailsBinding
    private var movie: Movie? = null
    private lateinit var wishlistViewModel: WishlistViewModel

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        SystemUtils.setImmersiveMode(this)
        binding = ActivityMovieDetailsBinding.inflate(layoutInflater)
        setContentView(binding.root)

        movie = intent.getParcelableExtra("movie")
        wishlistViewModel = ViewModelProvider(this)[WishlistViewModel::class.java]

        setupUI()
        observeViewModel()
        
        movie?.let { wishlistViewModel.checkIfFavorite(it.id) }
    }

    private fun setupUI() {
        movie?.let { movieItem ->
            binding.movieTitle.text = movieItem.title
            binding.movieRating.text = "⭐ ${movieItem.rating}"
            binding.movieReleaseDate.text = "${movieItem.releaseDate} • ${movieItem.duration}m • ${movieItem.language}"
            binding.movieOverview.text = movieItem.description
            binding.tvGenre.text = movieItem.genre
            binding.tvCast.text = "Cast: ${movieItem.cast}"
            binding.tvDirector.text = "Director: ${movieItem.director}"

            Glide.with(this).load(movieItem.posterUrl).into(binding.moviePoster)
            Glide.with(this).load(movieItem.bannerUrl).into(binding.movieBackdrop)

            if (movieItem.trailerUrl.isNotEmpty()) {
                binding.btnTrailer.visibility = View.VISIBLE
                binding.btnTrailer.setOnClickListener {
                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(movieItem.trailerUrl))
                    startActivity(intent)
                }
            } else {
                binding.btnTrailer.visibility = View.GONE
            }

            binding.btnBookTickets.setOnClickListener {
                val intent = Intent(this, TheatreActivity::class.java)
                intent.putExtra("movie", movieItem)
                startActivity(intent)
            }

            binding.btnFavorite.setOnClickListener {
                wishlistViewModel.toggleWishlist(movieItem)
            }
        }

        binding.toolbar.setNavigationOnClickListener { finish() }
    }

    private fun observeViewModel() {
        wishlistViewModel.isFavorite.observe(this) { isFav ->
            if (isFav) {
                binding.btnFavorite.setImageResource(R.drawable.ic_favorite_filled)
            } else {
                binding.btnFavorite.setImageResource(R.drawable.ic_favorite)
            }
        }
    }
}