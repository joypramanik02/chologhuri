<?php

require_once __DIR__ . '/../includes/db.php';

requireAdmin();

$pageTitle = 'Manage Users - CholoGhuri';

$search = trim($_GET['search'] ?? '');


/*
|--------------------------------------------------------------------------
| User Details
|--------------------------------------------------------------------------
*/

$viewUser = null;

if (isset($_GET['view'])) {

    $viewId = (int) $_GET['view'];

    if ($viewId > 0) {

        $stmt = $pdo->prepare("
            SELECT
                u.*,

                (
                    SELECT COUNT(*)
                    FROM bookings b
                    WHERE b.user_id = u.id
                ) AS booking_count,

                (
                    SELECT COUNT(*)
                    FROM wishlist w
                    WHERE w.user_id = u.id
                ) AS wishlist_count,

                (
                    SELECT COUNT(*)
                    FROM reviews r
                    WHERE r.user_id = u.id
                ) AS review_count,

                (
                    SELECT COALESCE(
                        SUM(b.final_price),
                        0
                    )
                    FROM bookings b
                    WHERE b.user_id = u.id
                    AND b.status = 'Confirmed'
                ) AS total_spent

            FROM users u

            WHERE u.id = ?

            LIMIT 1
        ");

        $stmt->execute([$viewId]);

        $viewUser = $stmt->fetch();

        if (!$viewUser) {

            redirectWithMessage(
                'users.php',
                'User not found.',
                'error'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| User Bookings for Details View
|--------------------------------------------------------------------------
*/

$userBookings = [];

if ($viewUser) {

    $stmt = $pdo->prepare("
        SELECT
            b.*,
            tp.title AS package_title,
            tp.destination,
            p.status AS payment_status,
            p.method AS payment_method

        FROM bookings b

        INNER JOIN tour_packages tp
            ON tp.id = b.package_id

        LEFT JOIN payments p
            ON p.booking_id = b.id

        WHERE b.user_id = ?

        ORDER BY b.created_at DESC
    ");

    $stmt->execute([
        $viewUser['id']
    ]);

    $userBookings = $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| Fetch Users
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        u.*,

        (
            SELECT COUNT(*)
            FROM bookings b
            WHERE b.user_id = u.id
        ) AS booking_count,

        (
            SELECT COUNT(*)
            FROM wishlist w
            WHERE w.user_id = u.id
        ) AS wishlist_count,

        (
            SELECT COUNT(*)
            FROM reviews r
            WHERE r.user_id = u.id
        ) AS review_count

    FROM users u

    WHERE 1 = 1
";

$params = [];


if ($search !== '') {

    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
            OR u.address LIKE ?
            OR u.id = ?
        )
    ";

    $searchLike = '%' . $search . '%';

    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = (int) $search;
}


$sql .= "
    ORDER BY u.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$users = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| User Statistics
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total_users
    FROM users
");

$totalUsers = (int) (
    $stmt->fetch()['total_users'] ?? 0
);


$stmt = $pdo->query("
    SELECT
        COUNT(DISTINCT user_id) AS active_users
    FROM bookings
    WHERE created_at >= DATE_SUB(
        NOW(),
        INTERVAL 30 DAY
    )
");

$activeUsers = (int) (
    $stmt->fetch()['active_users'] ?? 0
);


$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total_reviews
    FROM reviews
");

$totalReviews = (int) (
    $stmt->fetch()['total_reviews'] ?? 0
);


$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total_wishlist
    FROM wishlist
");

$totalWishlist = (int) (
    $stmt->fetch()['total_wishlist'] ?? 0
);


include __DIR__ . '/header.php';

?>


<div class="admin-page">


    <!-- Page Header -->

    <div class="admin-page-header">

        <div>

            <span class="section-tag">
                User Management
            </span>

            <h2>
                Registered Users
            </h2>

            <p>
                View and manage customers registered with CholoGhuri.
            </p>

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
                    <?= $totalUsers ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🟢
            </div>

            <div>

                <span>
                    Active Users
                </span>

                <strong>
                    <?= $activeUsers ?>
                </strong>

                <small>
                    Last 30 days
                </small>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⭐
            </div>

            <div>

                <span>
                    Reviews
                </span>

                <strong>
                    <?= $totalReviews ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ♡
            </div>

            <div>

                <span>
                    Wishlist Items
                </span>

                <strong>
                    <?= $totalWishlist ?>
                </strong>

            </div>

        </div>


    </div>


    <?php if ($viewUser): ?>


        <!-- User Details -->

        <div class="admin-card user-detail-card">

            <div class="card-header">

                <div>

                    <h3>
                        User Details
                    </h3>

                    <p>
                        Customer account information
                    </p>

                </div>

                <a
                    href="users.php"
                    class="btn btn-outline btn-sm"
                >
                    ← Back to Users
                </a>

            </div>


            <div class="user-detail-layout">


                <!-- Profile -->

                <div class="user-profile-panel">

                    <div class="large-user-avatar">

                        <?php if (
                            !empty(
                                $viewUser['profile_image']
                            )
                        ): ?>

                            <img
                                src="../<?= e(
                                    $viewUser['profile_image']
                                ) ?>"
                                alt="<?= e(
                                    $viewUser['name']
                                ) ?>"
                            >

                        <?php else: ?>

                            <?= e(
                                strtoupper(
                                    substr(
                                        $viewUser['name'],
                                        0,
                                        1
                                    )
                                )
                            ) ?>

                        <?php endif; ?>

                    </div>


                    <h3>
                        <?= e(
                            $viewUser['name']
                        ) ?>
                    </h3>


                    <p class="user-email">
                        <?= e(
                            $viewUser['email']
                        ) ?>
                    </p>


                    <span class="user-member-badge">
                        Customer
                    </span>


                    <div class="user-contact-info">

                        <?php if (
                            !empty(
                                $viewUser['phone']
                            )
                        ): ?>

                            <p>
                                📞
                                <?= e(
                                    $viewUser['phone']
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <?php if (
                            !empty(
                                $viewUser['address']
                            )
                        ): ?>

                            <p>
                                📍
                                <?= e(
                                    $viewUser['address']
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <p>
                            📅 Joined
                            <?= date(
                                'd M Y',
                                strtotime(
                                    $viewUser['created_at']
                                )
                            ) ?>
                        </p>

                    </div>

                </div>


                <!-- User Statistics -->

                <div class="user-stat-panel">


                    <div class="user-mini-stat">

                        <span>
                            Total Bookings
                        </span>

                        <strong>
                            <?= (int) (
                                $viewUser['booking_count']
                                ?? 0
                            ) ?>
                        </strong>

                    </div>


                    <div class="user-mini-stat">

                        <span>
                            Wishlist Items
                        </span>

                        <strong>
                            <?= (int) (
                                $viewUser['wishlist_count']
                                ?? 0
                            ) ?>
                        </strong>

                    </div>


                    <div class="user-mini-stat">

                        <span>
                            Reviews
                        </span>

                        <strong>
                            <?= (int) (
                                $viewUser['review_count']
                                ?? 0
                            ) ?>
                        </strong>

                    </div>


                    <div class="user-mini-stat">

                        <span>
                            Confirmed Spending
                        </span>

                        <strong>
                            ৳<?= number_format(
                                (float) (
                                    $viewUser['total_spent']
                                    ?? 0
                                ),
                                2
                            ) ?>
                        </strong>

                    </div>


                </div>

            </div>

        </div>


        <!-- User Booking History -->

        <div class="admin-card">

            <div class="card-header">

                <div>

                    <h3>
                        Booking History
                    </h3>

                    <p>
                        All bookings made by this customer.
                    </p>

                </div>

            </div>


            <?php if (empty($userBookings)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        📋
                    </div>

                    <h3>
                        No Bookings
                    </h3>

                    <p>
                        This user has not made any booking yet.
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
                                    Status
                                </th>

                                <th>
                                    Payment
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach (
                            $userBookings as $booking
                        ): ?>

                            <?php

                            $paymentStatus =
                                $booking['payment_status']
                                ?? 'Pending';

                            ?>

                            <tr>

                                <td>

                                    <strong>
                                        #<?= (int) (
                                            $booking['id']
                                        ) ?>
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


                                <td>

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

                                </td>


                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $booking['travel_date']
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= (int) (
                                        $booking['people']
                                    ) ?>

                                    <small>
                                        person(s)
                                    </small>

                                </td>


                                <td>

                                    <strong>
                                        ৳<?= number_format(
                                            (float) (
                                                $booking['final_price']
                                            ),
                                            2
                                        ) ?>
                                    </strong>

                                </td>


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

                                </td>


                                <td>

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
                                                $booking[
                                                    'payment_method'
                                                ]
                                            ) ?>
                                        </small>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>


    <?php else: ?>


        <!-- Search -->

        <div class="admin-card user-search-card">

            <form
                action="users.php"
                method="GET"
                class="admin-filter-form"
            >

                <div class="form-group">

                    <label for="search">
                        Search Users
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-control"
                        value="<?= e($search) ?>"
                        placeholder="Name, email, phone or address..."
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
                        href="users.php"
                        class="btn btn-outline"
                    >
                        Reset
                    </a>

                </div>

            </form>

        </div>


        <!-- Users Table -->

        <div class="admin-card">

            <div class="card-header">

                <div>

                    <h3>
                        All Users
                    </h3>

                    <p>
                        <?= count($users) ?>
                        user(s) found
                    </p>

                </div>

            </div>


            <?php if (empty($users)): ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        👥
                    </div>

                    <h3>
                        No Users Found
                    </h3>

                    <p>
                        No customer matches your search.
                    </p>

                </div>


            <?php else: ?>


                <div class="table-wrapper">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>
                                    User
                                </th>

                                <th>
                                    Contact
                                </th>

                                <th>
                                    Joined
                                </th>

                                <th>
                                    Bookings
                                </th>

                                <th>
                                    Wishlist
                                </th>

                                <th>
                                    Reviews
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($users as $user): ?>


                            <tr>


                                <!-- User -->

                                <td>

                                    <div class="admin-user-cell">


                                        <div class="admin-user-avatar">

                                            <?php if (
                                                !empty(
                                                    $user['profile_image']
                                                )
                                            ): ?>

                                                <img
                                                    src="../<?= e(
                                                        $user[
                                                            'profile_image'
                                                        ]
                                                    ) ?>"
                                                    alt="<?= e(
                                                        $user['name']
                                                    ) ?>"
                                                >

                                            <?php else: ?>

                                                <?= e(
                                                    strtoupper(
                                                        substr(
                                                            $user['name'],
                                                            0,
                                                            1
                                                        )
                                                    )
                                                ) ?>

                                            <?php endif; ?>

                                        </div>


                                        <div>

                                            <strong>
                                                <?= e(
                                                    $user['name']
                                                ) ?>
                                            </strong>

                                            <small>
                                                ID #<?= (int) (
                                                    $user['id']
                                                ) ?>
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- Contact -->

                                <td>

                                    <strong>
                                        <?= e(
                                            $user['email']
                                        ) ?>
                                    </strong>


                                    <?php if (
                                        !empty(
                                            $user['phone']
                                        )
                                    ): ?>

                                        <small>
                                            📞
                                            <?= e(
                                                $user['phone']
                                            ) ?>
                                        </small>

                                    <?php endif; ?>

                                </td>


                                <!-- Joined -->

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $user['created_at']
                                        )
                                    ) ?>

                                </td>


                                <!-- Bookings -->

                                <td>

                                    <span class="number-badge">
                                        <?= (int) (
                                            $user['booking_count']
                                        ) ?>
                                    </span>

                                </td>


                                <!-- Wishlist -->

                                <td>

                                    <span class="number-badge">
                                        <?= (int) (
                                            $user['wishlist_count']
                                        ) ?>
                                    </span>

                                </td>


                                <!-- Reviews -->

                                <td>

                                    <span class="number-badge">
                                        <?= (int) (
                                            $user['review_count']
                                        ) ?>
                                    </span>

                                </td>


                                <!-- Actions -->

                                <td>

                                    <a
                                        href="users.php?view=<?= (int) (
                                            $user['id']
                                        ) ?>"
                                        class="btn btn-outline btn-sm"
                                    >
                                        View Details
                                    </a>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>

        </div>


    <?php endif; ?>


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

.user-search-card {
    margin-bottom: 25px;
}

.admin-filter-form {
    display: flex;
    align-items: flex-end;
    gap: 15px;
}

.admin-filter-form .form-group {
    flex: 1;
    margin: 0;
}

.filter-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.user-detail-card {
    margin-bottom: 25px;
}

.user-detail-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
}

.user-profile-panel {
    text-align: center;
    padding: 25px;
    border-radius: 12px;
    background: #f8f9fc;
}

.large-user-avatar {
    width: 95px;
    height: 95px;
    border-radius: 50%;
    margin: 0 auto 15px;
    background: #eaf0ff;
    color: #3157d5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    font-weight: 700;
    overflow: hidden;
}

.large-user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-profile-panel h3 {
    margin: 5px 0;
}

.user-email {
    color: #777;
    word-break: break-word;
}

.user-member-badge {
    display: inline-block;
    padding: 5px 12px;
    margin-top: 5px;
    border-radius: 20px;
    background: #e9f8ef;
    color: #198754;
    font-size: 12px;
    font-weight: 600;
}

.user-contact-info {
    text-align: left;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

.user-contact-info p {
    font-size: 13px;
    color: #666;
    margin: 9px 0;
    word-break: break-word;
}

.user-stat-panel {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    align-content: start;
}

.user-mini-stat {
    padding: 25px;
    border: 1px solid #e7e7e7;
    border-radius: 12px;
    background: #fff;
}

.user-mini-stat span {
    display: block;
    color: #777;
    font-size: 13px;
    margin-bottom: 8px;
}

.user-mini-stat strong {
    font-size: 24px;
}

.admin-user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 190px;
}

.admin-user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #eef3ff;
    color: #3157d5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    overflow: hidden;
    flex-shrink: 0;
}

.admin-user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.admin-user-cell strong {
    display: block;
}

.admin-user-cell small,
.data-table td small {
    display: block;
    color: #777;
    font-size: 11px;
    margin-top: 3px;
}

.number-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 30px;
    padding: 0 8px;
    border-radius: 15px;
    background: #f0f3f8;
    font-weight: 600;
}

@media (max-width: 800px) {

    .admin-filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-actions {
        width: 100%;
    }

    .filter-actions .btn {
        flex: 1;
    }

    .user-detail-layout {
        grid-template-columns: 1fr;
    }

}

@media (max-width: 550px) {

    .user-stat-panel {
        grid-template-columns: 1fr;
    }

}

</style>


<?php include __DIR__ . '/footer.php'; ?>
