package com.example.bookmyticket

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.fragment.app.Fragment
import androidx.fragment.app.activityViewModels
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.bookmyticket.adapter.AdminUserAdapter
import com.example.bookmyticket.databinding.FragmentAdminUsersBinding
import com.example.bookmyticket.models.User
import com.example.bookmyticket.viewmodel.AdminViewModel

class AdminUsersFragment : Fragment() {

    private var _binding: FragmentAdminUsersBinding? = null
    private val binding get() = _binding!!
    private val viewModel: AdminViewModel by activityViewModels()
    private lateinit var adapter: AdminUserAdapter

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentAdminUsersBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupRecyclerView()

        viewModel.users.observe(viewLifecycleOwner) { users ->
            adapter.updateData(users)
            binding.progressBar.visibility = View.GONE
            binding.tvEmpty.visibility = if (users.isEmpty()) View.VISIBLE else View.GONE
        }

        viewModel.error.observe(viewLifecycleOwner) { errorMsg ->
            binding.progressBar.visibility = View.GONE
            Toast.makeText(requireContext(), errorMsg, Toast.LENGTH_LONG).show()
        }

        binding.progressBar.visibility = View.VISIBLE
        // viewModel.fetchUsers() // Reactive
    }

    private fun setupRecyclerView() {
        adapter = AdminUserAdapter(emptyList()) { user ->
            showRoleDialog(user)
        }
        binding.rvUsers.layoutManager = LinearLayoutManager(requireContext())
        binding.rvUsers.adapter = adapter
    }

    private fun showRoleDialog(user: User) {
        val roles = arrayOf("user", "admin")
        val checkedItem = if (user.role == "admin") 1 else 0

        AlertDialog.Builder(requireContext())
            .setTitle("Select Role for ${user.fullName}")
            .setSingleChoiceItems(roles, checkedItem) { dialog, which ->
                val newRole = roles[which]
                viewModel.updateUserRole(user.uid, newRole)
                Toast.makeText(requireContext(), "Role updated to $newRole", Toast.LENGTH_SHORT).show()
                dialog.dismiss()
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}