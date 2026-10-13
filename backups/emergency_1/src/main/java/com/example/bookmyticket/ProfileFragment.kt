package com.example.bookmyticket

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import com.example.bookmyticket.databinding.FragmentProfileBinding
import com.google.firebase.auth.FirebaseAuth

import android.widget.Toast
import com.example.bookmyticket.models.User
import com.example.bookmyticket.utils.ImageUtils
import com.google.firebase.firestore.FirebaseFirestore

class ProfileFragment : Fragment() {

    private var _binding: FragmentProfileBinding? = null
    private val binding get() = _binding!!
    private lateinit var auth: FirebaseAuth
    private val db = FirebaseFirestore.getInstance()

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentProfileBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        auth = FirebaseAuth.getInstance()

        fetchUserData()

        binding.menuEditProfile.setOnClickListener {
            startActivity(Intent(requireContext(), EditProfileActivity::class.java))
        }

        binding.menuBookingHistory.setOnClickListener {
            val bundle = Bundle().apply { putString("selected_tab", "History") }
            (activity as? HomeActivity)?.selectTab(R.id.nav_ticket, bundle)
        }

        binding.menuWishlist.setOnClickListener {
            (activity as? HomeActivity)?.selectTab(R.id.nav_wishlist)
        }

        binding.menuHelp.setOnClickListener {
            val helpIntent = Intent(Intent.ACTION_VIEW, Uri.parse("https://github.com"))
            startActivity(helpIntent)
        }

        binding.btnLogoutRow.setOnClickListener {
            auth.signOut()
            val intent = Intent(requireContext(), LoginActivity::class.java)
            intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
            startActivity(intent)
        }
    }

    private fun fetchUserData() {
        val uid = auth.currentUser?.uid ?: return
        db.collection("Users").document(uid).get()
            .addOnSuccessListener { document ->
                val user = document.toObject(User::class.java)
                user?.let {
                    binding.tvFullName.text = it.fullName
                    binding.tvEmail.text = it.email
                    binding.tvPhone.text = it.phone
                    
                    ImageUtils.loadImage(
                        binding.ivProfileImage, 
                        it.profileImage, 
                        com.example.bookmyticket.R.drawable.ic_profile
                    )
                }
            }
            .addOnFailureListener {
                Toast.makeText(requireContext(), "Failed to fetch user data", Toast.LENGTH_SHORT).show()
            }
    }

    override fun onResume() {
        super.onResume()
        fetchUserData() // Refresh data when returning from Edit Profile
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}