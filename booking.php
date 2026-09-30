<?php
require_once 'includes/db.php';

requireUser();

$pageTitle = 'Book Tour - CholoGhuri';

$packageId = isset($_GET['package_id'])
    ? (int) $_GET['package_id']
    : (int) ($_POST['package_id'] ?? 0);

if ($packageId <= 0) {
    redirectWithMessage(
        'packages.php',
        'Please select a valid tour package.',
        'error'
    );
}


/* =========================================
   GET PACKAGE
========================================= */

$stmt = $pdo->prepare("
    SELECT
        tp.*,

        (
            tp.total_seats -
            COALESCE(
                (
                    SELECT SUM(b.people)
                    FROM bookings b
                    WHERE b.package_id = tp.id
                    AND b.status != 'Cancelled'
                ),
                0
            )
        ) AS available_seats

    FROM tour_packages tp

    WHERE tp.id = :id
    AND tp.status = 'active'

    LIMIT 1
");

$stmt->execute([
    ':id' => $packageId
]);

$package = $stmt->fetch();

if (!$package) {
    redirectWithMessage(
        'packages.php',
        'Tour package not found.',
        'error'
    );
}

$availableSeats = max(
    0,
    (int) $package['available_seats']
);

$pricePerPerson = (float) $package['price'];


/* =========================================
   FORM VALUES
========================================= */

$travelDate = $_POST['travel_date'] ?? '';
$people = isset($_POST['people'])
    ? (int) $_POST['people']
    : 1;

$couponCode = strtoupper(
    trim($_POST['coupon_code'] ?? '')
);

$error = '';

$discount = 0;
$coupon = null;
$totalPrice = $pricePerPerson * max(1, $people);
$finalPrice = $totalPrice;


/* =========================================
   PROCESS BOOKING
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* -----------------------------
       Validate People
    ------------------------------ */

    if ($people < 1) {

        $error = 'Number of people must be at least 1.';

    } elseif ($people > $availableSeats) {

        $error = 'Only ' . $availableSeats .
                 ' seat(s) are currently available.';

    }


    /* -----------------------------
       Validate Travel Date
    ------------------------------ */

    if ($error === '' && $travelDate === '') {

        $error = 'Please select a travel date.';

    }


    if ($error === '' && $travelDate !== '') {

        $today = date('Y-m-d');

        if ($travelDate < $today) {

            $error = 'Travel date cannot be in the past.';

        }

    }


    /* -----------------------------
       Calculate Total
    ------------------------------ */

    $totalPrice = $pricePerPerson * $people;


    /* =================================
       COUPON VALIDATION
    ================================= */

    if ($error === '' && $couponCode !== '') {

        $couponStmt = $pdo->prepare("
            SELECT *
            FROM coupons

            WHERE code = :code
            AND status = 'active'

            AND (
                start_date IS NULL
                OR start_date <= :today
            )

            AND (
                end_date IS NULL
                OR end_date >= :today
            )

            LIMIT 1
        ");

        $couponStmt->execute([
            ':code' => $couponCode,
            ':today' => date('Y-m-d')
        ]);

        $coupon = $couponStmt->fetch();


        if (!$coupon) {

            $error = 'Invalid or expired coupon code.';

        } else {

            /* Check usage limit */

            if (
                $coupon['usage_limit'] !== null &&
                (int) $coupon['used_count'] >=
                (int) $coupon['usage_limit']
            ) {

                $error = 'This coupon has reached its usage limit.';

            }


            /* -----------------------------
               Check User Already Used Coupon
            ------------------------------ */

            if ($error === '') {

                $usageStmt = $pdo->prepare("
                    SELECT id

                    FROM coupon_usages

                    WHERE coupon_id = :coupon_id
                    AND user_id = :user_id

                    LIMIT 1
                ");

                $usageStmt->execute([
                    ':coupon_id' => $coupon['id'],
                    ':user_id' => $_SESSION['user_id']
                ]);

                if ($usageStmt->fetch()) {

                    $error =
                        'You have already used this coupon.';

                }

            }


            /* -----------------------------
               Calculate Discount
            ------------------------------ */

            if ($error === '') {

                if ($coupon['discount_type'] === 'percent') {

                    $discount =
                        ($totalPrice *
                        (float) $coupon['discount_value'])
                        / 100;

                } else {

                    $discount =
                        (float) $coupon['discount_value'];

                }


                /* Discount cannot exceed total */

                $discount = min(
                    $discount,
                    $totalPrice
                );

                $discount = round(
                    $discount,
                    2
                );

            }

        }

    }


    /* =================================
       FINAL PRICE
    ================================= */

    $finalPrice = max(
        0,
        $totalPrice - $discount
    );


    /* =================================
       INSERT BOOKING
    ================================= */

    if ($error === '') {

        try {

            $pdo->beginTransaction();


            /* --------------------------------
               Re-check available seats
            -------------------------------- */

            $seatStmt = $pdo->prepare("
                SELECT
                    total_seats,

                    (
                        total_seats -
                        COALESCE(
                            (
                                SELECT SUM(b.people)
                                FROM bookings b
                                WHERE b.package_id = tour_packages.id
                                AND b.status != 'Cancelled'
                            ),
                            0
                        )
                    ) AS available_seats

                FROM tour_packages

                WHERE id = :id

                FOR UPDATE
            ");

            $seatStmt->execute([
                ':id' => $packageId
            ]);

            $latestPackage = $seatStmt->fetch();


            if (
                !$latestPackage ||
                (int) $latestPackage['available_seats'] < $people
            ) {

                throw new Exception(
                    'Sorry, the selected number of seats is no longer available.'
                );

            }


            /* --------------------------------
               Insert Booking
            -------------------------------- */

            $bookingStmt = $pdo->prepare("
                INSERT INTO bookings
                (
                    user_id,
                    package_id,
                    travel_date,
                    people,
                    total_price,
                    discount,
                    final_price,
                    coupon_code,
                    status
                )

                VALUES
                (
                    :user_id,
                    :package_id,
                    :travel_date,
                    :people,
                    :total_price,
                    :discount,
                    :final_price,
                    :coupon_code,
                    'Pending'
                )
            ");

            $bookingStmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':package_id' => $packageId,
                ':travel_date' => $travelDate,
                ':people' => $people,
                ':total_price' => $totalPrice,
                ':discount' => $discount,
                ':final_price' => $finalPrice,
                ':coupon_code' =>
                    $coupon ? $coupon['code'] : null
            ]);


            $bookingId = $pdo->lastInsertId();


            /* --------------------------------
               Coupon Usage
            -------------------------------- */

            if ($coupon && $discount > 0) {

                $usageStmt = $pdo->prepare("
                    INSERT INTO coupon_usages
                    (
                        coupon_id,
                        user_id,
                        booking_id,
                        discount_amount
                    )

                    VALUES
                    (
                        :coupon_id,
                        :user_id,
                        :booking_id,
                        :discount_amount
                    )
                ");

                $usageStmt->execute([
                    ':coupon_id' =>
                        $coupon['id'],

                    ':user_id' =>
                        $_SESSION['user_id'],

                    ':booking_id' =>
                        $bookingId,

                    ':discount_amount' =>
                        $discount
                ]);


                /* Increase coupon usage */

                $updateCoupon = $pdo->prepare("
                    UPDATE coupons

                    SET used_count = used_count + 1

                    WHERE id = :id
                ");

                $updateCoupon->execute([
                    ':id' => $coupon['id']
                ]);

            }


            /* --------------------------------
               Create Payment Record
            -------------------------------- */

            $paymentStmt = $pdo->prepare("
                INSERT INTO payments
                (
                    booking_id,
                    method,
                    status,
                    amount
                )

                VALUES
                (
                    :booking_id,
                    'Cash',
                    'Pending',
                    :amount
                )
            ");

            $paymentStmt->execute([
                ':booking_id' => $bookingId,
                ':amount' => $finalPrice
            ]);


            $pdo->commit();


            redirectWithMessage(
                'payment.php?booking_id=' . $bookingId,
                'Booking created successfully. Please complete your payment.'
            );


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e->getMessage();
        }

    }

}


/* =========================================
   GET USER INFORMATION
========================================= */

$userStmt = $pdo->prepare("
    SELECT
        name,
        email,
        phone

    FROM users

    WHERE id = :id

    LIMIT 1
");

$userStmt->execute([
    ':id' => $_SESSION['user_id']
]);

$user = $userStmt->fetch();


include 'includes/header.php';
?>


<!-- =========================================
     PAGE BANNER
========================================= -->

<section class="page-banner">

    <div class="container">

        <h1>
            Book Your Tour
        </h1>

        <p>
            Complete the form below to reserve your seats.
        </p>

    </div>

</section>


<!-- =========================================
     BOOKING SECTION
========================================= -->

<section class="section">

    <div class="container">

        <?php if ($error !== ''): ?>

            <div class="flash-message flash-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <div class="booking-grid">


            <!-- =================================
                 BOOKING FORM
            ================================== -->

            <div class="booking-card">

                <div class="card-header">

                    <h2>
                        Booking Information
                    </h2>

                    <p>
                        Enter your travel details.
                    </p>

                </div>


                <form
                    method="POST"
                    action="booking.php?package_id=<?= (int) $packageId ?>"
                    class="booking-form"
                >

                    <input
                        type="hidden"
                        name="package_id"
                        value="<?= (int) $packageId ?>"
                    >


                    <!-- User Information -->

                    <div class="form-section">

                        <h3>
                            👤 Personal Information
                        </h3>


                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= e($user['name'] ?? '') ?>"
                                    readonly
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Email
                                </label>

                                <input
                                    type="email"
                                    class="form-control"
                                    value="<?= e($user['email'] ?? '') ?>"
                                    readonly
                                >

                            </div>

                        </div>


                        <div class="form-group">

                            <label>
                                Phone Number
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?= e($user['phone'] ?? 'Not provided') ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- Travel Information -->

                    <div class="form-section">

                        <h3>
                            🧳 Travel Information
                        </h3>


                        <div class="form-row">

                            <div class="form-group">

                                <label for="travel_date">
                                    Travel Date
                                </label>

                                <input
                                    type="date"
                                    name="travel_date"
                                    id="travel_date"
                                    class="form-control"
                                    min="<?= date('Y-m-d') ?>"
                                    value="<?= e($travelDate) ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="people">
                                    Number of People
                                </label>

                                <input
                                    type="number"
                                    name="people"
                                    id="people"
                                    class="form-control"
                                    min="1"
                                    max="<?= $availableSeats ?>"
                                    value="<?= max(1, $people) ?>"
                                    data-price="<?= e($pricePerPerson) ?>"
                                    <?= $availableSeats <= 0 ? 'disabled' : '' ?>
                                    required
                                >

                                <small class="form-help">
                                    <?= $availableSeats ?> seat(s) available
                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- Coupon -->

                    <div class="form-section">

                        <h3>
                            🎟️ Discount Coupon
                        </h3>

                        <p class="form-help">
                            Have a coupon? Enter it below.
                        </p>


                        <div class="coupon-row">

                            <input
                                type="text"
                                name="coupon_code"
                                id="coupon_code"
                                class="form-control"
                                placeholder="Enter coupon code"
                                value="<?= e($couponCode) ?>"
                            >

                        </div>


                        <div class="coupon-examples">

                            <span>
                                Try:
                            </span>

                            <button
                                type="button"
                                class="coupon-chip"
                                data-coupon="WELCOME10"
                            >
                                WELCOME10
                            </button>

                            <button
                                type="button"
                                class="coupon-chip"
                                data-coupon="TRAVEL500"
                            >
                                TRAVEL500
                            </button>

                        </div>

                    </div>


                    <!-- Submit -->

                    <div class="booking-submit">

                        <?php if ($availableSeats > 0): ?>

                            <button
                                type="submit"
                                class="btn btn-primary btn-lg"
                            >
                                Continue to Payment →
                            </button>

                        <?php else: ?>

                            <button
                                type="button"
                                class="btn btn-secondary btn-lg"
                                disabled
                            >
                                Sold Out
                            </button>

                        <?php endif; ?>

                    </div>

                </form>

            </div>


            <!-- =================================
                 BOOKING SUMMARY
            ================================== -->

            <aside class="summary-card">

                <h2>
                    Booking Summary
                </h2>


                <!-- Package -->

                <div class="summary-package">

                    <?php if (!empty($package['image'])): ?>

                        <img
                            src="<?= e($package['image']) ?>"
                            alt="<?= e($package['title']) ?>"
                        >

                    <?php else: ?>

                        <div class="summary-image-placeholder">
                            🌴
                        </div>

                    <?php endif; ?>


                    <div>

                        <h3>
                            <?= e($package['title']) ?>
                        </h3>

                        <p>
                            📍 <?= e($package['destination']) ?>
                        </p>

                        <p>
                            🕐 <?= e($package['duration']) ?>
                        </p>

                    </div>

                </div>


                <!-- Seats -->

                <div class="summary-seat-info">

                    <?php if ($availableSeats <= 0): ?>

                        <span class="status-badge status-cancelled">
                            Sold Out
                        </span>

                    <?php elseif ($availableSeats <= 5): ?>

                        <span class="status-badge status-pending">
                            ⚠️ <?= $availableSeats ?> seats left
                        </span>

                    <?php else: ?>

                        <span class="status-badge status-confirmed">
                            ✓ <?= $availableSeats ?> seats available
                        </span>

                    <?php endif; ?>

                </div>


                <!-- Price Summary -->

                <div class="summary-row">

                    <span>
                        Price per person
                    </span>

                    <strong>
                        ৳<?= number_format($pricePerPerson, 2) ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Number of people
                    </span>

                    <strong id="summaryPeople">
                        <?= max(1, $people) ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Total Price
                    </span>

                    <strong id="summaryTotal">
                        ৳<?= number_format($totalPrice, 2) ?>
                    </strong>

                </div>


                <?php if ($discount > 0): ?>

                    <div class="summary-row discount-row">

                        <span>
                            Discount
                        </span>

                        <strong>
                            -৳<?= number_format($discount, 2) ?>
                        </strong>

                    </div>

                <?php endif; ?>


                <div class="summary-total">

                    <span>
                        Final Price
                    </span>

                    <strong id="summaryFinal">
                        ৳<?= number_format($finalPrice, 2) ?>
                    </strong>

                </div>


                <div class="summary-note">

                    🔒 Your booking information is securely processed.

                </div>

            </aside>

        </div>

    </div>

</section>


<!-- =========================================
     BOOKING INFORMATION
========================================= -->

<section class="section light-section">

    <div class="container">

        <div class="info-grid">

            <div class="info-card">

                <div class="info-icon">
                    🔐
                </div>

                <h3>
                    Secure Booking
                </h3>

                <p>
                    Your personal and booking information is protected.
                </p>

            </div>


            <div class="info-card">

                <div class="info-icon">
                    🎟️
                </div>

                <h3>
                    Easy Discounts
                </h3>

                <p>
                    Apply available coupon codes during the booking process.
                </p>

            </div>


            <div class="info-card">

                <div class="info-icon">
                    💳
                </div>

                <h3>
                    Flexible Payment
                </h3>

                <p>
                    Continue to the payment page after confirming your booking.
                </p>

            </div>


            <div class="info-card">

                <div class="info-icon">
                    📞
                </div>

                <h3>
                    Customer Support
                </h3>

                <p>
                    Contact CholoGhuri for assistance with your booking.
                </p>

            </div>

        </div>

    </div>

</section>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const peopleInput = document.getElementById('people');
    const couponInput = document.getElementById('coupon_code');

    const summaryPeople =
        document.getElementById('summaryPeople');

    const summaryTotal =
        document.getElementById('summaryTotal');

    const summaryFinal =
        document.getElementById('summaryFinal');

    const pricePerPerson =
        <?= json_encode($pricePerPerson) ?>;

    const serverDiscount =
        <?= json_encode($discount) ?>;


    function updateBookingSummary() {

        if (!peopleInput) {
            return;
        }

        let people =
            parseInt(peopleInput.value) || 1;

        if (people < 1) {
            people = 1;
        }

        const total =
            pricePerPerson * people;

        const final =
            Math.max(
                0,
                total - serverDiscount
            );


        if (summaryPeople) {
            summaryPeople.textContent = people;
        }


        if (summaryTotal) {
            summaryTotal.textContent =
                '৳' + total.toFixed(2);
        }


        if (summaryFinal) {
            summaryFinal.textContent =
                '৳' + final.toFixed(2);
        }

    }


    if (peopleInput) {

        peopleInput.addEventListener(
            'input',
            updateBookingSummary
        );

        updateBookingSummary();
    }


    /* Coupon quick buttons */

    document
        .querySelectorAll('.coupon-chip')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    if (couponInput) {

                        couponInput.value =
                            this.dataset.coupon;

                    }

                }
            );

        });

});
</script>


<?php include 'includes/footer.php'; ?>
