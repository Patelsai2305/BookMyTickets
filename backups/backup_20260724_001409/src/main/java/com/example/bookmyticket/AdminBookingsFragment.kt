package com.example.bookmyticket

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.bookmyticket.adapter.AdminBookingAdapter
import com.example.bookmyticket.databinding.FragmentAdminBookingsBinding
import com.example.bookmyticket.viewmodel.AdminViewModel

class AdminBookingsFragment : Fragment() {

    private var _binding: FragmentAdminBookingsBinding? = null
    private val binding get() = _binding!!
    private val viewModel: AdminViewModel by viewModels()
    private lateinit var adapter: AdminBookingAdapter

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentAdminBookingsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupRecyclerView()

        viewModel.bookings.observe(viewLifecycleOwner) { bookings ->
            adapter.updateData(bookings)
            binding.progressBar.visibility = View.GONE
            binding.tvEmpty.visibility = if (bookings.isEmpty()) View.VISIBLE else View.GONE
        }

        binding.progressBar.visibility = View.VISIBLE
        viewModel.fetchBookings()
    }

    private fun setupRecyclerView() {
        adapter = AdminBookingAdapter(emptyList()) { bookingId ->
            viewModel.cancelBooking(bookingId)
        }
        binding.rvBookings.layoutManager = LinearLayoutManager(requireContext())
        binding.rvBookings.adapter = adapter
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}