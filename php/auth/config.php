<?php
// =============================================
// Horus Cloud - Shared Config
// =============================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'horus_cloud');
define('DB_USER', getenv('DB_USER') ?: 'horus_user');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('SITE_URL',  'https://horuscloud.edu.eg');
define('ADMIN_URL', 'https://admin.horuscloud.edu.eg');
define('MAIL_FROM', 'noreply@horuscloud.edu.eg');
define('MAIL_ADMIN','chep@fayoum.edu.eg');
define('APP_NAME',  'Horus Cloud');

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO(
            "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES   => false]
        );
    }
    return $pdo;
}

function sendMail(string $to, string $subject, string $body, string $replyTo = ''): void {
    $headers  = "From: ".APP_NAME." <".MAIL_FROM.">\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    if ($replyTo) $headers .= "Reply-To: $replyTo\r\n";
    @mail($to, $subject, nl2br(htmlspecialchars($body)), $headers);
}

function jsonOut(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

function sanitize(string $v, int $max = 255): string {
    return htmlspecialchars(substr(trim($v), 0, $max), ENT_QUOTES|ENT_HTML5, 'UTF-8');
}
