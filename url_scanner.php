<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $url = $data['url'];

    function scanURL($url) {
        $maliciousURLs = [
            'malware.com',
            'evil.com',
            'phishing.com',
        ];

        foreach ($maliciousURLs as $maliciousURL) {
            if (strpos($url, $maliciousURL) !== false) {
                return ["message" => "Malware detected! URL is not safe!", "url" => $url];
            }
        }

        return ["message" => "URL is safe.", "url" => $url];
    }

    $result = scanURL($url);
    echo json_encode($result);
    exit;
}
?>