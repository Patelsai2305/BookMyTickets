<?php

$showId = $_GET['showId'] ?? '';

$movieId = $_GET['movieId'] ?? '';

$movieTitle = $_GET['title'] ?? '';

$moviePoster = $_GET['poster'] ?? '';

$theatreId = $_GET['theatreId'] ?? '';

$theatreName = $_GET['theatreName'] ?? $theatreId;

$showDate = $_GET['date'] ?? '';

$showTime = $_GET['timing'] ?? '';

include 'includes/header.php';

?>


<div class="container"
     style="padding: 2rem 5%; text-align: center;">

    <h2>
        <?= htmlspecialchars($movieTitle) ?>
    </h2>


    <p id="show-details"
       style="color: var(--text-muted); margin-bottom: 1rem;">

        Loading show details...

    </p>


    <div class="seat-container">

        <div class="screen"></div>


        <p style="
            color: #444;
            margin-bottom: 2rem;
            font-size: 0.8rem;
        ">
            SCREEN THIS WAY
        </p>


        <!-- PRICE LEGEND -->

        <div style="
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        ">

            <span>
                <span class="seat"
                      style="
                      display:inline-block;
                      vertical-align:middle;
                      margin-right:5px;
                      background:rgba(245,158,11,0.2);
                      border:1px solid #f59e0b;
                      cursor:default;">
                </span>

                VIP (A–B) ₹450
            </span>


            <span>
                <span class="seat"
                      style="
                      display:inline-block;
                      vertical-align:middle;
                      margin-right:5px;
                      background:rgba(59,130,246,0.2);
                      border:1px solid #3b82f6;
                      cursor:default;">
                </span>

                Premium (C–D) ₹300
            </span>


            <span>
                <span class="seat"
                      style="
                      display:inline-block;
                      vertical-align:middle;
                      margin-right:5px;
                      cursor:default;">
                </span>

                Normal (E–F) ₹200
            </span>

        </div>


        <div id="seats-grid"
             aria-label="Seat selection grid">
        </div>


        <!-- SEAT LEGEND -->

        <div class="legend"
             style="
             display: flex;
             gap: 2rem;
             margin-top: 2rem;
             font-size: 0.9rem;
             justify-content: center;
             ">

            <span>
                <span class="seat"
                      style="
                      display:inline-block;
                      vertical-align:middle;
                      margin-right:5px;
                      cursor:default;">
                </span>

                Available
            </span>


            <span>
                <span class="seat selected"
                      style="
                      display:inline-block;
                      vertical-align:middle;
                      margin-right:5px;
                      cursor:default;">
                </span>

                Selected
            </span>


            <span>
                <span class="seat occupied"
                      style="
                      display:inline-block;
                      vertical-align:middle;
                      margin-right:5px;
                      cursor:default;">
                </span>

                Booked
            </span>

        </div>

    </div>


    <!-- BOOKING PANEL -->

    <div id="booking-panel"
         style="
         display:none;
         margin-top:2rem;
         background:var(--card-dark);
         padding:2rem;
         border-radius:12px;
         max-width:420px;
         margin-left:auto;
         margin-right:auto;
         ">

        <p>

            Selected Seats:

            <span id="selected-seats-text"
                  style="
                  color:var(--accent);
                  font-weight:bold;
                  ">
            </span>

        </p>


        <div id="price-breakdown"
             style="
             margin:0.75rem 0;
             font-size:0.9rem;
             color:var(--text-muted);
             ">
        </div>


        <p>

            Total Amount:

            <span id="total-price"
                  style="
                  font-size:1.5rem;
                  font-weight:bold;
                  ">
                ₹0
            </span>

        </p>


        <button id="book-now-btn"
                class="btn btn-primary"
                style="
                width:100%;
                margin-top:1rem;
                padding:1rem;
                ">

            Confirm & Pay

        </button>

    </div>

</div>


<script type="module">

import {
    auth,
    db,
    collection,
    query,
    where,
    onSnapshot
} from './assets/js/firebase-config.js';


import {
    buildShowKey,
    createTempLock,
    releaseTempLock
} from './assets/js/seat-lock.js';


// ============================================================
// PHP DATA
// ============================================================

const movieId =
    <?= json_encode((string)$movieId) ?>;


const movieTitle =
    <?= json_encode((string)$movieTitle) ?>;


const moviePoster =
    <?= json_encode((string)$moviePoster) ?>;


const theatreId =
    <?= json_encode((string)$theatreId) ?>;


const theatreName =
    <?= json_encode((string)$theatreName) ?>;


const showDate =
    <?= json_encode((string)$showDate) ?>;


const showTime =
    <?= json_encode((string)$showTime) ?>;


// ============================================================
// PRICES
// ============================================================

const TIER_PRICES = {

    A: 450,
    B: 450,

    C: 300,
    D: 300,

    E: 200,
    F: 200

};


const TIER_NAMES = {

    A: 'VIP',
    B: 'VIP',

    C: 'Premium',
    D: 'Premium',

    E: 'Normal',
    F: 'Normal'

};


// ============================================================
// STATE
// ============================================================

let selectedSeats = [];

let takenSeats = new Set();


// ============================================================
// ELEMENTS
// ============================================================

const gridContainer =
    document.getElementById(
        'seats-grid'
    );


const showDetails =
    document.getElementById(
        'show-details'
    );


const bookingPanel =
    document.getElementById(
        'booking-panel'
    );


// ============================================================
// SHOW DETAILS
// ============================================================

showDetails.innerText =
    `${theatreName || theatreId || 'Theatre'} | ${showDate || 'Date'} | ${showTime || 'Time'}`;


// ============================================================
// SHARED SHOW KEY
// ============================================================

const showKey =
    buildShowKey({

        movieId,

        movieTitle,

        theatreId,

        theatreName,

        date: showDate,

        time: showTime

    });


console.log(
    'Web Show Key:',
    showKey
);


// ============================================================
// FIRESTORE SEAT LISTENER
// ============================================================

onSnapshot(

    query(

        collection(
            db,
            'seatLocks'
        ),

        where(
            'showKey',
            '==',
            showKey
        )

    ),

    (snap) => {

        takenSeats =
            new Set();


        const currentUserId =
            auth.currentUser?.uid || '';


        snap.docs.forEach(
            (d) => {

                const data =
                    d.data();


                const status =
                    String(
                        data.status || ''
                    ).toLowerCase();


                const owner =
                    data.userId || '';


                // =============================================
                // YOUR OWN TEMP LOCK
                // Keep it selectable.
                // =============================================

                if (
                    status === 'locked' &&
                    owner === currentUserId
                ) {

                    return;
                }


                // =============================================
                // EVERY BOOKED SEAT
                // =============================================

                if (
                    status === 'booked'
                ) {

                    takenSeats.add(
                        data.seatId
                    );

                    return;
                }


                // =============================================
                // OTHER USER TEMP LOCK
                // Expired locks are available again.
                // =============================================

                if (status === 'locked') {
                    const expiresAt = data.expiresAt;
                    const active = !expiresAt ||
                        (typeof expiresAt.toMillis === 'function'
                            ? expiresAt.toMillis() > Date.now()
                            : new Date(expiresAt).getTime() > Date.now());

                    if (active) {
                        takenSeats.add(data.seatId);
                    }
                }

            }
        );


        // =============================================
        // Remove seats that are no longer available.
        // =============================================

        selectedSeats =
            selectedSeats.filter(
                seat =>
                    !takenSeats.has(
                        seat
                    )
            );


        renderSeats();

    },

    (error) => {

        console.error(
            'Unable to load seat locks:',
            error
        );

    }

);


// ============================================================
// INITIAL RENDER
// ============================================================

renderSeats();


// ============================================================
// RENDER SEATS
// ============================================================

function renderSeats() {

    const rows = [
        'A',
        'B',
        'C',
        'D',
        'E',
        'F'
    ];


    gridContainer.innerHTML = '';


    for (
        const row of rows
    ) {

        for (
            let i = 1;
            i <= 8;
            i++
        ) {

            const seatId =
                `${row}${i}`;


            let type;


            if (
                row === 'A' ||
                row === 'B'
            ) {

                type = 'VIP';

            }
            else if (
                row === 'C' ||
                row === 'D'
            ) {

                type = 'Premium';

            }
            else {

                type = 'Normal';

            }


            const price =
                TIER_PRICES[row];


            const seat =
                document.createElement(
                    'div'
                );


            seat.className =
                `seat ${type.toLowerCase()}`;


            seat.dataset.id =
                seatId;


            seat.dataset.price =
                String(price);


            seat.dataset.tier =
                type;


            seat.innerText =
                seatId;


            // =============================================
            // TAKEN
            // =============================================

            if (
                takenSeats.has(
                    seatId
                )
            ) {

                seat.classList.add(
                    'occupied'
                );

                seat.setAttribute(
                    'aria-disabled',
                    'true'
                );

            }


            // =============================================
            // SELECTED
            // =============================================

            else {

                if (
                    selectedSeats.includes(
                        seatId
                    )
                ) {

                    seat.classList.add(
                        'selected'
                    );

                }


                seat.onclick =
                    () =>
                        toggleSeat(
                            seatId
                        );

            }


            gridContainer.appendChild(
                seat
            );

        }

    }


    updatePanel();
}


// ============================================================
// TOGGLE SEAT
// ============================================================

async function toggleSeat(
    seatId
) {

    const user =
        auth.currentUser;


    if (!user) {

        window.location.href =
            `login.php?redirect=${encodeURIComponent(window.location.href)}`;

        return;
    }


    // =============================================
    // RELEASE
    // =============================================

    if (
        selectedSeats.includes(
            seatId
        )
    ) {

        selectedSeats =
            selectedSeats.filter(
                s =>
                    s !== seatId
            );


        renderSeats();


        try {

            await releaseTempLock(
                user.uid,
                showKey,
                seatId
            );

        }
        catch (error) {

            console.error(
                'Lock release failed:',
                error
            );

        }


        return;
    }


    // =============================================
    // CREATE LOCK
    // =============================================

    if (
        takenSeats.has(
            seatId
        )
    ) {

        alert(
            `Seat ${seatId} is already taken.`
        );

        return;
    }


    try {

        await createTempLock(
            user.uid,
            showKey,
            seatId
        );


        selectedSeats.push(
            seatId
        );


        renderSeats();

    }
    catch (error) {

        console.error(
            'Lock creation failed:',
            error
        );


        alert(
            error.message ||
            `Seat ${seatId} is no longer available.`
        );


        renderSeats();

    }

}


// ============================================================
// UPDATE BOOKING PANEL
// ============================================================

function updatePanel() {

    if (
        selectedSeats.length === 0
    ) {

        bookingPanel.style.display =
            'none';

        return;
    }


    bookingPanel.style.display =
        'block';


    document.getElementById(
        'selected-seats-text'
    ).innerText =
        selectedSeats.join(
            ', '
        );


    const byTier = {};


    selectedSeats.forEach(
        (seat) => {

            const tier =
                TIER_NAMES[
                    seat[0]
                ];


            byTier[tier] =
                (
                    byTier[tier] ||
                    0
                ) + 1;

        }
    );


    let total = 0;


    document.getElementById(
        'price-breakdown'
    ).innerHTML =

        Object.entries(
            byTier
        )
        .map(
            ([tier, count]) => {

                const unit =
                    {
                        VIP: 450,
                        Premium: 300,
                        Normal: 200
                    }[tier];


                total +=
                    unit * count;


                return `
                    <div>
                        ${tier}
                        (₹${unit}):
                        ${count}
                        seat(s)
                        —
                        ₹${unit * count}
                    </div>
                `;

            }
        )
        .join('');


    document.getElementById(
        'total-price'
    ).innerText =
        `₹${total}`;

}


// ============================================================
// CONFIRM & PAY
// ============================================================

document.getElementById(
    'book-now-btn'
).onclick = () => {

    const user =
        auth.currentUser;


    if (!user) {

        window.location.href =
            `login.php?redirect=${encodeURIComponent(window.location.href)}`;

        return;
    }


    if (
        selectedSeats.length === 0
    ) {

        return;
    }


    const params =
        new URLSearchParams({

            movieId,

            title: movieTitle,

            poster: moviePoster,

            theatreId,

            theatreName:
                theatreName ||
                theatreId,

            date:
                showDate,

            timing:
                showTime,

            seats:
                selectedSeats.join(',')

        });


    window.location.href =
        `payment.php?${params.toString()}`;

};

</script>


<?php

include 'includes/footer.php';

?>