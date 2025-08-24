<?php
session_start();

if (!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', 'admin123');

$host = '127.0.0.1';
$db = 'shopdb'; 
$user = 'root'; 
$pass = '';
$charset = 'utf8mb4';


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
];


try { $pdo = new PDO($dsn, $user, $pass, $options); }
catch (PDOException $e) {
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
http_response_code(500);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'เชื่อมต่อฐานข้อมูลไม่สำเร็จ']);
exit;
}
die('DB connection failed');
}


if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
ini_set('display_errors', '0');
set_error_handler(function($no,$str){
http_response_code(500);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'Server error', 'detail' => $str], JSON_UNESCAPED_UNICODE);
exit;
});
set_exception_handler(function($e){
http_response_code(500);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'Server exception', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
exit;
});
}


function require_admin_json(){
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
http_response_code(403);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'Forbidden']);
exit;
}
}