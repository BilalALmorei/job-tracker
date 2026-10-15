<?php
// الاتصال بقاعدة البيانات المحلية باستخدام PDO.
$dsn = "mysql:host=127.0.0.1;dbname=job_tracker;charset=utf8mb4";
$username = "YOUR_DATABASE_USER";
$password = "YOUR_DATABASE_PASSWORD";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    exit("تعذر الاتصال بقاعدة البيانات.");
}