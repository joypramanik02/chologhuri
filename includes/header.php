<?php
require_once __DIR__ . '/db.php';

$pageTitle = $pageTitle ?? 'CholoGhuri - Tour & Travel';

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="CholoGhuri - Tour & Travel Booking Management System">

    <meta name="keywords"
          content="Bangladesh tour, travel, tourism, tour package, CholoGhuri">

    <meta name="author"
          content="CholoGhuri">

    <title><?= e($pageTitle) ?></title>

    <!-- Google Font -->
    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="css/style.css">

</head>


<body>

<!-- =====================================================
     NAVBAR
===================================================== -->

<header class="navbar">

    <div class="container nav-inner">

        <!-- LOGO -->
        <a href="index.php" class="brand">

            <img
                src="images/logo.png"
                alt="CholoGhuri Logo"
                onerror="this.style.display='none';"
            >

            <span>
                Cholo<span>Ghuri</span>
            </span>

        </a>


        <!-- MOBILE MENU BUTTON -->
        <button
            class="menu-btn"
            id="menuBtn"
            type="button"
            aria-label="Open Menu"
        >
            ☰
        </button>


        <!-- NAVIGATION -->
        <nav class="nav-links" id="navLinks">

            <a
                href="index.php"
                class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"
            >
                Home
            </a>


            <a
                href="packages.php"
                class="<?= $currentPage === 'packages.php' ? 'active' : '' ?>"
            >
                Packages
            </a>


            <a href="index.php#about">
                About
            </a>


            <a href="index.php#contact">
                Contact
            </a>


            <?php if (isUserLoggedIn()): ?>

                <!-- WISHLIST -->
                <a
                    href="wishlist.php"
                    class="<?= $currentPage === 'wishlist.php' ? 'active' : '' ?>"
                >
                    ♡ Wishlist
                </a>


                <!-- MY BOOKINGS -->
                <a
                    href="my-bookings.php"
                    class="<?= $currentPage === 'my-bookings.php' ? 'active' : '' ?>"
                >
                    My Bookings
                </a>


                <!-- PROFILE -->
                <a
                    href="profile.php"
                    class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>"
                >
                    Profile
                </a>


                <!-- LOGOUT -->
                <a
                    href="logout.php"
                    class="nav-cta"
                >
                    Logout
                </a>

            <?php else: ?>

                <!-- LOGIN -->
                <a
                    href="login.php"
                    class="<?= $currentPage === 'login.php' ? 'active' : '' ?>"
                >
                    Login
                </a>


                <!-- REGISTER -->
                <a
                    href="register.php"
                    class="nav-cta"
                >
                    Join Now
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>


<!-- =====================================================
     FLASH MESSAGE
===================================================== -->

<?php showFlashMessage(); ?>


<!-- =====================================================
     MAIN CONTENT START
===================================================== -->

<main>
