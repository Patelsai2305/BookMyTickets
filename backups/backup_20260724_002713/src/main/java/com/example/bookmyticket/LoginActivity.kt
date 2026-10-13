package com.example.bookmyticket

import android.content.Intent
import android.os.Bundle
import android.util.Patterns
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.example.bookmyticket.databinding.ActivityLoginBinding
import com.example.bookmyticket.utils.SystemUtils
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore

class LoginActivity : AppCompatActivity() {

    private lateinit var binding: ActivityLoginBinding
    private lateinit var auth: FirebaseAuth

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        SystemUtils.setImmersiveMode(this)

        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        auth = FirebaseAuth.getInstance()

        // Login Button
        binding.btnLogin.setOnClickListener {
            loginUser()
        }

        // Register
        binding.tvRegister.setOnClickListener {
            startActivity(Intent(this, RegisterActivity::class.java))
        }

        // Forgot Password
        binding.tvForgotPassword.setOnClickListener {
            startActivity(Intent(this, ForgotPasswordActivity::class.java))
        }
    }

    private fun loginUser() {

        val email = binding.etEmail.text.toString().trim()
        val password = binding.etPassword.text.toString().trim()

        if (email.isEmpty()) {
            binding.etEmail.error = "Enter Email"
            binding.etEmail.requestFocus()
            return
        }

        if (!Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            binding.etEmail.error = "Invalid Email"
            binding.etEmail.requestFocus()
            return
        }

        if (password.isEmpty()) {
            binding.etPassword.error = "Enter Password"
            binding.etPassword.requestFocus()
            return
        }

        if (password.length < 6) {
            binding.etPassword.error = "Password must be at least 6 characters"
            binding.etPassword.requestFocus()
            return
        }

        binding.btnLogin.isEnabled = false
        binding.btnLogin.text = "Signing In..."

        auth.signInWithEmailAndPassword(email, password)
            .addOnCompleteListener(this) { task ->

                binding.btnLogin.isEnabled = true
                binding.btnLogin.text = "LOGIN"

                if (task.isSuccessful) {

                    val user = auth.currentUser

                    user?.reload()?.addOnCompleteListener {

                        if (user.isEmailVerified) {

                            Toast.makeText(
                                this,
                                "Welcome ${user.email}",
                                Toast.LENGTH_SHORT
                            ).show()

                            checkUserRoleAndRedirect()

                        } else {

                            auth.signOut()

                            Toast.makeText(
                                this,
                                "Please verify your email first.",
                                Toast.LENGTH_LONG
                            ).show()

                        }

                    }

                } else {

                    Toast.makeText(
                        this,
                        task.exception?.message ?: "Login Failed",
                        Toast.LENGTH_LONG
                    ).show()

                }

            }

    }

    private fun checkUserRoleAndRedirect() {
        val uid = auth.currentUser?.uid ?: return
        FirebaseFirestore.getInstance().collection("Users").document(uid).get()
            .addOnSuccessListener { document ->
                if (document.exists()) {
                    val role = document.getString("role") ?: "user"
                    if (role == "admin") {
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
