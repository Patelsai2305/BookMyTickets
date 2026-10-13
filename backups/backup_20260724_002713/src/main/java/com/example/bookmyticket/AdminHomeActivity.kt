package com.example.bookmyticket

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import com.example.bookmyticket.databinding.ActivityAdminHomeBinding
import com.example.bookmyticket.utils.SystemUtils
import com.google.firebase.auth.FirebaseAuth

class AdminHomeActivity : AppCompatActivity() {

    private lateinit var binding: ActivityAdminHomeBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityAdminHomeBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        SystemUtils.setImmersiveMode(this)

        setupBottomNavigation()

        binding.btnLogout.setOnClickListener {
            FirebaseAuth.getInstance().signOut()
            startActivity(Intent(this, LoginActivity::class.java))
            finishAffinity()
        }

        // Default fragment
        if (savedInstanceState == null) {
            replaceFragment(AdminStatsFragment())
        }
    }

    private fun setupBottomNavigation() {
        binding.bottomNavigation.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_admin_stats -> replaceFragment(AdminStatsFragment())
                R.id.nav_admin_movies -> replaceFragment(AdminMoviesFragment())
                R.id.nav_admin_users -> replaceFragment(AdminUsersFragment())
                R.id.nav_admin_bookings -> replaceFragment(AdminBookingsFragment())
                R.id.nav_admin_payments -> replaceFragment(AdminPaymentsFragment())
            }
            true
        }
    }

    private fun replaceFragment(fragment: Fragment) {
        supportFragmentManager.beginTransaction()
            .replace(R.id.nav_host_fragment, fragment)
            .commit()
    }
}