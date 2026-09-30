<?php require 'includes/db.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location:login.php');
    exit;
}+
$id = (int) ($_GET['package_id'] ?? $_POST['package_id'] ?? 0);
$s = $conn->prepare('SELECT * FROM tour_packages WHERE id=? AND status="active"');
$s->bind_param('i', $id);
$s->execute();
$p = $s->get_result()->fetch_assoc();
if (!$p)
    die('Package not found');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['travel_date'];
    $people = max(1, (int) $_POST['people']);
    $total = $p['price'] * $people;
    if ($date < date('Y-m-d'))
        $error = 'Please choose a future travel date.';
    else {
        $u = $_SESSION['user_id'];
        $q = $conn->prepare('INSERT INTO bookings(user_id,package_id,travel_date,people,total_price) VALUES(?,?,?,?,?)');
        $q->bind_param('iisid', $u, $id, $date, $people, $total);
        if ($q->execute()) {
            header('Location:my-bookings.php?success=1');
            exit;
        }
        $error = 'Booking failed.';
    }
}
$pageTitle = 'Book ' . $p['title'];
require 'includes/header.php'; ?>
<section class="section">
    <div class="container booking-grid">
        <div class="summary"><img src="<?= htmlspecialchars($p['image']) ?>">
            <h2><?= htmlspecialchars($p['title']) ?></h2>
            <p>📍 <?= htmlspecialchars($p['destination']) ?></p>
            <div class="price">৳<?= number_format($p['price']) ?> <small>/ person</small></div>
        </div>
        <div class="form-card">
            <h2>Booking Details</h2><?php if ($error): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post"><input type="hidden" name="package_id" value="<?= $p['id'] ?>">
                <div class="form-group"><label>Travel Date</label><input class="form-control" type="date"
                        name="travel_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required></div>
                <div class="form-group"><label>Number of People</label><input class="form-control" type="number"
                        name="people" min="1" max="20" value="1" required></div><button class="btn"
                    style="border:0;font:inherit" type="submit">Confirm Booking</button>
            </form>
        </div>
    </div>
</section><?php require 'includes/footer.php'; ?>