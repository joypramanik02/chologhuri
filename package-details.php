<?php
require_once 'includes/db.php';

$packageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($packageId <= 0) {
    redirectWithMessage('packages.php', 'Invalid package selected.', 'error');
}

/* --------------------------------
   Get Package Details
--------------------------------- */

$stmt = $pdo->prepare("
    SELECT
        tp.*,

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

    FROM tour_packages tp

    LEFT JOIN reviews r
        ON r.package_id = tp.id

    WHERE tp.id = :id
    AND tp.status = 'active'

    GROUP BY tp.id
");

$stmt->execute([
    ':id' => $packageId
]);

$package = $stmt->fetch();

if (!$package) {
    redirectWithMessage('packages.php', 'Tour package not found.', 'error');
}


/* --------------------------------
   Get Reviews
--------------------------------- */

$reviewStmt = $pdo->prepare("
    SELECT
        r.*,
        u.name,
        u.profile_image

    FROM reviews r

    INNER JOIN users u
        ON u.id = r.user_id

    WHERE r.package_id = :package_id

    ORDER BY r.created_at DESC
");

$reviewStmt->execute([
    ':package_id' => $packageId
]);

$reviews = $reviewStmt->fetchAll();


/* --------------------------------
   Wishlist Status
--------------------------------- */

$isWishlisted = false;

if (isUserLoggedIn()) {

    $wishlistStmt = $pdo->prepare("
        SELECT id
        FROM wishlist
        WHERE user_id = :user_id
        AND package_id = :package_id
        LIMIT 1
    ");

    $wishlistStmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':package_id' => $packageId
    ]);

    $isWishlisted = (bool) $wishlistStmt->fetch();
}


/* --------------------------------
   Seat Information
--------------------------------- */

$availableSeats = max(0, (int) $package['available_seats']);
$totalSeats = (int) $package['total_seats'];

$isFull = $availableSeats <= 0;
$isLow = $availableSeats > 0 && $availableSeats <= 5;

$avgRating = (float) $package['avg_rating'];
$reviewCount = (int) $package['review_count'];

$pageTitle = $package['title'] . ' - CholoGhuri';

include 'includes/header.php';
?>


<!-- =========================================
     PACKAGE DETAILS
========================================= -->

<section class="section package-details-section">

    <div class="container">

        <div class="details-grid">


            <!-- =================================
                 LEFT SIDE - IMAGE
            ================================== -->

            <div class="details-image">

                <?php if (!empty($package['image'])): ?>

                    <img
                        src="<?= e($package['image']) ?>"
                        alt="<?= e($package['title']) ?>"
                    >

                <?php else: ?>

                    <div class="details-image-placeholder">
                        🌴
                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================
                 RIGHT SIDE - INFORMATION
            ================================== -->

            <div class="details-info">

                <span class="details-location">
                    📍 <?= e($package['destination']) ?>
                </span>


                <h1>
                    <?= e($package['title']) ?>
                </h1>


                <!-- Rating -->

                <div class="details-rating">

                    <span class="rating-stars">

                        <?php
                        $roundedRating = round($avgRating);

                        for ($i = 1; $i <= 5; $i++):
                        ?>

                            <?php if ($i <= $roundedRating): ?>

                                ★

                            <?php else: ?>

                                ☆

                            <?php endif; ?>

                        <?php endfor; ?>

                    </span>

                    <strong>
                        <?= number_format($avgRating, 1) ?>
                    </strong>

                    <span>
                        (<?= $reviewCount ?> reviews)
                    </span>

                </div>


                <!-- Basic Information -->

                <div class="details-meta">

                    <div class="details-meta-item">

                        <span class="meta-icon">
                            🕐
                        </span>

                        <div>

                            <small>
                                Duration
                            </small>

                            <strong>
                                <?= e($package['duration']) ?>
                            </strong>

                        </div>

                    </div>


                    <div class="details-meta-item">

                        <span class="meta-icon">
                            👥
                        </span>

                        <div>

                            <small>
                                Total Seats
                            </small>

                            <strong>
                                <?= $totalSeats ?>
                            </strong>

                        </div>

                    </div>


                    <div class="details-meta-item">

                        <span class="meta-icon">
                            🎟️
                        </span>

                        <div>

                            <small>
                                Available
                            </small>

                            <strong>
                                <?= $availableSeats ?>
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- Description -->

                <div class="details-description">

                    <h3>
                        About This Tour
                    </h3>

                    <p>
                        <?= nl2br(e($package['description'])) ?>
                    </p>

                </div>


                <!-- Price -->

                <div class="details-price">

                    <span>
                        Starting from
                    </span>

                    <strong>
                        ৳<?= number_format($package['price'], 0) ?>
                    </strong>

                    <small>
                        / person
                    </small>

                </div>


                <!-- Seat Status -->

                <?php if ($isFull): ?>

                    <div class="seat-box full">

                        <strong>
                            🚫 Fully Booked
                        </strong>

                        <span>
                            No seats are currently available.
                        </span>

                    </div>

                <?php elseif ($isLow): ?>

                    <div class="seat-box low">

                        <strong>
                            ⚠️ Only <?= $availableSeats ?> seats left!
                        </strong>

                        <span>
                            Book now to secure your seat.
                        </span>

                    </div>

                <?php else: ?>

                    <div class="seat-box">

                        <strong>
                            ✅ <?= $availableSeats ?> seats available
                        </strong>

                        <span>
                            Seats are currently available for booking.
                        </span>

                    </div>

                <?php endif; ?>


                <!-- Actions -->

                <div class="details-actions">

                    <?php if ($isFull): ?>

                        <button
                            type="button"
                            class="btn btn-secondary"
                            disabled
                        >
                            Sold Out
                        </button>

                    <?php else: ?>

                        <a
                            href="booking.php?package_id=<?= (int) $package['id'] ?>"
                            class="btn btn-primary"
                        >
                            📝 Book Now
                        </a>

                    <?php endif; ?>


                    <?php if (isUserLoggedIn()): ?>

                        <form
                            action="wishlist.php"
                            method="POST"
                            class="wishlist-form"
                        >

                            <input
                                type="hidden"
                                name="package_id"
                                value="<?= (int) $package['id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="<?= $isWishlisted ? 'remove' : 'add' ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-outline"
                            >

                                <?= $isWishlisted ? '♥ Remove Wishlist' : '♡ Add Wishlist' ?>

                            </button>

                        </form>

                    <?php else: ?>

                        <a
                            href="login.php"
                            class="btn btn-outline"
                        >
                            ♡ Add to Wishlist
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================
     REVIEW SECTION
========================================= -->

<section class="section review-section">

    <div class="container">

        <div class="section-heading">

            <span class="section-tag">
                Customer Feedback
            </span>

            <h2 class="section-title">
                Reviews & Ratings
            </h2>

            <p>
                See what other travelers say about this package.
            </p>

        </div>


        <!-- Rating Summary -->

        <div class="rating-summary">

            <div class="rating-summary-number">

                <strong>
                    <?= number_format($avgRating, 1) ?>
                </strong>

                <div class="rating-stars large">

                    <?php
                    for ($i = 1; $i <= 5; $i++):
                    ?>

                        <?= $i <= round($avgRating) ? '★' : '☆' ?>

                    <?php endfor; ?>

                </div>

                <span>
                    Based on <?= $reviewCount ?> review<?= $reviewCount != 1 ? 's' : '' ?>
                </span>

            </div>


            <div class="rating-summary-text">

                <?php if ($avgRating >= 4.5): ?>

                    <h3>
                        Excellent Experience!
                    </h3>

                    <p>
                        Travelers are highly satisfied with this tour package.
                    </p>

                <?php elseif ($avgRating >= 3): ?>

                    <h3>
                        Good Experience
                    </h3>

                    <p>
                        Travelers have shared positive feedback about this package.
                    </p>

                <?php elseif ($reviewCount > 0): ?>

                    <h3>
                        Traveler Feedback
                    </h3>

                    <p>
                        Read the reviews below to learn more about the experience.
                    </p>

                <?php else: ?>

                    <h3>
                        Be the First to Review
                    </h3>

                    <p>
                        There are no reviews yet for this package.
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <!-- Review List -->

        <?php if (!empty($reviews)): ?>

            <div class="review-list">

                <?php foreach ($reviews as $review): ?>

                    <div class="review-card">

                        <div class="review-header">

                            <div class="review-user">

                                <?php if (!empty($review['profile_image'])): ?>

                                    <img
                                        src="<?= e($review['profile_image']) ?>"
                                        alt="<?= e($review['name']) ?>"
                                        class="review-avatar"
                                    >

                                <?php else: ?>

                                    <div class="review-avatar-placeholder">
                                        <?= strtoupper(substr($review['name'], 0, 1)) ?>
                                    </div>

                                <?php endif; ?>


                                <div>

                                    <strong>
                                        <?= e($review['name']) ?>
                                    </strong>

                                    <small>
                                        <?= date('d M Y', strtotime($review['created_at'])) ?>
                                    </small>

                                </div>

                            </div>


                            <div class="rating-stars">

                                <?php for ($i = 1; $i <= 5; $i++): ?>

                                    <?= $i <= (int) $review['rating'] ? '★' : '☆' ?>

                                <?php endfor; ?>

                            </div>

                        </div>


                        <?php if (!empty($review['comment'])): ?>

                            <p class="review-comment">
                                <?= nl2br(e($review['comment'])) ?>
                            </p>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">
                    ⭐
                </div>

                <h3>
                    No Reviews Yet
                </h3>

                <p>
                    Be one of the first travelers to review this package.
                </p>

            </div>

        <?php endif; ?>


        <!-- Review Notice -->

        <?php if (isUserLoggedIn()): ?>

            <div class="review-notice">

                <strong>
                    💡 Want to share your experience?
                </strong>

                <p>
                    After completing your tour, you can leave a rating
                    and review from your booking history.
                </p>

            </div>

        <?php else: ?>

            <div class="review-notice">

                <strong>
                    Want to share your experience?
                </strong>

                <p>
                    Please
                    <a href="login.php">login</a>
                    to manage your bookings and reviews.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- =========================================
     RELATED PACKAGES
========================================= -->

<section class="section light-section">

    <div class="container">

        <div class="section-heading">

            <span class="section-tag">
                More Adventures
            </span>

            <h2 class="section-title">
                Explore More Packages
            </h2>

        </div>


        <div class="related-actions">

            <a
                href="packages.php"
                class="btn btn-primary"
            >
                View All Packages →
            </a>

        </div>

    </div>

</section>


<?php include 'includes/footer.php'; ?>
