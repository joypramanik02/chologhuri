
<?php
require_once 'includes/db.php';

requireUser();

$pageTitle = 'Payment - CholoGhuri';

$bookingId = isset($_GET['booking_id'])
    ? (int) $_GET['booking_id']
    : (int) ($_POST['booking_id'] ?? 0);

if ($bookingId <= 0) {
    redirectWithMessage(
        'my-bookings.php',
        'Invalid booking selected.',
        'error'
    );
}


/* =========================================
   GET BOOKING
========================================= */

$stmt = $pdo->prepare("
    SELECT
        b.*,

        tp.title,
        tp.destination,
        tp.duration,
        tp.image,
        tp.price AS package_price,

        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone

    FROM bookings b

    INNER JOIN tour_packages tp
        ON tp.id = b.package_id

    INNER JOIN users u
        ON u.id = b.user_id

    WHERE b.id = :booking_id
    AND b.user_id = :user_id

    LIMIT 1
");

$stmt->execute([
    ':booking_id' => $bookingId,
    ':user_id' => $_SESSION['user_id']
]);

$booking = $stmt->fetch();

if (!$booking) {
    redirectWithMessage(
        'my-bookings.php',
        'Booking not found.',
        'error'
    );
}


/* =========================================
   GET PAYMENT
========================================= */

$paymentStmt = $pdo->prepare("
    SELECT *
    FROM payments
    WHERE booking_id = :booking_id
    ORDER BY id DESC
    LIMIT 1
");

$paymentStmt->execute([
    ':booking_id' => $bookingId
]);

$payment = $paymentStmt->fetch();


/* =========================================
   ALREADY PAID
========================================= */

if (
    $payment &&
    $payment['status'] === 'Paid'
) {

    include 'includes/header.php';
    ?>

    <section class="section">

        <div class="container">

            <div class="payment-success">

                <div class="success-icon">
                    ✓
                </div>

                <h1>
                    Payment Already Completed
                </h1>

                <p>
                    This booking has already been marked as paid.
                </p>

                <div class="success-details">

                    <p>
                        <strong>
                            Booking ID:
                        </strong>

                        #<?= (int) $booking['id'] ?>
                    </p>

                    <p>
                        <strong>
                            Package:
                        </strong>

                        <?= e($booking['title']) ?>
                    </p>

                    <p>
                        <strong>
                            Amount:
                        </strong>

                        ৳<?= number_format($booking['final_price'], 2) ?>
                    </p>

                </div>

                <div class="success-actions">

                    <a
                        href="my-bookings.php"
                        class="btn btn-primary"
                    >
                        My Bookings
                    </a>

                    <a
                        href="packages.php"
                        class="btn btn-outline"
                    >
                        Explore Packages
                    </a>

                </div>

            </div>

        </div>

    </section>

    <?php
    include 'includes/footer.php';
    exit;
}


/* =========================================
   FORM VALUES
========================================= */

$method = $_POST['method'] ?? 'Cash';
$transactionId = trim(
    $_POST['transaction_id'] ?? ''
);

$error = '';

$allowedMethods = [
    'Cash',
    'bKash',
    'Nagad',
    'Card',
    'Bank Transfer'
];


/* =========================================
   PROCESS PAYMENT
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Validate method */

    if (!in_array($method, $allowedMethods, true)) {

        $error = 'Please select a valid payment method.';

    }


    /* Transaction ID */

    if (
        $error === '' &&
        $method !== 'Cash' &&
        $transactionId === ''
    ) {

        $error =
            'Please enter your transaction ID.';
    }


    /* Transaction ID length */

    if (
        $error === '' &&
        $transactionId !== '' &&
        strlen($transactionId) > 100
    ) {

        $error =
            'Transaction ID is too long.';
    }


    /* -----------------------------------------
       Update Payment
    ------------------------------------------ */

    if ($error === '') {

        try {

            $pdo->beginTransaction();


            /* Re-check booking */

            $bookingCheck = $pdo->prepare("
                SELECT
                    id,
                    status,
                    final_price

                FROM bookings

                WHERE id = :booking_id
                AND user_id = :user_id

                FOR UPDATE
            ");

            $bookingCheck->execute([
                ':booking_id' => $bookingId,
                ':user_id' => $_SESSION['user_id']
            ]);

            $currentBooking = $bookingCheck->fetch();


            if (!$currentBooking) {

                throw new Exception(
                    'Booking could not be found.'
                );

            }


            if (
                $currentBooking['status'] === 'Cancelled'
            ) {

                throw new Exception(
                    'This booking has been cancelled.'
                );

            }


            /* --------------------------------
               Check Existing Payment
            -------------------------------- */

            $paymentCheck = $pdo->prepare("
                SELECT id
                FROM payments

                WHERE booking_id = :booking_id

                ORDER BY id DESC

                LIMIT 1

                FOR UPDATE
            ");

            $paymentCheck->execute([
                ':booking_id' => $bookingId
            ]);

            $existingPayment = $paymentCheck->fetch();


            /* --------------------------------
               Update Payment
            -------------------------------- */

            if ($existingPayment) {

                $updatePayment = $pdo->prepare("
                    UPDATE payments

                    SET
                        method = :method,
                        status = 'Paid',
                        transaction_id = :transaction_id,
                        amount = :amount,
                        paid_at = NOW()

                    WHERE id = :id
                ");

                $updatePayment->execute([
                    ':method' =>
                        $method,

                    ':transaction_id' =>
                        $transactionId !== ''
                        ? $transactionId
                        : null,

                    ':amount' =>
                        $currentBooking['final_price'],

                    ':id' =>
                        $existingPayment['id']
                ]);

            } else {

                $insertPayment = $pdo->prepare("
                    INSERT INTO payments
                    (
                        booking_id,
                        method,
                        status,
                        transaction_id,
                        amount,
                        paid_at
                    )

                    VALUES
                    (
                        :booking_id,
                        :method,
                        'Paid',
                        :transaction_id,
                        :amount,
                        NOW()
                    )
                ");

                $insertPayment->execute([
                    ':booking_id' =>
                        $bookingId,

                    ':method' =>
                        $method,

                    ':transaction_id' =>
                        $transactionId !== ''
                        ? $transactionId
                        : null,

                    ':amount' =>
                        $currentBooking['final_price']
                ]);

            }


            /* --------------------------------
               Confirm Booking
            -------------------------------- */

            $updateBooking = $pdo->prepare("
                UPDATE bookings

                SET status = 'Confirmed'

                WHERE id = :booking_id
                AND user_id = :user_id
            ");

            $updateBooking->execute([
                ':booking_id' => $bookingId,
                ':user_id' => $_SESSION['user_id']
            ]);


            $pdo->commit();


            redirectWithMessage(
                'my-bookings.php',
                'Payment completed successfully. Your booking is confirmed.'
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
   REFRESH PAYMENT
========================================= */

$paymentStmt = $pdo->prepare("
    SELECT *
    FROM payments

    WHERE booking_id = :booking_id

    ORDER BY id DESC

    LIMIT 1
");

$paymentStmt->execute([
    ':booking_id' => $bookingId
]);

$payment = $paymentStmt->fetch();


include 'includes/header.php';
?>


<!-- =========================================
     PAGE BANNER
========================================= -->

<section class="page-banner">

    <div class="container">

        <h1>
            Complete Payment
        </h1>

        <p>
            Complete your payment to confirm your tour booking.
        </p>

    </div>

</section>


<!-- =========================================
     PAYMENT SECTION
========================================= -->

<section class="section">

    <div class="container">

        <?php if ($error !== ''): ?>

            <div class="flash-message flash-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <div class="payment-grid">


            <!-- =================================
                 PAYMENT FORM
            ================================== -->

            <div class="payment-card">

                <div class="card-header">

                    <h2>
                        Payment Information
                    </h2>

                    <p>
                        Select your preferred payment method.
                    </p>

                </div>


                <form
                    method="POST"
                    action="payment.php?booking_id=<?= (int) $bookingId ?>"
                >

                    <input
                        type="hidden"
                        name="booking_id"
                        value="<?= (int) $bookingId ?>"
                    >


                    <!-- Payment Methods -->

                    <div class="form-group">

                        <label>
                            Payment Method
                        </label>


                        <div class="payment-methods">


                            <label class="payment-method">

                                <input
                                    type="radio"
                                    name="method"
                                    value="Cash"
                                    <?= $method === 'Cash' ? 'checked' : '' ?>
                                >

                                <span class="payment-method-content">

                                    <span class="payment-icon">
                                        💵
                                    </span>

                                    <strong>
                                        Cash
                                    </strong>

                                    <small>
                                        Pay directly to our representative
                                    </small>

                                </span>

                            </label>


                            <label class="payment-method">

                                <input
                                    type="radio"
                                    name="method"
                                    value="bKash"
                                    <?= $method === 'bKash' ? 'checked' : '' ?>
                                >

                                <span class="payment-method-content">

                                    <span class="payment-icon">
                                        📱
                                    </span>

                                    <strong>
                                        bKash
                                    </strong>

                                    <small>
                                        Mobile payment
                                    </small>

                                </span>

                            </label>


                            <label class="payment-method">

                                <input
                                    type="radio"
                                    name="method"
                                    value="Nagad"
                                    <?= $method === 'Nagad' ? 'checked' : '' ?>
                                >

                                <span class="payment-method-content">

                                    <span class="payment-icon">
                                        📲
                                    </span>

                                    <strong>
                                        Nagad
                                    </strong>

                                    <small>
                                        Mobile payment
                                    </small>

                                </span>

                            </label>


                            <label class="payment-method">

                                <input
                                    type="radio"
                                    name="method"
                                    value="Card"
                                    <?= $method === 'Card' ? 'checked' : '' ?>
                                >

                                <span class="payment-method-content">

                                    <span class="payment-icon">
                                        💳
                                    </span>

                                    <strong>
                                        Card
                                    </strong>

                                    <small>
                                        Debit / Credit Card
                                    </small>

                                </span>

                            </label>


                            <label class="payment-method">

                                <input
                                    type="radio"
                                    name="method"
                                    value="Bank Transfer"
                                    <?= $method === 'Bank Transfer' ? 'checked' : '' ?>
                                >

                                <span class="payment-method-content">

                                    <span class="payment-icon">
                                        🏦
                                    </span>

                                    <strong>
                                        Bank Transfer
                                    </strong>

                                    <small>
                                        Direct bank payment
                                    </small>

                                </span>

                            </label>

                        </div>

                    </div>


                    <!-- Transaction ID -->

                    <div
                        class="form-group"
                        id="transactionGroup"
                    >

                        <label for="transaction_id">

                            Transaction ID

                            <span>
                                *
                            </span>

                        </label>

                        <input
                            type="text"
                            name="transaction_id"
                            id="transaction_id"
                            class="form-control"
                            maxlength="100"
                            placeholder="Enter transaction ID"
                            value="<?= e($transactionId) ?>"
                        >

                        <small class="form-help">
                            Required for bKash, Nagad, Card and Bank Transfer.
                        </small>

                    </div>


                    <!-- Demo Notice -->

                    <div class="payment-demo-notice">

                        <strong>
                            ℹ️ Demo Payment System
                        </strong>

                        <p>
                            This project uses a simulated payment process
                            for academic demonstration. No real money is
                            transferred through this system.
                        </p>

                    </div>


                    <!-- Submit -->

                    <button
                        type="submit"
                        class="btn btn-primary btn-lg payment-submit"
                    >
                        ✓ Confirm Payment
                    </button>


                    <a
                        href="my-bookings.php"
                        class="btn btn-outline btn-lg"
                    >
                        Cancel
                    </a>

                </form>

            </div>


            <!-- =================================
                 BOOKING SUMMARY
            ================================== -->

            <aside class="payment-summary">

                <h2>
                    Booking Summary
                </h2>


                <!-- Package -->

                <div class="payment-package">

                    <?php if (!empty($booking['image'])): ?>

                        <img
                            src="<?= e($booking['image']) ?>"
                            alt="<?= e($booking['title']) ?>"
                        >

                    <?php else: ?>

                        <div class="summary-image-placeholder">
                            🌴
                        </div>

                    <?php endif; ?>


                    <div>

                        <h3>
                            <?= e($booking['title']) ?>
                        </h3>

                        <p>
                            📍 <?= e($booking['destination']) ?>
                        </p>

                    </div>

                </div>


                <!-- Details -->

                <div class="summary-row">

                    <span>
                        Travel Date
                    </span>

                    <strong>
                        <?= date(
                            'd M Y',
                            strtotime($booking['travel_date'])
                        ) ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Number of People
                    </span>

                    <strong>
                        <?= (int) $booking['people'] ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Package Price
                    </span>

                    <strong>
                        ৳<?= number_format(
                            $booking['total_price'],
                            2
                        ) ?>
                    </strong>

                </div>


                <?php if ((float) $booking['discount'] > 0): ?>

                    <div class="summary-row discount-row">

                        <span>
                            Discount
                        </span>

                        <strong>
                            -৳<?= number_format(
                                $booking['discount'],
                                2
                            ) ?>
                        </strong>

                    </div>

                <?php endif; ?>


                <?php if (!empty($booking['coupon_code'])): ?>

                    <div class="summary-row">

                        <span>
                            Coupon
                        </span>

                        <strong>
                            <?= e($booking['coupon_code']) ?>
                        </strong>

                    </div>

                <?php endif; ?>


                <div class="summary-total">

                    <span>
                        Amount to Pay
                    </span>

                    <strong>
                        ৳<?= number_format(
                            $booking['final_price'],
                            2
                        ) ?>
                    </strong>

                </div>


                <!-- Booking Status -->

                <div class="payment-status-box">

                    <span>
                        Booking Status
                    </span>

                    <strong class="status-badge status-pending">
                        <?= e($booking['status']) ?>
                    </strong>

                </div>

            </aside>

        </div>

    </div>

</section>


<!-- =========================================
     SECURITY SECTION
========================================= -->

<section class="section light-section">

    <div class="container">

        <div class="info-grid">

            <div class="info-card">

                <div class="info-icon">
                    🔒
                </div>

                <h3>
                    Secure Process
                </h3>

                <p>
                    Your booking and payment information is handled securely.
                </p>

            </div>


            <div class="info-card">

                <div class="info-icon">
                    🧾
                </div>

                <h3>
                    Booking Record
                </h3>

                <p>
                    Your payment information is stored with your booking record.
                </p>

            </div>


            <div class="info-card">

                <div class="info-icon">
                    ✅
                </div>

                <h3>
                    Instant Confirmation
                </h3>

                <p>
                    Your booking status becomes confirmed after payment.
                </p>

            </div>

        </div>

    </div>

</section>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const radios =
        document.querySelectorAll(
            'input[name="method"]'
        );

    const transactionGroup =
        document.getElementById(
            'transactionGroup'
        );

    const transactionInput =
        document.getElementById(
            'transaction_id'
        );


    function updateTransactionField() {

        const selected =
            document.querySelector(
                'input[name="method"]:checked'
            );

        if (!selected) {
            return;
        }


        if (selected.value === 'Cash') {

            transactionGroup.style.display = 'none';

            transactionInput.required = false;

            transactionInput.value = '';

        } else {

            transactionGroup.style.display = 'block';

            transactionInput.required = true;

        }

    }


    radios.forEach(function (radio) {

        radio.addEventListener(
            'change',
            updateTransactionField
        );

    });


    updateTransactionField();

});
</script>


<?php include 'includes/footer.php'; ?>
