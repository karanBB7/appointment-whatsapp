<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "13.232.224.97";
$user = "drupaladmin";
$pass = "Linqmd*123";
$db = "appointment";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected successfully";
?>
