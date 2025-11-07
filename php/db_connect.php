<?php
$host = "fdb1034.awardspace.net";
$user = "4694608_timetable"; // default for XAMPP
$pass = "ScoIT}hs6HVfp%hw";
$dbname = "4694608_timetable"; // data base name 

$conn = new mysqli($host, $user, $pass, $dbname); // creating new connection to the server

if ($conn->connect_error) { // if connection error 
    die("Connection failed: " . $conn->connect_error); // end connection
}
?>
