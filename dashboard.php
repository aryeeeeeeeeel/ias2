<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
            position: relative;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        .upload-section {
            margin-top: 20px;
        }

        input[type="file"] {
            display: none;
        }

        .file-upload {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            margin-bottom: 10px;
        }

        .file-upload:hover {
            background-color: #0056b3;
        }

        .scan-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .scan-button:hover {
            background-color: #0056b3;
        }

        .message {
            margin-top: 20px;
            color: #333;
            font-weight: bold;
            animation: fadeIn 0.5s ease-in-out;
        }

        .message.clean {
            color: #28a745;
            animation: bounce 0.5s ease-in-out;
        }

        .message.malware {
            color: #dc3545;
            animation: shake 0.5s ease-in-out;
        }

        .logout-link {
            display: block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }

        .logout-link:hover {
            text-decoration: underline;
        }

        .terms {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }

        .terms a {
            color: #007bff;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        .refresh-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: transparent;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: #007bff;
        }

        .refresh-button:hover {
            color: #0056b3;
        }

        .file-name {
            margin-top: 10px;
            color: #666;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-10px);
            }

            75% {
                transform: translateX(10px);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <button class="refresh-button" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
        <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
        <div class="upload-section">
            <label for="file-upload" class="file-upload">Choose File</label>
            <input id="file-upload" type="file" name="file" onchange="displayFileName()">
            <div class="file-name" id="file-name"></div>
            <button class="scan-button" onclick="scanFile()">
                <i class="fas fa-search"></i> Scan File
            </button>
        </div>
        <div class="message" id="message"></div>
        <div class="terms">
            By submitting data above, you are agreeing to our <a href="#">Terms of Service</a> and <a href="#">Privacy
                Notice</a>. Please do not submit any personal information; we are not responsible for the contents of
            your submission.
        </div>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>

    <script>
        function displayFileName() {
            const fileInput = document.getElementById('file-upload');
            const fileNameDiv = document.getElementById('file-name');
            if (fileInput.files.length > 0) {
                fileNameDiv.textContent = "Selected File: " + fileInput.files[0].name;
            } else {
                fileNameDiv.textContent = "";
            }
        }

        function scanFile() {
            const fileInput = document.getElementById('file-upload');
            const messageDiv = document.getElementById('message');

            if (fileInput.files.length === 0) {
                messageDiv.textContent = "Please upload a file.";
                messageDiv.className = "message";
                return;
            }

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            fetch('malware_scanner.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.message === "File is clean.") {
                        messageDiv.textContent = data.message + " File: " + data.fileName;
                        messageDiv.className = "message clean";
                    } else {
                        messageDiv.textContent = "Malware Detected! Please Delete The File '" + data.fileName + "'.";
                        messageDiv.className = "message malware";
                    }
                })
                .catch(error => {
                    messageDiv.textContent = "An error occurred while scanning the file.";
                    messageDiv.className = "message";
                });
        }
    </script>
</body>

</html>