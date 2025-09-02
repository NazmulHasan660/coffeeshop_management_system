CREATE DATABASE coffee_shop;
USE coffee_shop;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100),
    role ENUM('admin', 'user'),
    reset_token VARCHAR(100),
    token_expiry DATETIME
);

CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    description TEXT,
    price DECIMAL(8,2),
    category VARCHAR(50),
    stock INT DEFAULT 0
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2),
    tax DECIMAL(5,2),
    discount DECIMAL(5,2),
    final_amount DECIMAL(10,2),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    menu_item_id INT,
    quantity INT,
    price DECIMAL(8,2),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- Sample admin user with password 'admin123' hashed using PHP password_hash()
INSERT INTO users(username, password, email, role) VALUES 
('admin', '$2y$10$examplehashedpassword', 'admin@coffee.com', 'admin'),
('user1', '$2y$10$examplehashedpassword', 'user1@coffee.com', 'user');

-- Sample menu items
INSERT INTO menu_items(name, description, price, category, stock) VALUES
('Espresso', 'Strong black coffee', 2.50, 'Coffee', 50),
('Latte', 'Coffee with milk', 3.50, 'Coffee', 40),
('Blueberry Muffin', 'Fresh muffin with blueberries', 2.00, 'Pastry', 30);
