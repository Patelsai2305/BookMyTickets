<?php

$page_title =
    "Complete Payment - BookMyTicket";

include 'includes/header.php';

?>


<div class="container my-5">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">

            <div class="
                card
                bg-dark
                text-white
                border-secondary
                p-4
                shadow-lg
                rounded-4
            ">

                <h3 class="
                    text-center
                    fw-bold
                    mb-1
                    text-danger
                ">
                    Complete Your Payment
                </h3>


                <p class="
                    text-center
                    text-muted
                    small
                    mb-4
                ">
                    Review your booking below
                </p>


                <div id="bookingDetails">

                    <h5 id="movieTitle"
                        class="
                        fw-bold
                        text-danger
                        mb-3
                        ">
                        Loading...
                    </h5>


                    <div class="mb-2 text-secondary">

                        <i class="
                            bi
                            bi-building
                            me-2
                        "></i>

                        <span id="theatreName"
                              class="text-white">
                            Loading...
                        </span>

                    </div>


                    <div class="mb-2 text-secondary">

                        <i class="
                            bi
                            bi-calendar-event
                            me-2
                        "></i>

                        <span id="showDate"
                              class="text-white">
                            Loading...
                        </span>

                        |

                        <i class="
                            bi
                            bi-clock
                            me-1
                        "></i>

                        <span id="showTime"
                              class="text-white">
                            Loading...
                        </span>

                    </div>


                    <div class="
                        mb-3
                        text-secondary
                    ">

                        <i class="
                            bi
                            bi-ticket-perforated
                            me-2
                        "></i>

                        Seats:

                        <span id="seatsList"
                              class="
                              badge
                              bg-danger
                              fs-6
                              ">
                            Loading...
                        </span>

                    </div>


                    <hr class="
                        border-secondary
                        my-3
                    ">


                    <div class="
                        d-flex
                        justify-content-between
                        text-muted
                        mb-2
                    ">

                        <span>

                            Tickets

                            (
                            <span id="seatCount">
                                0
                            </span>
                            )

                            x

                            <span id="ticketPrice">
                                ₹0
                            </span>

                        </span>


                        <span id="subTotal">
                            ₹0
                        </span>

                    </div>


                    <div class="
                        d-flex
                        justify-content-between
                        align-items-center
                        mb-4
                    ">

                        <span class="
                            fs-5
                            fw-bold
                        ">
                            Total Payable
                        </span>


                        <span id="totalPayable"
                              class="
                              fs-4
                              fw-bold
                              text-danger
                              ">
                            ₹0
                        </span>

                    </div>


                    <div id="errorMessage"
                         class="
                         alert
                         alert-danger
                         d-none
                         small
                         py-2
                         text-center
                         "
                         role="alert">
                    </div>


                    <button id="payButton"
                            class="
                            btn
                            btn-danger
                            w-100
                            py-2
                            fw-bold
                            fs-5
                            shadow
                            ">

                        <i class="
                            bi
                            bi-lock-fill
                            me-2
                        "></i>

                        Pay & Book

                    </button>


                    <div class="
                        text-center
                        mt-3
                    ">

                        <a href="javascript:history.back()"
                           class="
                           btn
                           btn-outline-danger
                           btn-sm
                           w-100
                           ">

                            <i class="
                                bi
                                bi-arrow-left
                                me-1
                            "></i>

                            Back to Seat Selection

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script type="module">

import {

    auth,

    db,

    onAuthStateChanged,

    doc,

    getDoc,

    writeBatch

} from './assets/js/firebase-config.js';


import {

    buildShowKey,

    buildSeatLockId

} from './assets/js/seat-lock.js';


// ============================================================
// URL DATA
// ============================================================

const urlParams =
    new URLSearchParams(
        window.location.search
    );


const bookingData = {

    movieId:
        urlParams.get(
            'movieId'
        ),

    movieTitle:
        urlParams.get(
            'title'
        ),

    moviePoster:
        urlParams.get(
            'poster'
        ),

    theatreId:
        urlParams.get(
            'theatreId'
        ),

    theatreName:
        urlParams.get(
            'theatreName'
        ),

    date:
        urlParams.get(
            'date'
        ),

    timing:
        urlParams.get(
            'timing'
        ),

    seats:
        urlParams.get(
            'seats'
        )
        ? urlParams
            .get('seats')
            .split(',')
            .map(
                seat =>
                    seat.trim()
            )
            .filter(Boolean)
        : []

};


// ============================================================
// PRICE
// ============================================================

const TIER_PRICES = {

    A: 450,
    B: 450,

    C: 300,
    D: 300,

    E: 200,
    F: 200

};


// ============================================================
// DISPLAY DATA
// ============================================================

document.addEventListener(
    'DOMContentLoaded',
    () => {

        onAuthStateChanged(
            auth,
            (user) => {

                if (!user) {

                    window.location.href =
                        'login.php';

                    return;
                }


                if (
                    bookingData.seats.length === 0
                ) {

                    document
                        .getElementById(
                            'bookingDetails'
                        )
                        .innerHTML = `

                            <div class="
                                alert
                                alert-warning
                                text-center
                            ">

                                No active booking found.

                                <a href="index.php"
                                   class="alert-link">

                                    Explore movies

                                </a>

                            </div>

                        `;

                    return;
                }


                let total = 0;


                bookingData.seats
                    .forEach(
                        seat => {

                            const row =
                                seat[0]
                                    .toUpperCase();


                            total +=
                                TIER_PRICES[row] ||
                                200;

                        }
                    );


                document.getElementById(
                    'movieTitle'
                ).textContent =
                    bookingData.movieTitle ||
                    'Movie Ticket';


                document.getElementById(
                    'theatreName'
                ).textContent =
                    bookingData.theatreName ||
                    'Cinema Theatre';


                document.getElementById(
                    'showDate'
                ).textContent =
                    bookingData.date ||
                    'N/A';


                document.getElementById(
                    'showTime'
                ).textContent =
                    bookingData.timing ||
                    'N/A';


                document.getElementById(
                    'seatsList'
                ).textContent =
                    bookingData.seats.join(
                        ', '
                    );


                document.getElementById(
                    'seatCount'
                ).textContent =
                    bookingData.seats.length;


                document.getElementById(
                    'totalPayable'
                ).textContent =
                    `₹${total}`;


                document.getElementById(
                    'subTotal'
                ).textContent =
                    `₹${total}`;


                document.getElementById(
                    'ticketPrice'
                ).textContent =
                    `₹${total / bookingData.seats.length}`;

            }
        );

    }
);


// ============================================================
// PAYMENT
// ============================================================

async function processPayment() {

    const btn =
        document.getElementById(
            'payButton'
        );


    const errDiv =
        document.getElementById(
            'errorMessage'
        );


    errDiv.classList.add(
        'd-none'
    );


    btn.disabled = true;


    btn.innerHTML = `
        <span class="
            spinner-border
            spinner-border-sm
            me-2
        "></span>

        Processing...
    `;


    try {

        console.log(
            'Payment process started...'
        );


        const user =
            auth.currentUser;


        if (!user) {

            throw new Error(
                'Please login to complete your booking.'
            );

        }


        if (
            bookingData.seats.length === 0
        ) {

            throw new Error(
                'No seats selected.'
            );

        }


        // ========================================================
        // BOOKING ID
        // ========================================================

        const randomPart =
            Math.random()
                .toString(36)
                .substring(2, 7)
                .toUpperCase();


        const timestampPart =
            Date.now()
                .toString(36)
                .toUpperCase();


        const bookingId =
            `BMT${timestampPart}${randomPart}`;


        console.log(
            'Generated Booking ID:',
            bookingId
        );


        // ========================================================
        // TOTAL
        // ========================================================

        let totalPrice = 0;


        bookingData.seats.forEach(
            seat => {

                const row =
                    seat[0]
                        .toUpperCase();


                totalPrice +=
                    TIER_PRICES[row] ||
                    200;

            }
        );


        // ========================================================
        // SHOW KEY
        // ========================================================

        const showKey =
            buildShowKey({

                movieId:
                    bookingData.movieId,

                movieTitle:
                    bookingData.movieTitle,

                theatreId:
                    bookingData.theatreId,

                theatreName:
                    bookingData.theatreName,

                date:
                    bookingData.date,

                time:
                    bookingData.timing

            });


        console.log(
            'Computed Show Key:',
            showKey
        );


        // ========================================================
        // CREATE BATCH
        // ========================================================

        const batch =
            writeBatch(db);


        // ========================================================
        // BOOKING DOCUMENT
        // ========================================================

        const bookingRef =
            doc(
                db,
                'bookingticket_app_web',
                bookingId
            );


        const bookingPayload = {

            bookingId:
                bookingId,

            userId:
                user.uid,

            uid:
                user.uid,

            userEmail:
                user.email || '',

            userName:
                user.displayName ||
                user.email?.split('@')[0] ||
                'User',

            movieId:
                String(
                    bookingData.movieId || ''
                ),

            movieTitle:
                bookingData.movieTitle || '',

            moviePoster:
                bookingData.moviePoster || '',

            theatreId:
                String(
                    bookingData.theatreId || ''
                ),

            theatreName:
                bookingData.theatreName || '',

            showKey:
                showKey,

            showId:
                showKey,

            timing:
                bookingData.timing || '',

            date:
                bookingData.date ||
                new Date()
                    .toISOString()
                    .split('T')[0],

            seats:
                bookingData.seats,

            totalPrice:
                totalPrice,

            status:
                'Confirmed',

            createdAt:
                Date.now()

        };


        console.log(
            'Adding booking to batch...'
        );


        batch.set(
            bookingRef,
            bookingPayload
        );


        // ========================================================
        // VERIFY EACH USER LOCK BEFORE BOOKING
        // ========================================================

        for (
            const seatId of
            bookingData.seats
        ) {

            const lockId =
                buildSeatLockId(
                    showKey,
                    seatId
                );


            const lockRef =
                doc(
                    db,
                    'seatLocks',
                    lockId
                );


            const lockSnap =
                await getDoc(
                    lockRef
                );


            if (
                !lockSnap.exists()
            ) {

                throw new Error(
                    `Seat ${seatId} is no longer locked. Please go back and select it again.`
                );

            }


            const lockData =
                lockSnap.data();


            if (
                lockData.userId !==
                user.uid
            ) {

                throw new Error(
                    `Seat ${seatId} is already taken by another user.`
                );

            }


            if (
                lockData.status !==
                'locked'
            ) {

                throw new Error(
                    `Seat ${seatId} is already booked.`
                );

            }


            console.log(
                `Verified lock for ${seatId}`
            );


            // ====================================================
            // CONVERT LOCK TO BOOKED
            // ====================================================

            batch.update(
                lockRef,
                {

                    bookingId:
                        bookingId,

                    status:
                        'booked',

                    bookedAt:
                        Date.now()

                }
            );

        }


        // ========================================================
        // COMMIT
        // ========================================================

        console.log(
            'Committing batch to Firestore...'
        );


        await batch.commit();


        console.log(
            'Batch commit successful!'
        );


        // ========================================================
        // SUCCESS
        // ========================================================

        window.location.href =
            `booking_confirmation.php?bookingId=${encodeURIComponent(bookingId)}`;


    }
    catch (err) {

        console.error(
            'Payment process failed:',
            err
        );


        errDiv.textContent =
            err.message ||
            'An unexpected error occurred. Please try again.';


        errDiv.classList.remove(
            'd-none'
        );


        btn.disabled =
            false;


        btn.innerHTML = `
            <i class="
                bi
                bi-lock-fill
                me-2
            "></i>

            Pay & Book
        `;

    }

}


// ============================================================
// PAY BUTTON
// ============================================================

document.getElementById(
    'payButton'
).onclick =
    processPayment;

</script>


<?php

include 'includes/footer.php';

?>