<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Greenleaf Web Application - README</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      max-width: 900px;
      margin: 2rem auto;
      padding: 0 1rem;
      background: #f9f9f9;
      color: #333;
    }
    h1, h2, h3 {
      color: #2c6e49;
    }
    pre {
      background: #272822;
      color: #f8f8f2;
      padding: 1rem;
      overflow-x: auto;
      border-radius: 5px;
    }
    code {
      font-family: monospace;
      background: #eee;
      padding: 0.2rem 0.4rem;
      border-radius: 3px;
    }
    ul {
      margin: 0 0 1rem 1.5rem;
    }
    a {
      color: #2c6e49;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <h1>Greenleaf Web Application</h1>

  <p><em>30-Minute Side Fun Project</em></p>

  <p>Greenleaf is a full-stack web application designed for plant enthusiasts, professional gardeners, and nursery owners. It provides a robust platform for browsing plants, accessing gardening tips, managing personal gardens, and placing orders. This README provides comprehensive instructions for setting up, running, and contributing to the application.</p>

  <h2>Table of Contents</h2>
  <ul>
    <li><a href="#features">Features</a></li>
    <li><a href="#prerequisites">Prerequisites</a></li>
    <li><a href="#installation">Installation</a></li>
    <li><a href="#database-setup">Database Setup</a></li>
    <li><a href="#running-the-application">Running the Application</a></li>
    <li><a href="#project-structure">Project Structure</a></li>
    <li><a href="#user-credentials">User Credentials</a></li>
    <li><a href="#security-note">Security Note</a></li>
    <li><a href="#troubleshooting">Troubleshooting</a></li>
    <li><a href="#contributing">Contributing</a></li>
    <li><a href="#license">License</a></li>
  </ul>

  <h2 id="features">Features</h2>
  <ul>
    <li><strong>Plant Browsing:</strong> Explore a catalog of plants with detailed descriptions.</li>
    <li><strong>Gardening Tips:</strong> Access expert advice through blog posts.</li>
    <li><strong>Garden Management:</strong> Manage personal garden plans and set reminders.</li>
    <li><strong>Order Placement:</strong> Add plants to a cart and place orders seamlessly.</li>
    <li><strong>User Authentication:</strong> Secure login and registration system.</li>
    <li><strong>Blog Saving:</strong> Save favorite blog posts for later reference.</li>
  </ul>

  <h2 id="prerequisites">Prerequisites</h2>

  <h3>Software</h3>
  <ul>
    <li>PHP: Version 7.4 or higher (8.x recommended)</li>
    <li>MySQL: Version 8.0 or higher</li>
    <li>Web Server: Apache (via XAMPP/WAMP) or Nginx</li>
    <li>Composer: For PHP dependency management</li>
    <li>Git: For cloning the repository</li>
    <li>Browser: Chrome, Firefox, Edge, or Safari (latest versions recommended)</li>
  </ul>

  <h3>Hardware Requirements</h3>
  <ul>
    <li>Processor: A blazing-fast <code>Intel Pentium 27</code> or maybe a potato chip with some magic dust</li>
    <li>RAM: At least <code>200 GB</code> (or enough to keep your browser tabs from crying)</li>
    <li>Storage: A whopping <code>500 PB</code> (petabytes) — because why not hoard every plant photo ever?</li>
    <li>Keyboard: Any old thing with some keys still intact</li>
    <li>Mouse: Bonus points if it still clicks and doesn’t scare the cat</li>
  </ul>

  <h3>Optional Local Hosting Environments</h3>
  <p>XAMPP or WAMP for streamlined Apache and MySQL setup</p>

  <h2 id="installation">Installation</h2>
  <p>Clone the repository:</p>
  <pre><code>git clone https://github.com/RoaraxAli/GreenLeaf.git</code></pre>

  <p>Navigate to the project folder:</p>
  <pre><code>cd GreenLeaf</code></pre>

  <p>Install PHP dependencies:</p>
  <pre><code>composer install</code></pre>

  <h2 id="database-setup">Database Setup</h2>

  <h3>Create the Database:</h3>
  <p>Open your MySQL client or terminal and run:</p>
  <pre><code>mysql -u &lt;your_mysql_username&gt; -p -e "CREATE DATABASE greenleaf_db;"</code></pre>

  <h3>Import the Database Schema:</h3>
  <p>Locate the <code>database.sql</code> file in the project from the <code>sql</code> folder.</p>
  <p>This creates tables for users, plants, orders, cart, blogs, reminders, and saved_blogs with appropriate relationships.</p>

  <h2 id="running-the-application">Running the Application</h2>

  <p>Start the web server:</p>
  <ul>
    <li>If using XAMPP/WAMP, ensure Apache and MySQL services are running.</li>
    <li>If using a standalone web server, configure it to serve the <code>public</code> directory of the project.</li>
  </ul>
  <p>Alternatively, run PHP's built-in server (for quick local testing):</p>
  <pre><code>php -S localhost:8000 -t public/</code></pre>

  <p>Access the application by opening your browser and navigating to:</p>
  <p><a href="http://localhost:8000" target="_blank" rel="noopener noreferrer">http://localhost:8000</a></p>

  <h2 id="project-structure">Project Structure</h2>
  <pre><code>GreenLeaf/
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
</code></pre>

  <h2 id="user-credentials">User Credentials</h2>
  <p>Go to the signup page and create your account:</p>
  <ul>
    <li><strong>Username:</strong> Choose your preferred username or email</li>
    <li><strong>Password:</strong> Set a secure password of your choice</li>
  </ul>
  <p>After signing up, update your role to admin in the database.</p>
  <p>Open your SQL tool (e.g., phpMyAdmin) or use the MySQL command line client.</p>
  <p>Run the following SQL query (replace <code>your_email@example.com</code> with your actual email):</p>
  <pre><code>UPDATE users
SET role = 'admin'
WHERE email = 'your_email@example.com';</code></pre>

  <h2 id="security-note">Security Note</h2>
  <p>Update default credentials immediately after setup to ensure security.</p>

  <h2 id="troubleshooting">Troubleshooting</h2>

  <h3>Database Connection Errors:</h3>
  <ul>
    <li>Verify MySQL credentials in <code>config.php</code> or <code>config/database.php</code>.</li>
    <li>Ensure the MySQL server is running and accessible.</li>
  </
