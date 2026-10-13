package com.example.bookmyticket

import android.Manifest
import android.app.ProgressDialog
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.widget.ArrayAdapter
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.lifecycle.ViewModelProvider
import com.example.bookmyticket.databinding.ActivityAddEditMovieBinding
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.utils.ImageUtils
import com.example.bookmyticket.utils.SystemUtils
import com.example.bookmyticket.viewmodel.AdminViewModel
import com.google.android.material.datepicker.MaterialDatePicker
import com.google.android.material.snackbar.Snackbar
import java.text.SimpleDateFormat
import java.util.*

class AddEditMovieActivity : AppCompatActivity() {

    private lateinit var binding: ActivityAddEditMovieBinding
    private lateinit var viewModel: AdminViewModel
    private var movie: Movie? = null

    private var posterUri: Uri? = null
    private var bannerUri: Uri? = null

    private var pickingPoster = true

    private lateinit var progressDialog: ProgressDialog

    private val posterPicker = registerForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        uri?.let {
            posterUri = it
            ImageUtils.loadImage(binding.ivPosterPreview, it.toString())
            binding.ivPosterPreview.imageTintList = null
        }
    }

    private val bannerPicker = registerForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        uri?.let {
            bannerUri = it
            ImageUtils.loadImage(binding.ivBannerPreview, it.toString())
            binding.ivBannerPreview.imageTintList = null
        }
    }

    private val permissionLauncher = registerForActivityResult(ActivityResultContracts.RequestPermission()) { isGranted ->
        if (isGranted) {
            if (pickingPoster) posterPicker.launch("image/*") else bannerPicker.launch("image/*")
        } else {
            Toast.makeText(this, "Permission denied. Cannot pick image.", Toast.LENGTH_SHORT).show()
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        SystemUtils.setImmersiveMode(this)
        binding = ActivityAddEditMovieBinding.inflate(layoutInflater)
        setContentView(binding.root)

        viewModel = ViewModelProvider(this)[AdminViewModel::class.java]

        movie = intent.getParcelableExtra("movie")

        setupUI()
        observeViewModel()
    }

    private fun setupUI() {
        binding.toolbar.setNavigationOnClickListener { finish() }

        binding.btnSearchTMDB.setOnClickListener {
            val query = binding.etTitle.text.toString().trim()
            if (query.isNotEmpty()) {
                // UX Improvement: Show searching state
                binding.btnSearchTMDB.isEnabled = false
                binding.btnSearchTMDB.setText(R.string.searching)
                viewModel.searchTMDB(query)
            } else {
                Toast.makeText(this, "Enter a title to search", Toast.LENGTH_SHORT).show()
            }
        }

        val genres = arrayOf("Action", "Comedy", "Drama", "Horror", "Romance", "Sci-Fi", "Thriller")
        val languages = arrayOf("Hindi", "English", "Telugu", "Tamil", "Kannada", "Malayalam")
        val statuses = arrayOf("Now Playing", "Upcoming")

        binding.dropdownGenre.setAdapter(ArrayAdapter(this, android.R.layout.simple_list_item_1, genres))
        binding.dropdownLanguage.setAdapter(ArrayAdapter(this, android.R.layout.simple_list_item_1, languages))
        binding.dropdownStatus.setAdapter(ArrayAdapter(this, android.R.layout.simple_list_item_1, statuses))

        binding.etReleaseDate.setOnClickListener {
            val datePicker = MaterialDatePicker.Builder.datePicker()
                .setTitleText("Select Release Date")
                .setSelection(MaterialDatePicker.todayInUtcMilliseconds())
                .build()

            datePicker.addOnPositiveButtonClickListener { selection ->
                val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
                binding.etReleaseDate.setText(sdf.format(Date(selection)))
            }
            datePicker.show(supportFragmentManager, "DATE_PICKER")
        }

        binding.cardPoster.setOnClickListener {
            pickingPoster = true
            checkPermissionAndPickImage()
        }

        binding.cardBanner.setOnClickListener {
            pickingPoster = false
            checkPermissionAndPickImage()
        }

        binding.btnSave.setOnClickListener { validateAndSave() }

        progressDialog = ProgressDialog(this).apply {
            setMessage("Uploading to Firebase...")
            setCancelable(false)
        }

        if (movie != null) {
            setupEditMode()
        }
    }

    private fun checkPermissionAndPickImage() {
        val permission = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            Manifest.permission.READ_MEDIA_IMAGES
        } else {
            Manifest.permission.READ_EXTERNAL_STORAGE
        }

        if (ContextCompat.checkSelfPermission(this, permission) == PackageManager.PERMISSION_GRANTED) {
            if (pickingPoster) posterPicker.launch("image/*") else bannerPicker.launch("image/*")
        } else {
            permissionLauncher.launch(permission)
        }
    }

    private fun setupEditMode() {
        movie?.let {
            fillMovieDetails(it)
            binding.toolbar.title = "Edit Movie"
        }
    }

    private fun fillMovieDetails(it: Movie) {
        binding.etTitle.setText(it.title)
        binding.etDescription.setText(it.description)
        binding.dropdownGenre.setText(it.genre, false)
        binding.dropdownLanguage.setText(it.language, false)
        binding.etDuration.setText(it.duration.toString())
        binding.etReleaseDate.setText(it.releaseDate)
        binding.etRating.setText(it.rating.toString())
        binding.etCast.setText(it.cast)
        binding.etDirector.setText(it.director)
        binding.etProducer.setText(it.producer)
        binding.etTrailerUrl.setText(it.trailerUrl)
        binding.dropdownStatus.setText(it.status, false)

        if (!it.posterUrl.isNullOrEmpty()) {
            ImageUtils.loadImage(binding.ivPosterPreview, it.posterUrl)
            binding.ivPosterPreview.imageTintList = null
        }
        if (!it.bannerUrl.isNullOrEmpty()) {
            ImageUtils.loadImage(binding.ivBannerPreview, it.bannerUrl)
            binding.ivBannerPreview.imageTintList = null
        }
    }

    private fun validateAndSave() {
        val title = binding.etTitle.text.toString().trim()
        val desc = binding.etDescription.text.toString().trim()
        val genre = binding.dropdownGenre.text.toString().trim()
        val lang = binding.dropdownLanguage.text.toString().trim()
        val durationStr = binding.etDuration.text.toString().trim()
        val date = binding.etReleaseDate.text.toString().trim()
        val ratingStr = binding.etRating.text.toString().trim()
        val status = binding.dropdownStatus.text.toString().trim()

        // Robust validation
        if (title.isEmpty() || desc.isEmpty() || genre.isEmpty() || lang.isEmpty() ||
            durationStr.isEmpty() || date.isEmpty() || ratingStr.isEmpty() || status.isEmpty()) {
            Toast.makeText(this, "Please fill all required fields", Toast.LENGTH_SHORT).show()
            return
        }

        val updatedMovie = Movie(
            id = movie?.id ?: "",
            title = title,
            description = desc,
            genre = genre,
            language = lang,
            duration = durationStr.toIntOrNull() ?: 0,
            releaseDate = date,
            rating = ratingStr.toDoubleOrNull() ?: 0.0,
            cast = binding.etCast.text.toString().trim(),
            director = binding.etDirector.text.toString().trim(),
            producer = binding.etProducer.text.toString().trim(),
            trailerUrl = binding.etTrailerUrl.text.toString().trim(),
            status = status,
            posterUrl = movie?.posterUrl,
            bannerUrl = movie?.bannerUrl,
            createdAt = movie?.createdAt ?: System.currentTimeMillis()
        )

        android.util.Log.d("AddEditMovieActivity", "Saving Movie: title=$title, posterUri=$posterUri, bannerUri=$bannerUri")
        viewModel.saveMovieWithImages(this, updatedMovie, posterUri, bannerUri)
    }

    private fun observeViewModel() {
        viewModel.tmdbSearchResults.observe(this) { results ->
            // Restore button state
            binding.btnSearchTMDB.isEnabled = true
            binding.btnSearchTMDB.setText(R.string.search_tmdb)

            if (results.isNotEmpty()) {
                val importedMovie = results[0]
                movie?.let { existing ->
                    if (existing.id.isNotEmpty()) {
                        importedMovie.id = existing.id
                    }
                }
                this.movie = importedMovie
                fillMovieDetails(importedMovie)
                Toast.makeText(this, "Imported from TMDB", Toast.LENGTH_SHORT).show()
            } else {
                Toast.makeText(this, "No results found on TMDB", Toast.LENGTH_SHORT).show()
            }
        }

        viewModel.saveStatus.observe(this) { status ->
            when (status) {
                is AdminViewModel.SaveStatus.Loading -> {
                    progressDialog.show()
                    binding.btnSave.isEnabled = false
                }
                is AdminViewModel.SaveStatus.Success -> {
                    progressDialog.dismiss()
                    Snackbar.make(binding.root, status.message, Snackbar.LENGTH_SHORT).show()
                    binding.root.postDelayed({ finish() }, 1000)
                }
                is AdminViewModel.SaveStatus.Error -> {
                    progressDialog.dismiss()
                    binding.btnSave.isEnabled = true
                    Toast.makeText(this, status.message, Toast.LENGTH_LONG).show()
                }
            }
        }
    }
}
