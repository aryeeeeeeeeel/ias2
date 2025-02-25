<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // Start session to store messages

require 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
        header('Location: register.php');
        exit;
    } elseif ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
        header('Location: register.php');
        exit;
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error_message'] = "Email is already registered.";
            header('Location: register.php');
            exit;
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert new user into the database
            $stmt = $pdo->prepare('INSERT INTO users (first_name, middle_name, last_name, email, password) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$first_name, $middle_name, $last_name, $email, $hashedPassword]);

            // Success message
            $_SESSION['success_message'] = "Registration successful. Please log in.";
            header('Location: login.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            padding: 20px;
            text-align: center;
        }

        form {
            background-color: white;
            max-width: 400px;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #007bff;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle input[type="password"] {
            padding-right: 15px;
        }

        .toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        .tooltip {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            padding: 5px;
            border-radius: 6px;

            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;

            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>

<body>
    <h1>Register</h1>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p class="error"><?php echo $_SESSION['error_message'];
        unset($_SESSION['error_message']); ?></p>
    <?php endif; ?>
    <form method="post" onsubmit="return validateForm()">
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="middle_name" placeholder="Middle Name (Optional)">
        <input type="text" name="last_name" placeholder="Last Name" required>
        <input type="email" name="email" id="email" placeholder="Email" required>

        <div class="password-toggle tooltip">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <span class="toggle-btn" onclick="togglePassword('password')">👁️</span>
            <span class="tooltiptext">At least 8 characters, 1 uppercase, 1 lowercase, 1 number</span>
        </div>

        <div class="password-toggle tooltip">
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password"
                required>
            <span class="toggle-btn" onclick="togglePassword('confirm_password')">👁️</span>
            <span class="tooltiptext">Must match the password</span>
        </div>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        }

        function validateForm() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert("Please enter a valid email address.");
                return false;
            }

            // Validate password complexity
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
            if (!passwordRegex.test(password)) {
                alert("Password must be at least 8 characters long, with 1 uppercase letter, 1 lowercase letter, and 1 number.");
                return false;
            }

            // Validate password match
            if (password !== confirmPassword) {
                alert("Passwords do not match.");
                return false;
            }

            return true;
        }
    </script>
</body>

</html>