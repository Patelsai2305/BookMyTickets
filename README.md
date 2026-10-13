# BookMyTicket Web + Android + Admin Firebase Sync

All clients use Firebase project `book-my-ticket-35ee6`.

## Canonical collections
- `Users` — users/admin role
- `Movies` / `movies` — catalogue
- `theatres` / `Theatres` / `cinemas` — theatres
- `showtimes` / `Showtimes` / `shows` / `Shows` — show data
- `seatLocks` — shared real-time seat state
- `bookingticket_app_web` — shared booking history for Web + Android + Admin
- `Payments` / `payments` — payment records

## Booking status
- `CONFIRMED` — active paid booking
- `COMPLETED` — show finished
- `CANCELLED` — cancelled booking retained in history

## Seat status
- `locked` — temporary 5-minute selection lock
- `booked` — permanently reserved until cancellation
- `cancelled` — free/reusable seat marker

## Deploy rules + indexes
From this folder:

```bash
firebase login
firebase use book-my-ticket-35ee6
firebase deploy --only firestore:rules,firestore:indexes
```

The composite indexes are project-wide. Web and Android share them automatically because they use the same Firestore database.
