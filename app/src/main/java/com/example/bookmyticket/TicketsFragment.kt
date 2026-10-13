package com.example.bookmyticket

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.bookmyticket.adapter.TicketAdapter
import com.example.bookmyticket.databinding.FragmentTicketsBinding
import com.example.bookmyticket.models.Booking
import com.example.bookmyticket.repository.BookingRepository
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore
import androidx.lifecycle.lifecycleScope
import kotlinx.coroutines.launch

class TicketsFragment : Fragment() {

    private var _binding: FragmentTicketsBinding? = null
    private val binding get() = _binding!!
    private val db = FirebaseFirestore.getInstance()
    private val auth = FirebaseAuth.getInstance()
    private val bookingRepository = BookingRepository()
    
    private var allBookings = listOf<Booking>()
    private var isShowingHistory = false

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentTicketsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.rvTickets.layoutManager = LinearLayoutManager(requireContext())
        
        setupTabs()
        fetchTickets()
    }

    private fun setupTabs() {
        val initialTab = arguments?.getString("selected_tab") ?: "Upcoming"
        if (initialTab == "History") {
            binding.tabLayout.getTabAt(1)?.select()
            isShowingHistory = true
        }

        binding.tabLayout.addOnTabSelectedListener(object : com.google.android.material.tabs.TabLayout.OnTabSelectedListener {
            override fun onTabSelected(tab: com.google.android.material.tabs.TabLayout.Tab?) {
                isShowingHistory = tab?.position == 1
                filterAndDisplayTickets()
            }
            override fun onTabUnselected(tab: com.google.android.material.tabs.TabLayout.Tab?) {}
            override fun onTabReselected(tab: com.google.android.material.tabs.TabLayout.Tab?) {}
        })
    }

    private fun fetchTickets() {
        val userId = auth.currentUser?.uid ?: return

        binding.progressBar.visibility = View.VISIBLE

        lifecycleScope.launch {
            try {
                allBookings = bookingRepository.getUsersBookings(userId)
                binding.progressBar.visibility = View.GONE
                checkAndMarkExpiredTickets()
                filterAndDisplayTickets()
            } catch (e: Exception) {
                binding.progressBar.visibility = View.GONE
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun checkAndMarkExpiredTickets() {
        val currentTime = System.currentTimeMillis()
        val sdf = java.text.SimpleDateFormat("yyyy-MM-dd hh:mm a", java.util.Locale.getDefault())

        val updatedBookings = allBookings.map { booking ->
            try {
                if (booking.date.isEmpty() || booking.timing.isEmpty()) return@map booking
                
                val dateTimeString = "${booking.date} ${booking.timing}"
                val showStartTime = sdf.parse(dateTimeString)?.time ?: 0L
                val showEndTime = showStartTime + (booking.movieDuration * 60 * 1000)

                if (booking.status.equals("Upcoming", ignoreCase = true) && currentTime > showEndTime) {
                    // Update status in Firestore
                    updateStatusInFirestore(booking.bookingId, "Completed")
                    booking.copy(status = "Completed")
                } else {
                    booking
                }
            } catch (e: Exception) {
                booking
            }
        }
        allBookings = updatedBookings
    }

    private fun filterAndDisplayTickets() {
        val filteredTickets = if (isShowingHistory) {
            allBookings.filter { it.status.equals("Completed", ignoreCase = true) || it.status.equals("Cancelled", ignoreCase = true) }
        } else {
            allBookings.filter { it.status.equals("Upcoming", ignoreCase = true) }
        }

        if (filteredTickets.isEmpty()) {
            binding.tvNoTickets.visibility = View.VISIBLE
            binding.tvNoTickets.text = if (isShowingHistory) "No booking history" else "No upcoming tickets"
            binding.rvTickets.adapter = TicketAdapter(emptyList()) {}
        } else {
            binding.tvNoTickets.visibility = View.GONE
            binding.rvTickets.adapter = TicketAdapter(filteredTickets) { booking ->
                cancelTicket(booking)
            }
        }
    }

    private fun cancelTicket(booking: Booking) {
        lifecycleScope.launch {
            if (bookingRepository.updateBookingStatus(booking = booking, status = "Cancelled")) {
                Toast.makeText(requireContext(), "Ticket Cancelled", Toast.LENGTH_SHORT).show()
                fetchTickets()
            } else {
                Toast.makeText(requireContext(), "Failed to cancel ticket", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun updateStatusInFirestore(bookingId: String, newStatus: String, onComplete: ((Boolean) -> Unit)? = null) {
        lifecycleScope.launch {
            val success = bookingRepository.updateBookingStatus(bookingId = bookingId, status = newStatus)
            onComplete?.invoke(success)
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
