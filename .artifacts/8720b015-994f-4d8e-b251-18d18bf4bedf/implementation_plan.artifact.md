# Fix IllegalFormatConversionException in Price Formatting

The application is crashing with a `java.util.IllegalFormatConversionException` because `Double` values (specifically `totalPrice` from the `Booking` model) are being passed to string resources that expect an integer (`%d`).

## User Review Required

> [!IMPORTANT]
> The fix involves converting `Double` prices to `Int` before formatting. If fractional prices (e.g., ₹100.50) are required in the future, the string resources should be updated to use `%.2f` instead of `%d`. For now, I will stick to the existing integer format to maintain consistency with `AdminStatsFragment`.

## Proposed Changes

### Adapters

#### [MODIFY] [AdminBookingAdapter.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/adapter/AdminBookingAdapter.kt)
- Convert `booking.totalPrice` to `Int` before passing it to `context.getString(R.string.price_format_total, ...)`.
- Remove the redundant `try-catch` block around the price formatting logic.

#### [MODIFY] [AdminPaymentAdapter.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/adapter/AdminPaymentAdapter.kt)
- Convert `payment.totalPrice` to `Int` before passing it to `context.getString(R.string.amount_paid_format, ...)`. This is the likely source of the fatal crash as it lacks a `try-catch`.

## Verification Plan

### Automated Tests
- I will check if the project builds successfully after the changes using `./gradlew assembleDebug`.

### Manual Verification
- The user should verify that the Admin Booking and Admin Payment screens no longer crash and display the prices correctly as integers.
