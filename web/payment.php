<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Summary - Book My Ticket</title>
    <style>
        body { background: #121212; color: white; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: #1E1E1E; padding: 30px; border-radius: 16px; width: 350px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.5); }
        .divider { border-top: 1px solid #333; margin: 20px 0; }
        .btn-pay { background: #E50914; color: white; padding: 15px; border: none; border-radius: 8px; width: 100%; font-size: 16px; font-weight: bold; cursor: pointer; }
        .btn-pay:disabled { background: #555; cursor: not-allowed; }
        .price { font-size: 24px; color: #fff; font-weight: bold; margin-bottom: 20px; }
        .status { color: #aaa; font-size: 14px; margin-top: 10px; }
    </style>
</head>
<body>

<div class="card">
    <h2 style="margin-top: 0;"><?php echo $_GET['title']; ?></h2>
    <p style="color: #aaa;"><?php echo $_GET['theatreName']; ?></p>

    <div class="divider"></div>

    <div style="text-align: left; font-size: 14px; color: #aaa;">
        <p>Seats: <span style="color: white;"><?php echo $_GET['seats']; ?></span></p>
        <p>Time: <span style="color: white;"><?php echo $_GET['timing']; ?></span></p>
    </div>

    <div class="divider"></div>

    <p style="font-size: 12px; color: #aaa; margin-bottom: 5px;">Total Payable</p>
    <div class="price">₹ <?php echo $_GET['totalPrice']; ?></div>

    <button id="btn-pay" class="btn-pay">Pay & Book Now</button>
    <div id="status-msg" class="status"></div>
</div>

<script type="module">
    import { db, doc, setDoc, auth } from './js/firebase-config.js';

    const params = new URLSearchParams(window.location.search);
    const btnPay = document.getElementById('btn-pay');
    const statusMsg = document.getElementById('status-msg');

    btnPay.onclick = async () => {
        btnPay.disabled = true;
        btnPay.innerText = "Processing...";
        statusMsg.innerText = "Saving to Firebase...";

        const bookingId = Math.random().toString(36).substr(2, 8).toUpperCase();
        const userId = auth.currentUser ? auth.currentUser.uid : "web-guest";

        const bookingData = {
            bookingId: bookingId,
            userId: userId,
            movieId: params.get('movieId'),
            movieTitle: params.get('title'),
            theatreId: params.get('theatreId'),
            theatreName: params.get('theatreName'),
            date: params.get('date'),
            timing: params.get('timing'),
            seats: params.get('seats').split(','),
            totalPrice: parseInt(params.get('totalPrice')),
            status: "Upcoming",
            createdAt: Date.now()
        };

        try {
            // Write to the EXACT same collection as the Android app
            await setDoc(doc(db, "Bookings", bookingId), bookingData);

            statusMsg.style.color = "#22C55E";
            statusMsg.innerText = "Success! Ticket Booked.";

            setTimeout(() => {
                // Redirect to a confirmation page or home
                alert("Booking Confirmed! ID: #" + bookingId);
                window.location.href = "index.php";
            }, 1500);

        } catch (error) {
            btnPay.disabled = false;
            btnPay.innerText = "Pay & Book Now";
            statusMsg.style.color = "#E50914";
            statusMsg.innerText = "Error: " + error.message;
        }
    };
</script>

</body>
</html>
