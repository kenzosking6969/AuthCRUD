<?php
session_start();

// Database connection variables (renamed for clarity)
$dbHost = "localhost";
$dbName = "login_system";
$dbUser = "root";
$dbPass = "";

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $regUsername = trim($_POST['username']);           // Renamed variable for clarity
    $userPassword = trim($_POST['password']);
    $confirmPass = trim($_POST['confirm_password']);

    $errors = [];
    if (empty($firstName))
        $errors[] = "First name is required";
    if (empty($lastName))
        $errors[] = "Last name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Valid email is required";
    if (empty($contact) || !preg_match('/^[0-9]{10,15}$/', $contact))
        $errors[] = "Valid contact number is required";
    if (empty($regUsername))
        $errors[] = "Username is required";
    if (empty($userPassword))
        $errors[] = "Password is required";
    if ($userPassword !== $confirmPass)
        $errors[] = "Passwords do not match";

    if (empty($errors)) {
        // Check if the username or email already exists in the register table
        $stmt = $conn->prepare("SELECT id FROM register WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $regUsername, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username or Email already exists";
        } else {
            // Hash the password before storing
            $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

            // Insert user data into the register table
            // Note: The current schema does not include columns for 'middlename' or 'date_of_birth'.
            $insert_stmt = $conn->prepare("INSERT INTO register (first_name, last_name, email, contact_info, username, password) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssss", $firstName, $lastName, $email, $contact, $regUsername, $hashedPassword);

            if ($insert_stmt->execute()) {
                // Retrieve the newly inserted user ID
                $user_id = $conn->insert_id;

                // Insert the login credentials into the login table
                $login_stmt = $conn->prepare("INSERT INTO login (user_id, username, password) VALUES (?, ?, ?)");
                $login_stmt->bind_param("iss", $user_id, $regUsername, $hashedPassword);

                if ($login_stmt->execute()) {
                    $success = "Registration successful!";
                } else {
                    $error = "Error inserting login data: " . $conn->error;
                }
                $login_stmt->close();
            } else {
                $error = "Error inserting registration data: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $stmt->close();
    } else {
        $error = implode(", ", $errors);
    }

    // Handle AJAX response if applicable
    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
    ) {
        echo $success ?: $error;
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Form</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
</head>

<body>
    <div class="container-box">
        <form id="RegisterForm" action="register.php" method="POST" class="login-form">
            <h1>Register</h1>

            <div id="registerMessage" class="success-message"></div>

            <!-- Form Fields -->
            <div class="input-box">
                <input type="text" name="first_name" placeholder="First Name" required>
                <i class='bx bxs-user'></i>
            </div>

            <div class="input-box">
                <input type="text" name="last_name" placeholder="Last Name" required>
                <i class='bx bxs-user'></i>
            </div>

            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required>
                <i class='bx bxs-envelope'></i>
            </div>

            <div class="input-box">
                <input type="tel" name="contact" placeholder="Contact Number" required>
                <i class='bx bxs-phone'></i>
            </div>

            <div class="input-box">
                <input type="text" name="username" placeholder="Username" required>
                <i class='bx bxs-user-circle'></i>
            </div>

            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>

            <div class="input-box">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>

            <button type="submit" class="btn">Register</button>

            <div class="register-link">
                <p>Already have an account? <a href="index.php">Login</a></p>
            </div>
        </form>

        <div id="registerSuccessMessage" class="success-message"></div>
    </div>

    <script>
        document.getElementById('RegisterForm').addEventListener('submit', function (e) {
            e.preventDefault();

            let formData = new FormData(this);

            fetch('register.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(data => {
                    if (data === "Registration successful!") {
                        document.getElementById('RegisterForm').style.display = 'none';
                        document.getElementById('registerSuccessMessage').style.display = 'block';
                        document.getElementById('registerSuccessMessage').textContent = data;
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 2000);
                    } else {
                        const messageDiv = document.getElementById('registerMessage');
                        messageDiv.textContent = data;
                        messageDiv.style.color = '#ff4444';
                        messageDiv.style.display = 'block';
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    </script>
</body>

</html>