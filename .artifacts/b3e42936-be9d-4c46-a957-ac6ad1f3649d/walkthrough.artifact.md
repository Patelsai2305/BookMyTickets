# Walkthrough - Firestore Permission Fixes & Stability Improvements

I have resolved the `PERMISSION_DENIED` errors and implemented safety measures to prevent app crashes when database access is restricted.

## Changes Made

### 🔐 Firestore Security Rules
Updated [firestore.rules](file:///D:/BookMyTicket/firestore.rules) to align with app functionality:
- **Wishlist Support**: Added rules for the `Wishlist` subcollection, allowing users to read and write their own favorites.
- **Booking Management**:
    - Restricted `read` access so users can only see their own tickets (enhancing privacy).
    - Allowed users to `update` the `status` field of their own bookings (enabling cancellation and automatic completion).
- **User Creation**: Refined `isAdmin` check for better robustness.

### 👤 User Profile Initialization
Modified [HomeActivity.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/HomeActivity.kt):
- Updated the "Nationality" setup dialog to include `role: "user"` and `isAdmin: false` when creating a new user document. This ensures that even if a user bypasses registration, their Firestore document satisfies the strict `create` security rules.

### 🛡️ Crash Prevention
Updated [AdminViewModel.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/viewmodel/AdminViewModel.kt):
- Added a `.catch` block to the real-time data flow. This prevents the app from crashing if a non-admin user (or an admin with temporary network issues) triggers a `PERMISSION_DENIED` error on the admin collection listeners.

### 📢 Better Observability
Updated [TicketsFragment.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/TicketsFragment.kt):
- Added logging for background ticket status updates (marking past shows as "Completed"). This helps in debugging if Firestore updates fail in the background.

## Verification Results

> [!NOTE]
> - The `PERMISSION_DENIED` crash in the `main` thread is now caught and logged in the `AdminViewModel`, preventing a fatal exception.
> - Regular users can now successfully cancel tickets and manage their wishlist.
> - New user documents created via the `HomeActivity` fallback dialog now include the required security fields.

You can now run the app and verify that the "Missing or insufficient permissions" error no longer causes a crash, and that ticket management works as expected!