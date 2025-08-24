# Project mini-shop
## เว็บตัวอย่างร้านค้าเล็ก ๆ ด้วย PHP + MySQL (XAMPP) และ Tailwind CSS
#### 1.แสดงสินค้า, เพิ่มลงตะกร้า, ยืนยันคำสั่งซื้อ (สร้างออเดอร์ + ตัดสต๊อกแบบ Transaction)
#### 2.หน้าผู้ดูแล (แอดมิน) สำหรับเพิ่ม/แก้ไข/ลบสินค้า และดูคำสั่งซื้อ พร้อมอัปเดตสถานะ

### คุณสมบัติหลัก
#### 🛒 รายการสินค้า + ตะกร้า + สรุปยอด (แสดงเป็นสกุล THB)
#### 🧾 สร้างออเดอร์: บันทึกลูกค้า, เก็บราคาต่อหน่วย ณ ตอนซื้อ, ตัดสต๊อกแบบ Transaction + FOR UPDATE
#### 🔐 แอดมินล็อกอิน (session) → จัดการสินค้า (CRUD), เปิด/ปิดขาย, ดูรายการออเดอร์/เปลี่ยนสถานะ

### วิธีติดตั้งใช้งาน
#### 1. git clone https://github.com/solqhudin/mini-shop.git ในที่ C:\xampp\htdocs\ (Windows) หรือ /Applications/XAMPP/htdocs/(macOS)
#### เปิด phpMyAdmin → สร้างฐานข้อมูลชื่อ shopdb (Collation: utf8mb4_unicode_ci)
#### รัน SQL สร้างตาราง 

    USE shopdb;
    -- ตารางสินค้า (เก็บราคาเป็นบาท)
    CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price_baht INT NOT NULL, -- ราคาเป็น "บาท" (จำนวนเต็ม)
    image_url TEXT,
    stock INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


    -- ตารางออเดอร์ (บันทึกยอดรวมเป็นบาท)
    CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    address TEXT,
    total_baht INT NOT NULL,
    status ENUM('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


    -- รายการสินค้าในออเดอร์ (snapshot ราคาเป็นบาท ณ ตอนซื้อ)
    CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    qty INT NOT NULL,
    price_baht INT NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


    CREATE INDEX idx_products_active_stock ON products(is_active, stock);
    CREATE INDEX idx_orders_status_created ON orders(status, created_at);

#### 5.เปิดไฟล์ config.php และแก้ค่าการเชื่อมต่อฐานข้อมูล + เปลี่ยนรหัสแอดมิน
#### 6.เปิดเบราว์เซอร์: http://localhost/mini-shop-php/ (หน้าลูกค้า)
#### 7.แอดมิน: http://localhost/mini-shop-php/admin_login.php → เข้าสู่ระบบ → จัดการสินค้า/คำสั่งซื้อ