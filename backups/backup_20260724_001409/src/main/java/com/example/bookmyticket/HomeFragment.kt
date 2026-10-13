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
import com.example.bookmyticket.adapter.BannerAdapter
import com.example.bookmyticket.adapter.MovieAdapter
import com.example.bookmyticket.adapter.TheatreAdapter
import com.example.bookmyticket.databinding.FragmentHomeBinding
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.viewmodel.HomeViewModel

class HomeFragment : Fragment() {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!

    private lateinit var viewModel: HomeViewModel

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

        viewModel = ViewModelProvider(this)[HomeViewModel::class.java]

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
            binding.rvPopular.adapter = MovieAdapter(movies) { position ->
                openMovieDetails(movies[position])
            }

            if (movies.isNotEmpty()) {
                binding.bannerViewPager.adapter = BannerAdapter(movies) { movie ->
                    openMovieDetails(movie)
                }
            }
        }
        
        viewModel.upcomingMovies.observe(viewLifecycleOwner) { movies ->
            binding.rvUpcoming.adapter = MovieAdapter(movies) { position ->
                openMovieDetails(movies[position])
            }
        }
        
        viewModel.searchResults.observe(viewLifecycleOwner) { movies ->
            binding.progressBar.visibility = View.GONE
            binding.rvSearch.adapter = MovieAdapter(movies) { position ->
                openMovieDetails(movies[position])
            }
        }
        
        viewModel.theatreResults.observe(viewLifecycleOwner) { theatres ->
            binding.rvTheatreSearch.adapter = TheatreAdapter(theatres) { theatre, _ ->
                Toast.makeText(requireContext(), "Selected ${theatre.name}. Search for a movie to book!", Toast.LENGTH_SHORT).show()
            }
            binding.tvTheatreTitle.visibility = if (theatres.isNotEmpty()) View.VISIBLE else View.GONE
            binding.rvTheatreSearch.visibility = if (theatres.isNotEmpty()) View.VISIBLE else View.GONE
        }

        viewModel.error.observe(viewLifecycleOwner) {
            binding.progressBar.visibility = View.GONE
            Toast.makeText(requireContext(), it, Toast.LENGTH_LONG).show()
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
        binding.bannerViewPager.visibility = View.VISIBLE
        binding.rvPopular.visibility = View.VISIBLE
        binding.tvUpcomingTitle.visibility = View.VISIBLE
        binding.rvUpcoming.visibility = View.VISIBLE
        binding.tvSectionTitle.text = "🔥 Now Playing"
        binding.rvSearch.visibility = View.GONE
        binding.tvTheatreTitle.visibility = View.GONE
        binding.rvTheatreSearch.visibility = View.GONE
    }

    private fun showSearchContent() {
        binding.bannerViewPager.visibility = View.GONE
        binding.rvPopular.visibility = View.GONE
        binding.tvUpcomingTitle.visibility = View.GONE
        binding.rvUpcoming.visibility = View.GONE
        binding.tvSectionTitle.text = "Search Results"
        binding.rvSearch.visibility = View.VISIBLE
    }

    private fun openMovieDetails(movie: Movie) {
        val intent = Intent(requireContext(), MovieDetailsActivity::class.java)
        intent.putExtra("movie", movie)
        startActivity(intent)
    }

    fun refreshMovies() {
        viewModel.loadMovies()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}