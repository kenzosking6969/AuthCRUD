<?php
session_start();

// Update database connection to match your credentials
$host = "localhost";
$dbname = "login_system";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Update the query to search in the login table (not users table)
    $sql = "SELECT * FROM login WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        if (password_verify($pass, $user_data['password'])) {
            // Successful login
            $_SESSION['user_id'] = $user_data['user_id'];
            echo json_encode(['status' => 'success', 'message' => 'Login Successful']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Username or Password']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Username or Password']);
    }

    $stmt->close();
    $conn->close();
}
?>