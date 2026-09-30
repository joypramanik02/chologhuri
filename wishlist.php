<?php
require_once 'includes/db.php';

requireUser();

$pageTitle = 'My Wishlist - CholoGhuri';

/* --------------------------------
   Add / Remove Wishlist
--------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $packageId = (int) ($_POST['package_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($packageId <= 0) {
        redirectWithMessage('packages.php', 'Invalid package selected.', 'error');
    }

    /* Check package exists */
    $packageStmt = $pdo->prepare("
        SELECT id
        FROM tour_packages
        WHERE id = :id
        AND status = 'active'
        LIMIT 1
    ");

    $packageStmt->execute([
        ':id' => $packageId
    ]);

    if (!$packageStmt->fetch()) {
        redirectWithMessage('packages.php', 'Package not found.', 'error');
    }


    /* Add Wishlist */

    if ($action === 'add') {

        $checkStmt = $pdo->prepare("
            SELECT id
            FROM wishlist
            WHERE user_id = :user_id
            AND package_id = :package_id
            LIMIT 1
        ");

        $checkStmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':package_id' => $packageId
        ]);

        if (!$checkStmt->fetch()) {

            $insertStmt = $pdo->prepare("
                INSERT INTO wishlist
                (user_id, package_id)
                VALUES
                (:user_id, :package_id)
            ");

            $insertStmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':package_id' => $packageId
            ]);

            redirectWithMessage(
                'package-details.php?id=' . $packageId,
                'Package added to your wishlist.'
            );
        }

        redirectWithMessage(
            'package-details.php?id=' . $packageId,
            'This package is already in your wishlist.',
            'info'
        );
    }


    /* Remove Wishlist */

    if ($action === 'remove') {

        $deleteStmt = $pdo->prepare("
            DELETE FROM wishlist
            WHERE user_id = :user_id
            AND package_id = :package_id
        ");

        $deleteStmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':package_id' => $packageId
        ]);

        redirectWithMessage(
            'wishlist.php',
            'Package removed from your wishlist.'
        );
    }
}


/* --------------------------------
   Get Wishlist Packages
--------------------------------- */

$stmt = $pdo->prepare("
    SELECT
        tp.*,

        w.id AS wishlist_id,

        COALESCE(AVG(r.rating), 0) AS avg_rating,

        COUNT(DISTINCT r.id) AS review_count,

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

    FROM wishlist w

    INNER JOIN tour_packages tp
        ON tp.id = w.package_id

    LEFT JOIN reviews r
        ON r.package_id = tp.id

    WHERE w.user_id = :user_id
    AND tp.status = 'active'

    GROUP BY tp.id, w.id

    ORDER BY w.created_at DESC
");

$stmt->execute([
    ':user_id' => $_SESSION['user_id']
]);

$wishlist = $stmt->fetchAll();

include 'includes/header.php';
?>


<!-- =========================================
     WISHLIST HEADER
========================================= -->

<section class="page-banner">

    <div class="container">

        <h1>
            My Wishlist
        </h1>

        <p>
            Save your favorite tour packages and book them whenever you're ready.
        </p>

    </div>

</section>


<!-- =========================================
     WISHLIST CONTENT
========================================= -->

<section class="section">

    <div class="container">

        <?php if (!empty($wishlist)): ?>

            <div class="section-heading">

                <span class="section-tag">
                    Saved Tours
                </span>

                <h2 class="section-title">
                    Your Favorite Packages
                </h2>

                <p>
                    You have saved <?= count($wishlist) ?>
                    package<?= count($wishlist) != 1 ? 's' : '' ?>.
                </p>

            </div>


            <div class="wishlist-grid">

                <?php foreach ($wishlist as $package): ?>

                    <?php
                        $availableSeats = max(
                            0,
                            (int) $package['available_seats']
                        );

                        $avgRating = (float) $package['avg_rating'];

                        $reviewCount = (int) $package['review_count'];

                        $isFull = $availableSeats <= 0;

                        $isLow = $availableSeats > 0 && $availableSeats <= 5;
                    ?>


                    <div class="wishlist-card">


                        <!-- Image -->

                        <div class="wishlist-image">

                            <?php if (!empty($package['image'])): ?>

                                <img
                                    src="<?= e($package['image']) ?>"
                                    alt="<?= e($package['title']) ?>"
                                >

                            <?php else: ?>

                                <div class="package-image-placeholder">
                                    🌴
                                </div>

                            <?php endif; ?>


                            <?php if ($isFull): ?>

                                <span class="package-badge badge-full">
                                    Sold Out
                                </span>

                            <?php elseif ($isLow): ?>

                                <span class="package-badge badge-low">
                                    Few Seats Left
                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- Content -->

                        <div class="wishlist-body">

                            <h3>
                                <?= e($package['title']) ?>
                            </h3>


                            <div class="wishlist-location">

                                📍 <?= e($package['destination']) ?>

                            </div>


                            <div class="package-meta">

                                <span>
                                    🕐 <?= e($package['duration']) ?>
                                </span>

                                <span>
                                    👥 <?= $availableSeats ?> seats
                                </span>

                            </div>


                            <!-- Rating -->

                            <div class="package-rating">

                                <span class="rating-stars">

                                    <?php
                                    for ($i = 1; $i <= 5; $i++):
                                    ?>

                                        <?= $i <= round($avgRating) ? '★' : '☆' ?>

                                    <?php endfor; ?>

                                </span>

                                <span class="rating-number">
                                    <?= number_format($avgRating, 1) ?>
                                </span>

                                <span class="review-count">
                                    (<?= $reviewCount ?>)
                                </span>

                            </div>


                            <!-- Price -->

                            <div class="package-price">

                                <span class="price-label">
                                    Starting from
                                </span>

                                <strong>
                                    ৳<?= number_format($package['price'], 0) ?>
                                </strong>

                                <span class="price-person">
                                    / person
                                </span>

                            </div>


                            <!-- Actions -->

                            <div class="package-actions">


                                <a
                                    href="package-details.php?id=<?= (int) $package['id'] ?>"
                                    class="btn btn-outline btn-sm"
                                >
                                    View Details
                                </a>


                                <?php if ($isFull): ?>

                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm"
                                        disabled
                                    >
                                        Sold Out
                                    </button>

                                <?php else: ?>

                                    <a
                                        href="booking.php?package_id=<?= (int) $package['id'] ?>"
                                        class="btn btn-primary btn-sm"
                                    >
                                        Book Now
                                    </a>

                                <?php endif; ?>

                            </div>


                            <!-- Remove -->

                            <form
                                method="POST"
                                action="wishlist.php"
                                class="wishlist-remove-form"
                            >

                                <input
                                    type="hidden"
                                    name="package_id"
                                    value="<?= (int) $package['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="remove"
                                >

                                <button
                                    type="submit"
                                    class="wishlist-remove"
                                    data-confirm="Are you sure you want to remove this package from your wishlist?"
                                >
                                    ♥ Remove from Wishlist
                                </button>

                            </form>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


        <?php else: ?>

            <!-- Empty Wishlist -->

            <div class="empty-state">

                <div class="empty-icon">
                    ♡
                </div>

                <h2>
                    Your Wishlist is Empty
                </h2>

                <p>
                    You haven't saved any tour packages yet.
                    Explore our packages and save the ones you love.
                </p>

                <a
                    href="packages.php"
                    class="btn btn-primary"
                >
                    Explore Tour Packages
                </a>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- =========================================
     CTA
========================================= -->

<section class="section light-section">

    <div class="container">

        <div class="cta-box">

            <div>

                <span class="section-tag">
                    Ready for Adventure?
                </span>

                <h2>
                    Start Planning Your Next Trip
                </h2>

                <p>
                    Discover beautiful destinations across Bangladesh
                    with CholoGhuri.
                </p>

            </div>

            <a
                href="packages.php"
                class="btn btn-primary"
            >
                Explore Packages →
            </a>

        </div>

    </div>

</section>


<?php include 'includes/footer.php'; ?>
