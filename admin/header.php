<?php
require_once __DIR__ . '/../includes/db.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = $pageTitle ?? 'Admin Dashboard - CholoGhuri';

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title><?= e($pageTitle) ?></title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="../css/style.css"
>

</head>

<body class="admin-body">

<div class="admin-layout">


    <!-- Sidebar -->

    <aside class="admin-sidebar">

        <div class="admin-brand">

            <a href="index.php">

                <span class="admin-brand-icon">
                    🧳
                </span>

                <span>
                    Cholo<span>Ghuri</span>
                </span>

            </a>

        </div>


        <nav class="admin-menu">

            <a
                href="index.php"
                class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"
            >
                📊 Dashboard
            </a>


            <a href="packages.php">
                🗺️ Tour Packages
            </a>


            <a href="bookings.php">
                📋 Bookings
            </a>


            <a href="users.php">
                👥 Users
            </a>


            <a href="reviews.php">
                ⭐ Reviews
            </a>


            <a href="coupons.php">
                🎟️ Coupons
            </a>


            <a href="payments.php">
                💳 Payments
            </a>

        </nav>


        <div class="admin-sidebar-bottom">

            <a href="../index.php" target="_blank">
                🌐 View Website
            </a>

            <a href="logout.php">
                🚪 Logout
            </a>

        </div>

    </aside>


    <!-- Main Content -->

    <div class="admin-main">

        <header class="admin-topbar">

            <div>

                <h1>
                    <?= e($pageTitle) ?>
                </h1>

            </div>


            <div class="admin-user">

                <div class="admin-user-avatar">
                    <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?>
                </div>

                <div>

                    <strong>
                        <?= e($_SESSION['admin_name'] ?? 'Administrator') ?>
                    </strong>

                    <small>
                        Administrator
                    </small>

                </div>

            </div>

        </header>


        <?php showFlashMessage(); ?>

        <main class="admin-content">
