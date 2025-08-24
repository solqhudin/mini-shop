<?php
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pw = $_POST['password'] ?? '';
  if ($pw === ADMIN_PASSWORD) {
    $_SESSION['is_admin'] = true;
    header('Location: ./admin.php');
    exit;
  }
  $error = 'รหัสผ่านไม่ถูกต้อง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
  <style>body{font-family:'Kanit',sans-serif}</style>
</head>
<body class="min-h-screen bg-slate-50 grid place-items-center p-4">
  <a href="./index.php"
     class="fixed top-4 left-4 px-4 py-2 rounded-2xl bg-black text-white hover:bg-black/90 focus:outline-none focus:ring-2 focus:ring-black/50">
    กลับไปหน้าแรก
  </a>

  <form method="post" class="w-full max-w-sm bg-white rounded-3xl shadow p-6 space-y-4">
    <h1 class="text-2xl font-semibold">เข้าสู่ระบบแอดมิน</h1>
    <?php if (!empty($error)): ?>
      <div class="px-3 py-2 rounded-xl bg-amber-100 text-amber-800">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    <div>
      <label class="block mb-1">รหัสผ่าน</label>
      <input type="password" name="password" required
             class="w-full px-4 py-2 rounded-xl border focus:outline-none focus:ring">
    </div>
    <button class="w-full px-4 py-2 rounded-2xl bg-black text-white hover:bg-black/90">
      เข้าสู่ระบบ
    </button>
  </form>
</body>
</html>
