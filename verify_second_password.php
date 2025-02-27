<?php
session_start();

// Include the database connection file
require 'db.php';

if (!isset($_SESSION['user_id_temp'])) {
    // Redirect to login if the user didn't complete the first step
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $second_password = $_POST['second_password'];

    // Fetch the second password from the database
    $stmt = $pdo->prepare('SELECT * FROM user_second_passwords WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id_temp']]);
    $secondPasswordData = $stmt->fetch();

    if ($secondPasswordData && password_verify($second_password, $secondPasswordData['second_password'])) {
        // Second password is correct
        $_SESSION['user_id'] = $_SESSION['user_id_temp']; // Store user ID in session
        unset($_SESSION['user_id_temp']); // Clear temporary session data

        // Check for malicious attacks
        $stmt = $pdo->prepare('SELECT * FROM malicious_attacks WHERE user_id = ? ORDER BY attack_time DESC');
        $stmt->execute([$_SESSION['user_id']]);
        $maliciousAttack = $stmt->fetch();

        if ($maliciousAttack) {
            // Notify the user of the malicious attack
            $_SESSION['malicious_attack_detected'] = true;
            $_SESSION['attack_time'] = $maliciousAttack['attack_time']; // Store attack time
        }

        $_SESSION['success_message'] = "Login successful!";
        header('Location: dashboard.php');
        exit;
    } else {
        // Second password is incorrect
        // Log malicious attack
        $attackTime = (new DateTime())->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO malicious_attacks (user_id, attack_time) VALUES (?, ?)');
        $stmt->execute([$_SESSION['user_id_temp'], $attackTime]);

        // Lock the account for 5 minutes
        $lockTime = (new DateTime())->add(new DateInterval('PT5M'))->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('UPDATE users SET lock_time = ? WHERE id = ?');
        $stmt->execute([$lockTime, $_SESSION['user_id_temp']]);

        // Store attack details in session
        $_SESSION['malicious_attack_details'] = [
            'message' => "Malicious activity detected! Your account will be locked for 5 minutes.",
            'attack_time' => $attackTime,
        ];

        header('Location: login.php');
        exit;
    }
}
?>