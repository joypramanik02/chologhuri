<?php

require_once __DIR__ . '/../includes/db.php';

requireAdmin();

$pageTitle = 'Admin Dashboard - CholoGhuri';


/*
|--------------------------------------------------------------------------
| Total Users
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM users
");

$totalUsers = (int) $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Total Packages
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM tour_packages
    WHERE status = 'active'
");

$totalPackages = (int) $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Total Bookings
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM bookings
");

$totalBookings = (int) $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Pending Bookings
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE status = 'Pending'
");

$pendingBookings = (int) $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Confirmed Bookings
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE status = 'Confirmed'
");

$confirmedBookings = (int) $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Cancelled Bookings
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE status = 'Cancelled'
");

$cancelledBookings = (int) $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Total Revenue
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(final_price), 0) AS revenue
    FROM bookings
    WHERE status = 'Confirmed'
");

$totalRevenue = (float) $stmt->fetch()['revenue'];


/*
|--------------------------------------------------------------------------
| Paid Revenue
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) AS revenue
    FROM payments
    WHERE status = 'Paid'
");

$paidRevenue = (float) $stmt->fetch()['revenue'];


/*
|--------------------------------------------------------------------------
| Reviews
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM reviews
");

$totalReviews = (int) $stmt->fetch()['total'];


/*
|--------------------------------------------------------------------------
| Recent Bookings
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        b.id,
        b.travel_date,
        b.people,
        b.final_price,
        b.status,
        u.name AS user_name,
        u.email AS user_email,
        tp.title AS package_title
    FROM bookings b
    INNER JOIN users u
        ON u.id = b.user_id
    INNER JOIN tour_packages tp
        ON tp.id = b.package_id
    ORDER BY b.created_at DESC
    LIMIT 8
");

$recentBookings = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Recent Users
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        name,
        email,
        created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");

$recentUsers = $stmt->fetchAll();


include __DIR__ . '/header.php';
?>


<div class="admin-dashboard">


    <!-- Welcome -->

    <div class="admin-welcome">

        <div>

            <span class="section-tag">
                Administration
            </span>

            <h2>
                Welcome back,
                <?= e($_SESSION['admin_name'] ?? 'Administrator') ?> 👋
            </h2>

            <p>
                Here's what's happening with your CholoGhuri travel platform.
            </p>

        </div>

        <div class="dashboard-date">
            📅 <?= date('d M Y') ?>
        </div>

    </div>


    <!-- Statistics -->

    <div class="admin-stats">


        <div class="stat-card">

            <div class="stat-icon">
                👥
            </div>

            <div>

                <span>
                    Total Users
                </span>

                <strong>
                    <?= number_format($totalUsers) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🗺️
            </div>

            <div>

                <span>
                    Active Packages
                </span>

                <strong>
                    <?= number_format($totalPackages) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                📋
            </div>

            <div>

                <span>
                    Total Bookings
                </span>

                <strong>
                    <?= number_format($totalBookings) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                💰
            </div>

            <div>

                <span>
                    Paid Revenue
                </span>

                <strong>
                    ৳<?= number_format($paidRevenue, 2) ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- Booking Statistics -->

    <div class="admin-card">

        <div class="card-header">

            <div>

                <h3>
                    Booking Overview
                </h3>

                <p>
                    Current booking status summary
                </p>

            </div>

            <a
                href="bookings.php"
                class="btn btn-outline btn-sm"
            >
                View All
            </a>

        </div>


        <div class="booking-overview-grid">

            <div class="overview-item pending">

                <span class="overview-icon">
                    ⏳
                </span>

                <div>

                    <strong>
                        <?= number_format($pendingBookings) ?>
                    </strong>

                    <span>
                        Pending
                    </span>

                </div>

            </div>


            <div class="overview-item confirmed">

                <span class="overview-icon">
                    ✓
                </span>

                <div>

                    <strong>
                        <?= number_format($confirmedBookings) ?>
                    </strong>

                    <span>
                        Confirmed
                    </span>

                </div>

            </div>


            <div class="overview-item cancelled">

                <span class="overview-icon">
                    ×
                </span>

                <div>

                    <strong>
                        <?= number_format($cancelledBookings) ?>
                    </strong>

                    <span>
                        Cancelled
                    </span>

                </div>

            </div>


            <div class="overview-item reviews">

                <span class="overview-icon">
                    ⭐
                </span>

                <div>

                    <strong>
                        <?= number_format($totalReviews) ?>
                    </strong>

                    <span>
                        Reviews
                    </span>

                </div>

            </div>

        </div>

    </div>


    <!-- Recent Bookings -->

    <div class="admin-card">

        <div class="card-header">

            <div>

                <h3>
                    Recent Bookings
                </h3>

                <p>
                    Latest customer bookings
                </p>

            </div>

            <a
                href="bookings.php"
                class="btn btn-outline btn-sm"
            >
                Manage Bookings
            </a>

        </div>


        <?php if (empty($recentBookings)): ?>

            <div class="empty-state">

                <h3>
                    No bookings yet
                </h3>

                <p>
                    New bookings will appear here.
                </p>

            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table class="data-table">

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
                                Amount
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($recentBookings as $booking): ?>

                        <tr>

                            <td>
                                #<?= (int) $booking['id'] ?>
                            </td>


                            <td>

                                <strong>
                                    <?= e($booking['user_name']) ?>
                                </strong>

                                <small>
                                    <?= e($booking['user_email']) ?>
                                </small>

                            </td>


                            <td>
                                <?= e($booking['package_title']) ?>
                            </td>


                            <td>
                                <?= date(
                                    'd M Y',
                                    strtotime($booking['travel_date'])
                                ) ?>
                            </td>


                            <td>
                                ৳<?= number_format(
                                    $booking['final_price'],
                                    2
                                ) ?>
                            </td>


                            <td>

                                <span
                                    class="status status-<?= strtolower(e($booking['status'])) ?>"
                                >
                                    <?= e($booking['status']) ?>
                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>


    <!-- Bottom Grid -->

    <div class="admin-bottom-grid">


        <!-- Recent Users -->

        <div class="admin-card">

            <div class="card-header">

                <div>

                    <h3>
                        Recent Users
                    </h3>

                    <p>
                        New customer registrations
                    </p>

                </div>

                <a
                    href="users.php"
                    class="btn btn-outline btn-sm"
                >
                    View Users
                </a>

            </div>


            <div class="recent-users">

                <?php if (empty($recentUsers)): ?>

                    <p>
                        No users yet.
                    </p>

                <?php else: ?>

                    <?php foreach ($recentUsers as $user): ?>

                        <div class="recent-user">

                            <div class="recent-user-avatar">

                                <?= strtoupper(
                                    substr($user['name'], 0, 1)
                                ) ?>

                            </div>


                            <div class="recent-user-info">

                                <strong>
                                    <?= e($user['name']) ?>
                                </strong>

                                <span>
                                    <?= e($user['email']) ?>
                                </span>

                            </div>


                            <small>
                                <?= date(
                                    'd M',
                                    strtotime($user['created_at'])
                                ) ?>
                            </small>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>


        <!-- Quick Actions -->

        <div class="admin-card">

            <div class="card-header">

                <div>

                    <h3>
                        Quick Actions
                    </h3>

                    <p>
                        Manage your platform
                    </p>

                </div>

            </div>


            <div class="quick-actions">

                <a href="packages.php">
                    <span>🗺️</span>
                    <div>
                        <strong>Manage Packages</strong>
                        <small>Add or edit tours</small>
                    </div>
                </a>


                <a href="bookings.php">
                    <span>📋</span>
                    <div>
                        <strong>Manage Bookings</strong>
                        <small>View customer bookings</small>
                    </div>
                </a>


                <a href="coupons.php">
                    <span>🎟️</span>
                    <div>
                        <strong>Manage Coupons</strong>
                        <small>Create discounts</small>
                    </div>
                </a>


                <a href="payments.php">
                    <span>💳</span>
                    <div>
                        <strong>View Payments</strong>
                        <small>Check payment records</small>
                    </div>
                </a>

            </div>

        </div>

    </div>


</div>


<style>

.admin-welcome {
    background: #ffffff;
    border-radius: 16px;
    padding: 25px 28px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    border: 1px solid #eeeeee;
}

.admin-welcome h2 {
    margin: 7px 0 5px;
}

.admin-welcome p {
    margin: 0;
    color: #777;
}

.dashboard-date {
    color: #666;
    white-space: nowrap;
}

.booking-overview-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.overview-item {
    padding: 18px;
    border-radius: 12px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    gap: 13px;
}

.overview-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    font-size: 20px;
}

.overview-item div {
    display: flex;
    flex-direction: column;
}

.overview-item strong {
    font-size: 22px;
}

.overview-item span:last-child {
    color: #777;
    font-size: 13px;
}

.admin-bottom-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.recent-users {
    display: flex;
    flex-direction: column;
}

.recent-user {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 0;
    border-bottom: 1px solid #eeeeee;
}

.recent-user:last-child {
    border-bottom: none;
}

.recent-user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #f1f5ff;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: 700;
}

.recent-user-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.recent-user-info span {
    color: #777;
    font-size: 12px;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.quick-actions a {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 14px;
    border-radius: 10px;
    background: #f8f9fa;
    text-decoration: none;
    color: inherit;
    transition: 0.2s;
}

.quick-actions a:hover {
    transform: translateX(3px);
}

.quick-actions a > span {
    font-size: 23px;
}

.quick-actions a div {
    display: flex;
    flex-direction: column;
}

.quick-actions small {
    color: #777;
}

.data-table td strong {
    display: block;
}

.data-table td small {
    display: block;
    color: #777;
    margin-top: 3px;
}

@media (max-width: 900px) {

    .booking-overview-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .admin-bottom-grid {
        grid-template-columns: 1fr;
    }

}

@media (max-width: 600px) {

    .admin-welcome {
        flex-direction: column;
        align-items: flex-start;
    }

    .booking-overview-grid {
        grid-template-columns: 1fr;
    }

}

</style>


<?php include __DIR__ . '/footer.php'; ?>
