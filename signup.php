<?php
session_start();
require_once 'php/db_connect.php'; // includes the data base connection script

// keep in mind that this is my first php script so i do have some comments to help me remeber
if ($_SERVER["REQUEST_METHOD"] === "POST") { // recieving the post from the html
    $email = $_POST['email']; // takes the email from the post
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // hashes password for secruity -- PASSWORD_DEFAULT tells php to give the best hashing right now as of this php version 
    $First_Name = $_POST['First_Name'];
    $Last_Name = $_POST['Last_Name'];
    // Make sure they’re using a school email
    if (!str_ends_with($email, "@mybce.catholic.edu.au")) { // check the email string if it ends with the schools email / bce email 
        $error = "Please use your school email address."; 
    } else {
        $query = $conn->prepare("INSERT INTO users (email, password,First_Name,Last_Name) VALUES (?, ?,?,?)"); // ?,? are the unassigned values -- preparing the connection to insert the email and password
        $query->bind_param("ssss", $email, $password,$First_Name,$Last_Name); // we set the unassigned values in the sql to take the email and password -- ss mean string, string, which identifys both varibles as a string 
       
       
        if ($query->execute()) { // exectuting the sql query 
            $secondquery = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $secondquery->bind_param("s", $email);
            $secondquery->execute();
            $result = $secondquery->get_result();
            $user = $result->fetch_assoc();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['First_Name']; // optional
        
            if (isset($_POST['remember_me'])) {
                $token = bin2hex(random_bytes(16)); // secure random token
                setcookie('remember_me', $token, time() + (30*24*60*60), "/", "", true, true); // 30 days, secure & httponly

                // Store token in database
                $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->bind_param("si", $token, $user['id']);
                $stmt->execute();
            }
            header("Location: create_timetable.php"); // send user to the index page cuz why not 
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
        <input type="First_Name" name="First_Name" placeholder="First Name" required><br>
        <input type="Last_Name" name="Last_Name" placeholder="Last Name" required><br>
        <label>
            <input type="checkbox" name="remember_me"> Remember Me
        </label><br>
        <button type="submit">Create Account</button>
    </form>
    <p>Already have an account? <a href="index.php">Login</a></p>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
