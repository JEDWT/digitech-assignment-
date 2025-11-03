<?php
include 'php/db_connect.php'; // includes the data base connection script

if ($_SERVER["REQUEST_METHOD"] === "POST") { // recieving the post from the html
    $email = $_POST['email']; // takes the email from the post
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // hashes password for secruity -- PASSWORD_DEFAULT tells php to give the best hashing right now as of this php version 

    // Make sure they’re using a school email
    if (!str_ends_with($email, "@mybce.catholic.edu.au")) { // check the email string if it ends with the schools email / bce email 
        $error = "Please use your school email address."; 
    } else {
        $query = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)"); // ?,? are the unassigned values -- preparing the connection to insert the email and password
        $query->bind_param("ss", $email, $password); // we set the unassigned values in the sql to take the email and password -- ss mean string, string, which identifys both varibles as a string 
        
        if ($query->execute()) { // exectuting the sql query 
            header("Location: index.php"); // send user to the index page cuz why not 
            exit; // end no more php code runs 
        } else {
            $error = "That email is already registered."; // error cuz theres a double up on emails 
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - My Timetable</title>
</head>
<body>
    <h2>Sign Up</h2>
    <form method="POST" action="">
        <input type="email" name="email" placeholder="School Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Create Account</button>
    </form>
    <p>Already have an account? <a href="index.php">Login</a></p>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
