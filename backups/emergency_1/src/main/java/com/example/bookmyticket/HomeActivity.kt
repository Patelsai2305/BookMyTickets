package com.example.bookmyticket

import android.os.Bundle
import android.view.LayoutInflater
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.Spinner
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import com.example.bookmyticket.databinding.ActivityHomeBinding
import com.example.bookmyticket.utils.SystemUtils
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore

class HomeActivity : AppCompatActivity() {

    private lateinit var binding: ActivityHomeBinding
    private lateinit var firestore: FirebaseFirestore
    private lateinit var auth: FirebaseAuth
    private var pendingArguments: Bundle? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        SystemUtils.setImmersiveMode(this)

        binding = ActivityHomeBinding.inflate(layoutInflater)
        setContentView(binding.root)

        firestore = FirebaseFirestore.getInstance()
        auth = FirebaseAuth.getInstance()

        checkUserNationality()

        replaceFragment(HomeFragment())

        binding.bottomNavigation.setOnItemSelectedListener {
            val fragment = when (it.itemId) {
                R.id.nav_home -> HomeFragment()
                R.id.nav_ticket -> TicketsFragment()
                R.id.nav_wishlist -> WishlistFragment()
                R.id.nav_profile -> ProfileFragment()
                else -> HomeFragment()
            }

            pendingArguments?.let { args ->
                fragment.arguments = args
                pendingArguments = null
            }

            replaceFragment(fragment)
            true
        }
    }

    fun selectTab(itemId: Int, arguments: Bundle? = null) {
        pendingArguments = arguments
        if (binding.bottomNavigation.selectedItemId == itemId) {
            // Already on this tab, but we might need to refresh/update arguments
            // Triggers listener manually to handle fragment replacement with new args
            binding.bottomNavigation.post { 
                // Using post to ensure the listener is ready or if we're in the middle of a transaction
                binding.bottomNavigation.selectedItemId = itemId 
            }
        } else {
            binding.bottomNavigation.selectedItemId = itemId
        }
    }

    private fun checkUserNationality() {
        val user = auth.currentUser ?: return
        firestore.collection("Users").document(user.uid).get()
            .addOnSuccessListener { document ->
                val nationality = document.getString("nationality")
                if (nationality.isNullOrEmpty()) {
                    showNationalityDialog(user.uid)
                }
            }
    }

    private fun showNationalityDialog(uid: String) {
        val dialogView = LayoutInflater.from(this).inflate(R.layout.dialog_nationality, null)
        val spinner = dialogView.findViewById<Spinner>(R.id.spinnerCountry)
        val btnContinue = dialogView.findViewById<Button>(R.id.btnContinue)

        val countries = listOf("India", "USA", "UK", "Canada", "Australia")
        val adapter = ArrayAdapter(this, android.R.layout.simple_spinner_item, countries)
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        spinner.adapter = adapter

        val dialog = AlertDialog.Builder(this)
            .setView(dialogView)
            .setCancelable(false)
            .create()

        btnContinue.setOnClickListener {
            val selectedCountry = spinner.selectedItem.toString()
            val user = auth.currentUser
            
            if (user == null) {
                dialog.dismiss()
                return@setOnClickListener
            }

            // Visual feedback & prevent double-clicks
            btnContinue.isEnabled = false
            btnContinue.text = "Saving..."

            // Use set with merge to create the document if it doesn't exist.
            // We include default role fields to satisfy Firestore 'create' rules if the doc is missing.
            val userData = mutableMapOf<String, Any>(
                "uid" to user.uid,
                "email" to (user.email ?: ""),
                "nationality" to selectedCountry,
                "role" to "user",
                "isAdmin" to false
            )

            firestore.collection("Users").document(user.uid)
                .set(userData, com.google.firebase.firestore.SetOptions.merge())
                .addOnSuccessListener {
                    if (!isFinishing && !isDestroyed) {
                        dialog.dismiss()
                        Toast.makeText(this, "Region set to $selectedCountry", Toast.LENGTH_SHORT).show()
                        
                        // Find current fragment safely and refresh
                        val navHostFragment = supportFragmentManager.findFragmentById(R.id.frameLayout)
                        if (navHostFragment is HomeFragment) {
                            navHostFragment.refreshMovies()
                        }
                    }
                }
                .addOnFailureListener { e ->
                    btnContinue.isEnabled = true
                    btnContinue.text = "CONTINUE"
                    Toast.makeText(this, "Failed to save: ${e.message}", Toast.LENGTH_LONG).show()
                }
        }

        dialog.show()
    }

    private fun replaceFragment(fragment: Fragment) {
        supportFragmentManager
            .beginTransaction()
            .replace(R.id.frameLayout, fragment)
            .commit()
    }
}
