<?php require 'includes/db.php';
$pageTitle = 'CholoGhuri | Explore More. Live Better.';
require 'includes/header.php';
$result = $conn->query("SELECT * FROM tour_packages WHERE status='active' ORDER BY id DESC LIMIT 4"); ?>
<section class="hero">
    <div class="container hero-content">
        <div class="eyebrow">✈️ TRAVEL • EXPLORE • DISCOVER</div>
        <h1>CholoGhuri</h1>
        <p>Discover beautiful destinations, exciting tour packages and unforgettable journeys across Bangladesh.</p>
        <div class="search-box"><input id="packageSearch" placeholder="Search destinations or packages..."><a
                class="btn" href="packages.php">Explore Tours</a></div>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow" style="color:var(--orange)">POPULAR PICKS</div>
            <h2>Explore Our Tours</h2>
            <p>Handpicked journeys for your next adventure.</p>
        </div>
        <div class="cards"><?php while ($p = $result->fetch_assoc()): ?>
                <article class="card package-card"><img class="card-img" src="<?= htmlspecialchars($p['image']) ?>">
                    <div class="card-body"><span class="badge">📍 <?= htmlspecialchars($p['destination']) ?></span>
                        <h3><?= htmlspecialchars($p['title']) ?></h3>
                        <div class="meta">📅 <?= htmlspecialchars($p['duration']) ?> &nbsp; ⭐ 4.8</div>
                        <div class="price">৳<?= number_format($p['price']) ?> <small>/ person</small></div><a class="btn"
                            href="package-details.php?id=<?= $p['id'] ?>">View Details</a>
                    </div>
                </article><?php endwhile; ?>
        </div>
    </div>
</section>
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow" style="color:var(--green)">WHY CHOLOGHURI?</div>
            <h2>Travel Made Simple</h2>
        </div>
        <div class="features">
            <div class="feature">
                <div class="icon">🗺️</div>
                <h3>Best Destinations</h3>
                <p>Explore beautiful places with carefully planned tour packages.</p>
            </div>
            <div class="feature">
                <div class="icon">🎫</div>
                <h3>Easy Booking</h3>
                <p>Choose a package, select your date and book in a few simple steps.</p>
            </div>
            <div class="feature">
                <div class="icon">💚</div>
                <h3>Friendly Support</h3>
                <p>Get helpful support before and during your journey.</p>
            </div>
        </div>
    </div>
</section>
<section id="about" class="section">
    <div class="container about"><img class="about-img" src="images/logo.png" alt="CholoGhuri travel logo">
        <div>
            <div class="eyebrow" style="color:var(--orange)">ABOUT US</div>
            <h2>Let's make your next trip memorable.</h2>
            <p>CholoGhuri is a colorful tour and travel booking platform designed to make discovering Bangladesh easy.
                Browse packages, check details and manage your bookings from one place.</p><a class="btn"
                href="packages.php">See All Packages</a>
        </div>
    </div>
</section>
<section id="contact" class="section alt">
    <div class="container contact">
        <div>
            <div class="eyebrow" style="color:var(--orange)">GET IN TOUCH</div>
            <h2>Plan your next adventure.</h2>
            <p>Have a question about a package? Contact CholoGhuri and we will help you plan your trip.</p>
            <p>📍 Dhaka, Bangladesh</p>
            <p>📞 +880 1700 000000</p>
            <p>✉️ hello@chologhuri.com</p>
        </div>
        <div class="form-card">
            <form
                onsubmit="alert('Thanks! Your message form is ready to connect with PHP mail handling.');return false">
                <div class="form-group"><label>Name</label><input class="form-control" required></div>
                <div class="form-group"><label>Email</label><input type="email" class="form-control" required></div>
                <div class="form-group"><label>Message</label><textarea class="form-control" rows="4"
                        required></textarea></div><button class="btn" type="submit">Send Message</button>
            </form>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>