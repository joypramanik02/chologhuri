# 🌍 CholoGhuri

## Tour & Travel Booking Management System

CholoGhuri is a web-based **Tour & Travel Booking Management System** developed using PHP, MySQL, HTML, CSS and JavaScript.

The system allows users to explore tour packages, search and filter destinations, register accounts, make bookings, use discount coupons, manage wishlists, complete demo payments, submit reviews and manage their profiles.

An administrator can manage tour packages, bookings, users, reviews, coupons and payments through a dedicated Admin Panel.

---

# 📌 Table of Contents

1. [Project Overview](#-project-overview)
2. [Main Features](#-main-features)
3. [Technology Stack](#-technology-stack)
4. [Complete Project Structure](#-complete-project-structure)
5. [Root PHP Files](#-root-php-files)
6. [Includes Folder](#-includes-folder)
7. [CSS Folder](#-css-folder)
8. [JavaScript Folder](#-javascript-folder)
9. [Database Folder](#-database-folder)
10. [Images Folder](#-images-folder)
11. [Uploads Folder](#-uploads-folder)
12. [Admin Panel Structure](#-admin-panel-structure)
13. [Database Tables](#-database-tables)
14. [User System](#-user-system)
15. [Tour Package System](#-tour-package-system)
16. [Booking System](#-booking-system)
17. [Payment System](#-payment-system)
18. [Coupon System](#-coupon-system)
19. [Wishlist System](#-wishlist-system)
20. [Review & Rating System](#-review--rating-system)
21. [Profile System](#-profile-system)
22. [Admin System](#-admin-system)
23. [Complete User Flow](#-complete-user-flow)
24. [Complete Admin Flow](#-complete-admin-flow)
25. [Database Relationship Overview](#-database-relationship-overview)
26. [Installation](#-installation)
27. [XAMPP Configuration](#-xampp-configuration)
28. [Database Setup](#-database-setup)
29. [Admin Account Setup](#-admin-account-setup)
30. [How to Run](#-how-to-run)
31. [Testing Checklist](#-testing-checklist)
32. [Security Features](#-security-features)
33. [Future Improvements](#-future-improvements)
34. [Project Information](#-project-information)

---

# 📖 Project Overview

CholoGhuri is designed to simplify the process of discovering and booking tour packages.

Instead of managing tour information, customer information and bookings manually, the system provides a centralized platform where customers and administrators can manage everything digitally.

The system contains two major parts:

### 👤 User Side

Users can:

* Create an account
* Login and logout
* Browse tour packages
* Search packages
* Filter packages
* View package details
* Check seat availability
* Add packages to wishlist
* Book tour packages
* Apply discount coupons
* Make demo payments
* View booking history
* Cancel bookings
* Submit reviews
* Manage their profile

### 🔐 Admin Side

Administrators can:

* Login to Admin Panel
* View dashboard statistics
* Manage tour packages
* Manage bookings
* Manage users
* Manage reviews
* Manage coupons
* Manage payments
* Change package status
* Change booking status
* Change payment status
* Monitor revenue

---

# ✨ Main Features

## 1. User Registration

New users can create an account using:

* Name
* Email
* Phone
* Password
* Confirm Password

The system validates user information before creating an account.

---

## 2. User Login

Registered users can login using:

* Email
* Password

Passwords are stored using secure password hashing.

---

## 3. Tour Package Browsing

Users can browse available tour packages.

Each package contains:

* Package title
* Destination
* Duration
* Price
* Image
* Description
* Total seats
* Available seats
* Rating
* Number of reviews
* Package status

---

## 4. Advanced Search & Filter

Users can search packages using:

* Package title
* Destination
* Price range
* Duration

The package page dynamically displays matching results.

---

## 5. Package Details

Each package has a dedicated details page.

Users can see:

* Package information
* Price
* Destination
* Duration
* Description
* Total seats
* Available seats
* Rating
* Reviews
* Wishlist option
* Booking option
* Related packages

---

## 6. Wishlist

Logged-in users can save favorite packages.

Users can:

* Add package to wishlist
* Remove package from wishlist
* View all saved packages
* Open package details from wishlist

Duplicate wishlist entries are prevented.

---

## 7. Booking System

Users can book a tour package by selecting:

* Travel date
* Number of people
* Coupon code

The system automatically calculates:

```text
Total Price
      ↓
Coupon Discount
      ↓
Final Price
```

The system also checks seat availability before confirming the booking.

---

## 8. Seat Availability

Each package has a fixed number of seats.

For example:

```text
Total Seats = 30
Booked Seats = 12

Available Seats = 18
```

The system prevents users from booking more seats than available.

---

## 9. Coupon System

Users can apply discount coupons during booking.

Supported discount types:

### Percentage Discount

Example:

```text
WELCOME10
10% discount
```

### Fixed Discount

Example:

```text
TRAVEL500
৳500 discount
```

The system validates:

* Coupon code
* Coupon status
* Start date
* End date
* Usage limit
* Current usage count

---

## 10. Payment System

The project includes a demo payment system.

Supported methods:

* Cash
* bKash
* Nagad
* Card
* Bank Transfer

For online-style payment methods, a transaction ID can be entered.

> This project uses demo payment processing. It is not connected to a real payment gateway.

---

## 11. Booking Status

A booking can have:

```text
Pending
Confirmed
Cancelled
```

---

## 12. Payment Status

A payment can have:

```text
Pending
Paid
Failed
Refunded
```

---

## 13. Review & Rating

Users can review confirmed bookings.

Rating range:

```text
1 ★
2 ★
3 ★
4 ★
5 ★
```

Users can submit:

* Rating
* Comment

Package pages display the average rating and reviews.

---

## 14. User Profile

Users can manage:

* Name
* Phone
* Address
* Profile picture
* Password

Supported profile image formats:

* JPG
* PNG
* WEBP

---

# 🛠 Technology Stack

| Technology | Purpose                             |
| ---------- | ----------------------------------- |
| HTML5      | Page structure                      |
| CSS3       | Design and responsive layout        |
| JavaScript | Client-side interaction             |
| PHP        | Backend/server-side logic           |
| MySQL      | Database                            |
| PDO        | Database communication              |
| XAMPP      | Local development server            |
| Apache     | PHP web server                      |
| phpMyAdmin | Database management                 |
| GitHub     | Version control and project hosting |

---

# 📁 Complete Project Structure

```text
chologhuri/
│
├── admin/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── create-admin.php
│   ├── header.php
│   ├── footer.php
│   ├── packages.php
│   ├── bookings.php
│   ├── users.php
│   ├── reviews.php
│   ├── coupons.php
│   └── payments.php
│
├── css/
│   └── style.css
│
├── database/
│   └── chologhuri.sql
│
├── images/
│   └── logo.png
│
├── includes/
│   ├── db.php
│   ├── header.php
│   └── footer.php
│
├── js/
│   └── script.js
│
├── uploads/
│   ├── profiles/
│   └── packages/
│
├── index.php
├── packages.php
├── package-details.php
├── wishlist.php
├── booking.php
├── payment.php
├── profile.php
├── login.php
├── register.php
├── my-bookings.php
├── logout.php
│
└── README.md
```

---

# 📄 Root PHP Files

## `index.php`

This is the main homepage.

Responsibilities:

* Load active tour packages
* Display featured packages
* Display package ratings
* Display available seats
* Provide search functionality
* Display hero section
* Display About section
* Display Contact section
* Provide navigation to packages

Main database table used:

```text
tour_packages
reviews
bookings
```

---

# `packages.php`

This page displays all available tour packages.

Features:

* Search
* Destination filter
* Price filter
* Duration filter
* Package cards
* Rating
* Available seats
* Booking button
* Details button

It retrieves package information from MySQL using PDO.

---

# `package-details.php`

Displays complete information about a selected package.

Example URL:

```text
package-details.php?id=1
```

It displays:

* Package title
* Destination
* Duration
* Price
* Description
* Image
* Rating
* Reviews
* Available seats

It also provides:

```text
Add to Wishlist
Book Now
```

---

# `wishlist.php`

Handles the user's wishlist.

Main functions:

```text
Add package
Remove package
Display wishlist
Prevent duplicate wishlist entries
```

Authentication is required.

Main database table:

```text
wishlist
```

---

# `booking.php`

Handles the complete booking process.

Responsibilities:

* Validate logged-in user
* Validate package
* Check package status
* Check travel date
* Validate number of people
* Check available seats
* Validate coupon
* Calculate total price
* Calculate discount
* Calculate final price
* Create booking
* Create payment record

Important calculation:

```text
Total Price = Package Price × Number of People
```

Then:

```text
Final Price = Total Price - Discount
```

---

# `payment.php`

Handles booking payment confirmation.

Supported methods:

```text
Cash
bKash
Nagad
Card
Bank Transfer
```

The system verifies that the booking belongs to the logged-in user.

After successful demo payment:

```text
Payment Status → Paid
Booking Status → Confirmed
```

---

# `profile.php`

Handles user profile management.

Users can update:

* Name
* Phone
* Address
* Profile image

Users can also change their password.

Profile statistics may include:

* Total bookings
* Confirmed bookings
* Wishlist items
* Reviews

---

# `login.php`

Handles user authentication.

Process:

```text
Email + Password
        ↓
Find User
        ↓
Verify Password
        ↓
Create Session
        ↓
Redirect
```

---

# `register.php`

Handles new user registration.

Validation includes:

* Name
* Email
* Password
* Confirm Password
* Duplicate email checking

Passwords are hashed before storage.

---

# `my-bookings.php`

Displays the logged-in user's bookings.

Users can see:

* Booking ID
* Package
* Travel date
* Number of people
* Total price
* Discount
* Final price
* Booking status
* Payment status

Users can:

* Cancel eligible bookings
* Pay pending bookings
* Submit reviews for confirmed bookings

---

# `logout.php`

Destroys the user session and logs the user out.

---

# 📂 Includes Folder

## `includes/db.php`

This is one of the most important backend files.

Responsibilities:

* Connect PHP to MySQL
* Create PDO connection
* Start session
* Provide helper functions

Database configuration:

```text
Host: localhost
Database: chologhuri
Username: root
Password: empty
Charset: utf8mb4
```

It also provides functions such as:

```text
e()
isUserLoggedIn()
isAdminLoggedIn()
requireUser()
requireAdmin()
redirectWithMessage()
showFlashMessage()
```

---

# `includes/header.php`

Common website header.

Contains:

* Navigation bar
* Logo
* Home
* Packages
* About
* Contact
* Wishlist
* My Bookings
* Profile
* Login
* Join Now
* Logout

It also loads:

```text
style.css
```

---

# `includes/footer.php`

Common website footer.

Contains:

* Company information
* Quick links
* Account links
* Contact information
* Social links
* Admin login link
* Copyright

It also loads:

```text
js/script.js
```

---

# 🎨 CSS Folder

## `css/style.css`

This file controls the complete visual design of the website.

It includes styling for:

* Global layout
* Navbar
* Hero section
* Buttons
* Package cards
* Search section
* Filters
* Forms
* Login
* Registration
* Booking
* Payment
* Wishlist
* Profile
* Reviews
* Tables
* Footer
* Admin dashboard
* Responsive design

Responsive breakpoints are used for:

```text
Desktop
Tablet
Mobile
```

---

# ⚙️ JavaScript Folder

## `js/script.js`

Handles client-side interactions.

Main features:

### Mobile Menu

Opens and closes the mobile navigation menu.

### Package Search

Provides client-side search interaction where applicable.

### Filters

Handles package filtering UI.

### Booking Calculation

Calculates:

```text
People × Package Price
```

### Confirmation Dialog

Used for actions such as cancellation or deletion.

### Flash Messages

Automatically hides temporary messages.

### Number Validation

Validates booking quantity.

### Star Rating

Handles review rating interface.

### Password Toggle

Allows users to show/hide passwords.

### Smooth Scrolling

Provides smooth navigation to sections.

### Payment UI

Shows/hides transaction ID fields based on payment method.

---

# 🗄 Database Folder

## `database/chologhuri.sql`

This file contains the MySQL database structure.

Import this file using:

```text
phpMyAdmin
```

Main database:

```text
chologhuri
```

---

# 🖼 Images Folder

## `images/logo.png`

Contains the main CholoGhuri logo.

Used by:

```text
Header
Footer
Website branding
```

---

# 📤 Uploads Folder

## `uploads/profiles/`

Stores uploaded user profile images.

---

## `uploads/packages/`

Stores uploaded tour package images uploaded by administrators.

---

# 🔐 Admin Panel Structure

The Admin Panel is located inside:

```text
admin/
```

---

# `admin/login.php`

Admin authentication page.

Admin enters:

```text
Username
Password
```

After successful authentication:

```text
admin/index.php
```

---

# `admin/index.php`

Main Admin Dashboard.

Displays statistics such as:

* Total users
* Total packages
* Total bookings
* Total revenue
* Recent bookings
* Recent users

Also provides quick management actions.

---

# `admin/header.php`

Common Admin Panel header/sidebar.

Contains navigation to:

```text
Dashboard
Packages
Bookings
Users
Reviews
Coupons
Payments
View Website
Logout
```

---

# `admin/footer.php`

Common Admin Panel footer.

Contains closing HTML structure and shared scripts.

---

# `admin/logout.php`

Logs the administrator out.

It destroys the admin session.

---

# `admin/create-admin.php`

Used to create the initial administrator account.

Example default credentials configured by the setup script:

```text
Username:
admin

Password:
admin123
```

The password is stored using password hashing.

### Important

After creating the admin account, this setup file should be removed or disabled in a production environment.

---

# `admin/packages.php`

Complete package management system.

Admin can:

* Add package
* Edit package
* Delete package
* Upload package image
* Set destination
* Set duration
* Set price
* Set total seats
* Set description
* Activate package
* Deactivate package

Package status:

```text
active
inactive
```

---

# `admin/bookings.php`

Admin can manage customer bookings.

Features:

* Search bookings
* Filter bookings
* View booking information
* Update booking status
* View payment status
* Update payment status
* Monitor revenue

Booking statuses:

```text
Pending
Confirmed
Cancelled
```

---

# `admin/users.php`

Admin can view registered users.

Displays information such as:

* User name
* Email
* Phone
* Registration date
* Number of bookings
* Wishlist activity
* Review activity

---

# `admin/reviews.php`

Admin manages customer reviews.

Features:

* View reviews
* Filter reviews
* View ratings
* Delete inappropriate reviews

Rating statistics can also be displayed.

---

# `admin/coupons.php`

Admin manages discount coupons.

Admin can:

* Create coupon
* Edit coupon
* Delete coupon
* Activate/deactivate coupon
* Set discount type
* Set discount amount
* Set usage limit
* Set start date
* Set end date

Discount types:

```text
Percent
Fixed
```

Example:

```text
WELCOME10
10%
```

or:

```text
TRAVEL500
৳500
```

---

# `admin/payments.php`

Admin manages payment records.

Displays:

* Booking ID
* Customer
* Payment method
* Transaction ID
* Amount
* Payment status
* Payment date

Admin can update payment status.

Available statuses:

```text
Pending
Paid
Failed
Refunded
```

---

# 🗃 Database Tables

The project uses the following major tables:

```text
users
tour_packages
bookings
reviews
wishlist
coupons
coupon_usages
payments
admin_users
```

---

# 👤 `users`

Stores customer information.

Important columns:

```text
id
name
email
phone
password
profile_image
address
created_at
```

---

# 🧳 `tour_packages`

Stores tour package information.

Important columns:

```text
id
title
destination
duration
price
image
description
total_seats
status
created_at
```

---

# 📅 `bookings`

Stores customer booking information.

Important columns:

```text
id
user_id
package_id
travel_date
people
total_price
discount
final_price
coupon_code
status
created_at
```

Relationships:

```text
user_id → users.id
package_id → tour_packages.id
```

---

# ⭐ `reviews`

Stores package reviews and ratings.

Important columns:

```text
id
user_id
package_id
rating
comment
created_at
```

Relationships:

```text
user_id → users.id
package_id → tour_packages.id
```

---

# ❤️ `wishlist`

Stores packages saved by users.

Important columns:

```text
id
user_id
package_id
created_at
```

A unique constraint prevents the same user from adding the same package multiple times.

---

# 🎟 `coupons`

Stores discount coupon information.

Important columns:

```text
id
code
discount_type
discount_value
usage_limit
used_count
start_date
end_date
status
created_at
```

---

# 🧾 `coupon_usages`

Tracks coupon usage.

Important columns:

```text
id
coupon_id
user_id
booking_id
discount_amount
created_at
```

This allows the system to track which customer used which coupon for which booking.

---

# 💳 `payments`

Stores payment information.

Important columns:

```text
id
booking_id
method
status
transaction_id
amount
paid_at
created_at
```

Relationship:

```text
booking_id → bookings.id
```

---

# 👨‍💼 `admin_users`

Stores administrator accounts.

Important columns:

```text
id
username
password
name
created_at
```

---

# 🔗 Database Relationship Overview

The main relationships are:

```text
USER
 │
 ├───────────────┐
 │               │
 ▼               ▼
BOOKINGS       WISHLIST
 │
 ├───────────────┐
 │               │
 ▼               ▼
PACKAGE        PAYMENT
 │
 ▼
REVIEWS
```

More specifically:

```text
users
  │
  ├── bookings
  │       │
  │       ├── payments
  │       │
  │       └── tour_packages
  │
  ├── wishlist
  │       │
  │       └── tour_packages
  │
  └── reviews
          │
          └── tour_packages
```

Coupon relationship:

```text
coupons
    │
    ▼
coupon_usages
    │
    ├── users
    └── bookings
```

---

# 🔄 Complete User Flow

The normal user journey is:

```text
Homepage
   ↓
Browse Packages
   ↓
Search / Filter
   ↓
Package Details
   ↓
Login / Register
   ↓
Add Wishlist OR Book Now
   ↓
Select Travel Date
   ↓
Select Number of People
   ↓
Apply Coupon
   ↓
Calculate Price
   ↓
Confirm Booking
   ↓
Payment
   ↓
Booking Confirmed
   ↓
My Bookings
   ↓
Travel
   ↓
Submit Review
```

---

# 🔐 Complete Admin Flow

Admin journey:

```text
Admin Login
     ↓
Dashboard
     ↓
View Statistics
     ↓
Manage Packages
     ↓
Manage Bookings
     ↓
Manage Users
     ↓
Manage Reviews
     ↓
Manage Coupons
     ↓
Manage Payments
     ↓
Monitor System
```

---

# 💰 Booking Price Calculation

Suppose:

```text
Package Price = ৳5,500
People = 3
```

Then:

```text
Total Price
= 5,500 × 3
= ৳16,500
```

If the coupon gives 10% discount:

```text
Discount
= 16,500 × 10%
= ৳1,650
```

Final price:

```text
16,500 - 1,650
= ৳14,850
```

---

# 🪑 Seat Calculation

Suppose:

```text
Total Seats = 30
```

Existing bookings:

```text
8 + 5 + 4 = 17
```

Available:

```text
30 - 17 = 13
```

If a user wants:

```text
People = 5
```

The booking is allowed because:

```text
5 ≤ 13
```

After booking:

```text
Available Seats = 8
```

---

# 🔒 Security Features

The project uses several basic security practices.

### Password Hashing

Passwords are stored using PHP password hashing.

### PDO Prepared Statements

Database queries use PDO prepared statements to reduce SQL injection risk.

### Session Authentication

Users and administrators are protected using sessions.

### Access Control

Protected pages require login.

For example:

```text
requireUser()
requireAdmin()
```

### Input Escaping

Output is escaped using:

```text
htmlspecialchars()
```

through the helper:

```text
e()
```

### File Upload Validation

Profile image uploads are restricted by:

* File type
* File size

---

# 🧪 Testing Checklist

After installing the project, test the following.

## User Registration

```text
[ ] Open register.php
[ ] Create account
[ ] Try duplicate email
[ ] Try incorrect password confirmation
[ ] Verify successful registration
```

---

## User Login

```text
[ ] Login with correct credentials
[ ] Try incorrect credentials
[ ] Check session
[ ] Logout
```

---

## Package

```text
[ ] Open packages.php
[ ] Search package
[ ] Filter destination
[ ] Filter price
[ ] Open package details
[ ] Check available seats
```

---

## Wishlist

```text
[ ] Login
[ ] Add package to wishlist
[ ] Open wishlist
[ ] Remove package
[ ] Try duplicate wishlist
```

---

## Booking

```text
[ ] Select package
[ ] Select travel date
[ ] Select people
[ ] Check total price
[ ] Apply valid coupon
[ ] Apply invalid coupon
[ ] Try exceeding available seats
[ ] Confirm booking
```

---

## Payment

```text
[ ] Open pending booking
[ ] Select Cash
[ ] Select bKash
[ ] Select Nagad
[ ] Select Card
[ ] Enter transaction ID
[ ] Complete demo payment
[ ] Check booking status
```

---

## Review

```text
[ ] Confirm booking
[ ] Submit rating
[ ] Submit comment
[ ] Check package review
```

---

## Profile

```text
[ ] Update name
[ ] Update phone
[ ] Update address
[ ] Upload profile image
[ ] Change password
```

---

# 🧑‍💼 Admin Testing

## Admin Login

```text
[ ] Open admin/login.php
[ ] Login
[ ] Check dashboard
```

## Package Management

```text
[ ] Add package
[ ] Edit package
[ ] Upload image
[ ] Change price
[ ] Change seats
[ ] Activate package
[ ] Deactivate package
```

## Booking Management

```text
[ ] View booking
[ ] Search booking
[ ] Filter booking
[ ] Change booking status
```

## User Management

```text
[ ] View users
[ ] Search users
[ ] View user information
```

## Review Management

```text
[ ] View reviews
[ ] Filter reviews
[ ] Delete review
```

## Coupon Management

```text
[ ] Create coupon
[ ] Edit coupon
[ ] Activate/deactivate coupon
[ ] Delete coupon
[ ] Set usage limit
```

## Payment Management

```text
[ ] View payments
[ ] Filter payments
[ ] Change payment status
[ ] Check revenue
```

---

# 💻 Installation

## Step 1 — Install XAMPP

Install XAMPP with:

```text
Apache
MySQL
PHP
phpMyAdmin
```

---

# Step 2 — Copy Project

Copy the project into:

```text
C:\xampp\htdocs\
```

The final location should be:

```text
C:\xampp\htdocs\chologhuri\
```

---

# Step 3 — Start XAMPP

Open XAMPP Control Panel.

Start:

```text
Apache
MySQL
```

Both should show:

```text
Running
```

---

# Step 4 — Create Database

Open:

```text
http://localhost/phpmyadmin/
```

Create database:

```text
chologhuri
```

---

# Step 5 — Import SQL

Open:

```text
chologhuri
```

Then:

```text
Import
```

Select:

```text
database/chologhuri.sql
```

Click:

```text
Go
```

---

# Step 6 — Check Database Tables

The following tables should exist:

```text
users
tour_packages
bookings
reviews
wishlist
coupons
coupon_usages
payments
admin_users
```

---

# Step 7 — Check Database Connection

Open:

```text
includes/db.php
```

Default configuration:

```php
$host = 'localhost';
$db   = 'chologhuri';
$user = 'root';
$pass = '';
```

If your MySQL configuration is different, update these values.

---

# 🚀 How to Run

Open:

```text
http://localhost/chologhuri/
```

Main pages:

```text
Homepage
http://localhost/chologhuri/

Packages
http://localhost/chologhuri/packages.php

Login
http://localhost/chologhuri/login.php

Register
http://localhost/chologhuri/register.php

Wishlist
http://localhost/chologhuri/wishlist.php

Profile
http://localhost/chologhuri/profile.php

My Bookings
http://localhost/chologhuri/my-bookings.php
```

---

# 🔐 Admin Access

Admin login:

```text
http://localhost/chologhuri/admin/login.php
```

If the admin account has not been created yet, open:

```text
http://localhost/chologhuri/admin/create-admin.php
```

Then login using the created administrator credentials.

After successful setup, remove or disable:

```text
create-admin.php
```

for security.

---

# ⚠️ Common Errors

## Database Connection Failed

Check:

```text
Apache = Running
MySQL = Running
Database = chologhuri
```

Also check:

```text
includes/db.php
```

---

## Unknown Column Error

Example:

```text
Unknown column 'tp.total_seats'
```

This usually means the database structure is older than the current PHP code.

Make sure the `tour_packages` table contains:

```text
total_seats
```

The correct database SQL file should be imported.

---

## Page Not Found

Check that the project is inside:

```text
C:\xampp\htdocs\chologhuri\
```

and not inside an incorrectly nested folder such as:

```text
C:\xampp\htdocs\chologhuri\chologhuri\
```

---

# 📊 System Architecture

The project follows a simple web application architecture:

```text
                USER
                 │
                 ▼
          HTML / PHP Pages
                 │
                 ▼
             CSS / JS
                 │
                 ▼
            PHP Backend
                 │
                 ▼
             PDO Layer
                 │
                 ▼
              MySQL
```

Admin:

```text
ADMIN
  │
  ▼
Admin PHP Pages
  │
  ▼
PHP Backend
  │
  ▼
PDO
  │
  ▼
MySQL
```

---

# 📦 Sample Tour Packages

The database can contain packages such as:

### Cox's Bazar Beach Escape

```text
Destination: Cox's Bazar
Price: ৳5,500
Seats: 30
```

### Sajek Valley Adventure

```text
Destination: Sajek
Price: ৳4,200
Seats: 25
```

### Sylhet Nature Tour

```text
Destination: Sylhet
Price: ৳4,800
Seats: 30
```

### Sundarbans Explorer

```text
Destination: Sundarbans
Price: ৳6,200
Seats: 20
```

---

# 🎟 Sample Coupons

### WELCOME10

```text
Type: Percentage
Discount: 10%
```

### TRAVEL500

```text
Type: Fixed
Discount: ৳500
```

---

# 🔄 Important Data Flow

## Registration

```text
register.php
     ↓
users table
```

## Login

```text
login.php
     ↓
users table
     ↓
Session
```

## Package

```text
packages.php
     ↓
tour_packages
```

## Booking

```text
booking.php
     ↓
tour_packages
     ↓
coupons
     ↓
bookings
     ↓
payments
```

## Review

```text
my-bookings.php
     ↓
reviews
     ↓
package-details.php
```

## Wishlist

```text
package-details.php
     ↓
wishlist.php
     ↓
wishlist table
```

## Payment

```text
payment.php
     ↓
payments
     ↓
bookings
```

---

# 🧩 File Responsibility Summary

| File                      | Main Responsibility       |
| ------------------------- | ------------------------- |
| `index.php`               | Homepage                  |
| `packages.php`            | Package search/filter     |
| `package-details.php`     | Package details           |
| `wishlist.php`            | Wishlist management       |
| `booking.php`             | Booking creation          |
| `payment.php`             | Payment processing        |
| `profile.php`             | User profile              |
| `login.php`               | User login                |
| `register.php`            | User registration         |
| `logout.php`              | User logout               |
| `my-bookings.php`         | Booking history/reviews   |
| `includes/db.php`         | Database + sessions       |
| `includes/header.php`     | Website header            |
| `includes/footer.php`     | Website footer            |
| `css/style.css`           | Complete website styling  |
| `js/script.js`            | Client-side functionality |
| `database/chologhuri.sql` | Database structure        |
| `admin/index.php`         | Admin dashboard           |
| `admin/packages.php`      | Package management        |
| `admin/bookings.php`      | Booking management        |
| `admin/users.php`         | User management           |
| `admin/reviews.php`       | Review management         |
| `admin/coupons.php`       | Coupon management         |
| `admin/payments.php`      | Payment management        |
| `admin/login.php`         | Admin authentication      |
| `admin/create-admin.php`  | Initial admin setup       |

---

# 🎯 Project Objective

The main objective of CholoGhuri is to develop a centralized digital platform for tour and travel management.

The system reduces manual booking processes and provides an organized environment for:

```text
Customers
     +
Tour Packages
     +
Bookings
     +
Payments
     +
Reviews
     +
Coupons
     +
Administration
```

---

# 🚀 Future Improvements

Possible future improvements include:

* Real bKash payment gateway
* Real Nagad payment gateway
* SSL/HTTPS deployment
* Email booking confirmation
* SMS notifications
* Google Maps integration
* Online invoice generation
* PDF booking tickets
* Advanced ana
