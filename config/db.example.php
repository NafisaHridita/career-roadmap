<?php
// Copy this file to config/db.php and change the values for your machine.
// config/db.php is listed in .gitignore so your real credentials are never committed.
// [ashasuborna] Database config template; config/db.php stays out of git (#41)
$conn = new mysqli('127.0.0.1', 'root', '', 'career_roadmap');

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
