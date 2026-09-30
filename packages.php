<?php require 'includes/db.php';
$pageTitle = 'Tour Packages | CholoGhuri';
require 'includes/header.php';
$result = $conn->query("SELECT * FROM tour_packages WHERE status='active' ORDER BY id DESC"); ?>
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow" style="color:var(--orange)">DISCOVER</div>
            <h2>All Tour Packages</h2>
            <p>Find a trip that matches your travel mood.</p>
        </div>
        <div class="search-box" style="margin:0 auto 30px;max-width:700px;box-shadow:none;border:1px solid #e2edf3">
            <input id="packageSearch" placeholder="Search by destination or package name..."></div>
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
</section><?php require 'includes/footer.php'; ?>