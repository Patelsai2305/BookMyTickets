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
            when (it.itemId) {
                R.id.nav_home -> replaceFragment(HomeFragment())
                R.id.nav_ticket -> replaceFragment(TicketsFragment())
                R.id.nav_wishlist -> replaceFragment(WishlistFragment())
                R.id.nav_profile -> replaceFragment(ProfileFragment())
            }
            true
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
            firestore.collection("Users").document(uid)
                .update("nationality", selectedCountry)
                .addOnSuccessListener {
                    dialog.dismiss()
                    Toast.makeText(this, "Region set to $selectedCountry", Toast.LENGTH_SHORT).show()
                    val currentFragment = supportFragmentManager.findFragmentById(R.id.frameLayout)
                    if (currentFragment is HomeFragment) {
                        currentFragment.refreshMovies()
                    }
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