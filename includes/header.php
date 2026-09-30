<?php if (session_status() === PHP_SESSION_NONE)
    session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'CholoGhuri') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="navbar">
        <div class="container nav-inner"><a class="brand" href="index.php"><img src="images/logo.png"
                    alt="CholoGhuri logo"><span>Cholo<span>Ghuri</span></span></a><button class="menu-btn"
                onclick="document.querySelector('.nav-links').classList.toggle('show')">☰</button>
            <nav class="nav-links"><a href="index.php">Home</a><a href="packages.php">Packages</a><a
                    href="index.php#about">About</a><a
                    href="index.php#contact">Contact</a><?php if (isset($_SESSION['user_id'])): ?><a
                        href="my-bookings.php">My Bookings</a><a class="nav-cta"
                        href="logout.php">Logout</a><?php else: ?><a href="login.php">Login</a><a class="nav-cta"
                        href="register.php">Join Now</a><?php endif; ?></nav>
        </div>
    </header>
    <main>