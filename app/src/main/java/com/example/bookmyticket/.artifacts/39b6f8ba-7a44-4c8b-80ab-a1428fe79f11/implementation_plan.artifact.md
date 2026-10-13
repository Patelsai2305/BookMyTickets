# Implementation Plan - "Book My Ticket" Web Twin (PHP/HTML/CSS)

This plan updates the web twin project to use **PHP, HTML, and CSS** while maintaining a perfect sync with your Android app's Firebase backend.

## User Review Required

> [!IMPORTANT]
> **Firebase & PHP Integration**: Since PHP is server-side and your app needs real-time features (like seat booking and admin stats), I will design this as a **Hybrid App**.
> - **PHP**: Handles page routing, layouts, and server-side logic.
> - **Firebase JS SDK**: Handles real-time data sync, authentication, and the interactive seat selection directly in the browser.

> [!NOTE]
> I will provide a structure that is easy to host on any standard PHP server (XAMPP, WAMP, or any web host).

## Proposed Changes

### Web Project Structure (PHP)
I will create a `/web` folder with:
- `index.php`: The home page (Banner carousel + Movie lists).
- `details.php`: Movie synopsis and theatre selection.
- `booking.php`: The interactive 8x8 seat grid.
- `admin.php`: The dashboard for revenue and management.
- `auth.php`: Login and Registration.
- `/css`: Custom CSS files for your dark theme and red accents.
- `/js`: Firebase configuration and real-time listener scripts.

### Key Logic Implementation
1.  **Shared Database**: Every page will include a `firebase-config.js` to connect to your project: `book-my-ticket-35ee6`.
2.  **Dynamic PHP Templates**: Use PHP to dynamically generate movie cards by fetching data from Firestore (via JS) or the TMDB API.
3.  **Real-time Seats**: The `booking.php` page will use a JavaScript loop to generate the 8x8 grid and an `onSnapshot` listener to grey out booked seats instantly.

## Files to be Created

#### [NEW] [PHP Web Source](file:///D:/BookMyTicket/web/)
- Full source code for all `.php` files, CSS, and JS scripts.

## Verification Plan

### Manual Verification
1. **Local Setup**: You can place the `/web` folder in your `htdocs` (XAMPP) or `www` folder.
2. **Cross-Platform Sync**:
   - Book a seat on the PHP website -> Open the Android app -> Verify the seat is marked as "Booked" there too.
   - Change a movie price or title in the Admin PHP page -> Verify it updates in the Android app instantly.
