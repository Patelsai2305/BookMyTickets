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
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore

class TicketsFragment : Fragment() {

    private var _binding: FragmentTicketsBinding? = null
    private val binding get() = _binding!!
    private val db = FirebaseFirestore.getInstance()
    private val auth = FirebaseAuth.getInstance()

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
        fetchTickets()
    }

    private fun fetchTickets() {
        val userId = auth.currentUser?.uid ?: return

        binding.progressBar.visibility = View.VISIBLE

        db.collection("Bookings")
            .whereEqualTo("userId", userId)
            .get()
            .addOnSuccessListener { result ->
                binding.progressBar.visibility = View.GONE
                val tickets = result.toObjects(Booking::class.java)
                if (tickets.isEmpty()) {
                    binding.tvNoTickets.visibility = View.VISIBLE
                } else {
                    binding.tvNoTickets.visibility = View.GONE
                    binding.rvTickets.adapter = TicketAdapter(tickets) { bookingId ->
                        cancelTicket(bookingId)
                    }
                }
            }
            .addOnFailureListener { e ->
                binding.progressBar.visibility = View.GONE
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
    }

    private fun cancelTicket(bookingId: String) {
        db.collection("Bookings").document(bookingId)
            .update("status", "Cancelled")
            .addOnSuccessListener {
                Toast.makeText(requireContext(), "Ticket Cancelled", Toast.LENGTH_SHORT).show()
                fetchTickets()
            }
            .addOnFailureListener {
                Toast.makeText(requireContext(), "Failed to cancel ticket", Toast.LENGTH_SHORT).show()
            }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}