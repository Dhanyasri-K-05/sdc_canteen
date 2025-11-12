# Food Ordering System - Complete Setup Guide

A comprehensive PHP-based food ordering system with role-based access control, time-based menu display, payment integration, and QR code bill generation.

## 🚀 Features

### 👤 User Features
- Browse time-based food menu (breakfast, lunch, snacks, beverages)
- Add items to cart and place orders
- Wallet and online payment options (Razorpay integration)
- Digital receipts with QR codes
- Order history and transaction tracking

### 💰 Cashier Features
- Add, modify, and remove food items (requires admin approval)
- View stock details and sales reports
- Generate and download reports in CSV format
- Submit requests to admin for item changes

### 👑 Admin Features
- Approve or reject cashier requests
- Generate comprehensive reports (daily, weekly, monthly, yearly)
- User management and role assignment
- System-wide analytics and oversight

## 📋 Prerequisites

- **PHP 7.4 or higher**
- **MySQL 5.7 or higher**
- **Apache/Nginx web server**
- **Internet connection** (for QR code generation and Razorpay)

## 🛠️ Installation Steps

### Step 1: Environment Setup

#### For Windows (XAMPP)
1. Download and install XAMPP from https://www.apachefriends.org/
2. Start Apache and MySQL services from XAMPP Control Panel
3. Open phpMyAdmin (http://localhost/phpmyadmin)

#### For macOS (MAMP)
1. Download and install MAMP from https://www.mamp.info/
2. Start MAMP servers
3. Access phpMyAdmin through MAMP interface

#### For Linux
\`\`\`bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-curl php-json
sudo systemctl start apache2
sudo systemctl start mysql
\`\`\`

### Step 2: Database Setup
1. Open phpMyAdmin or MySQL command line
2. Create a new database named `food_ordering_system`
3. Import the SQL schema from `database/schema.sql`

**Via phpMyAdmin:**
- Click "Import" tab
- Choose `database/schema.sql` file
- Click "Go"

**Via MySQL Command Line:**
\`\`\`sql
mysql -u root -p
CREATE DATABASE food_ordering_system;
USE food_ordering_system;
SOURCE /path/to/your/project/database/schema.sql;
\`\`\`

### Step 3: Project Setup
1. Download/clone the project files
2. Place the project folder in your web server directory:
   - **XAMPP:** `C:\xampp\htdocs\food-ordering-system\`
   - **MAMP:** `/Applications/MAMP/htdocs/food-ordering-system/`
   - **Linux:** `/var/www/html/food-ordering-system/`

### Step 4: Configuration
1. Update database configuration in `config/database.php`:
\`\`\`php
private $host = "localhost";
private $db_name = "food_ordering_system";
private $username = "root";  // Your MySQL username
private $password = "";      // Your MySQL password (leave empty for XAMPP)
\`\`\`

### Step 5: File Permissions (Linux/macOS)
\`\`\`bash
chmod -R 755 /path/to/project/
chmod -R 777 /path/to/project/assets/qr_codes/
mkdir -p /path/to/project/assets/qr_codes/
\`\`\`

### Step 6: Razorpay Integration Setup

#### Get Razorpay API Keys
1. Sign up at https://razorpay.com/
2. Go to Dashboard → Settings → API Keys
3. Generate Test/Live API keys

#### Update Configuration
Edit `user/create_razorpay_order.php`:
\`\`\`php
// Replace with your actual Razorpay keys
$razorpay_key_id = "rzp_test_YOUR_KEY_ID";
$razorpay_key_secret = "YOUR_SECRET_KEY";
\`\`\`

**Important:** Use test keys for development and live keys for production.

### Step 7: Test the Installation
1. Open your web browser
2. Navigate to: `http://localhost/food-ordering-system/`
3. You should see the welcome page

## 🔐 Demo Accounts

The system comes with pre-configured demo accounts:

### Admin Account
- **Username:** `admin`
- **Password:** `password123`
- **Access:** Full system control, approve requests, generate reports

### Cashier Account
- **Username:** `cashier`
- **Password:** `password123`
- **Access:** Manage food items, view stock reports

### User Account
- **Username:** `user`
- **Password:** `password123`
- **Access:** Order food, manage wallet, view order history

## 📁 Project Structure

\`\`\`
food-ordering-system/
├── admin/
│   ├── dashboard.php          # Admin main dashboard
│   ├── reports.php           # Generate and download reports
│   └── manage_users.php      # User management
├── assets/
│   ├── css/
│   │   └── style.css         # Custom styling
│   └── qr_codes/            # Generated QR codes
├── cashier/
│   ├── dashboard.php         # Cashier main dashboard
│   └── stock_report.php     # Stock and sales reports
├── classes/
│   ├── User.php             # User management class
│   ├── FoodItem.php         # Food item operations
│   ├── Order.php            # Order processing
│   └── QRCodeGenerator.php  # QR code generation
├── config/
│   ├── database.php         # Database connection
│   └── session.php          # Session management
├── database/
│   └── schema.sql           # Database structure and sample data
├── user/
│   ├── dashboard.php        # User main dashboard
│   ├── wallet.php           # Wallet management
│   ├── process_payment.php  # Payment processing
│   ├── create_razorpay_order.php  # Razorpay integration
│   ├── verify_razorpay_payment.php # Payment verification
│   └── order_success.php    # Order confirmation
├── index.php                # Landing page
├── login.php               # User authentication
├── register.php            # User registration
├── logout.php              # Session termination
├── unauthorized.php        # Access denied page
└── README.md              # This file
\`\`\`

## ⏰ Time-Based Menu System

The system automatically displays different food categories based on current time:

- **Breakfast:** 06:00 - 11:00
- **Lunch:** 12:00 - 16:00
- **Snacks:** 16:00 - 19:00
- **Beverages:** 06:00 - 22:00 (Available all day)

## 💳 Payment Integration

### Wallet Payment
- Users can add money to their digital wallet
- Instant deduction for orders
- Complete transaction history

### Online Payment (Razorpay)
- Credit/Debit cards
- UPI payments
- Net banking
- Digital wallets

### Payment Flow
1. User selects items and proceeds to checkout
2. Chooses payment method (Wallet or Razorpay)
3. For wallet: Instant deduction if sufficient balance
4. For Razorpay: Redirected to secure payment gateway
5. Payment verification and order confirmation
6. Digital receipt with QR code generated

## 📊 QR Code Bills

Each successful order generates a QR code containing:
- Bill number
- Total amount
- Date and time
- Customer information
- Order details

## 🔧 Troubleshooting

### Common Issues

#### 1. Database Connection Error
\`\`\`
Error: Connection failed: Access denied for user 'root'@'localhost'
\`\`\`
**Solution:**
- Check MySQL service is running
- Verify database credentials in `config/database.php`
- Ensure database `food_ordering_system` exists

#### 2. Permission Denied Errors
\`\`\`
Warning: mkdir(): Permission denied
\`\`\`
**Solution:**
\`\`\`bash
chmod -R 755 /path/to/project/
chmod -R 777 /path/to/project/assets/qr_codes/
\`\`\`

#### 3. QR Code Generation Issues
\`\`\`
QR Code generation failed
\`\`\`
**Solution:**
- Ensure internet connection (uses Google Charts API)
- Check if `assets/qr_codes/` directory exists and is writable
- Verify file permissions

#### 4. Razorpay Integration Issues
\`\`\`
Payment failed: Invalid key
\`\`\`
**Solution:**
- Verify Razorpay API keys are correct
- Check if test mode is enabled for development
- Ensure webhook URLs are configured properly

#### 5. Session Issues
\`\`\`
Headers already sent
\`\`\`
**Solution:**
- Check for any output before `<?php` tags
- Ensure no spaces before opening PHP tags
- Verify session_start() is called before any output

### Debug Mode
To enable debug mode, add this to the top of any PHP file:
\`\`\`php
error_reporting(E_ALL);
ini_set('display_errors', 1);
\`\`\`

## 🔒 Security Features

- **Password Hashing:** Uses PHP's `password_hash()` with bcrypt
- **SQL Injection Prevention:** Prepared statements throughout
- **Session Security:** Secure session management
- **Role-Based Access:** Strict permission controls
- **Input Validation:** Server-side validation for all inputs
- **CSRF Protection:** Form tokens for sensitive operations

## 📈 Performance Optimization

- **Database Indexing:** Optimized queries with proper indexes
- **Session Management:** Efficient session handling
- **File Caching:** QR codes cached to reduce generation time
- **Responsive Design:** Mobile-optimized interface

## 🚀 Deployment to Production

### 1. Server Requirements
- PHP 7.4+ with required extensions
- MySQL 5.7+ or MariaDB
- SSL certificate for HTTPS
- Sufficient disk space for QR codes and logs

### 2. Configuration Changes
\`\`\`php
// config/database.php - Update for production
private $host = "your-production-host";
private $db_name = "your-production-db";
private $username = "your-production-user";
private $password = "your-secure-password";
\`\`\`

### 3. Razorpay Live Keys
\`\`\`php
// user/create_razorpay_order.php
$razorpay_key_id = "rzp_live_YOUR_LIVE_KEY";
$razorpay_key_secret = "YOUR_LIVE_SECRET";
\`\`\`

### 4. Security Hardening
- Remove demo accounts
- Change default passwords
- Enable HTTPS
- Configure proper file permissions
- Set up regular backups

## 📞 Support

For technical support:
1. Check error logs in your web server
2. Verify all file paths and permissions
3. Ensure all required PHP extensions are installed
4. Test with demo accounts first

## 📄 License

This project is open source and available under the MIT License.

---

**🎉 Your Food Ordering System is now ready to use!**

Visit `http://localhost/food-ordering-system/` to get started.
