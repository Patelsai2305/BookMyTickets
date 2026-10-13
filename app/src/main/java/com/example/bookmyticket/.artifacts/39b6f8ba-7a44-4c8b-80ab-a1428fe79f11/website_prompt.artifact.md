# Master Prompt for PHP Web Twin

Use this prompt to generate a web version of your app using **PHP, HTML, and CSS**. This ensures the site is a perfect "digital twin" of your Android app.

---

## The Master Prompt (PHP Version)

"Build a movie booking web application using **PHP**, **HTML**, and **CSS**, integrated with **Firebase (Firestore & Auth)**. This website must perfectly sync with an existing Android app using Firebase Project: `book-my-ticket-35ee6`.

### 1. Architecture: PHP + Firebase JS SDK
- **Frontend**: Standard HTML5 and Custom CSS. Use the **Firebase JS SDK (v9+)** for all database interactions (Auth, Firestore) to ensure real-time sync with the mobile app.
- **Backend**: Use PHP for page structure and session management.

### 2. Entity Logic (MANDATORY SYNC)
- **Movies**: Fetch from Firestore collection `Movies`. Display 'Now Playing' and 'Upcoming' sections.
  - **TMDB Sync**: Integrate TMDB API (Key: `12711b61d7c6fd67d82d3624be601459`) for fetching movie details.
- **Theatres & Location Sync (Google Places API)**:
  - Integrate **Google Maps JavaScript API (Places Library)** to fetch real-world cinemas nearby.
  - Use `navigator.geolocation` for user coordinates.
  - Perform a `nearbySearch` for `movie_theater` within a 10km radius.
  - Fallback to the static Surat theatres list if location is unavailable.
  - Automatically sort the theatre list by distance (nearest first).
- **Seat Booking (8x8 Grid)**:
  - Create an interactive grid using HTML/JS.
  - **Pricing**: VIP (A-B: ₹450), Premium (C-D: ₹300), Normal (E-F: ₹200).
  - **Sync**: Use `onSnapshot` in JS to disable seats in real-time based on the `Bookings` collection.
- **Admin Dashboard**:
  - Show stats: Total Users, Gross Revenue, Total Refunds, and **Net Earnings** (Revenue - Refunds).

### 3. UI/UX Design (Match the App)
- **Theme**: Dark Mode (`background: #121212`, `color: white`).
- **Accent Color**: Primary Red (`#E50914`).
- **Hero Banner**: Auto-sliding carousel.
  - **Hover/Single-Tap**: Updates the banner details without navigating.
  - **Double-Click**: Navigates to `details.php`.
- **Responsive**: Use CSS Media Queries to make it look like a native app on mobile and a dashboard on desktop.

### 4. Database Schema
Ensure all writes to Firestore match the Android app exactly:
- `Bookings`: `bookingId`, `userId`, `movieId`, `theatreId`, `date`, `timing`, `seats` (array), `totalPrice` (int), `status`.
- `Users`: `uid`, `fullName`, `email`, `role` (admin/user)."

---

## Technical Details
- **TMDB Key**: `12711b61d7c6fd67d82d3624be601459`
- **Firebase Config**: Use the credentials from your Firebase Console.
- **Seat Grid**: 8 columns x 6 rows (A through F).
