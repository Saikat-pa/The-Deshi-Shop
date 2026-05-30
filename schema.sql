-- Product Catalog System Database Schema
-- Compatible with MySQL 5.7+ and MySQL 8.0+

CREATE DATABASE IF NOT EXISTS `product_catalog` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `product_catalog`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `icon` VARCHAR(50) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `reviews`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `reviewer_name` VARCHAR(100) NOT NULL,
  `rating` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `cart_items`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `orders`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` TEXT DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `order_items`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert a default demo admin user
-- Username: admin, Password: adminpassword123 (hashed using password_hash with BCRYPT)
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$T2rUqDkWp95VlXhF4N4Jre.pL856jUWhc7m0yQh0HhQ59N.C736N.', 'admin')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- --------------------------------------------------------
-- Insert default categories
-- --------------------------------------------------------
INSERT INTO `categories` (`name`, `icon`, `sort_order`) VALUES
('Smartphones', 'smartphone', 1),
('Laptops', 'laptop', 2),
('Audio', 'headphones', 3),
('Wearables', 'watch', 4),
('Accessories', 'mouse', 5),
('Gaming', 'gamepad', 6),
('Cameras', 'camera', 7),
('Drones', 'plane', 8)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- --------------------------------------------------------
-- Insert mock products (prices in BDT)
-- --------------------------------------------------------
INSERT INTO `products` (`name`, `description`, `price`, `category`, `image_url`, `created_by`) VALUES
('Quantum Wireless Mouse', 'Ergonomic wireless mouse with 16000 DPI sensor, silent clicks, and 80-hour battery life. 2.4GHz USB receiver included.', 1250.00, 'Accessories', 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=500&auto=format&fit=crop&q=60', 1),
('Aura Mechanical Keyboard', 'Compact 75% mechanical keyboard featuring hot-swappable switches, RGB backlighting, and premium PBT keycaps.', 4500.00, 'Gaming', 'https://images.unsplash.com/photo-1618384887929-16ec33fab9ef?w=500&auto=format&fit=crop&q=60', 1),
('Sony WH-1000XM5 Headphones', 'Industry-leading noise canceling wireless headphones with 30-hour battery life and premium sound quality.', 28999.00, 'Audio', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop&q=60', 1),
('Apple Watch Ultra 2', 'Rugged smartwatch with advanced fitness tracking, GPS, and titanium case designed for extreme adventures.', 79900.00, 'Wearables', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&auto=format&fit=crop&q=60', 1),
('MacBook Air M3', 'Ultra-thin laptop powered by Apple M3 chip with 18-hour battery life and stunning Liquid Retina display.', 135000.00, 'Laptops', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&auto=format&fit=crop&q=60', 1),
('DJI Mini 4 Pro Drone', 'Lightweight 4K camera drone with omnidirectional obstacle sensing and 34-minute flight time.', 62500.00, 'Drones', 'https://images.unsplash.com/photo-1473968512647-3e447244af8f?w=500&auto=format&fit=crop&q=60', 1),
('Samsung Galaxy S24 Ultra', 'AI-powered flagship smartphone with 200MP camera, S Pen, and titanium frame. 6.8" Dynamic AMOLED display.', 129999.00, 'Smartphones', 'https://images.unsplash.com/photo-1610945415295-d9bbf0673842?w=500&auto=format&fit=crop&q=60', 1),
('JBL Tune 230NC TWS', 'Active noise cancelling true wireless earbuds with 40-hour battery life and IPX4 water resistance.', 4500.00, 'Audio', 'https://images.unsplash.com/photo-1590658268037-6bf12f2929f4?w=500&auto=format&fit=crop&q=60', 1),
('Razer DeathAdder V3', 'Ultra-lightweight ergonomic gaming mouse with 30K DPI optical sensor and 63g weight for competitive gaming.', 5800.00, 'Gaming', 'https://images.unsplash.com/photo-1527814050087-379x1e9387a8?w=500&auto=format&fit=crop&q=60', 1),
('Canon EOS R50 Camera', 'Compact mirrorless camera with 24.2MP APS-C sensor, 4K video, and intelligent autofocus for creators.', 78000.00, 'Cameras', 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=500&auto=format&fit=crop&q=60', 1),
('Xiaomi Smart Band 8', 'Slim fitness tracker with AMOLED display, 150+ workout modes, and 16-day battery life at an unbeatable price.', 2999.00, 'Wearables', 'https://images.unsplash.com/photo-1575311373937-3b8e9277b3d1?w=500&auto=format&fit=crop&q=60', 1),
('ASUS ROG Strix G16 Laptop', 'Gaming laptop with Intel i9-13980HX, RTX 4070, 16" 240Hz display, and advanced cooling system.', 185000.00, 'Laptops', 'https://images.unsplash.com/photo-1593640408713-7c82e3e70368?w=500&auto=format&fit=crop&q=60', 1),
('Baseus 65W GaN Charger', 'Compact 65W GaN fast charger with 3 ports (2 USB-C + 1 USB-A). Charges laptop, phone, and earbuds simultaneously.', 2200.00, 'Accessories', 'https://images.unsplash.com/photo-1583863788434-e58a3003b1dd?w=500&auto=format&fit=crop&q=60', 1)
ON DUPLICATE KEY UPDATE `id`=`id`;
