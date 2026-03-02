-- Tomato Food Delivery Database Schema
-- Created: 2026-01-25

-- Create database
CREATE DATABASE IF NOT EXISTS tomato_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tomato_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password_hash VARCHAR(255) NOT NULL,
    address TEXT,
    locality VARCHAR(100),
    city VARCHAR(50) DEFAULT 'Kolkata',
    is_new_user BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_locality (locality)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurants table
CREATE TABLE IF NOT EXISTS restaurants (
    restaurant_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    address TEXT,
    locality VARCHAR(100),
    city VARCHAR(50) DEFAULT 'Kolkata',
    phone VARCHAR(15),
    image_url VARCHAR(255),
    rating DECIMAL(2,1) DEFAULT 0.0,
    cuisine_type VARCHAR(100),
    average_cost_for_two INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_locality (locality),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu items table
CREATE TABLE IF NOT EXISTS menu_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    is_veg BOOLEAN DEFAULT TRUE,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(restaurant_id) ON DELETE CASCADE,
    INDEX idx_restaurant (restaurant_id),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_item (user_id, item_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_address TEXT NOT NULL,
    delivery_locality VARCHAR(100),
    payment_method VARCHAR(50) DEFAULT 'razorpay',
    payment_status VARCHAR(20) DEFAULT 'pending',
    razorpay_order_id VARCHAR(100),
    razorpay_payment_id VARCHAR(100),
    order_status VARCHAR(20) DEFAULT 'placed',
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(restaurant_id),
    INDEX idx_user (user_id),
    INDEX idx_order_number (order_number),
    INDEX idx_order_status (order_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(item_id),
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Localities table for chatbot suggestions
CREATE TABLE IF NOT EXISTS localities (
    locality_id INT AUTO_INCREMENT PRIMARY KEY,
    locality_name VARCHAR(100) UNIQUE NOT NULL,
    city VARCHAR(50) DEFAULT 'Kolkata',
    popular_cuisines TEXT,
    INDEX idx_locality_name (locality_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offers table
CREATE TABLE IF NOT EXISTS offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    offer_code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type VARCHAR(20) DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00,
    max_discount DECIMAL(10,2),
    is_new_user_only BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    valid_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_until TIMESTAMP NULL,
    INDEX idx_offer_code (offer_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample localities
INSERT INTO localities (locality_name, city, popular_cuisines) VALUES
('Southern Avenue', 'Kolkata', 'North Indian, Chinese, Continental'),
('Park Street', 'Kolkata', 'Continental, Italian, Chinese'),
('Salt Lake', 'Kolkata', 'Bengali, North Indian, Chinese'),
('Ballygunge', 'Kolkata', 'Bengali, Continental, Italian'),
('Alipore', 'Kolkata', 'North Indian, Continental, Chinese'),
('Jadavpur', 'Kolkata', 'Bengali, Chinese, Fast Food'),
('Howrah', 'Kolkata', 'Bengali, North Indian, Street Food'),
('Dum Dum', 'Kolkata', 'Bengali, Chinese, Biryani');

-- Insert sample restaurants
INSERT INTO restaurants (name, description, address, locality, phone, image_url, rating, cuisine_type, average_cost_for_two) VALUES
('Retro Cafe', 'Cozy retro-themed cafe with classic vibes', 'Southern Avenue, 122A', 'Southern Avenue', '+91 8068971413', 'images/restaurants/retrocafe.jpg', 4.5, 'Cafe, Continental', 900),
('Marbella\'s', 'Fine dining with Mexican and Continental cuisine', 'Hindustan Park, Kolkata', 'Ballygunge', '+91 9876543210', 'images/restaurants/marbellas.jpg', 4.3, 'Mexican, Continental', 1500),
('Spice Kraft', 'Authentic Indian flavors', 'Rashbehari, Kolkata', 'Ballygunge', '+91 9876543211', 'images/restaurants/spicekraft.jpg', 4.2, 'North Indian', 1200),
('Peter Cat', 'Famous for Chelo Kabab', 'Park Street Area, Kolkata', 'Park Street', '+91 9876543212', 'images/restaurants/petercat.jpg', 4.6, 'Continental, North Indian', 1800),
('Roastery Coffee House', 'Cozy cafe with great coffee', 'Gariahat, Kolkata', 'Ballygunge', '+91 9876543213', 'images/restaurants/roastery.jpg', 4.1, 'Cafe, Continental', 800);

-- Insert sample menu items
INSERT INTO menu_items (restaurant_id, item_name, description, category, price, is_veg, is_available) VALUES
-- Retro Cafe menu
(1, 'Hazelnut Cappuccino', 'Rich espresso with steamed milk and hazelnut syrup', 'Beverages', 220.00, TRUE, TRUE),
(1, 'Chicken Club Sandwich', 'Triple decker sandwich with grilled chicken and coleslaw', 'Main Course', 320.00, FALSE, TRUE),
(1, 'English Breakfast', 'Eggs, sausages, beans, mushrooms and toast', 'Breakfast', 450.00, FALSE, TRUE),
(1, 'Banana Walnut Muffin', 'Freshly baked muffin with walnuts', 'Desserts', 180.00, TRUE, TRUE),

-- Marbella's menu
(2, 'Chicken Tacos', 'Soft tacos with grilled chicken', 'Main Course', 420.00, FALSE, TRUE),
(2, 'Vegetarian Burrito', 'Loaded veggie burrito with guacamole', 'Main Course', 380.00, TRUE, TRUE),
(2, 'Nachos Supreme', 'Crispy nachos with cheese and salsa', 'Starters', 320.00, TRUE, TRUE),
(2, 'Quesadilla', 'Cheese quesadilla with sour cream', 'Main Course', 350.00, TRUE, TRUE),

-- Spice Kraft menu
(3, 'Peas Pulao', 'Fragrant basmati rice with peas', 'Main Course', 180.00, TRUE, TRUE),
(3, 'Butter Chicken', 'Creamy tomato-based chicken curry', 'Main Course', 380.00, FALSE, TRUE),
(3, 'Paneer Tikka', 'Grilled cottage cheese with spices', 'Starters', 280.00, TRUE, TRUE),
(3, 'Dal Makhani', 'Rich and creamy black lentils', 'Main Course', 220.00, TRUE, TRUE),

-- Peter Cat menu
(4, 'Chelo Kabab', 'Signature dish with mutton kabab and rice', 'Main Course', 520.00, FALSE, TRUE),
(4, 'Mutton Chelo Kabab', 'Premium mutton kabab with saffron rice', 'Main Course', 580.00, FALSE, TRUE),
(4, 'Chicken Stroganoff', 'Creamy chicken in mushroom sauce', 'Main Course', 450.00, FALSE, TRUE),

-- Roastery Coffee House menu
(5, 'Cappuccino', 'Classic Italian coffee', 'Beverages', 150.00, TRUE, TRUE),
(5, 'Club Sandwich', 'Triple-decker sandwich with chicken', 'Snacks', 280.00, FALSE, TRUE),
(5, 'Chocolate Brownie', 'Warm brownie with ice cream', 'Desserts', 180.00, TRUE, TRUE);

-- Insert sample offers
INSERT INTO offers (offer_code, description, discount_type, discount_value, min_order_amount, max_discount, is_new_user_only, is_active) VALUES
('WELCOME50', 'Welcome offer for new users - 50% off', 'percentage', 50.00, 300.00, 150.00, TRUE, TRUE),
('FIRST100', 'Flat ₹100 off on first order', 'fixed', 100.00, 500.00, 100.00, TRUE, TRUE),
('SAVE20', 'Get 20% off on orders above ₹500', 'percentage', 20.00, 500.00, 200.00, FALSE, TRUE);
