# Walkthrough - Atomic Seat Locking & Integrity

I have implemented a robust, atomic seat-locking mechanism to ensure that no two users (on App or Web) can ever book the same seat at the same time.

## Changes Made

### 1. Showtime "Source of Truth"
I introduced a dedicated `showtimes` collection in Firestore.
- Every unique show (Movie + Theatre + Date + Time) has its own document.
- This document contains a `bookedSeats` array which is the **master list** of all reserved seats.
- Listening to this single document in [SeatSelectionActivity.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/SeatSelectionActivity.kt) is much faster and more accurate than querying all bookings.

### 2. Atomic Transactions in Payment
Updated [PaymentActivity.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/PaymentActivity.kt) to use **Firestore Transactions**.
- **Security Check**: Before the payment is finalized, the app reads the `showtimes` document.
- **Race Condition Protection**: If another user (even on the Web) books the same seat during your payment process, the transaction will automatically fail and notify you.
- **Double Write**: Upon success, it updates the `showtimes` master list and creates your `Bookings` record simultaneously.

### 3. Web-App Sync Alignment
Updated the web prompts in [web_fix_prompt.artifact.md](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/.artifacts/39b6f8ba-7a44-4c8b-80ab-a1428fe79f11/web_fix_prompt.artifact.md) to use this same `showtimes` logic.
- This ensures that a seat locked on the Web instantly blocks the App, and vice versa, using an identical ID system and transaction rules.

## How to Verify
1. **Real-time Lock**: Open a show in the app and the same show on the web.
2. **Book on App**: Complete a payment on the phone.
3. **Observe Web**: You will see those seats turn **Gray** on the website instantly.
4. **Collision Test**: Try to pay for the same seat on both platforms at once. One will succeed, and the other will show a "Seats already taken" error.

> [!TIP]
> This "Showtimes" approach makes your database much more scalable as your app grows, as it avoids expensive complex queries during the critical booking step.
