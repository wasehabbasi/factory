<?php
$host = "localhost";      // cPanel usually 'localhost'
$user = "origamic_cortex"; // MySQL username
$pass = "cortex@786";    // MySQL password
$db   = "origamic_cortex"; 

// connect with database
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
