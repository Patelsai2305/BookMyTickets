package com.example.bookmyticket

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.ViewModelProvider
import androidx.recyclerview.widget.GridLayoutManager
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.viewpager2.widget.CompositePageTransformer
import androidx.viewpager2.widget.MarginPageTransformer
import androidx.viewpager2.widget.ViewPager2
import com.example.bookmyticket.adapter.BannerAdapter
import com.example.bookmyticket.adapter.MovieAdapter
import com.example.bookmyticket.adapter.TheatreAdapter
import com.example.bookmyticket.databinding.FragmentHomeBinding
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.viewmodel.HomeViewModel
import com.google.android.material.tabs.TabLayoutMediator
import android.os.Handler
import android.os.Looper

class HomeFragment : Fragment() {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!

    private lateinit var viewModel: HomeViewModel
    
    private var previewedMovieId: String? = null
    private val sliderHandler = Handler(Looper.getMainLooper())
    private val sliderRunnable = Runnable {
        binding.bannerViewPager.currentItem = (binding.bannerViewPager.currentItem + 1) % 
                (binding.bannerViewPager.adapter?.itemCount ?: 1)
    }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentHomeBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        // Use activity-scoped ViewModel so movies are cached when switching tabs
        viewModel = ViewModelProvider(requireActivity())[HomeViewModel::class.java]

        setupRecyclerViews()
        observeViewModel()
        setupSearch()

        binding.progressBar.visibility = View.VISIBLE
        viewModel.loadMovies()
    }

    private fun setupRecyclerViews() {
        binding.rvPopular.layoutManager =
            LinearLayoutManager(requireContext(), LinearLayoutManager.HORIZONTAL, false)
            
        binding.rvUpcoming.layoutManager =
            LinearLayoutManager(requireContext(), LinearLayoutManager.HORIZONTAL, false)
            
        binding.rvSearch.layoutManager = 
            GridLayoutManager(requireContext(), 2)
            
        binding.rvTheatreSearch.layoutManager = 
            LinearLayoutManager(requireContext())
    }

    private fun observeViewModel() {
        viewModel.nowPlayingMovies.observe(viewLifecycleOwner) { movies ->
            binding.progressBar.visibility = View.GONE
            if (movies.isNotEmpty()) {
                binding.tvEmpty.visibility = View.GONE
                binding.homeContent.visibility = if (binding.searchView.query.isNullOrEmpty()) View.VISIBLE else View.GONE
            }
            
            binding.rvPopular.adapter = MovieAdapter(movies) { position ->
                handleMovieTap(movies[position])
            }

            if (movies.isNotEmpty()) {
                binding.bannerViewPager.adapter = BannerAdapter(movies) { movie ->
                    openMovieDetails(movie)
                }
                
                TabLayoutMediator(binding.bannerIndicator, binding.bannerViewPager) { _, _ -> }.attach()
                
                setupAutoSlider()
            }
        }
        
        viewModel.upcomingMovies.observe(viewLifecycleOwner) { movies ->
            if (movies.isNotEmpty()) {
                binding.tvEmpty.visibility = View.GONE
            }
            binding.rvUpcoming.adapter = MovieAdapter(movies) { position ->
                handleMovieTap(movies[position])
            }
        }
        
        viewModel.searchResults.observe(viewLifecycleOwner) { movies ->
            binding.progressBar.visibility = View.GONE
            binding.rvSearch.adapter = MovieAdapter(movies) { position ->
                openMovieDetails(movies[position])
            }
        }
        
        viewModel.theatreResults.observe(viewLifecycleOwner) { theatres ->
            val todayStr = java.text.SimpleDateFormat("dd MMM yyyy", java.util.Locale.getDefault()).format(java.util.Calendar.getInstance().time)
            binding.rvTheatreSearch.adapter = TheatreAdapter(theatres, todayStr) { theatre, _ ->
                Toast.makeText(requireContext(), "Selected ${theatre.name}. Search for a movie to book!", Toast.LENGTH_SHORT).show()
            }
            binding.tvTheatreTitle.visibility = if (theatres.isNotEmpty()) View.VISIBLE else View.GONE
            binding.rvTheatreSearch.visibility = if (theatres.isNotEmpty()) View.VISIBLE else View.GONE
        }

        viewModel.error.observe(viewLifecycleOwner) {
            binding.progressBar.visibility = View.GONE
            Toast.makeText(requireContext(), it, Toast.LENGTH_LONG).show()
            binding.tvEmpty.visibility = View.VISIBLE
            binding.tvEmpty.text = it
            
            // Hide content if error is shown on home screen
            if (binding.searchView.query.isNullOrEmpty()) {
                binding.homeContent.visibility = View.GONE
            } else {
                binding.searchContent.visibility = View.GONE
            }
        }
    }

    private fun setupSearch() {
        binding.searchView.setOnQueryTextListener(object : androidx.appcompat.widget.SearchView.OnQueryTextListener {
            override fun onQueryTextSubmit(query: String?): Boolean {
                if (!query.isNullOrEmpty()) {
                    binding.progressBar.visibility = View.VISIBLE
                    viewModel.searchMovies(query)
                }
                return true
            }

            override fun onQueryTextChange(newText: String?): Boolean {
                if (newText.isNullOrEmpty()) {
                    showHomeContent()
                } else {
                    showSearchContent()
                }
                return true
            }
        })
    }

    private fun showHomeContent() {
        binding.homeContent.visibility = View.VISIBLE
        binding.searchContent.visibility = View.GONE
        binding.tvEmpty.visibility = View.GONE
    }

    private fun showSearchContent() {
        binding.homeContent.visibility = View.GONE
        binding.searchContent.visibility = View.VISIBLE
        binding.tvEmpty.visibility = View.GONE
    }

    private fun handleMovieTap(movie: Movie) {
        val movieId = movie.getUniqueId()
        if (previewedMovieId == movieId) {
            // Second tap -> Open details
            openMovieDetails(movie)
        } else {
            // First tap -> Preview in banner
            previewedMovieId = movieId
            val position = (viewModel.nowPlayingMovies.value ?: emptyList()).indexOfFirst { it.getUniqueId() == movieId }
            if (position != -1) {
                binding.bannerViewPager.setCurrentItem(position, true)
            } else {
                // If not in nowPlaying (e.g. from upcoming), just open details
                openMovieDetails(movie)
            }
        }
    }

    private fun setupAutoSlider() {
        // Add a nice scaling effect when swiping
        val compositePageTransformer = CompositePageTransformer()
        compositePageTransformer.addTransformer(MarginPageTransformer(40))
        compositePageTransformer.addTransformer { page: View, position: Float ->
            val r = 1 - Math.abs(position)
            page.scaleY = 0.85f + r * 0.15f
        }
        binding.bannerViewPager.setPageTransformer(compositePageTransformer)

        binding.bannerViewPager.registerOnPageChangeCallback(object : ViewPager2.OnPageChangeCallback() {
            override fun onPageSelected(position: Int) {
                super.onPageSelected(position)
                sliderHandler.removeCallbacks(sliderRunnable)
                sliderHandler.postDelayed(sliderRunnable, 5000)
            }
        })
    }

    private fun openMovieDetails(movie: Movie) {
        val intent = Intent(requireContext(), MovieDetailsActivity::class.java)
        intent.putExtra("movie", movie)
        startActivity(intent)
    }

    fun refreshMovies() {
        viewModel.loadMovies()
    }

    override fun onPause() {
        super.onPause()
        sliderHandler.removeCallbacks(sliderRunnable)
    }

    override fun onResume() {
        super.onResume()
        if (binding.bannerViewPager.adapter != null) {
            sliderHandler.postDelayed(sliderRunnable, 5000)
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}