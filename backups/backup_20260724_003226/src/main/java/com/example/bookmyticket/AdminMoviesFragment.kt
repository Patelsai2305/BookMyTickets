package com.example.bookmyticket

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.bookmyticket.adapter.AdminMovieAdapter
import com.example.bookmyticket.databinding.FragmentAdminMoviesBinding
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.viewmodel.AdminViewModel

class AdminMoviesFragment : Fragment() {

    private var _binding: FragmentAdminMoviesBinding? = null
    private val binding get() = _binding!!
    private val viewModel: AdminViewModel by viewModels()
    private lateinit var adapter: AdminMovieAdapter

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentAdminMoviesBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupRecyclerView()
        
        binding.fabAddMovie.setOnClickListener {
            startActivity(Intent(requireContext(), AddEditMovieActivity::class.java))
        }

        viewModel.movies.observe(viewLifecycleOwner) { movies ->
            adapter.updateData(movies)
            binding.progressBar.visibility = View.GONE
            binding.tvEmpty.visibility = if (movies.isEmpty()) View.VISIBLE else View.GONE
        }

        binding.progressBar.visibility = View.VISIBLE
        viewModel.fetchMovies()
    }

    private fun setupRecyclerView() {
        adapter = AdminMovieAdapter(emptyList(), { movie ->
            val intent = Intent(requireContext(), AddEditMovieActivity::class.java)
            intent.putExtra("movie", movie)
            startActivity(intent)
        }, { movie ->
            showDeleteDialog(movie)
        })
        binding.rvMovies.layoutManager = LinearLayoutManager(requireContext())
        binding.rvMovies.adapter = adapter
    }

    private fun showDeleteDialog(movie: Movie) {
        AlertDialog.Builder(requireContext())
            .setTitle("Delete Movie")
            .setMessage("Are you sure you want to delete ${movie.title}?")
            .setPositiveButton("Delete") { _, _ ->
                viewModel.deleteMovie(movie.id)
                Toast.makeText(requireContext(), "Movie deleted", Toast.LENGTH_SHORT).show()
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    override fun onResume() {
        super.onResume()
        viewModel.fetchMovies()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}