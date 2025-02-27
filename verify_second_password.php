<?php
session_start();

require 'db.php';

if (!isset($_SESSION['user_id_temp'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $second_password = $_POST['second_password'];

    $stmt = $pdo->prepare('SELECT * FROM user_second_passwords WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id_temp']]);
    $secondPasswordData = $stmt->fetch();

    if ($secondPasswordData && password_verify($second_password, $secondPasswordData['second_password'])) {
        $_SESSION['user_id'] = $_SESSION['user_id_temp']; 
        unset($_SESSION['user_id_temp']); 

        $stmt = $pdo->prepare('SELECT * FROM malicious_attacks WHERE user_id = ? ORDER BY attack_time DESC');
        $stmt->execute([$_SESSION['user_id']]);
        $maliciousAttack = $stmt->fetch();

        if ($maliciousAttack) {
            $_SESSION['malicious_attack_detected'] = true;
            $_SESSION['attack_time'] = $maliciousAttack['attack_time']; 
        }

        $_SESSION['success_message'] = "Login successful!";
        header('Location: dashboard.php');
        exit;
    } else {
        $attackTime = (new DateTime())->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO malicious_attacks (user_id, attack_time) VALUES (?, ?)');
        $stmt->execute([$_SESSION['user_id_temp'], $attackTime]);

        $lockTime = (new DateTime())->add(new DateInterval('PT5M'))->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('UPDATE users SET lock_time = ? WHERE id = ?');
        $stmt->execute([$lockTime, $_SESSION['user_id_temp']]);

        $_SESSION['malicious_attack_details'] = [
            'message' => "Malicious activity detected! Your account will be locked for 5 minutes.",
            'attack_time' => $attackTime,
        ];

        header('Location: login.php');
        exit;
    }
}
?>