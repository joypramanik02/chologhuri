<?php
require_once 'includes/db.php';

$pageTitle = 'Tour Packages - CholoGhuri';

/* -----------------------------
   Get Filter Values
------------------------------ */

$search = trim($_GET['search'] ?? '');
$destination = trim($_GET['destination'] ?? '');
$price = $_GET['price'] ?? '';
$duration = trim($_GET['duration'] ?? '');
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';

/* -----------------------------
   Build Query
------------------------------ */

$where = ["tp.status = 'active'"];
$params = [];

/* Search */
if ($search !== '') {
    $where[] = "(tp.title LIKE :search OR tp.destination LIKE :search OR tp.description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

/* Destination */
if ($destination !== '') {
    $where[] = "tp.destination = :destination";
    $params[':destination'] = $destination;
}

/* Price Filter */
if ($price === 'under5000') {
    $where[] = "tp.price < 5000";
}

if ($price === '5000-10000') {
    $where[] = "tp.price BETWEEN 5000 AND 10000";
}

if ($price === 'above10000') {
    $where[] = "tp.price > 10000";
}

/* Custom Minimum Price */
if ($minPrice !== '' && is_numeric($minPrice)) {
    $where[] = "tp.price >= :min_price";
    $params[':min_price'] = (float) $minPrice;
}

/* Custom Maximum Price */
if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $where[] = "tp.price <= :max_price";
    $params[':max_price'] = (float) $maxPrice;
}

/* Duration */
if ($duration !== '') {
    $where[] = "tp.duration LIKE :duration";
    $params[':duration'] = '%' . $duration . '%';
}

/* -----------------------------
   Main Package Query
------------------------------ */

$sql = "
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

    WHERE " . implode(' AND ', $where) . "

    GROUP BY tp.id

    ORDER BY tp.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$packages = $stmt->fetchAll();

/* -----------------------------
   Get Destinations
------------------------------ */

$destinationStmt = $pdo->query("
    SELECT DISTINCT destination
    FROM tour_packages
    WHERE status = 'active'
    ORDER BY destination ASC
");

$destinations = $destinationStmt->fetchAll();

/* -----------------------------
   Count Packages
------------------------------ */

$totalPackages = count($packages);

include 'includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <h1>Explore Tour Packages</h1>
        <p>Find your perfect destination and start your journey.</p>
    </div>
</section>


<section class="section packages-section">

    <div class="container">

        <!-- Search & Filter -->

        <div class="filter-box">

            <form method="GET" action="packages.php" class="filter-form">

                <div class="form-group">

                    <label for="packageSearch">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        id="packageSearch"
                        class="form-control"
                        placeholder="Search destination or package..."
                        value="<?= e($search) ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="destinationFilter">
                        Destination
                    </label>

                    <select
                        name="destination"
                        id="destinationFilter"
                        class="form-control"
                    >

                        <option value="">
                            All Destinations
                        </option>

                        <?php foreach ($destinations as $item): ?>

                            <option
                                value="<?= e($item['destination']) ?>"
                                <?= $destination === $item['destination'] ? 'selected' : '' ?>
                            >
                                <?= e($item['destination']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="priceFilter">
                        Price Range
                    </label>

                    <select
                        name="price"
                        id="priceFilter"
                        class="form-control"
                    >

                        <option value="">
                            All Prices
                        </option>

                        <option
                            value="under5000"
                            <?= $price === 'under5000' ? 'selected' : '' ?>
                        >
                            Under ৳5,000
                        </option>

                        <option
                            value="5000-10000"
                            <?= $price === '5000-10000' ? 'selected' : '' ?>
                        >
                            ৳5,000 - ৳10,000
                        </option>

                        <option
                            value="above10000"
                            <?= $price === 'above10000' ? 'selected' : '' ?>
                        >
                            Above ৳10,000
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="durationFilter">
                        Duration
                    </label>

                    <select
                        name="duration"
                        id="durationFilter"
                        class="form-control"
                    >

                        <option value="">
                            All Durations
                        </option>

                        <option
                            value="2 Days"
                            <?= $duration === '2 Days' ? 'selected' : '' ?>
                        >
                            2 Days
                        </option>

                        <option
                            value="3 Days"
                            <?= $duration === '3 Days' ? 'selected' : '' ?>
                        >
                            3 Days
                        </option>

                        <option
                            value="4 Days"
                            <?= $duration === '4 Days' ? 'selected' : '' ?>
                        >
                            4 Days
                        </option>

                        <option
                            value="5 Days"
                            <?= $duration === '5 Days' ? 'selected' : '' ?>
                        >
                            5 Days
                        </option>

                    </select>

                </div>


                <div class="filter-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        🔍 Search
                    </button>

                    <a
                        href="packages.php"
                        class="btn btn-outline"
                        id="resetFilters"
                    >
                        Reset
                    </a>

                </div>

            </form>

        </div>


        <!-- Result Header -->

        <div class="section-heading-row">

            <div>

                <h2 class="section-title">
                    Available Packages
                </h2>

                <p class="section-subtitle">
                    <?= $totalPackages ?>
                    package<?= $totalPackages != 1 ? 's' : '' ?> found
                </p>

            </div>

        </div>


        <!-- Package Grid -->

        <?php if (!empty($packages)): ?>

            <div class="package-grid" id="packageGrid">

                <?php foreach ($packages as $package): ?>

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

                    <article
                        class="package-card"
                        data-destination="<?= e($package['destination']) ?>"
                        data-price="<?= e($package['price']) ?>"
                        data-duration="<?= e($package['duration']) ?>"
                    >

                        <!-- Image -->

                        <div class="package-image">

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


                        <!-- Body -->

                        <div class="package-body">

                            <h3>
                                <?= e($package['title']) ?>
                            </h3>


                            <div class="package-location">

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

                                <span class="rating-number">
                                    <?= number_format($avgRating, 1) ?>
                                </span>

                                <span class="review-count">
                                    (<?= $reviewCount ?> reviews)
                                </span>

                            </div>


                            <!-- Description -->

                            <p class="package-description">

                                <?= e(
                                    strlen($package['description']) > 110
                                    ? substr($package['description'], 0, 110) . '...'
                                    : $package['description']
                                ) ?>

                            </p>


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

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


            <!-- JavaScript Empty State -->

            <div
                class="empty-state"
                id="noSearchResult"
                style="display:none;"
            >

                <div class="empty-icon">
                    🔍
                </div>

                <h3>
                    No matching packages found
                </h3>

                <p>
                    Try another destination, price range or duration.
                </p>

                <a
                    href="packages.php"
                    class="btn btn-primary"
                >
                    View All Packages
                </a>

            </div>


        <?php else: ?>

            <!-- Database Search Empty State -->

            <div class="empty-state">

                <div class="empty-icon">
                    🧳
                </div>

                <h3>
                    No Packages Found
                </h3>

                <p>
                    We couldn't find any tour packages matching your search.
                </p>

                <a
                    href="packages.php"
                    class="btn btn-primary"
                >
                    View All Packages
                </a>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- Why Choose CholoGhuri -->

<section class="section light-section">

    <div class="container">

        <div class="section-heading">

            <span class="section-tag">
                Travel With Confidence
            </span>

            <h2 class="section-title">
                Why Choose CholoGhuri?
            </h2>

            <p>
                We make your travel planning simple, secure and enjoyable.
            </p>

        </div>


        <div class="features-grid">

            <div class="feature-card">

                <div class="feature-icon">
                    🗺️
                </div>

                <h3>
                    Beautiful Destinations
                </h3>

                <p>
                    Discover some of the most amazing destinations
                    across Bangladesh.
                </p>

            </div>


            <div class="feature-card">

                <div class="feature-icon">
                    💰
                </div>

                <h3>
                    Affordable Packages
                </h3>

                <p>
                    Choose travel packages designed for different
                    budgets and preferences.
                </p>

            </div>


            <div class="feature-card">

                <div class="feature-icon">
                    🛡️
                </div>

                <h3>
                    Secure Booking
                </h3>

                <p>
                    Manage your bookings easily with our secure
                    booking management system.
                </p>

            </div>


            <div class="feature-card">

                <div class="feature-icon">
                    ⭐
                </div>

                <h3>
                    Customer Reviews
                </h3>

                <p>
                    Check ratings and reviews before choosing
                    your next tour package.
                </p>

            </div>

        </div>

    </div>

</section>


<?php include 'includes/footer.php'; ?>
