<?php
// Database configuration
$servername = "localhost";  // Typically localhost for local servers like XAMPP
$username = "root";         // Default username for XAMPP
$password = "";             // Default password for XAMPP (leave blank for XAMPP)
$dbname = "library_system"; // Name of your database (adjust this if your database has a different name)

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    // Connection failed, output error message and terminate the script
    die("Connection failed: " . mysqli_connect_error());
}

// Optionally, you can set the character set for the connection
mysqli_set_charset($conn, "utf8");

// If connection is successful, you can perform queries here
?>
