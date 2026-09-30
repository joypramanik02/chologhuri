<?php

require_once __DIR__ . '/includes/db.php';

$pageTitle = 'CholoGhuri - Explore Bangladesh';


// =====================================================
// GET FEATURED TOUR PACKAGES
// =====================================================

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

    WHERE tp.status = 'active'

    GROUP BY tp.id

    ORDER BY tp.created_at DESC

    LIMIT 6
");

$stmt->execute();

$packages = $stmt->fetchAll();


// =====================================================
// INCLUDE HEADER
// =====================================================

require_once __DIR__ . '/includes/header.php';

?>

<!-- =====================================================
     HERO SECTION
===================================================== -->

<section class="hero">

    <div class="container">

        <div class="hero-content">

            <div class="badge">
                ✈️ Discover Bangladesh with CholoGhuri
            </div>

            <h1>
                Travel More.
                <span>Live More.</span>
            </h1>

            <p>
                Discover beautiful destinations, book amazing
                tour packages and create unforgettable memories
                with CholoGhuri.
            </p>

            <div class="hero-buttons">

                <a
                    href="packages.php"
                    class="btn btn-primary"
                >
                    Explore Packages
                </a>

                <a
                    href="#about"
                    class="btn"
                    style="
                        background: rgba(255,255,255,.15);
                        color:white;
                        border:1px solid rgba(255,255,255,.3);
                    "
                >
                    Learn More
                </a>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     SEARCH BOX
===================================================== -->

<div class="search-box">

    <form
        action="packages.php"
        method="GET"
        class="search-form"
    >

        <input
            type="text"
            name="search"
            placeholder="Search destination or tour package..."
            aria-label="Search tour packages"
        >

        <button
            type="submit"
            class="btn btn-primary"
        >
            🔎 Search
        </button>

    </form>

</div>


<!-- =====================================================
     FEATURED PACKAGES
===================================================== -->

<section>

    <div class="container">

        <div class="section-title">

            <h2>
                Popular Tour Packages
            </h2>

            <p>
                Explore some of our most popular destinations
                and choose your next adventure.
            </p>

        </div>


        <?php if (!empty($packages)): ?>

            <div class="package-grid">

                <?php foreach ($packages as $package): ?>

                    <?php

                    $availableSeats =
                        max(
                            0,
                            (int) $package['available_seats']
                        );

                    ?>

                    <article
                        class="package-card"

                        data-destination="<?= e($package['destination']) ?>"

                        data-price="<?= e($package['price']) ?>"

                        data-duration="<?= e($package['duration']) ?>"
                    >

                        <!-- IMAGE -->

                        <div class="package-image">

                            <img
                                src="<?= e($package['image']) ?>"
                                alt="<?= e($package['title']) ?>"
                                loading="lazy"
                                onerror="
                                    this.src='https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1000&q=80';
                                "
                            >

                        </div>


                        <!-- BODY -->

                        <div class="package-body">

                            <h3>
                                <?= e($package['title']) ?>
                            </h3>


                            <p>
                                📍 <?= e($package['destination']) ?>
                            </p>


                            <div class="package-meta">

                                <span>
                                    🕐 <?= e($package['duration']) ?>
                                </span>

                                <span>
                                    🪑 <?= $availableSeats ?> seats
                                </span>

                            </div>


                            <!-- RATING -->

                            <div class="rating">

                                ⭐
                                <?= number_format(
                                    (float) $package['avg_rating'],
                                    1
                                ) ?>

                                <span class="rating-number">
                                    (
                                    <?= (int) $package['review_count'] ?>
                                    reviews
                                    )
                                </span>

                            </div>


                            <div
                                class="package-price"
                                style="margin-top:10px;"
                            >

                                ৳<?= number_format(
                                    (float) $package['price']
                                ) ?>

                                <small
                                    style="
                                        font-size:12px;
                                        color:#6b7280;
                                        font-weight:500;
                                    "
                                >
                                    / person
                                </small>

                            </div>


                            <div class="package-actions">

                                <a
                                    href="package-details.php?id=<?= (int) $package['id'] ?>"
                                    class="btn btn-outline"
                                >
                                    Details
                                </a>

                                <a
                                    href="booking.php?id=<?= (int) $package['id'] ?>"
                                    class="btn btn-primary"
                                >
                                    Book Now
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


            <!-- VIEW ALL -->

            <div
                class="text-center"
                style="margin-top:35px;"
            >

                <a
                    href="packages.php"
                    class="btn btn-secondary"
                >
                    View All Packages →
                </a>

            </div>


        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">
                    🌍
                </div>

                <h3>
                    No tour packages available
                </h3>

                <p>
                    Please check back later for new destinations.
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>


<!-- =====================================================
     WHY CHOLOGHURI
===================================================== -->

<section
    style="
        background:#ffffff;
        border-top:1px solid #eef0f2;
        border-bottom:1px solid #eef0f2;
    "
>

    <div class="container">

        <div class="section-title">

            <h2>
                Why Choose CholoGhuri?
            </h2>

            <p>
                Everything you need for a simple and enjoyable
                travel booking experience.
            </p>

        </div>


        <div
            class="package-grid"
            style="
                grid-template-columns:repeat(4,1fr);
            "
        >

            <!-- FEATURE 1 -->

            <div
                class="package-card"
                style="
                    padding:25px;
                    text-align:center;
                "
            >

                <div
                    style="
                        font-size:38px;
                        margin-bottom:12px;
                    "
                >
                    🗺️
                </div>

                <h3>
                    Amazing Destinations
                </h3>

                <p>
                    Explore beautiful and exciting destinations
                    across Bangladesh.
                </p>

            </div>


            <!-- FEATURE 2 -->

            <div
                class="package-card"
                style="
                    padding:25px;
                    text-align:center;
                "
            >

                <div
                    style="
                        font-size:38px;
                        margin-bottom:12px;
                    "
                >
                    💳
                </div>

                <h3>
                    Easy Booking
                </h3>

                <p>
                    Book your preferred package quickly with our
                    simple booking system.
                </p>

            </div>


            <!-- FEATURE 3 -->

            <div
                class="package-card"
                style="
                    padding:25px;
                    text-align:center;
                "
            >

                <div
                    style="
                        font-size:38px;
                        margin-bottom:12px;
                    "
                >
                    🎟️
                </div>

                <h3>
                    Special Offers
                </h3>

                <p>
                    Use available coupons and enjoy discounts on
                    selected tour packages.
                </p>

            </div>


            <!-- FEATURE 4 -->

            <div
                class="package-card"
                style="
                    padding:25px;
                    text-align:center;
                "
            >

                <div
                    style="
                        font-size:38px;
                        margin-bottom:12px;
                    "
                >
                    ⭐
                </div>

                <h3>
                    Trusted Reviews
                </h3>

                <p>
                    Read reviews and ratings from travelers before
                    making your booking.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     ABOUT SECTION
===================================================== -->

<section id="about">

    <div class="container">

        <div class="details-grid">

            <div class="details-image">

                <img
                    src="https://images.unsplash.com/photo-1527631746610-bca00a040d60?auto=format&fit=crop&w=1200&q=85"
                    alt="Travel in Bangladesh"
                    loading="lazy"
                >

            </div>


            <div class="details-info">

                <div class="destination">
                    ABOUT CHOLOGHURI
                </div>

                <h1>
                    Your Journey,
                    Our Responsibility.
                </h1>

                <p class="description">

                    CholoGhuri is a Tour & Travel Booking
                    Management System designed to make travel
                    planning easier, faster and more convenient.

                    From discovering destinations to booking
                    packages, managing reservations and sharing
                    reviews, everything can be managed from one
                    platform.

                </p>


                <div
                    style="
                        display:grid;
                        grid-template-columns:1fr 1fr;
                        gap:15px;
                        margin-top:25px;
                    "
                >

                    <div class="details-box">

                        <strong>
                            100%
                        </strong>

                        <p>
                            Easy Booking
                        </p>

                    </div>


                    <div class="details-box">

                        <strong>
                            24/7
                        </strong>

                        <p>
                            Online Access
                        </p>

                    </div>

                </div>


                <div style="margin-top:25px;">

                    <a
                        href="packages.php"
                        class="btn btn-primary"
                    >
                        Start Exploring
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     CALL TO ACTION
===================================================== -->

<section
    style="
        padding:70px 0;
        background:
            linear-gradient(
                135deg,
                #0f766e,
                #0ea5a4
            );
        color:white;
    "
>

    <div
        class="container"
        style="text-align:center;"
    >

        <h2
            style="
                font-size:36px;
                margin-bottom:12px;
            "
        >
            Ready for Your Next Adventure?
        </h2>

        <p
            style="
                max-width:650px;
                margin:0 auto 25px;
                color:#dffcf8;
            "
        >
            Find your favorite destination, choose a package
            and start planning your next unforgettable journey.
        </p>

        <a
            href="packages.php"
            class="btn"
            style="
                background:white;
                color:#0f766e;
            "
        >
            Explore Tours →
        </a>

    </div>

</section>


<!-- =====================================================
     CONTACT SECTION
===================================================== -->

<section id="contact">

    <div class="container">

        <div class="section-title">

            <h2>
                Contact Us
            </h2>

            <p>
                Have a question about a package or booking?
                We would love to hear from you.
            </p>

        </div>


        <div
            class="details-grid"
            style="align-items:stretch;"
        >

            <div class="details-box">

                <h3>
                    Get in Touch
                </h3>

                <p
                    style="
                        margin-top:15px;
                        color:#6b7280;
                    "
                >
                    📍 Dhaka, Bangladesh
                </p>

                <p
                    style="
                        margin-top:10px;
                        color:#6b7280;
                    "
                >
                    📞 +880 1700 000000
                </p>

                <p
                    style="
                        margin-top:10px;
                        color:#6b7280;
                    "
                >
                    ✉️ hello@chologhuri.com
                </p>

                <p
                    style="
                        margin-top:10px;
                        color:#6b7280;
                    "
                >
                    🕐 Sat - Thu: 9:00 AM - 8:00 PM
                </p>

            </div>


            <div class="details-box">

                <h3>
                    Start Your Journey
                </h3>

                <p
                    style="
                        margin:15px 0 20px;
                        color:#6b7280;
                    "
                >
                    Browse our available packages and find
                    a destination that matches your travel plan.
                </p>

                <a
                    href="packages.php"
                    class="btn btn-primary"
                >
                    Browse Packages
                </a>

            </div>

        </div>

    </div>

</section>


<?php

// =====================================================
// FOOTER
// =====================================================

require_once __DIR__ . '/includes/footer.php';

?>
