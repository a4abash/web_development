<?php
define('ABSPATH', __DIR__);
require_once(__DIR__ . '/db.php');

// create role table
$rolesTable = "CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
)";

// create permissions table
$permissionsTable = "CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
)";

// create users table
$usersTable = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL DEFAULT 3,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
)";

// create products table
$productsTable = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    category VARCHAR(100),
    image VARCHAR(255),
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
)";
// create contacts table
$contactTable = "CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// insert default roles
$insertRoles = "INSERT IGNORE INTO roles (role, description) VALUES
('admin', 'Full access to the system'),
('member', 'Registered member with limited access'),
('user', 'Normal user with basic access')";

$insertAdminUser = "INSERT IGNORE INTO users (username, name, email, role_id, password) VALUES
('admin', 'Administrator', 'admin@gmail.com', 1, '" . password_hash('admin123', PASSWORD_DEFAULT) . "')";

// Run queries
if ($conn->query($rolesTable) === TRUE) {
    echo "Table 'roles' created successfully.<br>";
} else {
    echo "Error creating roles table: " . $conn->error . "<br>";
}

if ($conn->query($usersTable) === TRUE) {
    echo "Table 'users' created successfully.<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

if ($conn->query($contactTable) === TRUE) {
    echo "Table 'contacts' created successfully.<br>";
} else {
    echo "Error creating contacts table: " . $conn->error . "<br>";
}

if ($conn->query($productsTable) === TRUE) {
    echo "Table 'products' created successfully.<br>";
} else {
    echo "Error creating products table: " . $conn->error . "<br>";
}

if ($conn->query($insertRoles) === TRUE) {
    echo "Roles Admin,Member and User created.<br>";
} else {
    echo "Error adding roles: " . $conn->error . "<br>";
}

if ($conn->query($insertAdminUser) === TRUE) {
    echo "Admin User Created.<br>";
} else {
    echo "Error adding admin user: " . $conn->error . "<br>";
}

$conn->close();
?>
