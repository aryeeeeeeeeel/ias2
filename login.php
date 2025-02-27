<?php
session_start();

// Check for any success or error messages
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Remove the message after displaying it
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Remove the message after displaying it
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the input data
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch the user from the database based on email
    require 'db.php';  // Include your database connection here
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Check if the account is locked due to too many failed attempts
        $failedAttempts = $user['failed_attempts'];
        $lockTime = $user['lock_time'];
        $currentTime = new DateTime();

        if ($lockTime && $currentTime < new DateTime($lockTime)) {
            $_SESSION['error_message'] = "Your account is locked. Try again later.";
            header('Location: login.php');
            exit;
        }

        if ($user && password_verify($password, $user['password'])) {
            // Email and password are correct
            $_SESSION['user_id_temp'] = $user['id']; // Store user ID temporarily

            // Check for malicious attacks
            $stmt = $pdo->prepare('SELECT * FROM malicious_attacks WHERE user_id = ? ORDER BY attack_time DESC');
            $stmt->execute([$user['id']]);
            $maliciousAttack = $stmt->fetch();

            if ($maliciousAttack) {
                // Notify the user of the malicious attack
                $_SESSION['malicious_attack_detected'] = true;
            }

            header('Location: login_step2.php'); // Redirect to the second step
            exit;
        } else {
            // Password is incorrect
            $failedAttempts++;

            // Lock the account after 5 failed attempts
            if ($failedAttempts >= 5) {
                $lockTime = $currentTime->add(new DateInterval('PT5M'))->format('Y-m-d H:i:s');
                $stmt = $pdo->prepare('UPDATE users SET failed_attempts = ?, lock_time = ? WHERE id = ?');
                $stmt->execute([$failedAttempts, $lockTime, $user['id']]);

                $_SESSION['error_message'] = "Too many failed attempts. Your account is locked for 5 minutes.";
            } else {
                // Update failed attempts
                $stmt = $pdo->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?');
                $stmt->execute([$failedAttempts, $user['id']]);

                $_SESSION['error_message'] = "Invalid email or password.";
            }

            header('Location: login.php');
            exit;
        }
    } else {
        // User not found
        $_SESSION['error_message'] = "Invalid email or password.";
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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

        .success {
            color: green;
            margin-bottom: 15px;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle input[type="password"] {
            padding-right: 40px;
        }

        .toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>

<body>
    <h1>Login</h1>

    <?php if (isset($error) && $error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if (isset($success) && $success): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['malicious_attack_detected'])): ?>
        <script>
            alert("We've detected malicious activity earlier. Please change your password immediately!");
        </script>
        <?php unset($_SESSION['malicious_attack_detected']); ?>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="email" placeholder="Email" required>
        <div class="password-toggle">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <span class="toggle-btn" onclick="togglePassword()">👁️</span>
        </div>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a>.</p>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        }
    </script>
</body>

</html>