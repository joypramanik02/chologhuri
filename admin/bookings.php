<?php

require_once __DIR__ . '/../includes/db.php';

requireAdmin();

$pageTitle = 'Manage Bookings - CholoGhuri';

$errors = [];


/*
|--------------------------------------------------------------------------
| Update Booking Status
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_status'])) {

    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    $allowedStatuses = [
        'Pending',
        'Confirmed',
        'Cancelled'
    ];

    if ($bookingId <= 0) {
        $errors[] = 'Invalid booking ID.';
    } elseif (!in_array($newStatus, $allowedStatuses, true)) {
        $errors[] = 'Invalid booking status.';
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM bookings
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$bookingId]);

        if (!$stmt->fetch()) {

            $errors[] = 'Booking not found.';

        } else {

            $stmt = $pdo->prepare("
                UPDATE bookings
                SET status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $newStatus,
                $bookingId
            ]);

            redirectWithMessage(
                'bookings.php',
                'Booking status updated successfully.',
                'success'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Update Payment Status
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_status'])) {

    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $paymentStatus = $_POST['payment_status'] ?? '';

    $allowedPaymentStatuses = [
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ];

    if ($bookingId <= 0) {
        $errors[] = 'Invalid booking ID.';
    } elseif (
        !in_array(
            $paymentStatus,
            $allowedPaymentStatuses,
            true
        )
    ) {
        $errors[] = 'Invalid payment status.';
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT
                b.id,
                b.final_price
            FROM bookings b
            WHERE b.id = ?
            LIMIT 1
        ");

        $stmt->execute([$bookingId]);

        $booking = $stmt->fetch();

        if (!$booking) {

            $errors[] = 'Booking not found.';

        } else {

            $stmt = $pdo->prepare("
                SELECT id
                FROM payments
                WHERE booking_id = ?
                LIMIT 1
            ");

            $stmt->execute([$bookingId]);

            $payment = $stmt->fetch();

            $paidAt =
                $paymentStatus === 'Paid'
                    ? date('Y-m-d H:i:s')
                    : null;


            if ($payment) {

                $stmt = $pdo->prepare("
                    UPDATE payments
                    SET
                        status = ?,
                        amount = ?,
                        paid_at = ?
                    WHERE booking_id = ?
                ");

                $stmt->execute([
                    $paymentStatus,
                    $booking['final_price'],
                    $paidAt,
                    $bookingId
                ]);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO payments (
                        booking_id,
                        method,
                        status,
                        amount,
                        paid_at
                    )
                    VALUES (
                        ?,
                        'Cash',
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $bookingId,
                    $paymentStatus,
                    $booking['final_price'],
                    $paidAt
                ]);
            }


            /*
             * If payment becomes Paid,
             * booking becomes Confirmed.
             *
             * If payment becomes Refunded,
             * booking is not automatically changed.
             * Admin can separately change booking status.
             */

            if ($paymentStatus === 'Paid') {

                $stmt = $pdo->prepare("
                    UPDATE bookings
                    SET status = 'Confirmed'
                    WHERE id = ?
                    AND status != 'Cancelled'
                ");

                $stmt->execute([$bookingId]);
            }


            redirectWithMessage(
                'bookings.php',
                'Payment status updated successfully.',
                'success'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$search = trim($_GET['search'] ?? '');

$allowedStatuses = [
    'Pending',
    'Confirmed',
    'Cancelled'
];

if (
    $statusFilter !== '' &&
    !in_array($statusFilter, $allowedStatuses, true)
) {
    $statusFilter = '';
}


/*
|--------------------------------------------------------------------------
| Build Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        b.*,

        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,

        tp.title AS package_title,
        tp.destination,
        tp.duration,
        tp.image AS package_image,

        p.method AS payment_method,
        p.status AS payment_status,
        p.transaction_id,
        p.paid_at

    FROM bookings b

    INNER JOIN users u
        ON u.id = b.user_id

    INNER JOIN tour_packages tp
        ON tp.id = b.package_id

    LEFT JOIN payments p
        ON p.booking_id = b.id

    WHERE 1 = 1
";

$params = [];


/*
 * Status Filter
 */

if ($statusFilter !== '') {

    $sql .= "
        AND b.status = ?
    ";

    $params[] = $statusFilter;
}


/*
 * Date Filter
 */

if ($dateFilter !== '') {

    $sql .= "
        AND b.travel_date = ?
    ";

    $params[] = $dateFilter;
}


/*
 * Search
 */

if ($search !== '') {

    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
            OR tp.title LIKE ?
            OR tp.destination LIKE ?
            OR b.id = ?
        )
    ";

    $searchLike = '%' . $search . '%';

    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = (int) $search;
}


$sql .= "
    ORDER BY b.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$bookings = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Booking Statistics
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total_bookings,

        SUM(
            CASE
                WHEN status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_bookings,

        SUM(
            CASE
                WHEN status = 'Confirmed'
                THEN 1
                ELSE 0
            END
        ) AS confirmed_bookings,

        SUM(
            CASE
                WHEN status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_bookings

    FROM bookings
");

$bookingStats = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Revenue
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN b.status = 'Confirmed'
                    THEN b.final_price
                    ELSE 0
                END
            ),
            0
        ) AS confirmed_revenue,

        COALESCE(
            SUM(
                CASE
                    WHEN p.status = 'Paid'
                    THEN p.amount
                    ELSE 0
                END
            ),
            0
        ) AS paid_revenue

    FROM bookings b

    LEFT JOIN payments p
        ON p.booking_id = b.id
");

$revenueStats = $stmt->fetch();


include __DIR__ . '/header.php';

?>


<div class="admin-page">


    <!-- Page Header -->

    <div class="admin-page-header">

        <div>

            <span class="section-tag">
                Booking Management
            </span>

            <h2>
                Customer Bookings
            </h2>

            <p>
                View and manage all customer tour bookings.
            </p>

        </div>

    </div>


    <!-- Errors -->

    <?php if (!empty($errors)): ?>

        <div class="admin-form-errors">

            <?php foreach ($errors as $error): ?>

                <div>
                    ⚠️ <?= e($error) ?>
                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- Statistics -->

    <div class="admin-stats booking-admin-stats">


        <div class="stat-card">

            <div class="stat-icon">
                📋
            </div>

            <div>

                <span>
                    Total Bookings
                </span>

                <strong>
                    <?= (int) (
                        $bookingStats['total_bookings'] ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⏳
            </div>

            <div>

                <span>
                    Pending
                </span>

                <strong>
                    <?= (int) (
                        $bookingStats['pending_bookings'] ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ✅
            </div>

            <div>

                <span>
                    Confirmed
                </span>

                <strong>
                    <?= (int) (
                        $bookingStats['confirmed_bookings'] ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ❌
            </div>

            <div>

                <span>
                    Cancelled
                </span>

                <strong>
                    <?= (int) (
                        $bookingStats['cancelled_bookings'] ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                💰
            </div>

            <div>

                <span>
                    Confirmed Revenue
                </span>

                <strong>
                    ৳<?= number_format(
                        (float) (
                            $revenueStats['confirmed_revenue']
                            ?? 0
                        ),
                        2
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                💳
            </div>

            <div>

                <span>
                    Paid Revenue
                </span>

                <strong>
                    ৳<?= number_format(
                        (float) (
                            $revenueStats['paid_revenue']
                            ?? 0
                        ),
                        2
                    ) ?>
                </strong>

            </div>

        </div>


    </div>


    <!-- Filters -->

    <div class="admin-card booking-filter-card">

        <div class="card-header">

            <div>

                <h3>
                    Search & Filter
                </h3>

                <p>
                    Find bookings quickly.
                </p>

            </div>

        </div>


        <form
            action="bookings.php"
            method="GET"
            class="admin-filter-form"
        >


            <div class="form-group">

                <label for="search">
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    value="<?= e($search) ?>"
                    placeholder="Name, email, phone, package..."
                >

            </div>


            <div class="form-group">

                <label for="status">
                    Booking Status
                </label>

                <select
                    id="status"
                    name="status"
                    class="form-control"
                >

                    <option value="">
                        All Statuses
                    </option>

                    <option
                        value="Pending"
                        <?= $statusFilter === 'Pending'
                            ? 'selected'
                            : '' ?>
                    >
                        Pending
                    </option>

                    <option
                        value="Confirmed"
                        <?= $statusFilter === 'Confirmed'
                            ? 'selected'
                            : '' ?>
                    >
                        Confirmed
                    </option>

                    <option
                        value="Cancelled"
                        <?= $statusFilter === 'Cancelled'
                            ? 'selected'
                            : '' ?>
                    >
                        Cancelled
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label for="date">
                    Travel Date
                </label>

                <input
                    type="date"
                    id="date"
                    name="date"
                    class="form-control"
                    value="<?= e($dateFilter) ?>"
                >

            </div>


            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    🔍 Search
                </button>

                <a
                    href="bookings.php"
                    class="btn btn-outline"
                >
                    Reset
                </a>

            </div>


        </form>

    </div>


    <!-- Booking Table -->

    <div class="admin-card">

        <div class="card-header">

            <div>

                <h3>
                    Booking List
                </h3>

                <p>
                    <?= count($bookings) ?>
                    booking(s) found
                </p>

            </div>

        </div>


        <?php if (empty($bookings)): ?>


            <div class="empty-state">

                <div class="empty-icon">
                    📋
                </div>

                <h3>
                    No Bookings Found
                </h3>

                <p>
                    No booking matches your selected filters.
                </p>

            </div>


        <?php else: ?>


            <div class="table-wrapper">

                <table class="data-table booking-table">

                    <thead>

                        <tr>

                            <th>
                                Booking
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Package
                            </th>

                            <th>
                                Travel Date
                            </th>

                            <th>
                                People
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Booking Status
                            </th>

                            <th>
                                Payment
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($bookings as $booking): ?>


                        <tr>


                            <!-- Booking -->

                            <td>

                                <strong>
                                    #<?= (int) $booking['id'] ?>
                                </strong>

                                <small>
                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $booking['created_at']
                                        )
                                    ) ?>
                                </small>

                            </td>


                            <!-- Customer -->

                            <td>

                                <div class="booking-customer">

                                    <div class="booking-avatar">
                                        <?= e(
                                            strtoupper(
                                                substr(
                                                    $booking['user_name'],
                                                    0,
                                                    1
                                                )
                                            )
                                        ) ?>
                                    </div>

                                    <div>

                                        <strong>
                                            <?= e(
                                                $booking['user_name']
                                            ) ?>
                                        </strong>

                                        <small>
                                            <?= e(
                                                $booking['user_email']
                                            ) ?>
                                        </small>

                                        <?php if (
                                            !empty(
                                                $booking['user_phone']
                                            )
                                        ): ?>

                                            <small>
                                                📞
                                                <?= e(
                                                    $booking['user_phone']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </td>


                            <!-- Package -->

                            <td>

                                <div class="booking-package-cell">


                                    <?php if (
                                        !empty(
                                            $booking['package_image']
                                        )
                                    ): ?>

                                        <img
                                            src="../<?= e(
                                                $booking['package_image']
                                            ) ?>"
                                            alt="<?= e(
                                                $booking['package_title']
                                            ) ?>"
                                        >

                                    <?php else: ?>

                                        <div
                                            class="booking-package-placeholder"
                                        >
                                            🌴
                                        </div>

                                    <?php endif; ?>


                                    <div>

                                        <strong>
                                            <?= e(
                                                $booking['package_title']
                                            ) ?>
                                        </strong>

                                        <small>
                                            📍
                                            <?= e(
                                                $booking['destination']
                                            ) ?>
                                        </small>

                                        <small>
                                            <?= e(
                                                $booking['duration']
                                            ) ?>
                                        </small>

                                    </div>

                                </div>

                            </td>


                            <!-- Travel Date -->

                            <td>

                                <strong>
                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $booking['travel_date']
                                        )
                                    ) ?>
                                </strong>

                            </td>


                            <!-- People -->

                            <td>

                                <strong>
                                    <?= (int) $booking['people'] ?>
                                </strong>

                                <small>
                                    person(s)
                                </small>

                            </td>


                            <!-- Amount -->

                            <td>

                                <strong>
                                    ৳<?= number_format(
                                        (float) $booking['final_price'],
                                        2
                                    ) ?>
                                </strong>

                                <?php if (
                                    (float) $booking['discount'] > 0
                                ): ?>

                                    <small>
                                        Discount:
                                        ৳<?= number_format(
                                            (float) $booking['discount'],
                                            2
                                        ) ?>
                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- Booking Status -->

                            <td>

                                <span
                                    class="status status-<?= strtolower(
                                        $booking['status']
                                    ) ?>"
                                >
                                    <?= e(
                                        $booking['status']
                                    ) ?>
                                </span>


                                <form
                                    action="bookings.php"
                                    method="POST"
                                    class="status-form"
                                >

                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?= (int) $booking['id'] ?>"
                                    >

                                    <select
                                        name="status"
                                        class="small-select"
                                        onchange="this.form.submit()"
                                    >

                                        <option
                                            value="Pending"
                                            <?= $booking['status'] === 'Pending'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Pending
                                        </option>

                                        <option
                                            value="Confirmed"
                                            <?= $booking['status'] === 'Confirmed'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Confirmed
                                        </option>

                                        <option
                                            value="Cancelled"
                                            <?= $booking['status'] === 'Cancelled'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Cancelled
                                        </option>

                                    </select>

                                    <input
                                        type="hidden"
                                        name="update_booking_status"
                                        value="1"
                                    >

                                </form>

                            </td>


                            <!-- Payment -->

                            <td>

                                <?php
                                $paymentStatus =
                                    $booking['payment_status']
                                    ?? 'Pending';
                                ?>


                                <span
                                    class="status status-payment-<?= strtolower(
                                        $paymentStatus
                                    ) ?>"
                                >
                                    <?= e(
                                        $paymentStatus
                                    ) ?>
                                </span>


                                <?php if (
                                    !empty(
                                        $booking['payment_method']
                                    )
                                ): ?>

                                    <small>
                                        <?= e(
                                            $booking['payment_method']
                                        ) ?>
                                    </small>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $booking['transaction_id']
                                    )
                                ): ?>

                                    <small>
                                        TXN:
                                        <?= e(
                                            $booking['transaction_id']
                                        ) ?>
                                    </small>

                                <?php endif; ?>


                                <form
                                    action="bookings.php"
                                    method="POST"
                                    class="status-form"
                                >

                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?= (int) $booking['id'] ?>"
                                    >

                                    <select
                                        name="payment_status"
                                        class="small-select"
                                        onchange="this.form.submit()"
                                    >

                                        <option
                                            value="Pending"
                                            <?= $paymentStatus === 'Pending'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Pending
                                        </option>

                                        <option
                                            value="Paid"
                                            <?= $paymentStatus === 'Paid'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Paid
                                        </option>

                                        <option
                                            value="Failed"
                                            <?= $paymentStatus === 'Failed'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Failed
                                        </option>

                                        <option
                                            value="Refunded"
                                            <?= $paymentStatus === 'Refunded'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Refunded
                                        </option>

                                    </select>

                                    <input
                                        type="hidden"
                                        name="update_payment_status"
                                        value="1"
                                    >

                                </form>

                            </td>


                            <!-- Actions -->

                            <td>

                                <div class="booking-actions">

                                    <a
                                        href="../package-details.php?id=<?= (int) $booking['package_id'] ?>"
                                        class="btn btn-outline btn-sm"
                                        target="_blank"
                                    >
                                        View Package
                                    </a>

                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>

    </div>


    <!-- Important Note -->

    <div class="admin-info-box">

        <strong>
            ℹ️ Admin Note
        </strong>

        <p>
            Changing a payment to <strong>Paid</strong> automatically
            confirms the booking. Marking a payment as
            <strong>Refunded</strong> only records the refund status;
            this demo system does not process real money refunds.
        </p>

    </div>


</div>


<style>

.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.admin-page-header h2 {
    margin: 6px 0;
}

.admin-page-header p {
    margin: 0;
    color: #777;
}

.booking-admin-stats {
    margin-bottom: 25px;
}

.booking-filter-card {
    margin-bottom: 25px;
}

.admin-filter-form {
    display: grid;
    grid-template-columns:
        minmax(180px, 2fr)
        minmax(150px, 1fr)
        minmax(150px, 1fr)
        auto;
    gap: 15px;
    align-items: end;
}

.filter-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.booking-customer {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 190px;
}

.booking-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #eef3ff;
    color: #3157d5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.booking-customer strong {
    display: block;
}

.booking-customer small {
    display: block;
    color: #777;
    margin-top: 2px;
    font-size: 11px;
}

.booking-package-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 210px;
}

.booking-package-cell img,
.booking-package-placeholder {
    width: 60px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}

.booking-package-placeholder {
    background: #f1f3f5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.booking-package-cell strong {
    display: block;
    margin-bottom: 3px;
}

.booking-package-cell small {
    display: block;
    color: #777;
    font-size: 11px;
    margin-top: 2px;
}

.data-table td small {
    display: block;
    color: #777;
    font-size: 11px;
    margin-top: 3px;
}

.status-form {
    margin-top: 7px;
}

.small-select {
    width: 100%;
    min-width: 100px;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fff;
    font-size: 11px;
    cursor: pointer;
}

.booking-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 110px;
}

.admin-info-box {
    margin-top: 20px;
    padding: 18px 20px;
    border-radius: 10px;
    background: #f4f8ff;
    border: 1px solid #d9e5ff;
    color: #445;
}

.admin-info-box strong {
    color: #234;
}

.admin-info-box p {
    margin: 8px 0 0;
    line-height: 1.6;
}

@media (max-width: 1100px) {

    .admin-filter-form {
        grid-template-columns: 1fr 1fr;
    }

}

@media (max-width: 700px) {

    .admin-filter-form {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        width: 100%;
    }

    .filter-actions .btn {
        flex: 1;
    }

}

</style>


<?php include __DIR__ . '/footer.php'; ?>
