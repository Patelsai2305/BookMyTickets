package com.example.bookmyticket

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.lifecycle.ViewModelProvider
import androidx.recyclerview.widget.GridLayoutManager
import com.example.bookmyticket.adapter.MovieAdapter
import com.example.bookmyticket.databinding.FragmentWishlistBinding
import com.example.bookmyticket.viewmodel.WishlistViewModel

class WishlistFragment : Fragment() {

    private var _binding: FragmentWishlistBinding? = null
    private val binding get() = _binding!!
    private lateinit var viewModel: WishlistViewModel

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentWishlistBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        viewModel = ViewModelProvider(this)[WishlistViewModel::class.java]

        setupRecyclerView()
        observeViewModel()

        viewModel.loadWishlist()
    }

    private fun setupRecyclerView() {
        binding.rvWishlist.layoutManager = GridLayoutManager(requireContext(), 2)
    }

    private fun observeViewModel() {
        viewModel.wishlistMovies.observe(viewLifecycleOwner) { movies ->
            if (movies.isEmpty()) {
                binding.tvEmpty.visibility = View.VISIBLE
                binding.rvWishlist.visibility = View.GONE
            } else {
                binding.tvEmpty.visibility = View.GONE
                binding.rvWishlist.visibility = View.VISIBLE
                binding.rvWishlist.adapter = MovieAdapter(movies) { position ->
                    val intent = Intent(requireContext(), MovieDetailsActivity::class.java)
                    intent.putExtra("movie", movies[position])
                    startActivity(intent)
                }
            }
        }

        viewModel.isLoading.observe(viewLifecycleOwner) { isLoading ->
            binding.progressBar.visibility = if (isLoading) View.VISIBLE else View.GONE
        }
    }

    override fun onResume() {
        super.onResume()
        viewModel.loadWishlist()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}