# Prompt to Fix Web Showtimes & Seats

Use this prompt to fix the "No shows available" error and implement the seat selection logic exactly as it is in your Android app.

---

## The Fix Prompt

"The 'Available Showtimes' page is currently empty. Fix the **PHP/JavaScript** logic to match the Android app's 'Theatre Selection' and 'Seat Selection' flows using this exact data and logic:

### 1. Real-World Theatre Sync (Google Places API)
Stop using only static default theatres. Make the web version feel real by fetching actual nearby cinemas using the **Google Maps JavaScript API (Places Library)**, just like the Android app does in `TheatreActivity.kt`.

- **API Key**: Use the same Google API Key found in the app: `AIzaSyA6rBujIxYxb_1W2UacEVTa18EQ1sX9x3c`.
- **Implementation**:
  - Load the Google Maps JS library with `libraries=places`.
  - Use `navigator.geolocation` to get the user's current coordinates.
  - Use `google.maps.places.PlacesService.nearbySearch` with `type: ['movie_theater']` and a `radius` of 10,000 meters (10km).
- **Fallback**: If Google fails or the user denies location, fallback to the static Surat theatres list (Inox VR Mall, Cinepolis Imperial, etc.).
- **Sorting**: Sort results by distance (Nearest first). Show the address and distance on each card.

### 2. Showtimes Logic
- **Today's Filter**: If the user selects 'Today', only show timings that are **at least 30 minutes in the future** from the current system time.
- **Dynamic Title**: The heading should be 'Available Showtimes for [Actual Movie Title]' (pass the movie title via URL parameter like `showtimes.php?movie_id=...&title=...`).

### 3. Seat Selection (The 8x8 Grid) - FIX REQUIRED
The seat grid is currently not rendering. Implement this exact loop in JavaScript to generate the grid:
- **Grid Structure**: 6 Rows (A, B, C, D, E, F) x 8 Columns (1-8).
- **Generation Logic**:
  ```javascript
  const rows = ['A', 'B', 'C', 'D', 'E', 'F'];
  const gridContainer = document.getElementById('seats-grid'); // Ensure this ID exists in HTML
  gridContainer.innerHTML = ''; // Clear previous
  for (let row of rows) {
    for (let i = 1; i <= 8; i++) {
      const seatId = `${row}${i}`;
      const type = (row === 'A' || row === 'B') ? 'VIP' : (row === 'C' || row === 'D') ? 'Premium' : 'Normal';
      const price = (type === 'VIP') ? 450 : (type === 'Premium') ? 300 : 200;
      // Create seat element and append
      const seat = document.createElement('div');
      seat.className = 'seat ' + type.toLowerCase();
      seat.dataset.id = seatId;
      seat.dataset.price = price;
      gridContainer.appendChild(seat);
    }
  }
  ```
- **URL Parameter Fix**: Your screenshot shows `movieId=` is empty in the URL. Ensure parameters like `movieId` are correctly passed from the Theatre page to the Seat Selection page.
- **Firestore Sync (Atomic Locking)**:
  - Generate a `showtimeId`: `${movieId}_${theatreId}_${date}_${timing.replace(' ', '_')}`.
  - Use `onSnapshot` to listen to `db.collection("showtimes").doc(showtimeId)`.
  - The document contains a `bookedSeats` array. Update seat colors to Gray and disable them if they exist in this array.
  - **Payment Transaction**: When clicking "Pay & Book", use `runTransaction` to:
    1. Read the `showtimes` document.
    2. Check if selected seats are in `bookedSeats`.
    3. If free, append seats to `bookedSeats` and write the `Bookings` document simultaneously.

### 5. IMAGE FIX: Support Base64 & URLs
The movie images added from the Android App are stored as **raw Base64 strings** in Firestore. The web version must handle both TMDB URLs and these Base64 strings correctly.

- **The Logic**:
  ```javascript
  function getImageUrl(path) {
    if (!path) return 'placeholder.jpg';
    if (path.startsWith('http')) return path; // TMDB URL
    // Assume it's a raw Base64 string from the Android App
    return `data:image/jpeg;base64,${path}`;
  }
  ```
- **Action**: Update your movie card and banner templates to use this `getImageUrl` function for the `src` attribute of every `<img>` tag.

### 7. Admin UI & Recent Activity Table FIX
The "Recent Web & Mobile Activity" table is currently empty. Fix the data fetching and styling for the Admin Dashboard:

- **Data Fetching (Firestore)**:
  - Query the `Bookings` collection.
  - Order by `createdAt` descending.
  - Limit to the last 10-20 bookings.
  - **Mapping**:
    - `User`: Display the `userId` (or fetch `fullName` from `Users` collection using the ID).
    - `Movie`: Display `movieTitle`.
    - `Amount`: Display `totalPrice` formatted with ₹.
    - `Status`: Style the status chip (Green for 'Upcoming/Completed', Red for 'Cancelled').

- **UI/Layout Improvements**:
  - **Sidebar**: Make the sidebar consistent. Ensure icons are aligned and the active tab ('Dashboard') is highlighted in your primary Red (`#E50914`).
  - **Cards**: The cards are good, but ensure the "Net Earnings" color changes to Red if negative (like the current ₹-800) and Green if positive.
  - **Table Styling**:
    - Add padding to table cells.
    - Use a subtle border or background color for table rows on hover.
    - Ensure the text is vertically centered.

### 9. Admin: Add Movie Feature (MANDATORY)
Implement an "Add Movie" form in the Admin Panel that matches the Android app's `AddEditMovieActivity`.

- **TMDB Search Integration**:
  - Add a "Search TMDB" button next to the Title input.
  - When clicked, fetch movie data from `https://api.themoviedb.org/3/search/movie?api_key=12711b61d7c6fd67d82d3624be601459&query=[title]`.
  - **Auto-Fill**: Populate fields (Description, Rating, Release Date, Poster URL) automatically from the search result.

- **Image Upload (Base64)**:
  - Do NOT use Firebase Storage. The app stores images as Base64 strings.
  - When a user selects a Poster or Banner file in the form, use a JavaScript `FileReader` to convert it to a Base64 string and store it in the Firestore document's `posterUrl` and `bannerUrl` fields.

- **Firestore Document Structure**:
  - Collection: `Movies`
  - Fields: `title`, `description`, `genre`, `language`, `duration` (Int), `releaseDate`, `rating` (Double), `cast`, `director`, `producer`, `trailerUrl`, `status` ("Now Playing" or "Upcoming"), `posterUrl` (Base64), `bannerUrl` (Base64), `createdAt` (Long).
  - **Document ID**: Generate a random Firestore ID and save it into the `id` field of the movie object as well.

### 10. Secure Booking & Payment Flow (CRITICAL)
Match the new "Pay-then-Book" logic from the Android app to prevent ghost bookings.

- **The Flow**:
  1. User selects seats on `booking.php`.
  2. Clicking "Confirm" does NOT save to Firestore yet. Instead, redirect to `payment.php` passing seat details in a session or URL.
  3. `payment.php` shows a summary: Movie Title, Seats (e.g. A1, A2), and **Total Price**.
  4. Clicking "Pay & Book" initiates the Firestore write.
- **Firestore Write**:
  - Generate a 8-character unique `bookingId`.
  - Create a new document in the `Bookings` collection.
  - Set `status` to "Upcoming".
- **Real-time Lock**: The moment the payment is successful and the document is written, the seats will automatically turn Gray for all other users (App & Web) because of the `onSnapshot` listener implemented in Section 3.

---

## Technical Context
- **Firestore Collection**: `Bookings`
- **Field used for filtering seats**: `movieId`, `theatreId`, `date`, `timing`.
- **Field containing seat list**: `seats` (array of strings like `['A1', 'A2']`).
