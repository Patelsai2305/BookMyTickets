package com.example.bookmyticket.utils

import android.app.Activity
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.WindowInsetsControllerCompat

object SystemUtils {

    /**
     * Hides the status bar and navigation bar in a "Sticky Immersive" way.
     * The bars will automatically re-hide after being shown by a swipe.
     */
    fun setImmersiveMode(activity: Activity) {
        val window = activity.window
        val decorView = window.decorView

        WindowCompat.setDecorFitsSystemWindows(window, false)
        val controller = WindowInsetsControllerCompat(window, decorView)
        
        // Hide both status bar and navigation bar
        controller.hide(WindowInsetsCompat.Type.statusBars() or WindowInsetsCompat.Type.navigationBars())
        
        // Behavior: Transient bars shown by swipe, then auto-hide
        controller.systemBarsBehavior = WindowInsetsControllerCompat.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE
    }
}
