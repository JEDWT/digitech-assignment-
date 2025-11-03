<?php
$host = "localhost";
$user = "root"; // default for XAMPP
$pass = "";
$dbname = "timetable_project"; // data base name 

$conn = new mysqli($host, $user, $pass, $dbname); // creating new connection to the server

if ($conn->connect_error) { // if connection error 
    die("Connection failed: " . $conn->connect_error); // end connection
}
?>
