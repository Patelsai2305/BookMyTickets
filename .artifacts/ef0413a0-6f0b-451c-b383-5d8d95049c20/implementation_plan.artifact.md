# Implementation Plan - Fix Admin Dashboard Crash and Booking Sync

The app is experiencing two main issues in the Admin section:
1.  **Crash in AdminStatsFragment**: Caused by an `IllegalFormatConversionException` (formatting `Double` with `%d`).
2.  **Booking Sync Issues**: Bookings made in the app are not appearing correctly in the Admin Panel because of field name mismatches between the App and Web/Admin versions of the database.

## Proposed Changes

### [Component: UI]

#### [MODIFY] [AdminStatsFragment.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/AdminStatsFragment.kt)
- Convert `Double` values to `Long` before passing them to `getString(R.string.price_format, ...)` to avoid the formatting crash.

### [Component: Models]

#### [MODIFY] [Booking.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/models/Booking.kt)
- Add `@get:PropertyName` and `@set:PropertyName` annotations to ensure field names match what the Admin panel and Web expect:
    - `bookingId` -> `Booking ID`
    - `status` -> `status` (and possibly `stability` if that typo exists in the DB)
    - Ensure `createdAt` is handled consistently.

#### [MODIFY] [User.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/models/User.kt)
- Update property names to match Web/Admin schema:
    - `fullName` -> `name`
    - `createdAt` -> `created_at`
    - Explicitly map `isAdmin`.

### [Component: Logic]

#### [MODIFY] [PaymentActivity.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/PaymentActivity.kt)
- Ensure the app saves bookings using the correct field names (this will be handled automatically by the `@PropertyName` annotations in the model).

## Verification Plan

### Automated Tests
- Run `./gradlew :app:compileDebugKotlin` to verify the build.

### Manual Verification
1.  **Crash Test**: Open Admin Dashboard -> Stats tab. It should load without crashing.
2.  **Booking Test**:
    - Make a booking in the app.
    - Verify it appears in the **Bookings** tab of the Admin Panel.
    - Verify it shows the correct details (Title, Seats, Price).
3.  **Data Mapping**: Check Logcat to ensure `CustomClassMapper` warnings are gone.
