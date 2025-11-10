-- Create database
CREATE DATABASE IF NOT EXISTS food_ordering_system;
USE food_ordering_system;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_no INT UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'cashier', 'admin') DEFAULT 'user',
    wallet_balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Food items table
CREATE TABLE food_items (order_itemsusers
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('breakfast', 'lunch', 'snacks', 'beverages') NOT NULL,
    time_available VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bill_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('wallet', 'razorpay') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    razorpay_order_id VARCHAR(100),
    razorpay_payment_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE CASCADE
);

-- Cashier requests table
CREATE TABLE cashier_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cashier_id INT NOT NULL,
    request_type ENUM('add_item', 'update_item', 'delete_item') NOT NULL,
    food_item_id INT,
    request_data JSON NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Wallet transactions table
CREATE TABLE wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255),
    order_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Insert sample users with different roles
INSERT INTO users (roll_no, email, password, role, wallet_balance) VALUES 
('001', 'admin@foodsystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0.00),
('002', 'cashier@foodsystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', 0.00),
('003', 'user@foodsystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 500.00),
('004', 'john@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0.00),
('005', 'mary@cashier.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', 0.00),
('006', 'bob@user.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1000.00);

-- Insert sample food items
INSERT INTO food_items (name, description, price, category, time_available) VALUES 
('Breakfast Combo', 'Eggs, toast, and coffee', 150.00, 'breakfast', '06:00-11:00'),
('Masala Dosa', 'South Indian crispy crepe with potato filling', 120.00, 'breakfast', '06:00-11:00'),
('Idli Sambar', 'Steamed rice cakes with lentil curry', 80.00, 'breakfast', '06:00-11:00'),
('Poha', 'Flattened rice with vegetables and spices', 60.00, 'breakfast', '06:00-11:00'),

('Chicken Biryani', 'Aromatic rice with tender chicken pieces', 250.00, 'lunch', '12:00-16:00'),
('Veg Thali', 'Complete vegetarian meal with rice, dal, vegetables', 180.00, 'lunch', '12:00-16:00'),
('Paneer Butter Masala', 'Cottage cheese in rich tomato gravy with rice', 220.00, 'lunch', '12:00-16:00'),
('Dal Tadka', 'Yellow lentils with spices and rice', 140.00, 'lunch', '12:00-16:00'),

('Samosa', 'Crispy fried pastry with spiced potato filling', 30.00, 'snacks', '16:00-19:00'),
('Pakora', 'Deep fried vegetable fritters', 40.00, 'snacks', '16:00-19:00'),
('Sandwich', 'Grilled vegetable sandwich', 80.00, 'snacks', '16:00-19:00'),
('Chaat', 'Spicy street food snack', 50.00, 'snacks', '16:00-19:00'),

('Masala Tea', 'Hot Indian spiced tea', 20.00, 'beverages', '06:00-22:00'),
('Coffee', 'Fresh brewed coffee', 25.00, 'beverages', '06:00-22:00'),
('Fresh Juice', 'Seasonal fruit juice', 50.00, 'beverages', '06:00-22:00'),
('Lassi', 'Yogurt-based drink', 40.00, 'beverages', '06:00-22:00'),
('Cold Drink', 'Soft drinks and sodas', 30.00, 'beverages', '06:00-22:00');
SELECT * from users;ordersorder_itemsorder_itemsmorning_balancefood_items




-- Note: All passwords are hashed version of 'password123'
-- You can login with any of the above users using 'password123' as password
