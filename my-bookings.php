<?php require 'includes/db.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location:login.php');
    exit;
}
$uid = $_SESSION['user_id'];
$s = $conn->prepare('SELECT b.*,p.title,p.destination FROM bookings b JOIN tour_packages p ON p.id=b.package_id WHERE b.user_id=? ORDER BY b.created_at DESC');
$s->bind_param('i', $uid);
$s->execute();
$r = $s->get_result();
$pageTitle = 'My Bookings | CholoGhuri';
require 'includes/header.php'; ?>
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <h2>My Bookings</h2>
            <p>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>.</p>
        </div><?php if (isset($_GET['success'])): ?>
            <div class="alert success">Your booking has been submitted successfully.</div>
        <?php endif; ?><?php if ($r->num_rows): ?>
            <div class="table-wrap">
                <table class="table">
                    <tr>
                        <th>Package</th>
                        <th>Destination</th>
                        <th>Date</th>
                        <th>People</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr><?php while ($b = $r->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['title']) ?></td>
                            <td><?= htmlspecialchars($b['destination']) ?></td>
                            <td><?= htmlspecialchars($b['travel_date']) ?></td>
                            <td><?= $b['people'] ?></td>
                            <td>৳<?= number_format($b['total_price']) ?></td>
                            <td><span
                                    class="status <?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span>
                            </td>
                        </tr><?php endwhile; ?>
                </table>
            </div><?php else: ?>
            <div class="empty">No bookings yet. <a style="color:var(--orange)" href="packages.php">Explore packages</a>.
            </div><?php endif; ?>
    </div>
</section><?php require 'includes/footer.php'; ?>