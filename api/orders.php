<?php
// api/orders.php — รับออเดอร์ + ตัดสต๊อก (Transaction + FOR UPDATE)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';


$in = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($in['customer_name'] ?? '');
$phone = trim($in['phone'] ?? '');
$address = trim($in['address'] ?? '');
$items = $in['items'] ?? [];


if ($name === '' || !is_array($items) || count($items)===0) {
http_response_code(400); echo json_encode(['message'=>'ข้อมูลไม่ครบถ้วน']); exit;
}


try {
$pdo->beginTransaction();


$sel = $pdo->prepare('SELECT id,name,price_baht,stock FROM products WHERE id=? FOR UPDATE');
$validated = [];
$total = 0;


foreach ($items as $it) {
$pid = (int)($it['product_id'] ?? 0); $qty = (int)($it['qty'] ?? 0);
if ($pid<=0 || $qty<=0) throw new Exception('ไอเท็มไม่ถูกต้อง');
$sel->execute([$pid]); $p = $sel->fetch(); if (!$p) throw new Exception("ไม่พบสินค้า id=$pid");
if ($p['stock'] < $qty) throw new Exception('สต๊อกสินค้าไม่พอ: '.$p['name']);
$total += $p['price_baht'] * $qty;
$validated[] = ['product_id'=>(int)$p['id'], 'qty'=>$qty, 'price_baht'=>(int)$p['price_baht']];
}


$code = 'OD'.random_int(100000,999999);
$insOrder = $pdo->prepare('INSERT INTO orders (code, customer_name, phone, address, total_baht) VALUES (?,?,?,?,?)');
$insOrder->execute([$code,$name,$phone?:null,$address?:null,$total]);
$orderId = (int)$pdo->lastInsertId();


$insItem = $pdo->prepare('INSERT INTO order_items (order_id,product_id,qty,price_baht) VALUES (?,?,?,?)');
$upd = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
foreach ($validated as $v) { $insItem->execute([$orderId,$v['product_id'],$v['qty'],$v['price_baht']]); $upd->execute([$v['qty'],$v['product_id']]); }


$pdo->commit();
echo json_encode(['order_id'=>$orderId,'code'=>$code], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
if ($pdo->inTransaction()) $pdo->rollBack();
http_response_code(400); echo json_encode(['message'=>$e->getMessage()?:'สร้างออเดอร์ไม่สำเร็จ'], JSON_UNESCAPED_UNICODE);
}