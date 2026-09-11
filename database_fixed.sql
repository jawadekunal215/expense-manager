-- ============================================
-- EXPENSE MANAGER - DATABASE SCHEMA
-- Theme: Industry-Oriented Web App (Agile)
-- Project By: [Your Name] | Internship Day 5
-- ============================================

CREATE DATABASE IF NOT EXISTS expense_manager;
USE expense_manager;

-- -----------------------------------------------
-- TABLE: users
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_pic VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
    monthly_budget DECIMAL(12,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'INR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- TABLE: categories
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-tag',
    color VARCHAR(20) DEFAULT '#6366f1',
    type ENUM('income','expense') DEFAULT 'expense',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- TABLE: transactions
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT,
    title VARCHAR(200) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('income','expense') NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    payment_method ENUM('cash','card','upi','bank_transfer','other') DEFAULT 'cash',
    receipt_img VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- -----------------------------------------------
-- TABLE: budgets
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT,
    title VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    spent DECIMAL(12,2) DEFAULT 0.00,
    month INT NOT NULL,
    year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- -----------------------------------------------
-- TABLE: savings_goals
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS savings_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    saved_amount DECIMAL(12,2) DEFAULT 0.00,
    deadline DATE,
    icon VARCHAR(50) DEFAULT 'fa-piggy-bank',
    color VARCHAR(20) DEFAULT '#10b981',
    status ENUM('active','achieved','cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- TABLE: notifications
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','warning','success','danger') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- INSERT: Default Categories (for demo user)
-- -----------------------------------------------
INSERT INTO users (full_name, username, email, password, monthly_budget, currency)
VALUES ('Demo User', 'demo', 'demo@expensemanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 30000.00, 'INR');
-- Default password: password

INSERT INTO categories (user_id, name, icon, color, type) VALUES
(1, 'Food & Dining', 'fa-utensils', '#f59e0b', 'expense'),
(1, 'Transportation', 'fa-car', '#3b82f6', 'expense'),
(1, 'Shopping', 'fa-shopping-bag', '#ec4899', 'expense'),
(1, 'Entertainment', 'fa-film', '#8b5cf6', 'expense'),
(1, 'Health & Medical', 'fa-heartbeat', '#ef4444', 'expense'),
(1, 'Education', 'fa-graduation-cap', '#06b6d4', 'expense'),
(1, 'Bills & Utilities', 'fa-file-invoice', '#f97316', 'expense'),
(1, 'Rent', 'fa-home', '#64748b', 'expense'),
(1, 'Salary', 'fa-briefcase', '#10b981', 'income'),
(1, 'Freelance', 'fa-laptop', '#6366f1', 'income'),
(1, 'Business', 'fa-chart-line', '#14b8a6', 'income'),
(1, 'Investments', 'fa-coins', '#f59e0b', 'income'),
(1, 'Gift', 'fa-gift', '#ec4899', 'income'),
(1, 'Other Income', 'fa-plus-circle', '#84cc16', 'income'),
(1, 'Other Expense', 'fa-minus-circle', '#94a3b8', 'expense');

-- Sample Transactions
INSERT INTO transactions (user_id, category_id, title, amount, type, description, date, payment_method) VALUES
(1, 1, 'Monthly Groceries', 3500.00, 'expense', 'Grocery shopping at D-Mart', '2025-06-01', 'card'),
(1, 2, 'Uber Rides', 800.00, 'expense', 'Office commute for the week', '2025-06-02', 'upi'),
(1, 9, 'Monthly Salary', 45000.00, 'income', 'June salary credited', '2025-06-01', 'bank_transfer'),
(1, 7, 'Electricity Bill', 1200.00, 'expense', 'June electricity bill', '2025-06-05', 'upi'),
(1, 8, 'House Rent', 8000.00, 'expense', 'Monthly rent payment', '2025-06-01', 'bank_transfer'),
(1, 4, 'Netflix Subscription', 649.00, 'expense', 'Monthly streaming plan', '2025-06-03', 'card'),
(1, 3, 'Online Shopping', 2100.00, 'expense', 'Amazon purchase', '2025-06-07', 'card'),
(1, 10, 'Freelance Project', 12000.00, 'income', 'Web design project payment', '2025-06-10', 'upi'),
(1, 5, 'Doctor Visit', 500.00, 'expense', 'General checkup', '2025-06-12', 'cash'),
(1, 6, 'Online Course', 1999.00, 'expense', 'Udemy PHP course', '2025-06-15', 'card');

-- Sample Budgets
INSERT INTO budgets (user_id, category_id, title, amount, spent, month, year) VALUES
(1, 1, 'Food Budget', 5000.00, 3500.00, 6, 2025),
(1, 2, 'Transport Budget', 2000.00, 800.00, 6, 2025),
(1, 3, 'Shopping Budget', 3000.00, 2100.00, 6, 2025),
(1, 7, 'Bills Budget', 3000.00, 1200.00, 6, 2025);

-- Sample Savings Goals
INSERT INTO savings_goals (user_id, title, target_amount, saved_amount, deadline, icon, color) VALUES
(1, 'Emergency Fund', 50000.00, 15000.00, '2025-12-31', 'fa-shield-alt', '#10b981'),
(1, 'Laptop Upgrade', 80000.00, 25000.00, '2025-09-30', 'fa-laptop', '#6366f1'),
(1, 'Vacation Trip', 30000.00, 8000.00, '2025-11-30', 'fa-plane', '#f59e0b');
