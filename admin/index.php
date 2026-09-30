<?php require '../includes/db.php';
session_start();
$packages = $conn->query('SELECT COUNT(*) c FROM tour_packages')->fetch_assoc()['c'];
$users = $conn->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c'];
$bookings = $conn->query('SELECT COUNT(*) c FROM bookings')->fetch_assoc()['c']; ?><!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <header class="navbar">
        <div class="container nav-inner"><a class="brand" href="../index.php"><img
                    src="../images/logo.png"><span>Cholo<span>Ghuri</span></span></a>
            <nav class="nav-links" style="display:flex"><a href="../index.php">View Website</a></nav>
        </div>
    </header>
    <section class="section alt">
        <div class="container">
            <div class="section-head">
                <div class="eyebrow" style="color:var(--orange)">ADMIN PANEL</div>
                <h2>Dashboard</h2>
                <p>CholoGhuri booking management overview</p>
            </div>
            <div class="features">
                <div class="feature">
                    <div class="icon">🧳</div>
                    <h3><?= $packages ?></h3>
                    <p>Tour Packages</p>
                </div>
                <div class="feature">
                    <div class="icon">👥</div>
                    <h3><?= $users ?></h3>
                    <p>Registered Users</p>
                </div>
                <div class="feature">
                    <div class="icon">🎫</div>
                    <h3><?= $bookings ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="form-card" style="margin-top:30px">
                <h3>Admin modules ready to expand</h3>
                <p>Next modules can include Add/Edit/Delete Packages, Booking Approval, User Management and Reports.</p>
            </div>
        </div>
    </section>
</body>

</html>