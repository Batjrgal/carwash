<?php
// Database configuration
$host = "localhost";     // Database host (e.g., localhost)
$username = "root";      // Database username
$password = "";          // Database password
$dbname = "carwash";  // Your database name

// Create a database connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Uncomment the following line for debugging purposes:
// echo "Database connected successfully!";
?>