<?php
// api/products.php — คืนรายการสินค้า (เฉพาะที่เปิดขายและสต๊อก > 0)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';


try {
$stmt = $pdo->query("SELECT id, name, description, price_baht, image_url, stock FROM products WHERE is_active=1 AND stock>0 ORDER BY id DESC");
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
http_response_code(500);
echo json_encode(['message' => 'server error']);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['message'=>'missing id']); exit; }


try {
$st = $pdo->prepare('SELECT id, name, description, price_baht, image_url, stock, is_active FROM products WHERE id=?');
$st->execute([$id]);
$row = $st->fetch();
if (!$row || (int)$row['is_active']!==1) { http_response_code(404); echo json_encode(['message'=>'not found']); exit; }
echo json_encode($row, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
http_response_code(500);
echo json_encode(['message'=>'server error']);
}
}exit;