# Implementation Plan - Align App with Firestore Rules & Fix Data Visibility

The user reported that data is not showing in the app. Based on the provided Firestore rules, there are several inconsistencies between the app's data access patterns and the required security logic. Additionally, a bug in `HomeActivity` is demoting admin users to normal users, causing `PERMISSION_DENIED` errors on the Admin Dashboard.

## User Review Required

> [!IMPORTANT]
> I am moving the app to a "Multi-Collection" mode for certain data types (Movies, Bookings, Users) to match the legacy and hybrid structure described in the rules. This ensures data created on either the Web or Android platforms is visible.

## Proposed Changes

### 1. Fix Admin Demotion

#### [MODIFY] [HomeActivity.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/HomeActivity.kt)
- Update `showNationalityDialog` to NOT overwrite `role` and `isAdmin` if the user is already an admin.
- The app will now check the existing document before updating.

### 2. Robust Admin Checks

#### [MODIFY] [User.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/models/User.kt)
- Update `createdAtLong` and add a new `isUserAdmin` helper that checks both `role == "admin"` and `isAdmin` (Boolean or String "true") to match the Firestore `isAdmin()` rule exactly.

#### [MODIFY] [SplashActivity.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/SplashActivity.kt)
- Update role check logic to use the new robust helper.

### 3. Multi-Collection Repositories

#### [MODIFY] [MovieRepository.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/repository/MovieRepository.kt)
- Update `getMoviesFlow` to combine snapshots from both `Movies` and `movies` collections using `kotlinx.coroutines.flow.combine`.

#### [MODIFY] [BookingRepository.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/repository/BookingRepository.kt)
- Update `getBookingsFlow` to combine snapshots from `bookingticket_app_web`, `Bookings`, and `bookings`.

#### [MODIFY] [UserRepository.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/repository/UserRepository.kt)
- Update `getUsersFlow` to combine snapshots from `Users` and `users`.

### 4. Field Mapping Correction

#### [MODIFY] [Booking.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/models/Booking.kt)
- Change `@PropertyName("stability")` back to `status` to align with the provided rules.

## Verification Plan

### Automated Tests
- Perform a Gradle Sync.
- Build the application: `gradlew assembleDebug`.

### Manual Verification
- Log in as an admin.
- Verify that the Dashboard now displays data from all merged collections.
- Verify that setting the nationality does NOT demote the admin.
- Check Logcat for "Mapping error" messages to identify any documents with incompatible structures.
