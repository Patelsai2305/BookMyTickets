package com.example.bookmyticket

import android.content.Intent
import android.os.Bundle
import android.util.Patterns
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.example.bookmyticket.databinding.ActivityRegisterBinding
import com.example.bookmyticket.models.User
import com.example.bookmyticket.utils.SystemUtils
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.firestore.FirebaseFirestore

class RegisterActivity : AppCompatActivity() {

    private lateinit var binding: ActivityRegisterBinding

    private lateinit var auth: FirebaseAuth

    private lateinit var firestore: FirebaseFirestore

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        SystemUtils.setImmersiveMode(this)

        binding = ActivityRegisterBinding.inflate(layoutInflater)
        setContentView(binding.root)

        auth = FirebaseAuth.getInstance()
        firestore = FirebaseFirestore.getInstance()

        binding.btnRegister.setOnClickListener {
            registerUser()
        }

        binding.tvLogin.setOnClickListener {
            finish()
        }
    }

    private fun registerUser() {

        val fullName = binding.etName.text.toString().trim()
        val phone = binding.etPhone.text.toString().trim()
        val email = binding.etEmail.text.toString().trim()
        val password = binding.etPassword.text.toString().trim()
        val confirmPassword = binding.etConfirmPassword.text.toString().trim()

        if (fullName.isEmpty()) {
            binding.etName.error = "Enter Full Name"
            return
        }

        if (phone.length != 10) {
            binding.etPhone.error = "Enter Valid Phone Number"
            return
        }

        if (!Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            binding.etEmail.error = "Enter Valid Email"
            return
        }

        if (password.length < 6) {
            binding.etPassword.error = "Password must be at least 6 characters"
            return
        }

        if (password != confirmPassword) {
            binding.etConfirmPassword.error = "Passwords do not match"
            return
        }

        binding.btnRegister.isEnabled = false
        binding.btnRegister.text = "Creating Account..."

        auth.createUserWithEmailAndPassword(email, password)
            .addOnSuccessListener {

                val firebaseUser = auth.currentUser

                firebaseUser?.sendEmailVerification()

                val user = User(
                    uid = firebaseUser!!.uid,
                    fullName = fullName,
                    email = email,
                    phone = phone,
                    role = "user",
                    profileImage = "",
                    createdAt = System.currentTimeMillis()
                )

                firestore.collection("Users")
                    .document(firebaseUser.uid)
                    .set(user)
                    .addOnSuccessListener {

                        auth.signOut()

                        Toast.makeText(
                            this,
                            "Account Created Successfully.\nPlease verify your email.",
                            Toast.LENGTH_LONG
                        ).show()

                        startActivity(
                            Intent(
                                this,
                                LoginActivity::class.java
                            )
                        )

                        finish()

                    }
                    .addOnFailureListener {

                        binding.btnRegister.isEnabled = true
                        binding.btnRegister.text = "CREATE ACCOUNT"

                        Toast.makeText(
                            this,
                            it.message,
                            Toast.LENGTH_LONG
                        ).show()

                    }

            }
            .addOnFailureListener {

                binding.btnRegister.isEnabled = true
                binding.btnRegister.text = "CREATE ACCOUNT"

                Toast.makeText(
                    this,
                    it.message,
                    Toast.LENGTH_LONG
                ).show()

            }

    }
}