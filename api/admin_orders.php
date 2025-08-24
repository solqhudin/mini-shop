<?php
// api/admin_orders.php — สำหรับแอดมินดู/ค้น/อัปเดตสถานะคำสั่งซื้อ (หน่วย "บาท")
require_once __DIR__ . '/../config.php';
require_admin_json(); // ต้องล็อกอินแอดมินก่อนเสมอ
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  // ----- ดูคำสั่งซื้อรายตัว -----
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id > 0) {
    // header ออเดอร์ (ไม่ใช้ total_* จากตาราง เพื่อกัน schema เก่า/ใหม่)
    $o = $pdo->prepare('SELECT id, code, customer_name, phone, address, status, created_at FROM orders WHERE id = ?');
    $o->execute([$id]);
    $order = $o->fetch();
    if (!$order) {
      http_response_code(404);
      echo json_encode(['message' => 'not found']);
      exit;
    }

    // รายการสินค้า: หน่วย "บาท"
    $it = $pdo->prepare('
      SELECT
        oi.product_id,
        p.name,
        oi.qty,
        oi.price_baht,
        (oi.qty * oi.price_baht) AS line_total
      FROM order_items oi
      LEFT JOIN products p ON p.id = oi.product_id
      WHERE oi.order_id = ?
      ORDER BY oi.id ASC
    ');
    $it->execute([$id]);
    $items = $it->fetchAll();

    // คุมชนิด/คำนวณยอดรวม และทำ field compatibility
    $total_baht = 0.0;
    foreach ($items as &$row) {
      $row['qty']         = (int)$row['qty'];
      $row['price_baht']  = (float)$row['price_baht'];
      $row['line_total']  = (float)$row['line_total']; // บาท
      $total_baht        += $row['line_total'];

      // เพิ่มฟิลด์เก่าเพื่อความเข้ากันได้ (หน้าแอดมินเดิมอาจอ่าน price_satang)
      $row['price_satang'] = (int)round($row['price_baht'] * 100);
    }
    unset($row);

    // ใส่ยอดรวมทั้งสองหน่วย (บาท + สตางค์ เผื่อโค้ดหน้าแอดมินเดิม)
    $order['total_baht']   = (float)$total_baht;
    $order['total_satang'] = (int)round($total_baht * 100);

    echo json_encode(['order' => $order, 'items' => $items], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ----- ลิสต์คำสั่งซื้อแบบหน้าละ N -----
  $limit  = max(1, min(100, (int)($_GET['limit'] ?? 20)));
  $page   = max(1, (int)($_GET['page'] ?? 1));
  $offset = ($page - 1) * $limit;
  $status = trim($_GET['status'] ?? ''); // pending/paid/shipped/completed/cancelled
  $q      = trim($_GET['q'] ?? '');      // ค้น code หรือชื่อ

  $where = [];
  $args  = [];
  if ($status !== '') {
    $where[] = 'o.status = ?';
    $args[] = $status;
  }
  if ($q !== '') {
    $where[] = '(o.code LIKE ? OR o.customer_name LIKE ?)';
    $args[] = "%$q%";
    $args[] = "%$q%";
  }
  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  // คิดยอดรวมเป็น "บาท" จากรายการสินค้าเสมอ → ไม่พึ่งพา total_* ในตาราง orders
  $sql = "
    SELECT
      o.id,
      o.code,
      o.customer_name,
      o.status,
      o.created_at,
      COALESCE(SUM(oi.qty * oi.price_baht), 0) AS total_baht,
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

  // แปลงชนิด + เติม total_satang เพื่อความเข้ากันได้
  foreach ($rows as &$r) {
    $r['item_count']    = (int)$r['item_count'];
    $r['total_baht']    = (float)$r['total_baht'];
    $r['total_satang']  = (int)round($r['total_baht'] * 100);
  }
  unset($r);

  // นับทั้งหมดเพื่อทำหน้า
  $c = $pdo->prepare("SELECT COUNT(*) AS cnt FROM orders o $whereSql");
  $c->execute($args);
  $total = (int)$c->fetchColumn();

  echo json_encode(['data' => $rows, 'page' => $page, 'limit' => $limit, 'total' => $total], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($method === 'POST') {
  // อัปเดตสถานะคำสั่งซื้อ
  $in  = json_decode(file_get_contents('php://input'), true) ?? [];
  $act = $in['action'] ?? '';
  try {
    if ($act === 'update_status') {
      $id     = (int)($in['id'] ?? 0);
      $status = trim($in['status'] ?? '');
      $allowed = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
      if ($id <= 0) throw new Exception('missing id');
      if (!in_array($status, $allowed, true)) throw new Exception('invalid status');

      $st = $pdo->prepare('UPDATE orders SET status=? WHERE id=?');
      $st->execute([$status, $id]);
      echo json_encode(['ok' => true]);
      exit;
    }
    throw new Exception('unknown action');
  } catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['message' => $e->getMessage() ?: 'error']);
  }
  exit;
}

http_response_code(405);
echo 'Method Not Allowed';
