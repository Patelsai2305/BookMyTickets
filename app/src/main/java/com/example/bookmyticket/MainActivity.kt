package com.example.bookmyticket

import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.example.bookmyticket.utils.SystemUtils

class MainActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        SystemUtils.setImmersiveMode(this)
        setContentView(R.layout.activity_main)
    }
}