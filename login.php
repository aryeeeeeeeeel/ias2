<?php
session_start();

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']); 
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['malicious_attack_details'])) {
    $maliciousAttackDetails = $_SESSION['malicious_attack_details'];
    unset($_SESSION['malicious_attack_details']); 
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    require 'db.php'; 
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $failedAttempts = $user['failed_attempts'];
        $lockTime = $user['lock_time'];
        $currentTime = new DateTime();

        if ($lockTime && $currentTime < new DateTime($lockTime)) {
            $_SESSION['error_message'] = "Your account is locked. Try again later.";
            header('Location: login.php');
            exit;
        }

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id_temp'] = $user['id']; 

            $stmt = $pdo->prepare('SELECT * FROM malicious_attacks WHERE user_id = ? ORDER BY attack_time DESC');
            $stmt->execute([$user['id']]);
            $maliciousAttack = $stmt->fetch();

            if ($maliciousAttack) {
                $_SESSION['malicious_attack_detected'] = true;
            }

            header('Location: login_step2.php');
            exit;
        } else {
            $failedAttempts++;

            if ($failedAttempts >= 5) {
                $lockTime = $currentTime->add(new DateInterval('PT5M'))->format('Y-m-d H:i:s');
                $stmt = $pdo->prepare('UPDATE users SET failed_attempts = ?, lock_time = ? WHERE id = ?');
                $stmt->execute([$failedAttempts, $lockTime, $user['id']]);

                $_SESSION['error_message'] = "Too many failed attempts. Your account is locked for 5 minutes.";
            } else {
                $stmt = $pdo->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?');
                $stmt->execute([$failedAttempts, $user['id']]);

                $_SESSION['error_message'] = "Invalid email or password.";
            }

            header('Location: login.php');
            exit;
        }
    } else {
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

    <?php if (isset($maliciousAttackDetails)): ?>
        <script>
            alert("<?php echo $maliciousAttackDetails['message']; ?>\nAttack Time: <?php echo $maliciousAttackDetails['attack_time']; ?>");
        </script>
    <?php endif; ?>

    <?php if (isset($_SESSION['malicious_attack_detected'])): ?>
        <script>
            alert("We've detected malicious activity earlier. Please change your password immediately!\nAttack Time: <?php echo $_SESSION['attack_time']; ?>");
        </script>
        <?php unset($_SESSION['malicious_attack_detected']); ?>
        <?php unset($_SESSION['attack_time']); ?>
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