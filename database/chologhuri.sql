CREATE DATABASE IF NOT EXISTS chologhuri
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE chologhuri;

-- =========================================
-- USERS
-- =========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- TOUR PACKAGES
-- =========================================
CREATE TABLE IF NOT EXISTS tour_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    description TEXT,
    total_seats INT NOT NULL DEFAULT 20,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- BOOKINGS
-- =========================================
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    travel_date DATE NOT NULL,
    people INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    final_price DECIMAL(10,2) NOT NULL,
    coupon_code VARCHAR(50) DEFAULT NULL,
    status ENUM('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (package_id)
        REFERENCES tour_packages(id)
        ON DELETE CASCADE
);


-- =========================================
-- REVIEWS & RATINGS
-- =========================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (package_id)
        REFERENCES tour_packages(id)
        ON DELETE CASCADE,

    CONSTRAINT rating_range
        CHECK (rating >= 1 AND rating <= 5)
);


-- =========================================
-- WISHLIST
-- =========================================
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_wishlist (user_id, package_id),

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (package_id)
        REFERENCES tour_packages(id)
        ON DELETE CASCADE
);


-- =========================================
-- COUPONS
-- =========================================
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(10,2) NOT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT NOT NULL DEFAULT 0,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- COUPON USAGE
-- =========================================
CREATE TABLE IF NOT EXISTS coupon_usages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE CASCADE,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON DELETE CASCADE
);


-- =========================================
-- PAYMENTS
-- =========================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    method ENUM('Cash','bKash','Nagad','Card','Bank Transfer') DEFAULT 'Cash',
    status ENUM('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
    transaction_id VARCHAR(100) DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON DELETE CASCADE
);


-- =========================================
-- ADMIN USERS
-- =========================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) DEFAULT 'Administrator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- SAMPLE ADMIN
-- =========================================
INSERT INTO admin_users (username, password, name)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEaY4K9XhQ8P5gXg1XQjQY9s5W',
    'CholoGhuri Admin'
)
ON DUPLICATE KEY UPDATE username = username;


-- =========================================
-- TOUR PACKAGES
-- =========================================
INSERT INTO tour_packages
(
    title,
    destination,
    duration,
    price,
    image,
    description,
    total_seats
)
SELECT
    'Cox''s Bazar Beach Escape',
    'Cox''s Bazar',
    '3 Days / 2 Nights',
    5500,
    'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?auto=format&fit=crop&w=1200&q=80',
    'Relax beside the sea, enjoy the beach and explore local attractions.',
    30
WHERE NOT EXISTS (
    SELECT 1
    FROM tour_packages
    WHERE title = 'Cox''s Bazar Beach Escape'
);


INSERT INTO tour_packages
(
    title,
    destination,
    duration,
    price,
    image,
    description,
    total_seats
)
SELECT
    'Sajek Valley Adventure',
    'Sajek',
    '2 Days / 1 Night',
    4200,
    'https://images.unsplash.com/photo-1596895111956-bf1cf0599ce5?auto=format&fit=crop&w=1200&q=80',
    'A refreshing hill trip with clouds, green landscapes and beautiful viewpoints.',
    25
WHERE NOT EXISTS (
    SELECT 1
    FROM tour_packages
    WHERE title = 'Sajek Valley Adventure'
);


INSERT INTO tour_packages
(
    title,
    destination,
    duration,
    price,
    image,
    description,
    total_seats
)
SELECT
    'Sylhet Nature Tour',
    'Sylhet',
    '3 Days / 2 Nights',
    4800,
    'https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?auto=format&fit=crop&w=1200&q=80',
    'Discover tea gardens, waterfalls and peaceful natural scenery.',
    30
WHERE NOT EXISTS (
    SELECT 1
    FROM tour_packages
    WHERE title = 'Sylhet Nature Tour'
);


INSERT INTO tour_packages
(
    title,
    destination,
    duration,
    price,
    image,
    description,
    total_seats
)
SELECT
    'Sundarbans Explorer',
    'Khulna',
    '3 Days / 2 Nights',
    6200,
    'https://images.unsplash.com/photo-1531058020387-3be344556be6?auto=format&fit=crop&w=1200&q=80',
    'Explore the world famous mangrove forest with an unforgettable boat journey.',
    20
WHERE NOT EXISTS (
    SELECT 1
    FROM tour_packages
    WHERE title = 'Sundarbans Explorer'
);


-- =========================================
-- SAMPLE COUPONS
-- =========================================
INSERT INTO coupons
(
    code,
    discount_type,
    discount_value,
    usage_limit,
    start_date,
    end_date,
    status
)
VALUES
(
    'WELCOME10',
    'percent',
    10,
    100,
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 365 DAY),
    'active'
),
(
    'TRAVEL500',
    'fixed',
    500,
    50,
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 365 DAY),
    'active'
)
ON DUPLICATE KEY UPDATE code = VALUES(code);
