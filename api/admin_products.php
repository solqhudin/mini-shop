<?php
// api/admin_products.php — ต้องล็อกอินแอดมิน
require_once __DIR__ . '/../config.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';


if ($method === 'GET') {
require_admin_json();
header('Content-Type: application/json; charset=utf-8');
$st = $pdo->query('SELECT id,name,description,price_baht,image_url,stock,is_active FROM products ORDER BY id DESC');
echo json_encode($st->fetchAll(), JSON_UNESCAPED_UNICODE); exit;
}


if ($method === 'POST') {
require_admin_json();
header('Content-Type: application/json; charset=utf-8');
$in = json_decode(file_get_contents('php://input'), true) ?? [];
$act = $in['action'] ?? '';
try {
if ($act === 'create') {
$name = trim($in['name'] ?? ''); if ($name==='') throw new Exception('name required');
$desc = $in['description'] ?? null; $price = (int)($in['price_baht'] ?? 0); $stock = (int)($in['stock'] ?? 0);
$img = trim($in['image_url'] ?? ''); $active = (int)($in['is_active'] ?? 1);
$st = $pdo->prepare('INSERT INTO products (name,description,price_baht,image_url,stock,is_active) VALUES (?,?,?,?,?,?)');
$st->execute([$name,$desc,$price,$img,$stock,$active]);
echo json_encode(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]); exit;
}


if ($act === 'update') {
$id = (int)($in['id'] ?? 0); if ($id<=0) throw new Exception('missing id');
$name = trim($in['name'] ?? ''); $desc = $in['description'] ?? null; $price = (int)($in['price_baht'] ?? 0);
$stock = (int)($in['stock'] ?? 0); $img = trim($in['image_url'] ?? ''); $active = (int)($in['is_active'] ?? 1);
$st = $pdo->prepare('UPDATE products SET name=?,description=?,price_baht=?,image_url=?,stock=?,is_active=? WHERE id=?');
$st->execute([$name,$desc,$price,$img,$stock,$active,$id]);
echo json_encode(['ok'=>true]); exit;
}


if ($act === 'delete') {
$id = (int)($in['id'] ?? 0); if ($id<=0) throw new Exception('missing id');
try {
$pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
echo json_encode(['ok'=>true,'deleted'=>'hard']); exit;
} catch (PDOException $e) {
// ถ้าลบจริงไม่ได้เพราะติด FK (เช่น มีในออเดอร์) → soft delete
if ($e->getCode()==='23000') { // Integrity constraint
$pdo->prepare('UPDATE products SET is_active=0 WHERE id=?')->execute([$id]);
echo json_encode(['ok'=>true,'deleted'=>'soft']); exit;
}
throw $e;
}
}


throw new Exception('unknown action');
} catch (Throwable $e) {
http_response_code(400); echo json_encode(['message'=>$e->getMessage()?:'error']);
}
exit;
}


http_response_code(405); echo 'Method Not Allowed';