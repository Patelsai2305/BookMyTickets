package com.example.bookmyticket.utils

import com.google.firebase.Timestamp
import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.FieldValue
import com.google.firebase.firestore.FirebaseFirestoreException

/**
 * Book My Ticket
 *
 * Shared seat locking system for Android App.
 *
 * Firestore:
 *
 * seatLocks/{showKey}__{seatId}
 *
 * Temporary lock = 5 minutes
 * Booked lock = permanent
 */

object SeatLock {

    private val db = FirebaseFirestore.getInstance()

    // Temporary seat lock = 5 minutes
    private const val LOCK_DURATION_SECONDS = 5 * 60L


    // ============================================================
    // MAKE SAFE KEY
    // ============================================================

    private fun keyPart(value: String?): String {

        val cleaned = (value ?: "")
            .lowercase()
            .replace(
                Regex("\\([^)]*\\)"),
                ""
            )
            .replace(
                Regex("[^a-z0-9]+"),
                "-"
            )
            .trim('-')

        return cleaned.ifEmpty {
            "x"
        }
    }


    // ============================================================
    // BUILD SHOW KEY
    // ============================================================

    fun buildShowKey(
        movieId: String?,
        movieTitle: String?,
        theatreId: String?,
        theatreName: String?,
        date: String?,
        time: String?
    ): String {

        val movieKey =
            if (!movieId.isNullOrBlank()) {
                keyPart(movieId)
            } else {
                keyPart(movieTitle)
            }


        val theatreKey =
            if (!theatreName.isNullOrBlank()) {
                keyPart(theatreName)
            } else {
                keyPart(theatreId)
            }


        return "${movieKey}__${theatreKey}__${keyPart(date)}__${keyPart(time)}"
    }


    // ============================================================
    // BUILD SEAT LOCK DOCUMENT ID
    // ============================================================

    fun buildSeatLockId(
        showKey: String,
        seatId: String
    ): String {

        return "${showKey}__${keyPart(seatId)}"
    }


    // ============================================================
    // CREATE TEMPORARY LOCK
    // ============================================================

    fun createTempLock(
        userId: String,
        showKey: String,
        seatId: String,
        onSuccess: () -> Unit = {},
        onFailure: (Exception) -> Unit = {}
    ) {

        if (userId.isBlank()) {

            onFailure(
                Exception("User is not logged in.")
            )

            return
        }


        if (showKey.isBlank()) {

            onFailure(
                Exception("Invalid show.")
            )

            return
        }


        if (seatId.isBlank()) {

            onFailure(
                Exception("Invalid seat.")
            )

            return
        }


        val lockId =
            buildSeatLockId(
                showKey,
                seatId
            )


        val lockRef =
            db.collection("seatLocks")
                .document(lockId)


        /*
         * Transaction prevents two users from taking
         * the same seat at the same time.
         */

        db.runTransaction { transaction ->

            val document =
                transaction.get(lockRef)


            // ====================================================
            // EXISTING DOCUMENT
            // ====================================================

            if (document.exists()) {

                val existingUser =
                    document.getString("userId")

                val status =
                    document.getString("status")

                val expiresAt =
                    document.getTimestamp("expiresAt")


                // ------------------------------------------------
                // ALREADY OWNED BY THIS USER
                // ------------------------------------------------

                if (
                    existingUser == userId &&
                    status == "locked"
                ) {

                    return@runTransaction null
                }


                // ------------------------------------------------
                // PERMANENTLY BOOKED
                // ------------------------------------------------

                if (status == "booked") {

                    throw FirebaseFirestoreException(
                        "Seat $seatId is already booked.",
                        FirebaseFirestoreException.Code.ALREADY_EXISTS
                    )
                }


                // ------------------------------------------------
                // TEMPORARY LOCK
                // ------------------------------------------------

                if (status == "locked") {

                    val now =
                        Timestamp.now()


                    // Lock still active
                    if (
                        expiresAt != null &&
                        expiresAt.seconds > now.seconds
                    ) {

                        throw FirebaseFirestoreException(
                            "Seat $seatId is currently locked by another user.",
                            FirebaseFirestoreException.Code.ALREADY_EXISTS
                        )
                    }

                    /*
                     * Lock expired.
                     *
                     * We continue below and replace it
                     * with the current user's lock.
                     */
                }
            }


            // ====================================================
            // CREATE NEW LOCK
            // OR REPLACE EXPIRED LOCK
            // ====================================================

            val expiresAt =
                Timestamp(
                    System.currentTimeMillis() / 1000
                            + LOCK_DURATION_SECONDS,
                    0
                )


            val lockData =
                hashMapOf<String, Any>(

                    "showKey" to showKey,

                    "seatId" to seatId,

                    "userId" to userId,

                    "status" to "locked",

                    "lockedAt" to FieldValue.serverTimestamp(),

                    "expiresAt" to expiresAt
                )


            transaction.set(
                lockRef,
                lockData
            )


            null

        }.addOnSuccessListener {

            onSuccess()

        }.addOnFailureListener { error ->

            onFailure(error)
        }
    }


    // ============================================================
    // RELEASE TEMPORARY LOCK
    // ============================================================

    fun releaseTempLock(
        userId: String,
        showKey: String,
        seatId: String,
        onSuccess: () -> Unit = {},
        onFailure: (Exception) -> Unit = {}
    ) {

        if (userId.isBlank()) {
            onFailure(Exception("User is not logged in."))
            return
        }

        val lockId = buildSeatLockId(showKey, seatId)

        val lockRef = db
            .collection("seatLocks")
            .document(lockId)

        db.runTransaction { transaction ->

            val document = transaction.get(lockRef)

            if (!document.exists()) {
                return@runTransaction null
            }

            val owner = document.getString("userId")
            val status = document.getString("status")

            if (owner != userId) {
                throw FirebaseFirestoreException(
                    "This seat belongs to another user.",
                    FirebaseFirestoreException.Code.PERMISSION_DENIED
                )
            }

            if (status == "booked") {
                throw FirebaseFirestoreException(
                    "This seat has already been booked.",
                    FirebaseFirestoreException.Code.FAILED_PRECONDITION
                )
            }

            transaction.delete(lockRef)

            null

        }.addOnSuccessListener {

            onSuccess()

        }.addOnFailureListener { error ->

            onFailure(error)
        }
    }


    // ============================================================
    // CHECK WHETHER USER OWNS ACTIVE LOCK
    // ============================================================

    fun checkLock(
        userId: String,
        showKey: String,
        seatId: String,
        onResult: (Boolean, String?) -> Unit
    ) {

        val lockId =
            buildSeatLockId(
                showKey,
                seatId
            )


        db.collection("seatLocks")
            .document(lockId)
            .get()
            .addOnSuccessListener { document ->

                if (!document.exists()) {

                    onResult(
                        false,
                        "Seat is not locked."
                    )

                    return@addOnSuccessListener
                }


                val owner =
                    document.getString("userId")

                val status =
                    document.getString("status")

                val expiresAt =
                    document.getTimestamp("expiresAt")


                // Wrong user
                if (owner != userId) {

                    onResult(
                        false,
                        "This seat belongs to another user."
                    )

                    return@addOnSuccessListener
                }


                // Must be locked
                if (status != "locked") {

                    onResult(
                        false,
                        "Seat is no longer available for payment."
                    )

                    return@addOnSuccessListener
                }


                // Check expiration
                if (expiresAt != null) {

                    val now =
                        Timestamp.now()


                    if (
                        expiresAt.seconds <= now.seconds
                    ) {

                        onResult(
                            false,
                            "Your seat lock has expired."
                        )

                        return@addOnSuccessListener
                    }
                }


                onResult(
                    true,
                    null
                )
            }
            .addOnFailureListener { error ->

                onResult(
                    false,
                    error.message
                )
            }
    }
}