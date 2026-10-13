package com.example.bookmyticket

import android.os.Bundle
import android.util.Patterns
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.example.bookmyticket.databinding.ActivityForgotPasswordBinding
import com.example.bookmyticket.utils.SystemUtils
import com.google.firebase.auth.FirebaseAuth

class ForgotPasswordActivity : AppCompatActivity() {

    private lateinit var binding: ActivityForgotPasswordBinding
    private lateinit var auth: FirebaseAuth

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        SystemUtils.setImmersiveMode(this)

        binding = ActivityForgotPasswordBinding.inflate(layoutInflater)
        setContentView(binding.root)

        auth = FirebaseAuth.getInstance()

        binding.btnReset.setOnClickListener {
            resetPassword()
        }

        binding.tvBack.setOnClickListener {
            finish()
        }
    }

    private fun resetPassword() {

        val email = binding.etEmail.text.toString().trim()

        if (email.isEmpty()) {
            binding.etEmail.error = "Enter Email"
            return
        }

        if (!Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            binding.etEmail.error = "Invalid Email"
            return
        }

        binding.btnReset.isEnabled = false
        binding.btnReset.text = "Sending..."

        auth.sendPasswordResetEmail(email)
            .addOnSuccessListener {

                binding.btnReset.isEnabled = true
                binding.btnReset.text = "SEND RESET LINK"

                Toast.makeText(
                    this,
                    "Password reset email sent successfully.",
                    Toast.LENGTH_LONG
                ).show()

                finish()
            }
            .addOnFailureListener {

                binding.btnReset.isEnabled = true
                binding.btnReset.text = "SEND RESET LINK"

                Toast.makeText(
                    this,
                    it.localizedMessage ?: "Something went wrong.",
                    Toast.LENGTH_LONG
                ).show()
            }
    }
}