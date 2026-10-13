package com.example.bookmyticket

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.bookmyticket.adapter.AdminPaymentAdapter
import com.example.bookmyticket.databinding.FragmentAdminPaymentsBinding
import com.example.bookmyticket.viewmodel.AdminViewModel

class AdminPaymentsFragment : Fragment() {

    private var _binding: FragmentAdminPaymentsBinding? = null
    private val binding get() = _binding!!
    private val viewModel: AdminViewModel by viewModels()
    private lateinit var adapter: AdminPaymentAdapter

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentAdminPaymentsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupRecyclerView()

        viewModel.bookings.observe(viewLifecycleOwner) { bookings ->
            adapter.updateData(bookings) // derived from bookings
            binding.progressBar.visibility = View.GONE
            binding.tvEmpty.visibility = if (bookings.isEmpty()) View.VISIBLE else View.GONE
        }

        binding.progressBar.visibility = View.VISIBLE
        viewModel.fetchBookings()
    }

    private fun setupRecyclerView() {
        adapter = AdminPaymentAdapter(emptyList())
        binding.rvPayments.layoutManager = LinearLayoutManager(requireContext())
        binding.rvPayments.adapter = adapter
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}