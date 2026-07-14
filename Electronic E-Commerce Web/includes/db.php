<?php
// Database connection settings for WAMPServer (default MySQL user 'root', no password)
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "Electronic E-Commerce Web";

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
