</main>

<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="site-footer">

    <div class="container footer-grid">

        <!-- BRAND -->
        <div class="footer-column footer-about">

            <a href="index.php" class="brand footer-brand">

                <img
                    src="images/logo.png"
                    alt="CholoGhuri Logo"
                    onerror="this.style.display='none';"
                >

                <span>
                    Cholo<span>Ghuri</span>
                </span>

            </a>

            <p>
                Explore more. Live better. Discover the beauty
                of Bangladesh with CholoGhuri.
            </p>

            <div class="social-links">

                <a href="#" aria-label="Facebook">
                    Facebook
                </a>

                <a href="#" aria-label="Instagram">
                    Instagram
                </a>

                <a href="#" aria-label="YouTube">
                    YouTube
                </a>

            </div>

        </div>


        <!-- QUICK LINKS -->
        <div class="footer-column">

            <h4>Quick Links</h4>

            <a href="index.php">
                Home
            </a>

            <a href="packages.php">
                Tour Packages
            </a>

            <a href="index.php#about">
                About Us
            </a>

            <a href="index.php#contact">
                Contact
            </a>

        </div>


        <!-- ACCOUNT -->
        <div class="footer-column">

            <h4>Account</h4>

            <?php if (isUserLoggedIn()): ?>

                <a href="profile.php">
                    My Profile
                </a>

                <a href="my-bookings.php">
                    My Bookings
                </a>

                <a href="wishlist.php">
                    My Wishlist
                </a>

                <a href="logout.php">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php">
                    Login
                </a>

                <a href="register.php">
                    Create Account
                </a>

            <?php endif; ?>

        </div>


        <!-- CONTACT -->
        <div class="footer-column">

            <h4>Contact Us</h4>

            <p>
                📍 Dhaka, Bangladesh
            </p>

            <p>
                📞 +880 1700 000000
            </p>

            <p>
                ✉️ hello@chologhuri.com
            </p>

            <p>
                🕐 Sat - Thu: 9:00 AM - 8:00 PM
            </p>

        </div>

    </div>


    <!-- ADMIN AREA -->

    <div class="container admin-footer-link">

        <a href="admin/">
            🔐 Admin Login
        </a>

    </div>


    <!-- COPYRIGHT -->

    <div class="copyright">

        <div class="container copyright-inner">

            <p>
                © <?= date('Y') ?>
                <strong>CholoGhuri</strong>.
                All rights reserved.
            </p>

            <p>
                Tour & Travel Booking Management System
            </p>

        </div>

    </div>

</footer>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script src="js/script.js"></script>

</body>

</html>
