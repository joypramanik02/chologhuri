<?php
require_once __DIR__ . '/includes/db.php';

requireUser();

$pageTitle = 'My Bookings - CholoGhuri';

$userId = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Cancel Booking
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {

    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    if ($bookingId <= 0) {
        redirectWithMessage(
            'my-bookings.php',
            'Invalid booking.',
            'error'
        );
    }

    $stmt = $pdo->prepare("
        SELECT b.*, p.status AS payment_status
        FROM bookings b
        LEFT JOIN payments p ON p.booking_id = b.id
        WHERE b.id = ?
        AND b.user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        redirectWithMessage(
            'my-bookings.php',
            'Booking not found.',
            'error'
        );
    }

    /*
     * Only Pending bookings can be cancelled by users.
     * Paid bookings are not automatically refunded because
     * this system does not process real refunds.
     */
    if ($booking['status'] !== 'Pending') {
        redirectWithMessage(
            'my-bookings.php',
            'Only pending bookings can be cancelled from your account.',
            'error'
        );
    }

    $stmt = $pdo->prepare("
        UPDATE bookings
        SET status = 'Cancelled'
        WHERE id = ?
        AND user_id = ?
        AND status = 'Pending'
    ");

    $stmt->execute([$bookingId, $userId]);

    redirectWithMessage(
        'my-bookings.php',
        'Booking cancelled successfully.',
        'success'
    );
}


/*
|--------------------------------------------------------------------------
| Submit Review
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {

    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($bookingId <= 0 || $rating < 1 || $rating > 5) {
        redirectWithMessage(
            'my-bookings.php',
            'Please provide a valid rating.',
            'error'
        );
    }

    if (strlen($comment) > 1000) {
        redirectWithMessage(
            'my-bookings.php',
            'Review comment is too long.',
            'error'
        );
    }

    /*
     * Make sure the booking belongs to this user
     * and is confirmed.
     */
    $stmt = $pdo->prepare("
        SELECT id, package_id, status
        FROM bookings
        WHERE id = ?
        AND user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        redirectWithMessage(
            'my-bookings.php',
            'Booking not found.',
            'error'
        );
    }

    if ($booking['status'] !== 'Confirmed') {
        redirectWithMessage(
            'my-bookings.php',
            'You can review only confirmed bookings.',
            'error'
        );
    }

    /*
     * Check whether this user already reviewed this package.
     */
    $stmt = $pdo->prepare("
        SELECT id
        FROM reviews
        WHERE user_id = ?
        AND package_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $userId,
        $booking['package_id']
    ]);

    if ($stmt->fetch()) {
        redirectWithMessage(
            'my-bookings.php',
            'You have already reviewed this package.',
            'error'
        );
    }

    /*
     * Insert review.
     */
    $stmt = $pdo->prepare("
        INSERT INTO reviews (
            user_id,
            package_id,
            rating,
            comment
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $booking['package_id'],
        $rating,
        $comment
    ]);

    redirectWithMessage(
        'my-bookings.php',
        'Thank you! Your review has been submitted.',
        'success'
    );
}


/*
|--------------------------------------------------------------------------
| Fetch User Bookings
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        b.*,
        tp.title AS package_title,
        tp.destination,
        tp.duration,
        tp.image AS package_image,
        p.method AS payment_method,
        p.status AS payment_status,
        p.transaction_id,
        p.paid_at
    FROM bookings b
    INNER JOIN tour_packages tp
        ON tp.id = b.package_id
    LEFT JOIN payments p
        ON p.booking_id = b.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");

$stmt->execute([$userId]);

$bookings = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Check Existing Reviews
|--------------------------------------------------------------------------
*/
$reviewedPackages = [];

$stmt = $pdo->prepare("
    SELECT package_id
    FROM reviews
    WHERE user_id = ?
");

$stmt->execute([$userId]);

while ($review = $stmt->fetch()) {
    $reviewedPackages[] = (int) $review['package_id'];
}

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <h1>My Bookings</h1>
        <p>View and manage all your tour bookings.</p>
    </div>
</section>


<section class="section">
    <div class="container">

        <?php if (empty($bookings)): ?>

            <div class="empty-state">

                <div class="empty-icon">🧳</div>

                <h2>No Bookings Yet</h2>

                <p>
                    You have not booked any tour package yet.
                    Start exploring Bangladesh and plan your next trip.
                </p>

                <a href="packages.php" class="btn btn-primary">
                    Explore Tour Packages
                </a>

            </div>

        <?php else: ?>

            <div class="section-heading">

                <div>
                    <span class="section-tag">Travel History</span>
                    <h2>Your Bookings</h2>
                </div>

                <a href="packages.php" class="btn btn-outline">
                    + Book Another Tour
                </a>

            </div>


            <div class="booking-history">

                <?php foreach ($bookings as $booking): ?>

                    <?php
                    $bookingId = (int) $booking['id'];
                    $packageId = (int) $booking['package_id'];

                    $image = !empty($booking['package_image'])
                        ? $booking['package_image']
                        : 'images/default-tour.jpg';

                    $isReviewed = in_array(
                        $packageId,
                        $reviewedPackages,
                        true
                    );

                    $travelDate = date(
                        'd M Y',
                        strtotime($booking['travel_date'])
                    );

                    $bookingDate = date(
                        'd M Y',
                        strtotime($booking['created_at'])
                    );
                    ?>

                    <div class="booking-card">

                        <!-- Booking Header -->
                        <div class="booking-card-header">

                            <div>
                                <span class="booking-id">
                                    Booking #<?= $bookingId ?>
                                </span>

                                <h3>
                                    <?= e($booking['package_title']) ?>
                                </h3>
                            </div>

                            <span class="status status-<?= strtolower(e($booking['status'])) ?>">
                                <?= e($booking['status']) ?>
                            </span>

                        </div>


                        <!-- Booking Main -->
                        <div class="booking-card-content">

                            <!-- Package Image -->
                            <div class="booking-package-image">

                                <?php if (!empty($booking['package_image'])): ?>

                                    <img
                                        src="<?= e($booking['package_image']) ?>"
                                        alt="<?= e($booking['package_title']) ?>"
                                    >

                                <?php else: ?>

                                    <div class="summary-image-placeholder">
                                        🌴
                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- Booking Information -->
                            <div class="booking-information">

                                <div class="info-grid">

                                    <div class="info-card">
                                        <span class="info-icon">📍</span>
                                        <div>
                                            <small>Destination</small>
                                            <strong>
                                                <?= e($booking['destination']) ?>
                                            </strong>
                                        </div>
                                    </div>


                                    <div class="info-card">
                                        <span class="info-icon">📅</span>
                                        <div>
                                            <small>Travel Date</small>
                                            <strong>
                                                <?= e($travelDate) ?>
                                            </strong>
                                        </div>
                                    </div>


                                    <div class="info-card">
                                        <span class="info-icon">👥</span>
                                        <div>
                                            <small>Travelers</small>
                                            <strong>
                                                <?= (int) $booking['people'] ?>
                                                <?= ((int) $booking['people'] === 1) ? 'Person' : 'People' ?>
                                            </strong>
                                        </div>
                                    </div>


                                    <div class="info-card">
                                        <span class="info-icon">⏱️</span>
                                        <div>
                                            <small>Duration</small>
                                            <strong>
                                                <?= e($booking['duration']) ?>
                                            </strong>
                                        </div>
                                    </div>

                                </div>


                                <!-- Price Summary -->
                                <div class="booking-price-summary">

                                    <div class="summary-row">
                                        <span>Total Price</span>
                                        <strong>
                                            ৳<?= number_format($booking['total_price'], 2) ?>
                                        </strong>
                                    </div>

                                    <?php if ((float) $booking['discount'] > 0): ?>

                                        <div class="summary-row discount-row">
                                            <span>
                                                Discount
                                                <?php if (!empty($booking['coupon_code'])): ?>
                                                    (<?= e($booking['coupon_code']) ?>)
                                                <?php endif; ?>
                                            </span>

                                            <strong>
                                                -৳<?= number_format($booking['discount'], 2) ?>
                                            </strong>
                                        </div>

                                    <?php endif; ?>


                                    <div class="summary-row summary-total">
                                        <span>Final Price</span>

                                        <strong>
                                            ৳<?= number_format($booking['final_price'], 2) ?>
                                        </strong>
                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- Payment Information -->
                        <div class="booking-payment-info">

                            <div>
                                <strong>Payment</strong>

                                <?php if ($booking['payment_status'] === 'Paid'): ?>

                                    <span class="status status-confirmed">
                                        Paid
                                    </span>

                                    <?php if (!empty($booking['payment_method'])): ?>
                                        <small>
                                            via <?= e($booking['payment_method']) ?>
                                        </small>
                                    <?php endif; ?>

                                <?php elseif ($booking['payment_status'] === 'Refunded'): ?>

                                    <span class="status status-cancelled">
                                        Refunded
                                    </span>

                                <?php elseif ($booking['payment_status'] === 'Failed'): ?>

                                    <span class="status status-cancelled">
                                        Failed
                                    </span>

                                <?php else: ?>

                                    <span class="status status-pending">
                                        Payment Pending
                                    </span>

                                <?php endif; ?>

                            </div>


                            <?php if (!empty($booking['transaction_id'])): ?>

                                <div>
                                    <strong>Transaction ID</strong>
                                    <span>
                                        <?= e($booking['transaction_id']) ?>
                                    </span>
                                </div>

                            <?php endif; ?>


                            <div>
                                <strong>Booked On</strong>
                                <span>
                                    <?= e($bookingDate) ?>
                                </span>
                            </div>

                        </div>


                        <!-- Actions -->
                        <div class="booking-actions">

                            <a
                                href="package-details.php?id=<?= $packageId ?>"
                                class="btn btn-outline btn-sm"
                            >
                                View Package
                            </a>


                            <?php if (
                                $booking['status'] !== 'Cancelled' &&
                                $booking['payment_status'] !== 'Paid'
                            ): ?>

                                <a
                                    href="payment.php?booking_id=<?= $bookingId ?>"
                                    class="btn btn-primary btn-sm"
                                >
                                    Pay Now
                                </a>

                            <?php endif; ?>


                            <?php if ($booking['status'] === 'Pending'): ?>

                                <form
                                    action="my-bookings.php"
                                    method="POST"
                                    class="inline-form"
                                >

                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?= $bookingId ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="cancel_booking"
                                        class="btn btn-danger btn-sm"
                                        data-confirm="Are you sure you want to cancel this booking?"
                                    >
                                        Cancel Booking
                                    </button>

                                </form>

                            <?php endif; ?>


                            <?php if (
                                $booking['status'] === 'Confirmed' &&
                                !$isReviewed
                            ): ?>

                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sm"
                                    onclick="toggleReview(<?= $bookingId ?>)"
                                >
                                    ⭐ Write Review
                                </button>

                            <?php elseif (
                                $booking['status'] === 'Confirmed' &&
                                $isReviewed
                            ): ?>

                                <span class="review-completed">
                                    ✓ Review Submitted
                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- Review Form -->
                        <?php if (
                            $booking['status'] === 'Confirmed' &&
                            !$isReviewed
                        ): ?>

                            <div
                                class="review-form-box"
                                id="review-<?= $bookingId ?>"
                                style="display:none;"
                            >

                                <div class="review-form-header">
                                    <h3>Review This Tour</h3>

                                    <button
                                        type="button"
                                        class="review-close"
                                        onclick="toggleReview(<?= $bookingId ?>)"
                                    >
                                        ×
                                    </button>
                                </div>


                                <form
                                    action="my-bookings.php"
                                    method="POST"
                                    class="review-form"
                                >

                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?= $bookingId ?>"
                                    >


                                    <div class="form-group">

                                        <label>
                                            Your Rating
                                        </label>

                                        <div class="review-stars">

                                            <?php for ($star = 5; $star >= 1; $star--): ?>

                                                <input
                                                    type="radio"
                                                    id="star<?= $bookingId ?>_<?= $star ?>"
                                                    name="rating"
                                                    value="<?= $star ?>"
                                                    required
                                                >

                                                <label
                                                    for="star<?= $bookingId ?>_<?= $star ?>"
                                                    title="<?= $star ?> Star"
                                                >
                                                    ★
                                                </label>

                                            <?php endfor; ?>

                                        </div>

                                    </div>


                                    <div class="form-group">

                                        <label for="comment<?= $bookingId ?>">
                                            Your Review
                                        </label>

                                        <textarea
                                            id="comment<?= $bookingId ?>"
                                            name="comment"
                                            class="form-control"
                                            rows="4"
                                            maxlength="1000"
                                            placeholder="Share your experience about this tour..."
                                        ></textarea>

                                    </div>


                                    <button
                                        type="submit"
                                        name="submit_review"
                                        class="btn btn-primary"
                                    >
                                        Submit Review
                                    </button>

                                </form>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>
</section>


<style>
.booking-history {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.booking-card {
    background: #ffffff;
    border: 1px solid #e8e8e8;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.05);
}

.booking-card-header {
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    border-bottom: 1px solid #eeeeee;
}

.booking-card-header h3 {
    margin: 6px 0 0;
}

.booking-id {
    font-size: 13px;
    color: #777;
}

.booking-card-content {
    padding: 24px;
    display: grid;
    grid-template-columns: 230px 1fr;
    gap: 25px;
}

.booking-package-image img {
    width: 100%;
    height: 170px;
    object-fit: cover;
    border-radius: 12px;
}

.booking-package-image .summary-image-placeholder {
    width: 100%;
    height: 170px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f3f3;
    border-radius: 12px;
    font-size: 50px;
}

.booking-information {
    min-width: 0;
}

.booking-price-summary {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eeeeee;
}

.booking-payment-info {
    padding: 16px 24px;
    background: #fafafa;
    border-top: 1px solid #eeeeee;
    border-bottom: 1px solid #eeeeee;
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    align-items: center;
}

.booking-payment-info > div {
    display: flex;
    align-items: center;
    gap: 8px;
}

.booking-payment-info small {
    color: #777;
}

.booking-actions {
    padding: 18px 24px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.inline-form {
    display: inline;
}

.review-completed {
    font-size: 14px;
    font-weight: 600;
    color: #198754;
}

.review-form-box {
    margin: 0 24px 24px;
    padding: 22px;
    background: #f8f9fa;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
}

.review-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.review-form-header h3 {
    margin: 0;
}

.review-close {
    border: none;
    background: transparent;
    font-size: 26px;
    cursor: pointer;
    color: #777;
}

.review-stars {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.review-stars input {
    display: none;
}

.review-stars label {
    font-size: 30px;
    color: #ccc;
    cursor: pointer;
    transition: 0.2s;
}

.review-stars label:hover,
.review-stars label:hover ~ label,
.review-stars input:checked ~ label {
    color: #f5b301;
}

@media (max-width: 760px) {

    .booking-card-content {
        grid-template-columns: 1fr;
    }

    .booking-package-image img,
    .booking-package-image .summary-image-placeholder {
        height: 220px;
    }

    .booking-card-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .booking-payment-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

}
</style>


<script>
function toggleReview(bookingId) {

    const box = document.getElementById('review-' + bookingId);

    if (!box) {
        return;
    }

    if (box.style.display === 'none' || box.style.display === '') {
        box.style.display = 'block';

        box.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

    } else {

        box.style.display = 'none';
    }
}
</script>


<?php include __DIR__ . '/includes/footer.php'; ?>
