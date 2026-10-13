# Technical Specification: Auth, Firestore & Base64 Strategy

This document provides a deep dive into the core technical pillars of the "Book My Ticket" ecosystem. Use this to ensure your Android app and Web Twin are perfectly aligned.

---

## 1. Authentication & Role-Based Access
Every user in the system is managed via **Firebase Authentication** and a corresponding document in the **`Users`** collection.

### Auth Flow
- **Registration**: User signs up with Email/Password. A document is created in `Users` with `role: "user"`.
- **Email Verification**: Required for login. Handled via `firebaseUser.sendEmailVerification()`.
- **Login Redirect**:
  - The app fetches the user document from Firestore.
  - If `role == "admin"`, the **Admin Panel** opens.
  - If `role == "user"`, the **Main Home** opens.

### Firestore Security Rules (Essential)
To allow the Admin to see all data while keeping user data private:
```javascript
function isAdmin() {
  return request.auth != null && get(/databases/$(database)/documents/Users/$(request.auth.uid)).data.role == 'admin';
}
// Apply isAdmin() to 'read' on Bookings and Users collections.
```

---

## 2. Firestore Database Schema
The database uses three primary collections. All field names are **case-sensitive**.

### `Users` Collection
- `uid`: String (Primary Key)
- `fullName`: String
- `email`: String
- `phone`: String
- `role`: String ("admin" or "user")
- `createdAt`: Long (Timestamp)

### `Movies` Collection
- `id`: String (Firestore Doc ID)
- `title`: String
- `description`: String
- `posterUrl`: String (**Base64** or **TMDB URL**)
- `bannerUrl`: String (**Base64** or **TMDB URL**)
- `rating`: Double
- `duration`: Int (Minutes)
- `status`: String ("Now Playing" or "Upcoming")

### `Bookings` Collection
- `bookingId`: String
- `userId`: String
- `movieId`: String
- `theatreId`: String
- `date`: String (YYYY-MM-DD)
- `timing`: String
- `seats`: Array of Strings (e.g., `["A1", "A2"]`)
- `totalPrice`: Int
- `status`: String ("Upcoming" or "Cancelled")

---

## 3. The Base64 Image Strategy
To avoid using Firebase Storage (saving costs and simplifying sync), we store images as text.

### Conversion (App/Web to Firestore)
1.  **Scale**: Images are resized to a maximum of **500px**.
2.  **Compress**: Compressed to **70% quality** JPEG.
3.  **Encode**: Converted to a standard **Base64 string**.
4.  **Store**: Saved directly into the `posterUrl` or `bannerUrl` fields in Firestore.

### Rendering (Firestore to App/Web)
The renderer must be "Hybrid" to support both local uploads and global TMDB images:
- **IF** the string starts with `http`: Load as a normal URL.
- **ELSE**: Prepend `data:image/jpeg;base64,` to the string and render as a data URI.

---

## 4. Real-time Synchronization
The system uses **Snapshot Listeners** (Flows in Kotlin, `onSnapshot` in JS) to ensure that:
- **Admin Stats**: Update instantly when a new booking is made.
- **Seat Selection**: Seats turn gray the moment someone else books them on any device.
- **Movie List**: New movies appear on the home screen immediately after an Admin adds them.
