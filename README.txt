CHOLOGHURI TOUR & TRAVEL BOOKING MANAGEMENT SYSTEM

Stack: HTML, CSS, JavaScript, PHP, MySQL

SETUP (XAMPP):
1. Copy the chologhuri folder into C:\xampp\htdocs\
2. Start Apache and MySQL from XAMPP.
3. Open phpMyAdmin and import database/chologhuri.sql.
4. Check includes/db.php. Default XAMPP values are host=localhost, user=root, password=empty.
5. Visit http://localhost/chologhuri/
6. Register a user, login, choose a package and create a booking.
7. Admin dashboard: http://localhost/chologhuri/admin/

NOTES:
- Demo package images use remote Unsplash URLs. Replace with local images if you want fully offline operation.
- The supplied CholoGhuri logo is stored at images/logo.png.
- For production, add CSRF protection, server-side authorization for admin routes, stronger validation, secure session settings, payment gateway integration and environment-based database credentials.
