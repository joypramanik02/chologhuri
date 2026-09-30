<?php

require_once __DIR__ . '/../includes/db.php';

requireAdmin();

$pageTitle = 'Manage Payments - CholoGhuri';

$errors = [];


/*
|--------------------------------------------------------------------------
| Update Payment Status
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_payment'])
) {

    $paymentId = (int) (
        $_POST['payment_id'] ?? 0
    );

    $newStatus = $_POST['status'] ?? 'Pending';


    $allowedStatuses = [
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ];


    if ($paymentId <= 0) {

        $errors[] = 'Invalid payment ID.';

    } elseif (
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $errors[] = 'Invalid payment status.';

    } else {

        /*
         * Get payment and booking.
         */
        $stmt = $pdo->prepare("
            SELECT
                p.*,
                b.status AS booking_status
            FROM payments p
            INNER JOIN bookings b
                ON b.id = p.booking_id
            WHERE p.id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $paymentId
        ]);

        $payment = $stmt->fetch();


        if (!$payment) {

            $errors[] =
                'Payment record not found.';

        } else {

            try {

                $pdo->beginTransaction();


                /*
                 * Payment timestamp.
                 */
                if ($newStatus === 'Paid') {

                    $paidAt = date(
                        'Y-m-d H:i:s'
                    );

                } else {

                    $paidAt = null;
                }


                /*
                 * Update payment.
                 */
                $stmt = $pdo->prepare("
                    UPDATE payments
                    SET
                        status = ?,
                        paid_at = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $newStatus,
                    $paidAt,
                    $paymentId
                ]);


                /*
                 * If payment is Paid,
                 * confirm booking automatically.
                 *
                 * Cancelled booking will remain cancelled.
                 */
                if (
                    $newStatus === 'Paid' &&
                    $payment['booking_status'] !== 'Cancelled'
                ) {

                    $stmt = $pdo->prepare("
                        UPDATE bookings
                        SET status = 'Confirmed'
                        WHERE id = ?
                        AND status != 'Cancelled'
                    ");

                    $stmt->execute([
                        $payment['booking_id']
                    ]);
                }


                /*
                 * If payment becomes Failed or Pending,
                 * do not automatically cancel a booking.
                 *
                 * Admin can separately change
                 * booking status from bookings.php.
                 */


                $pdo->commit();


                redirectWithMessage(
                    'payments.php',
                    'Payment status updated successfully.',
                    'success'
                );


            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $errors[] =
                    'Failed to update payment status.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET['search'] ?? ''
);

$statusFilter =
    $_GET['status'] ?? '';

$methodFilter =
    $_GET['method'] ?? '';

$dateFilter =
    $_GET['date'] ?? '';


/*
|--------------------------------------------------------------------------
| Build Query
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];


if ($search !== '') {

    $where[] = "
        (
            CAST(p.id AS CHAR) LIKE ?
            OR CAST(p.booking_id AS CHAR) LIKE ?
            OR u.name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
            OR tp.title LIKE ?
            OR p.transaction_id LIKE ?
        )
    ";

    $searchValue =
        '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


if (
    in_array(
        $statusFilter,
        [
            'Pending',
            'Paid',
            'Failed',
            'Refunded'
        ],
        true
    )
) {

    $where[] = "p.status = ?";

    $params[] =
        $statusFilter;
}


if (
    in_array(
        $methodFilter,
        [
            'Cash',
            'bKash',
            'Nagad',
            'Card',
            'Bank Transfer'
        ],
        true
    )
) {

    $where[] = "p.method = ?";

    $params[] =
        $methodFilter;
}


if ($dateFilter !== '') {

    $dateObject =
        DateTime::createFromFormat(
            'Y-m-d',
            $dateFilter
        );

    if (
        $dateObject &&
        $dateObject->format('Y-m-d') === $dateFilter
    ) {

        $where[] =
            "DATE(p.created_at) = ?";

        $params[] =
            $dateFilter;
    }
}


$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' . implode(
            ' AND ',
            $where
        );
}


/*
|--------------------------------------------------------------------------
| Fetch Payments
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.booking_id,
        p.method,
        p.status,
        p.transaction_id,
        p.amount,
        p.paid_at,
        p.created_at,

        b.travel_date,
        b.people,
        b.total_price,
        b.discount,
        b.final_price,
        b.status AS booking_status,

        u.id AS user_id,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,

        tp.id AS package_id,
        tp.title AS package_title,
        tp.destination,
        tp.image

    FROM payments p

    INNER JOIN bookings b
        ON b.id = p.booking_id

    INNER JOIN users u
        ON u.id = b.user_id

    INNER JOIN tour_packages tp
        ON tp.id = b.package_id

    $whereSql

    ORDER BY p.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$payments =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Payment Statistics
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        COUNT(*) AS total_payments,

        SUM(
            CASE
                WHEN status = 'Paid'
                THEN 1
                ELSE 0
            END
        ) AS paid_payments,

        SUM(
            CASE
                WHEN status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_payments,

        SUM(
            CASE
                WHEN status = 'Failed'
                THEN 1
                ELSE 0
            END
        ) AS failed_payments,

        SUM(
            CASE
                WHEN status = 'Refunded'
                THEN 1
                ELSE 0
            END
        ) AS refunded_payments,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Paid'
                    THEN amount
                    ELSE 0
                END
            ),
            0
        ) AS paid_amount,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Pending'
                    THEN amount
                    ELSE 0
                END
            ),
            0
        ) AS pending_amount,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Refunded'
                    THEN amount
                    ELSE 0
                END
            ),
            0
        ) AS refunded_amount

    FROM payments
");

$paymentStats =
    $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Payment Method Statistics
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        method,
        COUNT(*) AS total,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Paid'
                    THEN amount
                    ELSE 0
                END
            ),
            0
        ) AS paid_amount
    FROM payments
    GROUP BY method
    ORDER BY total DESC
");

$methodStats =
    $stmt->fetchAll();


include __DIR__ . '/header.php';

?>


<div class="admin-page">


    <!-- Page Header -->

    <div class="admin-page-header">

        <div>

            <span class="section-tag">
                Financial Management
            </span>

            <h2>
                Payments
            </h2>

            <p>
                Monitor and manage customer payment records.
            </p>

        </div>


        <a
            href="bookings.php"
            class="btn btn-outline"
        >
            View Bookings
        </a>

    </div>


    <!-- Statistics -->

    <div class="admin-stats">


        <div class="stat-card">

            <div class="stat-icon">
                💳
            </div>

            <div>

                <span>
                    Total Payments
                </span>

                <strong>
                    <?= (int) (
                        $paymentStats[
                            'total_payments'
                        ] ?? 0
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
                    Paid
                </span>

                <strong>
                    <?= (int) (
                        $paymentStats[
                            'paid_payments'
                        ] ?? 0
                    ) ?>
                </strong>

                <small>
                    ৳<?= number_format(
                        (float) (
                            $paymentStats[
                                'paid_amount'
                            ] ?? 0
                        ),
                        2
                    ) ?>
                </small>

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
                        $paymentStats[
                            'pending_payments'
                        ] ?? 0
                    ) ?>
                </strong>

                <small>
                    ৳<?= number_format(
                        (float) (
                            $paymentStats[
                                'pending_amount'
                            ] ?? 0
                        ),
                        2
                    ) ?>
                </small>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ❌
            </div>

            <div>

                <span>
                    Failed
                </span>

                <strong>
                    <?= (int) (
                        $paymentStats[
                            'failed_payments'
                        ] ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ↩️
            </div>

            <div>

                <span>
                    Refunded
                </span>

                <strong>
                    <?= (int) (
                        $paymentStats[
                            'refunded_payments'
                        ] ?? 0
                    ) ?>
                </strong>

                <small>
                    ৳<?= number_format(
                        (float) (
                            $paymentStats[
                                'refunded_amount'
                            ] ?? 0
                        ),
                        2
                    ) ?>
                </small>

            </div>

        </div>


    </div>


    <!-- Error Messages -->

    <?php if (!empty($errors)): ?>

        <div class="admin-form-errors">

            <?php foreach ($errors as $error): ?>

                <div>
                    ⚠️ <?= e($error) ?>
                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- Payment Methods -->

    <?php if (!empty($methodStats)): ?>

        <div class="admin-card payment-method-summary">

            <div class="card-header">

                <div>

                    <h3>
                        Payment Methods
                    </h3>

                    <p>
                        Payment distribution by method.
                    </p>

                </div>

            </div>


            <div class="method-summary-grid">


                <?php foreach (
                    $methodStats as $method
                ): ?>


                    <div class="method-summary-card">

                        <span class="method-summary-icon">

                            <?php

                            $methodIcon = [
                                'Cash' => '💵',
                                'bKash' => '📱',
                                'Nagad' => '📲',
                                'Card' => '💳',
                                'Bank Transfer' => '🏦'
                            ];

                            echo $methodIcon[
                                $method['method']
                            ] ?? '💰';

                            ?>

                        </span>


                        <div>

                            <strong>
                                <?= e(
                                    $method['method']
                                ) ?>
                            </strong>

                            <span>
                                <?= (int) (
                                    $method['total']
                                ) ?>
                                payment(s)
                            </span>

                            <small>
                                Paid:
                                ৳<?= number_format(
                                    (float) (
                                        $method[
                                            'paid_amount'
                                        ]
                                    ),
                                    2
                                ) ?>
                            </small>

                        </div>

                    </div>


                <?php endforeach; ?>


            </div>

        </div>

    <?php endif; ?>


    <!-- Filters -->

    <div class="admin-card payment-filter-card">

        <div class="card-header">

            <div>

                <h3>
                    Search & Filter
                </h3>

                <p>
                    Find specific payment records.
                </p>

            </div>

        </div>


        <form
            action="payments.php"
            method="GET"
            class="payment-filter-form"
        >


            <div class="form-row">


                <!-- Search -->

                <div class="form-group">

                    <label for="search">
                        Search
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-control"
                        value="<?= e(
                            $search
                        ) ?>"
                        placeholder="Name, email, booking ID, transaction..."
                    >

                </div>


                <!-- Status -->

                <div class="form-group">

                    <label for="status">
                        Payment Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-control"
                    >

                        <option value="">
                            All Status
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
                            value="Paid"
                            <?= $statusFilter === 'Paid'
                                ? 'selected'
                                : '' ?>
                        >
                            Paid
                        </option>

                        <option
                            value="Failed"
                            <?= $statusFilter === 'Failed'
                                ? 'selected'
                                : '' ?>
                        >
                            Failed
                        </option>

                        <option
                            value="Refunded"
                            <?= $statusFilter === 'Refunded'
                                ? 'selected'
                                : '' ?>
                        >
                            Refunded
                        </option>

                    </select>

                </div>


                <!-- Method -->

                <div class="form-group">

                    <label for="method">
                        Payment Method
                    </label>

                    <select
                        id="method"
                        name="method"
                        class="form-control"
                    >

                        <option value="">
                            All Methods
                        </option>

                        <option
                            value="Cash"
                            <?= $methodFilter === 'Cash'
                                ? 'selected'
                                : '' ?>
                        >
                            Cash
                        </option>

                        <option
                            value="bKash"
                            <?= $methodFilter === 'bKash'
                                ? 'selected'
                                : '' ?>
                        >
                            bKash
                        </option>

                        <option
                            value="Nagad"
                            <?= $methodFilter === 'Nagad'
                                ? 'selected'
                                : '' ?>
                        >
                            Nagad
                        </option>

                        <option
                            value="Card"
                            <?= $methodFilter === 'Card'
                                ? 'selected'
                                : '' ?>
                        >
                            Card
                        </option>

                        <option
                            value="Bank Transfer"
                            <?= $methodFilter === 'Bank Transfer'
                                ? 'selected'
                                : '' ?>
                        >
                            Bank Transfer
                        </option>

                    </select>

                </div>


                <!-- Date -->

                <div class="form-group">

                    <label for="date">
                        Payment Date
                    </label>

                    <input
                        type="date"
                        id="date"
                        name="date"
                        class="form-control"
                        value="<?= e(
                            $dateFilter
                        ) ?>"
                    >

                </div>


            </div>


            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    🔍 Search
                </button>

                <a
                    href="payments.php"
                    class="btn btn-outline"
                >
                    Reset
                </a>

            </div>


        </form>

    </div>


    <!-- Payment List -->

    <div class="admin-card">

        <div class="card-header">

            <div>

                <h3>
                    Payment Records
                </h3>

                <p>
                    <?= count($payments) ?>
                    payment(s) found
                </p>

            </div>

        </div>


        <?php if (empty($payments)): ?>


            <div class="empty-state">

                <div class="empty-icon">
                    💳
                </div>

                <h3>
                    No Payments Found
                </h3>

                <p>
                    No payment records match your current search or filters.
                </p>

            </div>


        <?php else: ?>


            <div class="table-wrapper">

                <table class="data-table payment-table">


                    <thead>

                        <tr>

                            <th>
                                Payment
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Package
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Method
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Booking
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $payments as $payment
                    ): ?>


                        <tr>


                            <!-- Payment ID -->

                            <td>

                                <strong>
                                    #PAY<?= (int) (
                                        $payment['id']
                                    ) ?>
                                </strong>

                                <small>
                                    Booking #<?= (int) (
                                        $payment[
                                            'booking_id'
                                        ]
                                    ) ?>
                                </small>


                                <?php if (
                                    !empty(
                                        $payment[
                                            'transaction_id'
                                        ]
                                    )
                                ): ?>

                                    <small class="transaction-id">

                                        TXN:
                                        <?= e(
                                            $payment[
                                                'transaction_id'
                                            ]
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- Customer -->

                            <td>

                                <strong>
                                    <?= e(
                                        $payment[
                                            'user_name'
                                        ]
                                    ) ?>
                                </strong>

                                <small>
                                    <?= e(
                                        $payment[
                                            'user_email'
                                        ]
                                    ) ?>
                                </small>

                                <?php if (
                                    !empty(
                                        $payment[
                                            'user_phone'
                                        ]
                                    )
                                ): ?>

                                    <small>
                                        <?= e(
                                            $payment[
                                                'user_phone'
                                            ]
                                        ) ?>
                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- Package -->

                            <td>

                                <strong>
                                    <?= e(
                                        $payment[
                                            'package_title'
                                        ]
                                    ) ?>
                                </strong>

                                <small>
                                    📍
                                    <?= e(
                                        $payment[
                                            'destination'
                                        ]
                                    ) ?>
                                </small>

                                <small>
                                    📅
                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $payment[
                                                'travel_date'
                                            ]
                                        )
                                    ) ?>
                                </small>

                            </td>


                            <!-- Amount -->

                            <td>

                                <strong class="payment-amount">
                                    ৳<?= number_format(
                                        (float) (
                                            $payment[
                                                'amount'
                                            ]
                                        ),
                                        2
                                    ) ?>
                                </strong>

                                <?php if (
                                    (float) (
                                        $payment[
                                            'discount'
                                        ]
                                    ) > 0
                                ): ?>

                                    <small>
                                        Discount:
                                        ৳<?= number_format(
                                            (float) (
                                                $payment[
                                                    'discount'
                                                ]
                                            ),
                                            2
                                        ) ?>
                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- Method -->

                            <td>

                                <span class="payment-method-badge">

                                    <?php

                                    $icon = [
                                        'Cash' => '💵',
                                        'bKash' => '📱',
                                        'Nagad' => '📲',
                                        'Card' => '💳',
                                        'Bank Transfer' => '🏦'
                                    ];

                                    echo $icon[
                                        $payment['method']
                                    ] ?? '💰';

                                    ?>

                                    <?= e(
                                        $payment['method']
                                    ) ?>

                                </span>

                            </td>


                            <!-- Status -->

                            <td>

                                <?php

                                $statusClass = strtolower(
                                    $payment['status']
                                );

                                ?>

                                <span
                                    class="payment-status payment-status-<?= e(
                                        $statusClass
                                    ) ?>"
                                >
                                    <?= e(
                                        $payment['status']
                                    ) ?>
                                </span>


                                <?php if (
                                    !empty(
                                        $payment[
                                            'paid_at'
                                        ]
                                    )
                                ): ?>

                                    <small>

                                        Paid:
                                        <?= date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $payment[
                                                    'paid_at'
                                                ]
                                            )
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- Booking Status -->

                            <td>

                                <?php

                                $bookingStatusClass =
                                    strtolower(
                                        $payment[
                                            'booking_status'
                                        ]
                                    );

                                ?>

                                <span
                                    class="status status-<?= e(
                                        $bookingStatusClass
                                    ) ?>"
                                >
                                    <?= e(
                                        $payment[
                                            'booking_status'
                                        ]
                                    ) ?>
                                </span>

                                <small>

                                    <?= (int) (
                                        $payment[
                                            'people'
                                        ]
                                    ) ?>
                                    person(s)

                                </small>

                            </td>


                            <!-- Created -->

                            <td>

                                <?= date(
                                    'd M Y',
                                    strtotime(
                                        $payment[
                                            'created_at'
                                        ]
                                    )
                                ) ?>

                                <small>
                                    <?= date(
                                        'h:i A',
                                        strtotime(
                                            $payment[
                                                'created_at'
                                            ]
                                        )
                                    ) ?>
                                </small>

                            </td>


                            <!-- Action -->

                            <td>

                                <form
                                    action="payments.php"
                                    method="POST"
                                    class="payment-update-form"
                                >

                                    <input
                                        type="hidden"
                                        name="payment_id"
                                        value="<?= (int) (
                                            $payment[
                                                'id'
                                            ]
                                        ) ?>"
                                    >


                                    <select
                                        name="status"
                                        class="form-control form-control-sm"
                                    >

                                        <option
                                            value="Pending"
                                            <?= $payment[
                                                'status'
                                            ] === 'Pending'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Pending
                                        </option>

                                        <option
                                            value="Paid"
                                            <?= $payment[
                                                'status'
                                            ] === 'Paid'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Paid
                                        </option>

                                        <option
                                            value="Failed"
                                            <?= $payment[
                                                'status'
                                            ] === 'Failed'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Failed
                                        </option>

                                        <option
                                            value="Refunded"
                                            <?= $payment[
                                                'status'
                                            ] === 'Refunded'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Refunded
                                        </option>

                                    </select>


                                    <button
                                        type="submit"
                                        name="update_payment"
                                        class="btn btn-primary btn-sm"
                                    >
                                        Update
                                    </button>

                                </form>


                                <a
                                    href="bookings.php?search=<?= urlencode(
                                        $payment[
                                            'booking_id'
                                        ]
                                    ) ?>"
                                    class="btn btn-outline btn-sm payment-booking-link"
                                >
                                    View Booking
                                </a>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>


    </div>


    <!-- Important Notice -->

    <div class="admin-info-box payment-info-box">

        <strong>
            ℹ️ Payment Management Note
        </strong>

        <p>
            This project uses a demo payment system. No real bKash,
            Nagad, Card or Bank payment gateway is connected.
            Updating a payment to <strong>Paid</strong> records the
            payment as successful and automatically confirms the related
            booking.
        </p>

        <p>
            Setting a payment to <strong>Refunded</strong> only changes
            the payment record. Actual money refunds are not processed
            automatically.
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

.payment-method-summary {
    margin-bottom: 25px;
}

.method-summary-grid {
    display: grid;
    grid-template-columns: repeat(
        auto-fit,
        minmax(180px, 1fr)
    );
    gap: 15px;
}

.method-summary-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border: 1px solid #e8ebf0;
    border-radius: 10px;
    background: #fafbfc;
}

.method-summary-icon {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #eef2ff;
    font-size: 21px;
}

.method-summary-card strong {
    display: block;
    margin-bottom: 3px;
}

.method-summary-card span {
    display: block;
    font-size: 12px;
    color: #777;
}

.method-summary-card small {
    display: block;
    margin-top: 4px;
    color: #198754;
    font-weight: 600;
}

.payment-filter-card {
    margin-bottom: 25px;
}

.payment-filter-form .form-row {
    align-items: end;
}

.filter-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 5px;
}

.payment-table td {
    vertical-align: top;
}

.payment-table td strong {
    display: block;
}

.payment-table td small {
    display: block;
    margin-top: 4px;
    color: #777;
    font-size: 11px;
    line-height: 1.4;
}

.transaction-id {
    word-break: break-all;
}

.payment-amount {
    color: #198754;
    font-size: 15px;
}

.payment-method-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border-radius: 20px;
    background: #f2f4f8;
    white-space: nowrap;
    font-size: 12px;
    font-weight: 600;
}

.payment-status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.payment-status-paid {
    background: #e8f7ed;
    color: #198754;
}

.payment-status-pending {
    background: #fff5d8;
    color: #946c00;
}

.payment-status-failed {
    background: #ffe9e9;
    color: #c62828;
}

.payment-status-refunded {
    background: #e9eefc;
    color: #3157a6;
}

.payment-update-form {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 125px;
}

.form-control-sm {
    min-height: 34px;
    padding: 6px 8px;
    font-size: 12px;
}

.payment-booking-link {
    display: block;
    margin-top: 6px;
    text-align: center;
}

.payment-info-box {
    margin-top: 20px;
}

.payment-info-box p {
    margin: 8px 0 0;
    line-height: 1.6;
}

@media (max-width: 950px) {

    .admin-page-header {
        align-items: flex-start;
        flex-direction: column;
    }

}

@media (max-width: 700px) {

    .method-summary-grid {
        grid-template-columns: 1fr;
    }

}

</style>


<?php include __DIR__ . '/footer.php'; ?>
