-- Event Booking System Database Schema
CREATE DATABASE event_booking_system;
USE event_booking_system;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Venues table
CREATE TABLE venues (
    venue_id INT AUTO_INCREMENT PRIMARY KEY,
    venue_name VARCHAR(100) NOT NULL,
    city VARCHAR(50) NOT NULL,
    address TEXT,
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(200) NOT NULL,
    event_description TEXT,
    venue_id INT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    ticket_price DECIMAL(10,2) NOT NULL,
    available_tickets INT NOT NULL,
    total_tickets INT NOT NULL,
    event_image VARCHAR(255),
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venues(venue_id)
);

-- Bookings table
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_id INT,
    number_of_tickets INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    booking_status ENUM('confirmed', 'cancelled', 'pending') DEFAULT 'confirmed',
    payment_status ENUM('paid', 'pending', 'failed') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id)
);

-- Insert sample data
-- Insert venues
INSERT INTO venues (venue_name, city, address, capacity) VALUES
('National Stadium', 'Karachi', 'Stadium Road, Karachi', 34000),
('Gaddafi Stadium', 'Lahore', 'Ferozpur Road, Lahore', 27000),
('Pindi Cricket Stadium', 'Rawalpindi', 'Committee Chowk, Rawalpindi', 15000),
('Multan Cricket Stadium', 'Multan', 'Vehari Road, Multan', 35000),
('National Bank Stadium', 'Karachi', 'PECHS, Karachi', 20000);

-- Insert sample PSL events
INSERT INTO events (event_name, event_description, venue_id, event_date, event_time, ticket_price, available_tickets, total_tickets, event_image, status) VALUES
('PSL 2024 - Karachi Kings vs Lahore Qalandars', 'Exciting T20 match between Karachi Kings and Lahore Qalandars in PSL Season 9', 1, '2024-06-15', '19:00:00', 1500.00, 25000, 30000, 'kk_vs_lq.jpg', 'active'),
('PSL 2024 - Islamabad United vs Peshawar Zalmi', 'Thrilling encounter between Islamabad United and Peshawar Zalmi', 2, '2024-06-18', '19:30:00', 1200.00, 20000, 25000, 'iu_vs_pz.jpg', 'active'),
('PSL 2024 - Quetta Gladiators vs Multan Sultans', 'High-voltage match between Quetta Gladiators and Multan Sultans', 4, '2024-06-20', '20:00:00', 1800.00, 30000, 32000, 'qg_vs_ms.jpg', 'active'),
('PSL 2024 Final', 'The ultimate showdown - PSL 2024 Final Match', 1, '2024-06-25', '20:00:00', 3000.00, 15000, 34000, 'psl_final.jpg', 'active'),
('PSL 2024 Opening Ceremony', 'Grand opening ceremony of PSL Season 9 with musical performances', 2, '2024-06-10', '18:00:00', 2000.00, 18000, 27000, 'opening_ceremony.jpg', 'active');

-- Insert sample users
INSERT INTO users (username, email, password, full_name, phone) VALUES
('ahmed_ali', 'ahmed.ali@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmed Ali', '03001234567'),
('fatima_khan', 'fatima.khan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fatima Khan', '03009876543'),
('hassan_shah', 'hassan.shah@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hassan Shah', '03005551234');