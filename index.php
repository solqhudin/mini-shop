<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mini Shop</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Kanit', 'ui-sans-serif', 'system-ui']
          }
        }
      }
    };
  </script>
  <style>
    body {
      font-family: 'Kanit', sans-serif;
    }
  </style>
</head>

<body class="min-h-screen bg-slate-50">
  <div id="toast" class="fixed top-4 right-4 z-50 hidden"></div>

  <header class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
      <a href="#" class="text-2xl font-bold tracking-tight">Mini Shop</a>
      <div class="flex items-center gap-2">
        <a id="btnDiag"></a>
        <a href="./admin_login.php" class="px-3 py-2 rounded-xl border hidden sm:inline">แอดมิน</a>
        <button id="btnCart" class="relative px-4 py-2 rounded-2xl border shadow-sm hover:shadow">ตะกร้า <span id="cartCount" class="ml-1 inline-flex items-center justify-center min-w-[1.5rem] h-6 text-sm rounded-full bg-black/90 text-white px-1">0</span></button>
      </div>
    </div>
  </header>
  <div class="max-w-7xl mx-auto px-4 py-5 grid md:grid-cols-2 gap-8 items-center"></div>

  <main id="products" class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-6">
      <h2 id="btnDiag2" class="text-2xl font-semibold">สินค้า</h2>
    </div>
    <div id="grid" class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"></div>

    <!-- Diagnostics (ซ่อนอยู่จนกดปุ่ม) -->
    <div id="diag" class="hidden mt-8">
      <div class="bg-white rounded-3xl shadow p-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Diagnostics</h3>
          <button id="btnDiagClose" class="px-3 py-1 rounded-xl border">ปิด</button>
        </div>
        <p class="text-sm text-slate-600 mt-2">ตรวจเส้นทาง API อัตโนมัติและแสดงผลลัพธ์แต่ละ candidate</p>
        <pre id="diagOut" class="mt-3 bg-slate-900 text-slate-100 rounded-2xl p-3 overflow-x-auto text-xs"></pre>
      </div>
    </div>
  </main>

  <!-- Cart Drawer -->
  <aside id="drawer" class="fixed top-0 right-0 h-full w-full sm:w-[28rem] bg-white shadow-2xl translate-x-full transition-transform z-40">
    <div class="flex items-center justify-between px-4 py-3 border-b sticky top-0 bg-white/90 backdrop-blur">
      <h3 class="text-xl font-semibold">ตะกร้าสินค้า</h3>
      <button id="btnCloseDrawer" class="p-2 rounded-xl hover:bg-slate-100">✕</button>
    </div>
    <div id="cartItems" class="p-4 space-y-3 max-h-[calc(100%-13rem)] overflow-auto"></div>
    <div class="p-4 border-t sticky bottom-0 bg-white">
      <div class="flex items-center justify-between mb-3">
        <span class="text-slate-600">ยอดรวม</span>
        <span id="cartTotal" class="text-xl font-semibold">฿0.00</span>
      </div>
      <button id="btnCheckout" class="w-full px-4 py-3 rounded-2xl bg-black text-white font-semibold hover:opacity-90">ชำระเงิน/กรอกที่อยู่</button>
    </div>
  </aside>

  <!-- Checkout Modal -->
  <div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
          <h3 class="text-xl font-semibold">ข้อมูลการจัดส่ง</h3>
          <button id="btnCloseModal" class="p-2 rounded-xl hover:bg-slate-100">✕</button>
        </div>
        <form id="checkoutForm" class="p-6 space-y-4">
          <div>
            <label class="block mb-1">ชื่อ-นามสกุล *</label>
            <input name="customer_name" required class="w-full px-4 py-2 rounded-xl border focus:outline-none focus:ring" />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-1">เบอร์โทร</label>
              <input name="phone" class="w-full px-4 py-2 rounded-xl border focus:outline-none focus:ring" />
            </div>
            <div>
              <label class="block mb-1">รหัสไปรษณีย์</label>
              <input name="postal" class="w-full px-4 py-2 rounded-xl border focus:outline-none focus:ring" />
            </div>
          </div>
          <div>
            <label class="block mb-1">ที่อยู่</label>
            <textarea name="address" rows="3" class="w-full px-4 py-2 rounded-xl border focus:outline-none focus:ring"></textarea>
          </div>
          <button class="w-full px-4 py-3 rounded-2xl bg-black text-white font-semibold hover:opacity-90">ยืนยันการสั่งซื้อ</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Product Detail Modal -->
  <div id="detailModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
          <h3 id="d_name" class="text-xl font-semibold">รายละเอียดสินค้า</h3>
          <button id="btnCloseDetail" class="p-2 rounded-xl hover:bg-slate-100">✕</button>
        </div>
        <div class="grid md:grid-cols-2 gap-0">
          <div class="aspect-square md:aspect-auto md:h-full overflow-hidden bg-slate-100">
            <img id="d_img" src="" alt="" class="w-full h-full object-cover" onerror="this.src='https://via.placeholder.com/600x450?text=No+Image'" />
          </div>
          <div class="p-6 space-y-3">
            <div class="text-2xl font-bold" id="d_price">฿0.00</div>
            <div class="text-sm text-slate-600">สต๊อก: <span id="d_stock">0</span></div>
            <p id="d_desc" class="text-slate-700 whitespace-pre-wrap"></p>
            <button id="btnAddDetail" class="w-full px-4 py-3 rounded-2xl bg-black text-white font-semibold hover:opacity-90">เพิ่มลงตะกร้า</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <footer class="py-10 text-center text-sm text-slate-500">BY SOLAHUDIN DOLOH <span id="year"></span></footer>

  <script>
    // ---------- helpers ----------
    const fmtTHB = new Intl.NumberFormat('th-TH', {
      style: 'currency',
      currency: 'THB'
    });
    const state = {
      products: [],
      cart: new Map(),
      detail: null
    };
    const el = id => document.getElementById(id);
    const grid = el('grid');
    const drawer = el('drawer');
    const cartItems = el('cartItems');
    const cartTotal = el('cartTotal');
    const cartCount = el('cartCount');
    const toast = el('toast');
    const diag = el('diag');
    const diagOut = el('diagOut');
    el('year').textContent = new Date().getFullYear();

    // ราคา: รองรับทั้งหน่วย "บาท" หรือ "สตางค์"
    const toSatang = v => {
      const n = Number(v) || 0;
      return n >= 1000 ? n : n * 100;
    };
    const formatPrice = v => {
      const n = Number(v) || 0;
      const baht = n >= 1000 ? n / 100 : n;
      return fmtTHB.format(baht);
    };

    const escapeHtml = (str = '') => String(str).replace(/[&<>"']/g, m => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      "\"": "&quot;",
      "'": "&#39;"
    } [m]));
    const safeUrl = (u = '') => /^https?:\/\//i.test(u) ? u : '#';

    // ✅ FIX: พิมพ์ selector ผิด ทำให้สคริปต์พัง
    const META_API_BASE = (document.querySelector('meta[name="api-base"]')?.content || '').trim();

    function unique(arr) {
      return [...new Set(arr)];
    }

    function buildCandidates(path) {
      const {
        origin,
        pathname
      } = window.location;
      const dir = pathname.endsWith('/') ? pathname : pathname.replace(/[^/]+$/, '/');
      const firstSeg = '/' + (pathname.split('/').filter(Boolean)[0] || '') + '/';
      const fromMeta = META_API_BASE ? new URL(path, META_API_BASE.endsWith('/') ? META_API_BASE : META_API_BASE + '/').href : null;
      return unique([fromMeta, new URL(`api/${path}`, origin + dir).href, new URL(`api/${path}`, origin + firstSeg).href, new URL(`/api/${path}`, origin).href].filter(Boolean));
    }

    async function fetchJSONFromCandidates(path, options = {}) {
      const errors = [];
      for (const url of buildCandidates(path)) {
        try {
          const res = await fetch(url, {
            ...options,
            headers: {
              'Accept': 'application/json',
              ...(options.headers || {})
            }
          });
          const ct = (res.headers.get('content-type') || '').toLowerCase();
          if (!ct.includes('application/json')) {
            const text = await res.text();
            errors.push({
              url,
              status: res.status,
              body: text.slice(0, 200)
            });
            continue;
          }
          const data = await res.json();
          if (!res.ok) {
            errors.push({
              url,
              status: res.status,
              body: (data && data.message) || JSON.stringify(data).slice(0, 200)
            });
            continue;
          }
          return {
            data,
            url,
            errors
          };
        } catch (e) {
          errors.push({
            url,
            status: 'network',
            body: String(e)
          });
        }
      }
      const err = new Error('ทุก endpoint ล้มเหลว');
      err.details = errors;
      throw err;
    }

    function showToast(msg, type = 'ok') {
      toast.className = 'fixed top-4 right-4 z-50';
      const box = document.createElement('div');
      box.className = `mb-2 px-4 py-3 rounded-2xl shadow text-white ${type==='ok'?'bg-emerald-600':'bg-amber-600'}`;
      box.textContent = msg;
      toast.appendChild(box);
      setTimeout(() => box.remove(), 2200);
    }

    // ---------- drawers & modals ----------
    el('btnCart').addEventListener('click', () => drawer.classList.remove('translate-x-full'));
    el('btnCloseDrawer').addEventListener('click', () => drawer.classList.add('translate-x-full'));
    const modal = el('modal');
    el('btnCheckout').addEventListener('click', () => {
      if (state.cart.size === 0) return showToast('ตะกร้าว่าง', 'warn');
      modal.classList.remove('hidden');
    });
    el('btnCloseModal').addEventListener('click', () => modal.classList.add('hidden'));

    const detailModal = el('detailModal');
    el('btnCloseDetail').addEventListener('click', () => detailModal.classList.add('hidden'));
    el('btnAddDetail').addEventListener('click', () => {
      if (!state.detail) return;
      addToCart(state.detail.id);
      detailModal.classList.add('hidden');
    });

    // ---------- API calls ----------
    async function loadProducts() {
      try {
        const {
          data
        } = await fetchJSONFromCandidates('products.php');
        state.products = Array.isArray(data) ? data : [];
        renderProducts();
        if (state.products.length === 0) showToast('ยังไม่มีสินค้า หรือสต๊อกหมด', 'warn');
      } catch (err) {
        console.error(err);
        showToast('โหลดสินค้าไม่สำเร็จ: ตรวจเส้นทาง API', 'warn');
        renderDiagnostics('products.php', err.details || []);
      }
    }

    async function showDetail(id) {
      const local = state.products.find(x => Number(x.id) === Number(id));
      if (local) {
        openDetail(local);
        return;
      }
      try {
        const {
          data
        } = await fetchJSONFromCandidates(`product.php?id=${encodeURIComponent(id)}`);
        openDetail(data);
      } catch (err) {
        console.error(err);
        showToast('โหลดรายละเอียดสินค้าไม่สำเร็จ', 'warn');
        renderDiagnostics(`product.php?id=${encodeURIComponent(id)}`, err.details || []);
      }
    }

    // ---------- rendering ----------
    function productCard(p) {
      const low = Number(p.stock) <= 5;
      const card = document.createElement('div');
      card.className = 'group bg-white rounded-3xl shadow hover:shadow-lg transition overflow-hidden border';
      card.innerHTML = `
        <button data-show="${p.id}" class="block w-full text-left">
          <div class="aspect-[4/3] relative overflow-hidden">
            <img src="${safeUrl(p.image_url||'')}" alt="${escapeHtml(p.name||'')}"
                 class="w-full h-full object-cover group-hover:scale-105 transition"
                 onerror="this.src='https://via.placeholder.com/600x450?text=No+Image'"/>
            ${low ? '<span class="absolute top-3 left-3 text-xs px-2 py-1 bg-amber-100 text-amber-800 rounded-full">ใกล้หมด</span>' : ''}
          </div>
        </button>
        <div class="p-4">
          <div class="flex items-baseline justify-between gap-3">
            <button data-show="${p.id}" class="text-left">
              <h3 class="text-lg font-semibold line-clamp-1">${escapeHtml(p.name||'')}</h3>
            </button>
            <div class="text-right">
              <div class="text-xl font-bold">${formatPrice(p.price_satang)}</div>
              <div class="text-xs text-slate-500">สต๊อก: ${p.stock ?? 0}</div>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-2 mt-4">
            <button data-show="${p.id}" class="px-4 py-2 rounded-2xl border">ดูรายละเอียด</button>
            <button data-id="${p.id}" class="px-4 py-2 rounded-2xl bg-black text-white font-semibold hover:opacity-90">เพิ่มลงตะกร้า</button>
          </div>
        </div>`;
      return card;
    }

    function renderProducts() {
      grid.innerHTML = '';
      state.products.forEach(p => grid.appendChild(productCard(p)));
      grid.querySelectorAll('button[data-id]').forEach(btn => btn.addEventListener('click', () => addToCart(Number(btn.dataset.id))));
      grid.querySelectorAll('button[data-show]').forEach(btn => btn.addEventListener('click', () => showDetail(Number(btn.dataset.show))));
    }

    // ---------- cart ----------
    function addToCart(productId) {
      const p = state.products.find(x => x.id === productId);
      if (!p) return;
      const curr = state.cart.get(productId) || {
        product: p,
        qty: 0
      };
      if (curr.qty + 1 > Number(p.stock)) return showToast('เกินสต๊อกที่มี', 'warn');
      curr.qty += 1;
      state.cart.set(productId, curr);
      renderCart();
      showToast(`เพิ่ม "${p.name}" ลงตะกร้าแล้ว`, 'ok');
    }

    function renderCart() {
      cartItems.innerHTML = '';
      let total = 0,
        count = 0;
      for (const {
          product: p,
          qty
        }
        of state.cart.values()) {
        total += toSatang(p.price_satang) * qty;
        count += qty;
        const row = document.createElement('div');
        row.className = 'flex gap-3 items-center p-2 rounded-xl border';
        row.innerHTML = `
          <img src="${safeUrl(p.image_url||'')}" class="w-16 h-16 rounded-xl object-cover" onerror="this.src='https://via.placeholder.com/64?text=?'"/>
          <div class="flex-1">
            <div class="font-medium line-clamp-1">${escapeHtml(p.name||'')}</div>
            <div class="text-slate-500 text-sm">${formatPrice(p.price_satang)} × ${qty}</div>
          </div>
          <div class="flex items-center gap-2">
            <button class="px-2 py-1 rounded-lg border" data-dec="${p.id}">-</button>
            <span>${qty}</span>
            <button class="px-2 py-1 rounded-lg border" data-inc="${p.id}">+</button>
            <button class="ml-2 p-2 rounded-lg hover:bg-slate-100" data-del="${p.id}">ยกเลิก</button>`;
        cartItems.appendChild(row);
      }
      cartTotal.textContent = fmtTHB.format(total / 100);
      cartCount.textContent = count;
      cartItems.querySelectorAll('[data-inc]').forEach(b => b.addEventListener('click', () => changeQty(Number(b.dataset.inc), +1)));
      cartItems.querySelectorAll('[data-dec]').forEach(b => b.addEventListener('click', () => changeQty(Number(b.dataset.dec), -1)));
      cartItems.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => {
        state.cart.delete(Number(b.dataset.del));
        renderCart();
      }));
    }

    function changeQty(productId, delta) {
      const entry = state.cart.get(productId);
      if (!entry) return;
      const newQty = entry.qty + delta;
      if (newQty <= 0) {
        state.cart.delete(productId);
      } else {
        if (newQty > Number(entry.product.stock)) return showToast('เกินสต๊อกที่มี', 'warn');
        entry.qty = newQty;
        state.cart.set(productId, entry);
      }
      renderCart();
    }

    function openDetail(p) {
      state.detail = p;
      el('d_name').textContent = p.name || '';
      el('d_img').src = safeUrl(p.image_url || '');
      el('d_img').alt = p.name || '';
      el('d_price').textContent = formatPrice(p.price_satang);
      el('d_stock').textContent = p.stock ?? 0;
      el('d_desc').textContent = p.description || '';
      detailModal.classList.remove('hidden');
    }

    // ---------- checkout ----------
    document.getElementById('checkoutForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const payload = {
        customer_name: fd.get('customer_name'),
        phone: fd.get('phone') || '',
        address: `${fd.get('address') || ''}`,
        items: Array.from(state.cart.values()).map(({
          product,
          qty
        }) => ({
          product_id: product.id,
          qty
        }))
      };
      try {
        const {
          data
        } = await fetchJSONFromCandidates('orders.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        });
        modal.classList.add('hidden');
        drawer.classList.add('translate-x-full');
        state.cart.clear();
        renderCart();
        showToast(`สั่งซื้อสำเร็จ! รหัสออเดอร์ ${data.code}`, 'ok');
        await loadProducts();
      } catch (err) {
        console.error(err);
        showToast('สั่งซื้อไม่สำเร็จ: ตรวจเส้นทาง API', 'warn');
        renderDiagnostics('orders.php', err.details || []);
      }
    });

    // ---------- diagnostics UI ----------
    function renderDiagnostics(path, details) {
      const lines = [
        `Path ทดสอบ: ${path}`,
        'Candidates ที่ลอง (สถานะ/ตัวอย่างเนื้อหา):',
        ...details.map(d => `- ${d.url} -> ${d.status}\n  ${String(d.body||'').replace(/\n/g,' ').slice(0,140)}`)
      ];
      diagOut.textContent = lines.join('\n');
      diag.classList.remove('hidden');
    }
    el('btnDiag').addEventListener('click', async () => {
      try {
        await loadProducts();
      } catch {}
    });
    el('btnDiag2').addEventListener('click', async () => {
      try {
        await loadProducts();
      } catch {}
    });
    el('btnDiagClose').addEventListener('click', () => diag.classList.add('hidden'));

    // boot
    loadProducts();
  </script>
</body>

</html>