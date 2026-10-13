# Implementation Plan - Remove Duplicate Users from Admin

The user is seeing duplicate users in the Admin panel. This is likely due to the app fetching users from multiple collections (`Users` and `users`) and the current logic not fully deduplicating them if they have different `uid` values but the same email, or other sync artifacts.

## Proposed Changes

### ViewModels

#### [MODIFY] [AdminViewModel.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/viewmodel/AdminViewModel.kt)
- **Deduplicate Users**: Update `startListening` to deduplicate the users list by `email` (case-insensitive) before posting it to `_users`. This ensures that even if a user exists in multiple collections with different IDs, they only appear once in the Admin panel.
- Update `totalUsers` count in `AdminStats` to reflect the deduplicated count.

## Verification Plan

### Automated Tests
- Clean build: `gradlew :app:assembleDebug`

### Manual Verification
- **Admin Users Screen**:
    - Verify that only one entry per user email is displayed.
    - Verify that the total user count in the dashboard matches the unique user count.
