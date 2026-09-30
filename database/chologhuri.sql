CREATE DATABASE IF NOT EXISTS chologhuri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chologhuri;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30),
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tour_packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  destination VARCHAR(100) NOT NULL,
  duration VARCHAR(50) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255),
  description TEXT,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  package_id INT NOT NULL,
  travel_date DATE NOT NULL,
  people INT NOT NULL DEFAULT 1,
  total_price DECIMAL(10,2) NOT NULL,
  status ENUM('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  package_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE CASCADE
);

INSERT INTO tour_packages (title,destination,duration,price,image,description) VALUES
('Cox\'s Bazar Beach Escape','Cox\'s Bazar','3 Days / 2 Nights',5500,'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?auto=format&fit=crop&w=1200&q=80','Relax beside the sea, enjoy the beach and explore local attractions.'),
('Sajek Valley Adventure','Sajek','2 Days / 1 Night',4200,'https://images.unsplash.com/photo-1596895111956-bf1cf0599ce5?auto=format&fit=crop&w=1200&q=80','A refreshing hill trip with clouds, green landscapes and beautiful viewpoints.'),
('Sylhet Nature Tour','Sylhet','3 Days / 2 Nights',4800,'https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?auto=format&fit=crop&w=1200&q=80','Discover tea gardens, waterfalls and peaceful natural scenery.'),
('Sundarbans Explorer','Khulna','3 Days / 2 Nights',6200,'https://images.unsplash.com/photo-1531058020387-3be344556be6?auto=format&fit=crop&w=1200&q=80','Explore the world famous mangrove forest with an unforgettable boat journey.');
