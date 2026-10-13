# Fix inheritance error in MovieApi.kt

The project fails to build because the `movie` class in `MovieApi.kt` attempts to inherit from the `Constants` object. In Kotlin, `object` declarations are compiled to `final` classes in Java, and inheritance from them is not permitted. Furthermore, the `movie` class wrapper is redundant as it only contains the `MovieApi` interface.

## Proposed Changes

### [Network]

#### [MODIFY] [MovieApi.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/network/MovieApi.kt)
- Remove the redundant `class movie : Constants {` wrapper.
- Move the `MovieApi` interface to the top level of the file.
- Correct the file structure to follow standard Retrofit interface patterns.

#### [MODIFY] [ApiClient.kt](file:///D:/BookMyTicket/app/src/main/java/com/example/bookmyticket/network/ApiClient.kt)
- Ensure it correctly references the top-level `MovieApi` interface. (It likely already does, but we'll verify if any qualified names were used).

## Verification Plan

### Automated Tests
- Run `./gradlew :app:kaptDebugKotlin` to verify the build error is resolved.
- Run a full build `./gradlew assembleDebug` to ensure no regression.

### Manual Verification
- Verify that the IDE no longer shows errors in `MovieApi.kt` or `ApiClient.kt`.
