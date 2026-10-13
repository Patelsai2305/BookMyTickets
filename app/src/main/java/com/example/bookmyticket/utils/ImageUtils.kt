package com.example.bookmyticket.utils

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.net.Uri
import android.util.Base64
import java.io.ByteArrayOutputStream

object ImageUtils {

    private const val TMDB_IMAGE_BASE = "https://image.tmdb.org/t/p/w500"

    /**
     * Converts a Uri to a compressed Base64 string.
     * Shrinks image to max 500px and compresses to 70% quality to fit Firestore limits.
     */
    fun uriToBase64(context: Context, uri: Uri): String? {
        return try {
            val inputStream = context.contentResolver.openInputStream(uri)
            val originalBitmap = BitmapFactory.decodeStream(inputStream)
            inputStream?.close()

            if (originalBitmap == null) return null

            // 1. Scale down the image (Max 500px width/height)
            val scaledBitmap = scaleBitmap(originalBitmap, 500)

            // 2. Compress to JPEG
            val outputStream = ByteArrayOutputStream()
            scaledBitmap.compress(Bitmap.CompressFormat.JPEG, 70, outputStream)
            val byteArray = outputStream.toByteArray()

            // 3. Encode to Base64 (NO_WRAP to keep it as one long string for Web/Firestore)
            Base64.encodeToString(byteArray, Base64.NO_WRAP)
        } catch (e: Exception) {
            android.util.Log.e("ImageUtils", "Error converting image to Base64: ${e.message}")
            null
        }
    }

    private fun scaleBitmap(bitmap: Bitmap, maxSize: Int): Bitmap {
        var width = bitmap.width
        var height = bitmap.height

        val bitmapRatio = width.toFloat() / height.toFloat()
        if (bitmapRatio > 1) {
            width = maxSize
            height = (maxSize / bitmapRatio).toInt()
        } else {
            height = maxSize
            width = (maxSize * bitmapRatio).toInt()
        }
        return Bitmap.createScaledBitmap(bitmap, width, height, true)
    }

    /**
     * Loads an image into an ImageView. Handles URLs, TMDB paths, and Base64 strings.
     * Logic matches web's hybrid strategy.
     */
    fun loadImage(view: android.widget.ImageView, path: String?, fallbackResId: Int = com.example.bookmyticket.R.drawable.ic_movie) {
        if (path.isNullOrEmpty()) {
            view.setImageResource(fallbackResId)
            return
        }

        val request = com.bumptech.glide.Glide.with(view.context)
            .applyDefaultRequestOptions(
                com.bumptech.glide.request.RequestOptions()
                    .placeholder(fallbackResId)
                    .error(fallbackResId)
            )

        when {
            path.startsWith("http") || path.startsWith("data:image/") || 
            path.startsWith("content://") || path.startsWith("file://") -> {
                request.load(path).into(view)
            }
            // TMDB paths are short (usually ~30 chars). Base64 images are long (> 500 chars).
            path.startsWith("/") && path.length < 100 -> {
                // TMDB Relative Path
                request.load(TMDB_IMAGE_BASE + path).into(view)
            }
            // If it's long, or short but doesn't start with /, try Base64
            path.length >= 20 && isBase64(path) -> {
                try {
                    val imageBytes = Base64.decode(path, Base64.DEFAULT)
                    request.load(imageBytes).into(view)
                } catch (e: Exception) {
                    view.setImageResource(fallbackResId)
                }
            }
            else -> {
                view.setImageResource(fallbackResId)
            }
        }
    }

    private fun isBase64(s: String): Boolean {
        return try {
            Base64.decode(s, Base64.DEFAULT)
            true
        } catch (e: Exception) {
            false
        }
    }
}
