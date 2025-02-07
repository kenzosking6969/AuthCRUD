<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="index.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container-box">
        <form id="LoginForm" action="login.php" method="POST" class="login-form">
            <h1>Login</h1>

            <div class="input-box">
                <input type="text" name="username" placeholder="Username" required>
                <i class='bx bxs-user'></i>
            </div>

            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>

            <div class="remember-forgot">
                <label><input type="checkbox"> Remember me </label>
                <a href="#">Forgot Password?</a>
            </div>

            <button type="submit" class="btn">Login</button>

            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </form>
    </div>

    <!-- Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>Login Successful!</h2>
            <p>You are being redirected...</p>
            <button class="modal-button" id="redirectBtn">OK</button>
        </div>
    </div>

    <script>
        document.getElementById('LoginForm').addEventListener('submit', function (e) {
            e.preventDefault();

            let formData = new FormData(this);

            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                if (data.status === 'success') {
                    // Show the custom modal
                    document.getElementById('successModal').style.display = 'block';

                    // Automatic redirection after 3 seconds
                    const autoRedirect = setTimeout(function () {
                        window.location.href = 'dashboard.php'; // Redirect to dashboard
                    }, 3000);

                    // Allow manual redirection by clicking "OK"
                    document.getElementById('redirectBtn').addEventListener('click', function () {
                        clearTimeout(autoRedirect); // Clear the automatic redirect
                        window.location.href = 'dashboard.php';
                    });
                } else {
                    alert(data.message); // Show error message if login fails
                }
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>
