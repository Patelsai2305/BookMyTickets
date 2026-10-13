package com.example.bookmyticket

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import com.example.bookmyticket.databinding.FragmentAdminStatsBinding
import com.example.bookmyticket.viewmodel.AdminViewModel

class AdminStatsFragment : Fragment() {

    private var _binding: FragmentAdminStatsBinding? = null
    private val binding get() = _binding!!
    private val viewModel: AdminViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentAdminStatsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        viewModel.stats.observe(viewLifecycleOwner) { stats ->
            binding.tvTotalUsers.text = stats.totalUsers.toString()
            binding.tvTotalMovies.text = stats.totalMovies.toString()
            binding.tvTotalBookings.text = stats.totalBookings.toString()
            binding.tvTotalRevenue.text = getString(R.string.price_format, stats.totalRevenue)
            binding.tvTotalRefunds.text = getString(R.string.price_format, stats.totalRefunds)
        }

        viewModel.fetchStats()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}