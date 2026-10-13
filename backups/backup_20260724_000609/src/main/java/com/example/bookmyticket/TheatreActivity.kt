package com.example.bookmyticket

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.location.Location
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.bookmyticket.adapter.TheatreAdapter
import com.example.bookmyticket.databinding.ActivityTheatreBinding
import com.example.bookmyticket.models.Movie
import com.example.bookmyticket.models.Theatre
import com.example.bookmyticket.utils.SystemUtils
import com.example.bookmyticket.repository.TheatreRepository
import com.google.android.gms.location.FusedLocationProviderClient
import com.google.android.gms.location.LocationServices
import com.google.android.libraries.places.api.Places
import com.google.android.libraries.places.api.model.Place
import com.google.android.libraries.places.api.net.PlacesClient
import com.google.android.libraries.places.api.net.SearchNearbyRequest

class TheatreActivity : AppCompatActivity() {

    private lateinit var binding: ActivityTheatreBinding
    private var movie: Movie? = null
    private lateinit var fusedLocationClient: FusedLocationProviderClient
    private lateinit var placesClient: PlacesClient
    private var userLocation: Location? = null
    private var originalTheatres = mutableListOf<Theatre>()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        SystemUtils.setImmersiveMode(this)
        binding = ActivityTheatreBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Initialize Places
        if (!Places.isInitialized()) {
            Places.initialize(applicationContext, "AIzaSyA6rBujIxYxb_1W2UacEVTa18EQ1sX9x3c")
        }
        placesClient = Places.createClient(this)

        movie = intent.getParcelableExtra<Movie>("movie")
        fusedLocationClient = LocationServices.getFusedLocationProviderClient(this)
        
        binding.toolbar.title = movie?.title ?: "Select Theatre"
        binding.toolbar.setNavigationOnClickListener { finish() }

        setupSearch()
        checkLocationPermission()
    }

    private fun setupSearch() {
        binding.searchTheatre.setOnQueryTextListener(object : androidx.appcompat.widget.SearchView.OnQueryTextListener {
            override fun onQueryTextSubmit(query: String?): Boolean {
                filterTheatres(query)
                return true
            }

            override fun onQueryTextChange(newText: String?): Boolean {
                filterTheatres(newText)
                return true
            }
        })
    }

    private fun filterTheatres(query: String?) {
        if (query.isNullOrEmpty()) {
            updateRecyclerView(originalTheatres)
        } else {
            val filtered = originalTheatres.filter { 
                it.name.contains(query, ignoreCase = true) || it.location.contains(query, ignoreCase = true)
            }
            updateRecyclerView(filtered)
        }
    }

    private fun checkLocationPermission() {
        if (ActivityCompat.checkSelfPermission(
                this,
                Manifest.permission.ACCESS_FINE_LOCATION
            ) != PackageManager.PERMISSION_GRANTED && ActivityCompat.checkSelfPermission(
                this,
                Manifest.permission.ACCESS_COARSE_LOCATION
            ) != PackageManager.PERMISSION_GRANTED
        ) {
            ActivityCompat.requestPermissions(
                this,
                arrayOf(Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION),
                1001
            )
            return
        }

        fusedLocationClient.lastLocation.addOnSuccessListener { location: Location? ->
            if (location != null) {
                userLocation = location
                fetchRealTheatres(location)
            } else {
                requestNewLocation()
            }
        }.addOnFailureListener {
            setupTheatres()
        }
    }

    private fun requestNewLocation() {
        // Simple fallback
        setupTheatres()
    }

    private fun fetchRealTheatres(location: Location) {
        val center = com.google.android.gms.maps.model.LatLng(location.latitude, location.longitude)
        val placeFields = listOf(Place.Field.ID, Place.Field.NAME, Place.Field.ADDRESS, Place.Field.LAT_LNG)
        val circularBounds = com.google.android.libraries.places.api.model.CircularBounds.newInstance(center, 10000.0) // 10km

        val searchNearbyRequest = SearchNearbyRequest.builder(circularBounds, placeFields)
            .setIncludedTypes(listOf("movie_theater"))
            .setMaxResultCount(20)
            .build()

        placesClient.searchNearby(searchNearbyRequest)
            .addOnSuccessListener { response ->
                val realTheatres = response.places.map { place ->
                    val distance = if (place.latLng != null) {
                        val theatreLoc = Location("").apply {
                            latitude = place.latLng!!.latitude
                            longitude = place.latLng!!.longitude
                        }
                        "%.1f km".format(location.distanceTo(theatreLoc) / 1000)
                    } else "N/A"

                    Theatre(
                        id = place.id ?: "",
                        name = place.name ?: "Unknown Theatre",
                        location = place.address ?: "",
                        distance = distance,
                        showTimings = listOf("10:00 AM", "01:30 PM", "04:45 PM", "09:00 PM"),
                        latitude = place.latLng?.latitude ?: 0.0,
                        longitude = place.latLng?.longitude ?: 0.0
                    )
                }
                
                if (realTheatres.isEmpty()) {
                    setupTheatres()
                } else {
                    originalTheatres = realTheatres.toMutableList()
                    updateRecyclerView(originalTheatres)
                    Toast.makeText(this, "Found ${realTheatres.size} real theatres near you", Toast.LENGTH_SHORT).show()
                }
            }
            .addOnFailureListener { exception ->
                android.util.Log.e("TheatreActivity", "Places API error: ${exception.message}")
                setupTheatres()
            }
    }

    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<out String>,
        grantResults: IntArray
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == 1001 && grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
            checkLocationPermission()
        } else {
            setupTheatres()
        }
    }

    private fun setupTheatres() {
        val rawTheatres = TheatreRepository.getStaticTheatres()

        originalTheatres = userLocation?.let { location ->
            rawTheatres.map { theatre ->
                val theatreLoc = Location("").apply {
                    latitude = theatre.latitude
                    longitude = theatre.longitude
                }
                val distanceInKm = location.distanceTo(theatreLoc) / 1000
                theatre.copy(distance = "%.1f km".format(distanceInKm))
            }.sortedBy { it.distance.replace(" km", "").toDouble() }.toMutableList()
        } ?: rawTheatres.toMutableList()

        updateRecyclerView(originalTheatres)
    }

    private fun updateRecyclerView(theatres: List<Theatre>) {
        binding.rvTheatres.layoutManager = LinearLayoutManager(this)
        binding.rvTheatres.adapter = TheatreAdapter(theatres) { theatre, timing ->
            val intent = Intent(this, SeatSelectionActivity::class.java)
            intent.putExtra("movie", movie)
            intent.putExtra("theatre", theatre)
            intent.putExtra("timing", timing)
            startActivity(intent)
        }
    }
}