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

$safeDir = 'safe_files/';
$safeFiles = [];
if (is_dir($safeDir)) {
    $files = scandir($safeDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $safeFiles[] = $file;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malware Detector System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            background-image: url('./img/bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }

        .container {
            display: flex;
            align-items: center;
            gap: 20px;
            z-index: 1;
        }

        .scanner {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
            width: 500px;
            text-align: center;
            position: relative;
        }

        .safe-files {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
            width: 300px;
            max-height: 80vh;
            overflow-y: auto;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .malware-title {
            color: #007bff;
        }

        .file-title {
            color: #007bff;
        }

        .url-title {
            color: #007bff;
        }

        .file-scan-section,
        .url-scan-section {
            margin-top: 20px;
        }

        .file-upload-label,
        .url-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .file-upload-label {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            text-align: center;
        }

        .file-upload-label:hover {
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

        .scan-url-button {
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

        .scan-url-button:hover {
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
            color: #dc3545;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
            color: #6c757d;
        }

        .refresh-button:hover {
            color: #5a6268;
        }

        .file-name {
            margin-top: 10px;
            color: #666;
        }

        .safe-files h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }

        .safe-files ul {
            list-style-type: none;
            padding: 0;
        }

        .safe-files li {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .safe-files li .file-name {
            color: #007bff;
        }

        .safe-files li .file-download {
            color: #28a745;
            text-decoration: none;
        }

        .safe-files li .file-download:hover {
            text-decoration: underline;
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
        <div class="scanner">
            <button class="refresh-button" onclick="location.reload()"><i class="fas fa-sync-alt"></i></button>
            <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
            <h2 class="malware-title" id="malware-title">
                <i class="fas fa-bug"></i> 
                Malware Detector System
                <i class="fas fa-search"></i> 
            </h2>

            <!-- File Scanner Section -->
            <div class="file-scan-section">
                <h2 class="file-title" id="file-title">
                    <i class="fas fa-file"></i> 
                    File Scanner
                    <i class="fas fa-search"></i> 
                </h2>
                <input id="file-upload" type="file" name="file" onchange="displayFileName()">
                <div class="file-name" id="file-name"></div>
                <button class="scan-button" id="scan-file-button" onclick="scanFile()">
                    <i class="fas fa-search"></i> Scan File
                </button>
                <div class="message" id="message"></div>
            </div>

            <!-- URL Scanner Section -->
            <div class="url-scan-section">
                <h2 class="url-title" id="url-title">
                    <i class="fas fa-link"></i> 
                    URL Scanner
                    <i class="fas fa-search"></i> 
                </h2>
                <input type="text" id="url-input" class="url-input" placeholder="Enter URL to scan">
                <button class="scan-url-button" id="scan-url-button" onclick="scanURL()">
                    <i class="fas fa-search"></i> Scan URL
                </button>
                <div class="message" id="url-message"></div>
            </div>

            <div class="terms">
                By submitting data above, you are agreeing to our <a href="#">Terms of Service</a> and <a
                    href="#">Privacy
                    Notice</a>. Please do not submit any personal information; we are not responsible for the contents
                of
                your submission.
            </div>
            <a href="logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> 
                Logout
            </a>
        </div>

        <!-- Safe Files Section -->
        <div class="safe-files">
            <h3><i class="fas fa-shield-alt"></i> Safe Files</h3>
            <?php if (!empty($safeFiles)): ?>
                <ul>
                    <?php foreach ($safeFiles as $file): ?>
                        <li>
                            <span class="file-name"><?php echo htmlspecialchars($file); ?></span>
                            <a href="<?php echo $safeDir . rawurlencode($file); ?>" class="file-download" download>
                                <i class="fas fa-download"></i> 
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No safe files found.</p>
            <?php endif; ?>
        </div>
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

        function updateColors(elementId, color) {
            const element = document.getElementById(elementId);
            if (element) {
                element.style.color = color;
            }
        }

        function updateButtonColor(buttonId, color) {
            const button = document.getElementById(buttonId);
            if (button) {
                button.style.backgroundColor = color;
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
                        updateColors('malware-title', '#28a745'); 
                        updateColors('file-title', '#28a745'); 
                        updateButtonColor('scan-file-button', '#28a745'); 
                    } else if (data.message.includes("Malware detected")) {
                        messageDiv.textContent = data.message + " File: " + data.fileName;
                        messageDiv.className = "message malware";
                        updateColors('malware-title', '#dc3545');
                        updateColors('file-title', '#dc3545'); 
                        updateButtonColor('scan-file-button', '#dc3545'); 
                    }
                    setTimeout(() => location.reload(), 2000);
                })
                .catch(error => {
                    messageDiv.textContent = "An error occurred while scanning the file.";
                    messageDiv.className = "message";
                });
        }

        function scanURL() {
            const urlInput = document.getElementById('url-input');
            const urlMessageDiv = document.getElementById('url-message');

            if (!urlInput.value) {
                urlMessageDiv.textContent = "Please enter a URL.";
                urlMessageDiv.className = "message";
                return;
            }

            fetch('url_scanner.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ url: urlInput.value }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.message === "URL is safe.") {
                        urlMessageDiv.textContent = data.message + " URL: " + data.url;
                        urlMessageDiv.className = "message clean";
                        updateColors('malware-title', '#28a745'); 
                        updateColors('url-title', '#28a745'); 
                        updateButtonColor('scan-url-button', '#28a745'); 
                    } else if (data.message.includes("Malware detected")) {
                        urlMessageDiv.textContent = data.message + " URL: " + data.url;
                        urlMessageDiv.className = "message malware";
                        updateColors('malware-title', '#dc3545'); 
                        updateColors('url-title', '#dc3545'); 
                        updateButtonColor('scan-url-button', '#dc3545'); 
                    }
                })
                .catch(error => {
                    urlMessageDiv.textContent = "An error occurred while scanning the URL.";
                    urlMessageDiv.className = "message";
                });
        }
    </script>
</body>

</html>