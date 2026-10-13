package com.example.bookmyticket

import android.animation.ObjectAnimator
import android.content.Intent
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.animation.AccelerateDecelerateInterpolator
import androidx.appcompat.app.AppCompatActivity
import com.example.bookmyticket.databinding.ActivitySplashBinding
import com.example.bookmyticket.utils.SystemUtils
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore

class SplashActivity : AppCompatActivity() {

    private lateinit var binding: ActivitySplashBinding
    private lateinit var auth: FirebaseAuth

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        SystemUtils.setImmersiveMode(this)

        binding = ActivitySplashBinding.inflate(layoutInflater)
        setContentView(binding.root)

        auth = FirebaseAuth.getInstance()

        startAnimations()

        Handler(Looper.getMainLooper()).postDelayed({

            if (auth.currentUser == null) {

                startActivity(
                    Intent(
                        this,
                        LoginActivity::class.java
                    )
                )

            } else {

                checkUserRoleAndRedirect()

            }

        }, 3000)
    }

    private fun startAnimations() {

        binding.logoImage.alpha = 0f
        binding.appName.alpha = 0f
        binding.tagLine.alpha = 0f

        binding.logoImage.animate()
            .alpha(1f)
            .rotation(360f)
            .setDuration(1500)
            .start()

        binding.appName.animate()
            .alpha(1f)
            .setDuration(2000)
            .start()

        binding.tagLine.animate()
            .alpha(1f)
            .setDuration(2500)
            .start()

        ObjectAnimator.ofFloat(
            binding.logoImage,
            "scaleX",
            1f,
            1.08f,
            1f
        ).apply {
            duration = 2000
            repeatCount = ObjectAnimator.INFINITE
            interpolator = AccelerateDecelerateInterpolator()
            start()
        }

        ObjectAnimator.ofFloat(
            binding.logoImage,
            "scaleY",
            1f,
            1.08f,
            1f
        ).apply {
            duration = 2000
            repeatCount = ObjectAnimator.INFINITE
            interpolator = AccelerateDecelerateInterpolator()
            start()
        }
    }

    private fun checkUserRoleAndRedirect() {
        val uid = auth.currentUser?.uid ?: run {
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
            return
        }
        
        FirebaseFirestore.getInstance().collection("Users").document(uid).get()
            .addOnSuccessListener { document ->
                if (document.exists()) {
                    val user = document.toObject(com.example.bookmyticket.models.User::class.java)
                    if (user?.isUserAdmin() == true) {
                        startActivity(Intent(this, AdminHomeActivity::class.java))
                    } else {
                        startActivity(Intent(this, HomeActivity::class.java))
                    }
                } else {
                    startActivity(Intent(this, HomeActivity::class.java))
                }
                finish()
            }
            .addOnFailureListener {
                startActivity(Intent(this, HomeActivity::class.java))
                finish()
            }
    }
}
