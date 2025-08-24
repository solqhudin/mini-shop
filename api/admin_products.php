<?php
// api/admin_products.php — ต้องล็อกอินแอดมิน
require_once __DIR__ . '/../config.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/**
 * Helper: แปลง input ตัวเลขให้เป็น "บาท (float)" อย่างปลอดภัย
 * - รองรับกรณีผู้ใช้พิมพ์ด้วยคอมมา/ช่องว่าง/สัญลักษณ์สกุลเงิน เช่น "1,420" หรือ "฿1,420.50"
 * - รองรับเลขไทย (๐๑๒๓๔๕๖๗๘๙)
 * - คืนค่าเป็น float (หน่วยบาท) แล้วค่อยปัดเป็นจำนวนเต็มใน norm_baht()
 */
function num_from_input($v): float
{
    // แทนที่เลขไทย -> อารบิก และลบตัวอักษรที่ไม่จำเป็นออก (ยกเว้น . และ -)
    $mapThaiDigits = ['๐' => '0', '๑' => '1', '๒' => '2', '๓' => '3', '๔' => '4', '๕' => '5', '๖' => '6', '๗' => '7', '๘' => '8', '๙' => '9'];
    $s = strtr((string)$v, $mapThaiDigits);
    // ลบคอมมา/ช่องว่าง/สัญลักษณ์สกุลเงินทั่วไป
    $s = str_replace([',', ' ', '฿', 'บาท', "\u{0E3F}" /* ฿ unicode */], '', $s);
    // เก็บไว้เฉพาะตัวเลข จุด ทศนิยม และเครื่องหมายลบตัวแรก
    $s = preg_replace('/(?!^)-|[^0-9.\-]/u', '', $s);
    if ($s === '' || $s === '.' || $s === '-' || $s === '-.') return 0.0;
    return (float)$s;
}

/**
 * แปลงอินพุตราคาให้เป็น "บาท (int)"
 * - รองรับทั้ง price_baht (อาจส่งมาเป็นสตริงมีคอมมา) และ price_satang (เก่า)
 * - ตัดค่าติดลบเป็น 0 เพื่อกันผิดพลาด
 */
function norm_baht(array $in): int
{
    if (array_key_exists('price_baht', $in)) {
        $b = num_from_input($in['price_baht']);     // e.g. "1,420" -> 1420.0
        $b = (int) round($b);                       // เก็บเป็นจำนวนเต็มบาท
        return max(0, $b);
    }
    if (array_key_exists('price_satang', $in)) {
        $s = (int) (preg_replace('/[^0-9\-]/', '', (string)$in['price_satang']) ?: 0);
        $b = (int) round($s / 100);
        return max(0, $b);
    }
    return 0;
}

if ($method === 'GET') {
    require_admin_json();
    header('Content-Type: application/json; charset=utf-8');

    // เก็บจริงใน DB = price_baht (int)
    // เพื่อความเข้ากันได้กับ UI เดิมที่ยังอ้าง price_satang อยู่ → คืนทั้งสองฟิลด์
    $st = $pdo->query('
        SELECT
            id,
            name,
            description,
            price_baht,
            (price_baht * 100) AS price_satang,
            image_url,
            stock,
            is_active
        FROM products
        ORDER BY id DESC
    ');
    echo json_encode($st->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    require_admin_json();
    header('Content-Type: application/json; charset=utf-8');

    $in  = json_decode(file_get_contents('php://input'), true) ?? [];
    $act = $in['action'] ?? '';

    try {
        if ($act === 'create') {
            $name = trim($in['name'] ?? '');
            if ($name === '') throw new Exception('name required');

            $desc   = $in['description'] ?? null;
            $price  = norm_baht($in);                 // <<< เก็บเป็นบาท (int)
            $stock  = (int)($in['stock'] ?? 0);
            $img    = trim($in['image_url'] ?? '');
            $active = (int)($in['is_active'] ?? 1);

            $st = $pdo->prepare('
                INSERT INTO products (name,description,price_baht,image_url,stock,is_active)
                VALUES (?,?,?,?,?,?)
            ');
            $st->execute([$name, $desc, $price, $img, $stock, $active]);

            echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
            exit;
        }

        if ($act === 'update') {
            $id = (int)($in['id'] ?? 0);
            if ($id <= 0) throw new Exception('missing id');

            $name   = trim($in['name'] ?? '');
            $desc   = $in['description'] ?? null;
            $price  = norm_baht($in);                 // <<< เก็บเป็นบาท (int)
            $stock  = (int)($in['stock'] ?? 0);
            $img    = trim($in['image_url'] ?? '');
            $active = (int)($in['is_active'] ?? 1);

            $st = $pdo->prepare('
                UPDATE products
                SET name=?, description=?, price_baht=?, image_url=?, stock=?, is_active=?
                WHERE id=?
            ');
            $st->execute([$name, $desc, $price, $img, $stock, $active, $id]);

            echo json_encode(['ok' => true]);
            exit;
        }

        if ($act === 'delete') {
            $id = (int)($in['id'] ?? 0);
            if ($id <= 0) throw new Exception('missing id');

            try {
                $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
                echo json_encode(['ok' => true, 'deleted' => 'hard']);
                exit;
            } catch (PDOException $e) {
                // ถ้าลบจริงไม่ได้เพราะติด FK (เช่น มีในออเดอร์) → soft delete
                if ($e->getCode() === '23000') { // Integrity constraint
                    $pdo->prepare('UPDATE products SET is_active=0 WHERE id=?')->execute([$id]);
                    echo json_encode(['ok' => true, 'deleted' => 'soft']);
                    exit;
                }
                throw $e;
            }
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
