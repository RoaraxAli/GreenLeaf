Greenleaf Web Application
Greenleaf is a full-stack web application designed for plant enthusiasts, professional gardeners, and nursery owners. It provides a robust platform for browsing plants, accessing gardening tips, managing personal gardens, and placing orders. This README provides comprehensive instructions for setting up, running, and contributing to the application.
Table of Contents

Features
Prerequisites
Installation
Database Setup
Configuration
Running the Application
Project Structure
User Credentials
Troubleshooting
Contributing
License

Features

Plant Browsing: Explore a catalog of plants with detailed descriptions.
Gardening Tips: Access expert advice through blog posts.
Garden Management: Manage personal garden plans and set reminders.
Order Placement: Add plants to a cart and place orders seamlessly.
User Authentication: Secure login and registration system.
Blog Saving: Save favorite blog posts for later reference.

Prerequisites
Ensure the following software and hardware requirements are met before setting up the project:
Software

PHP: Version 7.4 or higher (8.x recommended)
MySQL: Version 8.0 or higher
Web Server: Apache (via XAMPP/WAMP) or Nginx
Composer: For PHP dependency management
Node.js and npm: Optional, for JavaScript frameworks like ReactJS
Git: For cloning the repository
Browser: Chrome, Firefox, Edge, or Safari (latest versions recommended)

Hardware

Processor: Intel Core i5 or equivalent
RAM: 8GB or higher
Storage: 500GB free disk space

Optional Local Hosting Environments

XAMPP or WAMP for streamlined Apache and MySQL setup

Installation
Follow these steps to set up the Greenleaf application from the GitHub repository:

Clone the Repository:
git clone https://github.com/RoaraxAli/GreenLeaf.git


Navigate to the Project Folder:
cd GreenLeaf


Install PHP Dependencies:
composer install


Install JavaScript Dependencies (if applicable):
npm install



Database Setup
Configure the MySQL database to support the Greenleaf application:

Create the Database:

Access your MySQL client (e.g., phpMyAdmin, MySQL Workbench, or command line).
Create a new database named greenleaf:mysql -u <your_mysql_username> -p -e "CREATE DATABASE greenleaf;"




Import the Database Schema:

Locate the database.sql file in the project root or database folder.
Import the schema into the greenleaf database:
Using phpMyAdmin: Navigate to the "Import" tab and upload database.sql.
Using Command Line:mysql -u <your_mysql_username> -p greenleaf < database.sql




This creates tables for users, plants, orders, cart, blogs, reminders, and saved_blogs with appropriate relationships.



Configuration
Update configuration files to ensure proper database connectivity:

Update config.php:

Locate config.php in the config directory or project root.
Update the database credentials:<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');
define('DB_NAME', 'greenleaf');
?>




Update config/database.php (if applicable):

Ensure the database connection settings match your environment:<?php
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


Replace your_mysql_username and your_mysql_password with your actual MySQL credentials.



Running the Application

Start the Web Server:

If using XAMPP/WAMP, ensure Apache and MySQL services are running.
If using a standalone web server, configure it to point to the public directory of the project.

Alternatively, configure your web server to serve the public directory and access it via http://localhost.


Access the Application:

Open your browser and navigate to http://localhost:8000 (or the appropriate URL based on your server configuration).



Project Structure
The project follows a standard structure for a PHP-based web application:
GreenLeaf/
├── sql/                        # Database-related files
├── config/                     # Configuration files
├── public/                     # Public-facing resources (e.g., index.php)
├── assets/                     # Static files like images, CSS, JS
├── styles/                     # Custom stylesheets
├── includes/                   # Included PHP modules or logic
├── components/                 # UI components
├── admin/                      # Admin panel related code
├── auth/                       # Authentication-related scripts
├── app/                        # Application logic
├── api/                        # API endpoints or handlers
├── image.webp                  # Example image file
├── placeholder.jfif            # Placeholder image
├── components.json             # Component definitions (if using JS framework)
├── index.php                   # Main entry point
├── about.php
├── blog.php
├── blog-detail.php
├── cart.php
├── catalog.php
├── checkout.php
├── my-garden.php
├── order-confirmation.php
├── orders.php
├── plant-detail.php
├── reminders.php
├── README.md                   # Project documentation

User Credentials

# Go to the signup page and create your account:
# Username: Choose your preferred username or email
# Password: Set a secure password of your choice

# After signing up, update your role to admin in the database.

# Open your SQL tool (e.g., phpMyAdmin) or use a MySQL client.

# Run the following SQL query (replace your_email@example.com with your email):

UPDATE users
SET role = 'admin'
WHERE email = 'your_email@example.com';


Security Note: Update default credentials immediately after setup to ensure security.
Troubleshooting

Database Connection Errors:
Verify MySQL credentials in config.php or config/database.php.
Ensure the MySQL server is running and accessible.


404 Errors:
Confirm the web server is pointing to the public directory.
Check .htaccess configuration for Apache servers.


Logs:
Review error logs in the web server or PHP configuration for detailed error messages.


Contributing
Contributions are welcome! To contribute to Greenleaf:

Create a Pull Request on GitHub.

Please ensure your code adheres to the project's coding standards and includes appropriate tests.
