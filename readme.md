Greenleaf Web Application
Greenleaf is a full-stack web application designed for plant enthusiasts, professional gardeners, and nursery owners. It provides a platform for browsing plants, accessing gardening tips, managing a personal garden, and placing orders. This README provides instructions to set up and run the application locally.
Table of Contents

Prerequisites
Installation
Database Setup
Configuration
Running the Application
Project Structure
User Credentials
Troubleshooting
Deliverables

Prerequisites
Ensure you have the following installed:

PHP (>= 7.4, recommended 8.x)
MySQL (>= 8.0)
Web Server (e.g., Apache via XAMPP/WAMP or Nginx)
Composer (for PHP dependency management, if using Laravel)
Node.js and npm (optional, if using JavaScript frameworks like ReactJS)
Browser (Chrome, Firefox, Edge, or Safari)
Hardware: Intel Core i5 or higher, 8GB RAM, 500GB storage

Optional local hosting environments:

XAMPP or WAMP for Apache and MySQL setup

Installation

Project is not uploaded to github so u have to manually open it no cloning


Alternatively, download and extract the project ZIP file.


Open the Project Folder:

Navigate to the project directory:cd greenleaf-app




Install PHP Dependencies (if using Laravel or other PHP frameworks):

Run the following command in the project root to install dependencies:composer install


Database Setup

Create the Database:

Open your MySQL client (e.g., phpMyAdmin, MySQL Workbench, or command line).
Create a new database named greenleaf:CREATE DATABASE greenleaf;




Import the Database Schema:

Locate the database.sql file in the project root or database folder.
Import the schema into the greenleaf database using:
phpMyAdmin: Use the "Import" tab to upload database.sql.
Command Line:mysql -u <username> -p greenleaf < database.sql


This will create tables for users, plants, orders, cart, blogs, reminders, and saved_blogs with appropriate relationships.





Configuration

Update Database Configuration:
Open config.php (typically in the config directory or project root) and update the database credentials:<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');
define('DB_NAME', 'greenleaf');
?>


Open config/database.php and ensure the database connection settings match:<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'greenleaf';
    private $username = 'your_mysql_username';
    private $password = 'your_mysql_password';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            $this->conn->set_charset('utf8mb4');
        } catch (Exception $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }
        return $this->conn;
    }
}
?>


Replace your_mysql_username


