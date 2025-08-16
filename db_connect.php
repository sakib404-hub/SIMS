<?php
// db_connect.php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_information_msdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
?>
