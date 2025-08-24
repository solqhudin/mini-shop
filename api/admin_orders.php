<?php
// api/admin_orders.php — สำหรับแอดมินดู/ค้น/อัปเดตสถานะคำสั่งซื้อ
require_once __DIR__ . '/../config.php';
require_admin_json();                         // ต้องล็อกอินแอดมินก่อนเสมอ
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  // ถ้าใส่ ?id= จะดึงรายละเอียดคำสั่งซื้อ + รายการสินค้า
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id > 0) {
    $o = $pdo->prepare('SELECT id, code, customer_name, phone, address, total_satang, status, created_at FROM orders WHERE id = ?');
    $o->execute([$id]);
    $order = $o->fetch();
    if (!$order) { http_response_code(404); echo json_encode(['message'=>'not found']); exit; }

    $it = $pdo->prepare('
      SELECT oi.product_id, p.name, oi.qty, oi.price_satang,
             (oi.qty * oi.price_satang) AS line_total
      FROM order_items oi
      LEFT JOIN products p ON p.id = oi.product_id
      WHERE oi.order_id = ?
      ORDER BY oi.id ASC
    ');
    $it->execute([$id]);
    $items = $it->fetchAll();

    echo json_encode(['order'=>$order, 'items'=>$items], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ไม่ใส่ id = ดึงรายการแบบหน้าละ N (มี filter พื้นฐาน)
  $limit  = max(1, min(100, (int)($_GET['limit'] ?? 20)));
  $page   = max(1, (int)($_GET['page'] ?? 1));
  $offset = ($page - 1) * $limit;
  $status = trim($_GET['status'] ?? '');     // pending/paid/shipped/completed/cancelled
  $q      = trim($_GET['q'] ?? '');          // ค้น code หรือชื่อ

  $where = [];
  $args  = [];
  if ($status !== '') { $where[] = 'o.status = ?'; $args[] = $status; }
  if ($q !== '')      { $where[] = '(o.code LIKE ? OR o.customer_name LIKE ?)'; $args[]="%$q%"; $args[]="%$q%"; }
  $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

  $sql = "
    SELECT o.id, o.code, o.customer_name, o.total_satang, o.status, o.created_at,
           COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    $whereSql
    GROUP BY o.id
    ORDER BY o.id DESC
    LIMIT $limit OFFSET $offset
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($args);
  $rows = $stmt->fetchAll();

  // นับทั้งหมดเพื่อทำหน้า
  $c = $pdo->prepare("SELECT COUNT(*) AS cnt FROM orders o $whereSql");
  $c->execute($args);
  $total = (int)$c->fetchColumn();

  echo json_encode(['data'=>$rows,'page'=>$page,'limit'=>$limit,'total'=>$total], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($method === 'POST') {
  // อัปเดตสถานะคำสั่งซื้อ
  $in = json_decode(file_get_contents('php://input'), true) ?? [];
  $act = $in['action'] ?? '';
  try {
    if ($act === 'update_status') {
      $id = (int)($in['id'] ?? 0);
      $status = trim($in['status'] ?? '');
      $allowed = ['pending','paid','shipped','completed','cancelled'];
      if ($id<=0) throw new Exception('missing id');
      if (!in_array($status, $allowed, true)) throw new Exception('invalid status');

      $st = $pdo->prepare('UPDATE orders SET status=? WHERE id=?');
      $st->execute([$status, $id]);
      echo json_encode(['ok'=>true]); exit;
    }
    throw new Exception('unknown action');
  } catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['message'=>$e->getMessage() ?: 'error']);
  }
  exit;
}

http_response_code(405);
echo 'Method Not Allowed';
