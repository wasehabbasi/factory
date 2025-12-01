<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
/**
 * db_setup.php
 * Run once to create tables + default admin
 * Database must already exist in cPanel.
 */

// DB connection config
$host = "localhost";      // cPanel usually 'localhost'
$user = "origamic_admin"; // MySQL username
$pass = "factory123@";    // MySQL password
$db   = "origamic_inventory_db";   // Existing database

// Connect to database
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

// Insert default admin user if not exists
$adminEmail = "usman@gmail.com";
$adminPass = password_hash("Usman@786", PASSWORD_DEFAULT);
$check = mysqli_query($conn, "SELECT id FROM users WHERE email='$adminEmail' LIMIT 1");

$adminRoleId = 1;

if (mysqli_num_rows($check) == 0) {
  $insert = mysqli_prepare($conn, "INSERT INTO users (name,email,password_hash,role_id) VALUES (?,?,?,?)");
  $name = "System Admin";
  mysqli_stmt_bind_param($insert, "sssi", $name, $adminEmail, $adminPass, $adminRoleId);
  mysqli_stmt_execute($insert);
  mysqli_stmt_close($insert);
  echo "Default admin created (Email: admin@factory.com | Password: admin123)<br>";
}

mysqli_close($conn);