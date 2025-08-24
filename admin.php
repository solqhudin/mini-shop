<?php
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ./admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — จัดการสินค้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50">
    <header class="sticky top-0 z-40 backdrop-blur bg-white/70 border-b">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <h1 class="text-2xl font-bold">แอดมิน — จัดการสินค้า</h1>
            <a href="./admin_logout.php" class="px-4 py-2 rounded-2xl border shadow hover:bg-slate-100">ออกจากระบบ</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div id="toast" class="fixed top-4 right-4 z-50"></div>

        <!-- เพิ่มสินค้าใหม่ -->
        <section class="mb-6">
            <div class="bg-white rounded-3xl shadow p-4">
                <h2 class="text-xl font-semibold mb-4">เพิ่มสินค้าใหม่</h2>
                <div class="grid md:grid-cols-6 gap-3">
                    <input id="n_name" placeholder="ชื่อสินค้า" class="px-3 py-2 rounded-xl border">
                    <input id="n_price" placeholder="ราคา (สตางค์)" class="px-3 py-2 rounded-xl border">
                    <input id="n_stock" placeholder="สต๊อก" class="px-3 py-2 rounded-xl border">
                    <input id="n_image" placeholder="Image URL" class="px-3 py-2 rounded-xl border md:col-span-2">
                    <select id="n_active" class="px-3 py-2 rounded-xl border">
                        <option value="1" selected>แสดง</option>
                        <option value="0">ซ่อน</option>
                    </select>
                    <textarea id="n_desc" placeholder="รายละเอียดสินค้า" class="px-3 py-2 rounded-xl border md:col-span-6"></textarea>
                    <button id="btnCreate" class="px-4 py-2 rounded-2xl bg-black text-white w-full md:w-auto">เพิ่มสินค้า</button>
                </div>
            </div>
        </section>

        <!-- รายการสินค้า -->
        <section class="bg-white rounded-3xl shadow overflow-hidden">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <h2 class="text-xl font-semibold">รายการสินค้า</h2>
                <button id="btnReload" class="px-3 py-2 rounded-xl border">รีโหลด</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="p-3 text-left">ID</th>
                            <th class="p-3 text-left">ชื่อ</th>
                            <th class="p-3 text-left">รายละเอียด</th>
                            <th class="p-3 text-left">ราคา (สต.)</th>
                            <th class="p-3 text-left">สต๊อก</th>
                            <th class="p-3 text-left">รูปภาพ</th>
                            <th class="p-3 text-left">สถานะ</th>
                            <th class="p-3 text-right">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody id="rows"></tbody>
                </table>
            </div>
        </section>

        <!-- ===== Orders section ===== -->
        <section class="bg-white rounded-3xl shadow overflow-hidden mt-8">
            <div class="px-4 py-3 border-b flex flex-wrap items-center gap-2 justify-between">
                <h2 class="text-xl font-semibold">คำสั่งซื้อ</h2>
                <div class="flex gap-2">
                    <select id="o_status" class="px-3 py-2 rounded-xl border">
                        <option value="">ทั้งหมด</option>
                        <option value="pending">pending</option>
                        <option value="paid">paid</option>
                        <option value="shipped">shipped</option>
                        <option value="completed">completed</option>
                        <option value="cancelled">cancelled</option>
                    </select>
                    <input id="o_q" placeholder="ค้นหา code/ชื่อ" class="px-3 py-2 rounded-xl border">
                    <button id="o_reload" class="px-3 py-2 rounded-xl border">รีโหลด</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="p-3 text-left">เวลา</th>
                            <th class="p-3 text-left">รหัส</th>
                            <th class="p-3 text-left">ลูกค้า</th>
                            <th class="p-3 text-right">ยอดรวม</th>
                            <th class="p-3 text-left">สถานะ</th>
                            <th class="p-3 text-right">การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody id="o_rows"></tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t flex items-center justify-between text-sm">
                <div id="o_pageinfo" class="text-slate-600"></div>
                <div class="flex gap-2">
                    <button id="o_prev" class="px-3 py-1 rounded-xl border">ก่อนหน้า</button>
                    <button id="o_next" class="px-3 py-1 rounded-xl border">ถัดไป</button>
                </div>
            </div>
        </section>

    </main>

    <script>
        // ===== helpers =====
        const fmtTHB = new Intl.NumberFormat('th-TH', {
            style: 'currency',
            currency: 'THB'
        });

        // แปลงเป็น "สตางค์": ถ้าน้อยกว่า 1000 ถือว่าเก็บเป็นบาท → คูณ 100 (รองรับข้อมูลเก่า)
        const toSatang = (v) => {
            const n = Number(v) || 0;
            return n >= 1000 ? n : n * 100;
        };
        const fmtSatang = (v) => fmtTHB.format(toSatang(v) / 100);

        const toast = (m, t = 'ok') => {
            const b = document.createElement('div');
            b.className = `mb-2 px-4 py-3 rounded-2xl shadow text-white ${t === 'ok' ? 'bg-emerald-600' : 'bg-amber-600'} fixed top-4 right-4 z-50`;
            b.textContent = m;
            document.body.appendChild(b);
            setTimeout(() => b.remove(), 2000)
        };

        // ===== Orders — list, view, update status =====
        let O_PAGE = 1,
            O_LIMIT = 20,
            O_TOTAL = 0;

        // cache รายละเอียดออเดอร์เพื่อไม่ยิง API ซ้ำ
        const ORDER_CACHE = new Map(); // id -> {order, items, sumSatang}

        async function fetchOrders() {
            const s = document.getElementById('o_status').value.trim();
            const q = document.getElementById('o_q').value.trim();
            const url = new URL('./api/admin_orders.php', location.href);
            url.searchParams.set('page', O_PAGE);
            url.searchParams.set('limit', O_LIMIT);
            if (s) url.searchParams.set('status', s);
            if (q) url.searchParams.set('q', q);
            const r = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!r.ok) {
                toast('โหลดคำสั่งซื้อไม่สำเร็จ', 'warn');
                return {
                    data: [],
                    total: 0
                };
            }
            const d = await r.json();
            O_TOTAL = d.total || 0;
            return d;
        }

        function renderOrdersTable(data) {
            const tb = document.getElementById('o_rows');
            const info = document.getElementById('o_pageinfo');
            tb.innerHTML = '';
            for (const o of data) {
                const tr = document.createElement('tr');
                tr.className = 'border-b last:border-0';
                tr.dataset.oid = o.id;
                tr.innerHTML = `
      <td class="p-3">${o.created_at}</td>
      <td class="p-3">${o.code}</td>
      <td class="p-3">${o.customer_name}</td>
      <td class="p-3 text-right" data-total>${fmtSatang(o.total_baht)}</td>
      <td class="p-3">
        <select class="px-2 py-1 rounded border" data-oid="${o.id}" data-status>
          ${['pending','paid','shipped','completed','cancelled'].map(st=>`<option value="${st}" ${o.status===st?'selected':''}>${st}</option>`).join('')}
        </select>
      </td>
      <td class="p-3 text-right">
        <button class="px-3 py-1 rounded-xl border" data-view="${o.id}">ดูรายการ (${o.item_count})</button>
      </td>`;
                tb.appendChild(tr);

                // คำนวณยอดรวมใหม่จากรายการสินค้าเสมอ แล้วอัปเดต cell ยอดรวม
                recalcAndPatchTotal(o.id).catch(() => {});
            }
            const lastPage = Math.max(1, Math.ceil(O_TOTAL / O_LIMIT));
            info.textContent = `หน้า ${O_PAGE}/${lastPage} — ทั้งหมด ${O_TOTAL} รายการ`;

            tb.querySelectorAll('[data-view]').forEach(b => b.addEventListener('click', () => viewOrder(Number(b.dataset.view))));
            tb.querySelectorAll('[data-status]').forEach(sel => sel.addEventListener('change', () => updateOrderStatus(Number(sel.dataset.oid), sel.value)));
        }

        async function loadOrders() {
            const d = await fetchOrders();
            renderOrdersTable(d.data || []);
        }

        // ดึงรายละเอียด → หาผลรวมแบบกันพลาด → อัปเดตช่องยอดรวมของแถว
        async function recalcAndPatchTotal(orderId) {
            let data = ORDER_CACHE.get(orderId);
            if (!data) {
                const url = new URL('./api/admin_orders.php', location.href);
                url.searchParams.set('id', orderId);
                const r = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const d = await r.json();
                if (!r.ok || !d?.items) return;
                const sumSatang = (d.items || []).reduce((acc, it) => {
                    const unit = toSatang(it.price_baht);
                    const line = it.line_total != null ? toSatang(it.line_total) : unit * (Number(it.qty) || 0);
                    return acc + line;
                }, 0);
                data = {
                    order: d.order,
                    items: d.items,
                    sumSatang
                };
                ORDER_CACHE.set(orderId, data);
            }
            const row = document.querySelector(`tr[data-oid="${orderId}"] [data-total]`);
            if (row) row.textContent = fmtTHB.format((data.sumSatang || 0) / 100);
        }

        async function viewOrder(id) {
            let d = ORDER_CACHE.get(id);
            if (!d) {
                const url = new URL('./api/admin_orders.php', location.href);
                url.searchParams.set('id', id);
                const r = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const j = await r.json();
                if (!r.ok) {
                    toast(j.message || 'ไม่พบออเดอร์', 'warn');
                    return;
                }
                const sumSatang = (j.items || []).reduce((acc, it) => {
                    const unit = toSatang(it.price_baht);
                    const line = it.line_total != null ? toSatang(it.line_total) : unit * (Number(it.qty) || 0);
                    return acc + line;
                }, 0);
                d = {
                    order: j.order,
                    items: j.items,
                    sumSatang
                };
                ORDER_CACHE.set(id, d);
            }
            showOrderModal(d.order, d.items);
        }

        async function updateOrderStatus(id, status) {
            const r = await fetch('./api/admin_orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update_status',
                    id,
                    status
                })
            });
            const d = await r.json().catch(() => ({}));
            if (!r.ok || !d.ok) return toast(d.message || 'อัปเดตสถานะไม่สำเร็จ', 'warn');
            toast('อัปเดตสถานะแล้ว');
        }

        // bind controls เมื่อ DOM พร้อม
        window.addEventListener('DOMContentLoaded', () => {
            const reloadBtn = document.getElementById('o_reload');
            if (reloadBtn) reloadBtn.addEventListener('click', () => {
                O_PAGE = 1;
                loadOrders();
            });
            const prevBtn = document.getElementById('o_prev');
            if (prevBtn) prevBtn.addEventListener('click', () => {
                if (O_PAGE > 1) {
                    O_PAGE--;
                    loadOrders();
                }
            });
            const nextBtn = document.getElementById('o_next');
            if (nextBtn) nextBtn.addEventListener('click', () => {
                const last = Math.max(1, Math.ceil(O_TOTAL / O_LIMIT));
                if (O_PAGE < last) {
                    O_PAGE++;
                    loadOrders();
                }
            });
            const statusSel = document.getElementById('o_status');
            if (statusSel) statusSel.addEventListener('change', () => {
                O_PAGE = 1;
                loadOrders();
            });

            const btnClose = document.getElementById('btnCloseOrderModal');
            if (btnClose) btnClose.addEventListener('click', () => {
                const m = document.getElementById('orderModal');
                if (m) m.classList.add('hidden');
            });

            loadOrders();
        });

        // ===== Products CRUD =====
        async function fetchList() {
            const r = await fetch('./api/admin_products.php');
            if (r.status === 403) {
                location.href = './admin_login.php';
                return [];
            }
            if (!r.ok) {
                toast('โหลดสินค้าไม่สำเร็จ', 'warn');
                return [];
            }
            return r.json();
        }

        function escapeHtml(s) {
            return (s || '').replace(/[&<>"']/g, m => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                "\"": "&quot;",
                "'": "&#39;"
            } [m]))
        }

        function renderRows(items) {
            const tb = document.getElementById('rows');
            tb.innerHTML = '';
            for (const p of items) {
                const tr = document.createElement('tr');
                tr.className = 'border-b last:border-0';
                tr.dataset.id = p.id;
                tr.innerHTML = `<td class="p-3">${p.id}</td>
                    <td class="p-3"><input class="w-48 px-2 py-1 rounded border" value="${escapeHtml(p.name)}" data-k="name"></td>
                    <td class="p-3"><textarea class="w-64 h-16 px-2 py-1 rounded border" data-k="description">${escapeHtml(p.description||'')}</textarea></td>
                    <td class="p-3"><input type="number" class="w-28 px-2 py-1 rounded border" value="${p.price_baht}" data-k="price_baht"></td>
                    <td class="p-3"><input type="number" class="w-20 px-2 py-1 rounded border" value="${p.stock}" data-k="stock"></td>
                    <td class="p-3"><input class="w-64 px-2 py-1 rounded border" value="${escapeHtml(p.image_url||'')}" data-k="image_url"></td>
                    <td class="p-3"><select class="px-2 py-1 rounded border" data-k="is_active"><option value="1" ${p.is_active==1?'selected':''}>แสดง</option><option value="0" ${p.is_active==0?'selected':''}>ซ่อน</option></select></td>
                    <td class="p-3 text-right"><button class="px-3 py-1 rounded-xl border mr-2" data-act="save">บันทึก</button><button class="px-3 py-1 rounded-xl border" data-act="del">ลบ</button></td>`;
                tb.appendChild(tr);
            }
            document.querySelectorAll('[data-act="save"]').forEach(b => b.addEventListener('click', () => saveRow(b.closest('tr'))));
            document.querySelectorAll('[data-act="del"]').forEach(b => b.addEventListener('click', () => delRow(b.closest('tr'))));
        }

        async function saveRow(tr) {
            const id = Number(tr.dataset.id);
            const payload = {
                action: 'update',
                id
            };
            tr.querySelectorAll('[data-k]').forEach(i => payload[i.dataset.k] = i.value);
            payload.price_baht = parseInt(payload.price_baht || 0, 10);
            payload.stock = parseInt(payload.stock || 0, 10);
            payload.is_active = parseInt(payload.is_active || 1, 10);
            const r = await fetch('./api/admin_products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const d = await r.json().catch(() => ({}));
            if (!r.ok || !d.ok) return toast(d.message || 'บันทึกไม่สำเร็จ', 'warn');
            toast('บันทึกแล้ว');
        }

        async function delRow(tr) {
            if (!confirm('ลบสินค้านี้?')) return;
            const id = Number(tr.dataset.id);
            const r = await fetch('./api/admin_products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    id
                })
            });
            const d = await r.json().catch(() => ({}));
            if (!r.ok || !d.ok) return toast(d.message || 'ลบไม่สำเร็จ', 'warn');
            tr.remove();
            toast(d.deleted === 'soft' ? 'ลบแบบซ่อน (เพราะมีออเดอร์อ้างอิง)' : 'ลบแล้ว');
        }

        document.getElementById('btnReload').addEventListener('click', async () => renderRows(await fetchList()));
        document.getElementById('btnCreate').addEventListener('click', async () => {
            const p = {
                action: 'create',
                name: document.getElementById('n_name').value.trim(),
                price_baht: parseInt(document.getElementById('n_price').value || 0, 10),
                stock: parseInt(document.getElementById('n_stock').value || 0, 10),
                image_url: document.getElementById('n_image').value.trim(),
                is_active: parseInt(document.getElementById('n_active').value || 1, 10),
                description: document.getElementById('n_desc').value
            };
            const r = await fetch('./api/admin_products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(p)
            });
            const d = await r.json().catch(() => ({}));
            if (!r.ok || !d.ok) return toast(d.message || 'เพิ่มสินค้าไม่สำเร็จ', 'warn');
            toast('เพิ่มสินค้าแล้ว');
            document.getElementById('n_name').value = document.getElementById('n_price').value = document.getElementById('n_stock').value = document.getElementById('n_image').value = document.getElementById('n_desc').value = '';
            renderRows(await fetchList());
        });

        (async () => {
            renderRows(await fetchList());
        })();

        // ===== Order Detail Modal =====
        function showOrderModal(order, items) {
            const orderModal = document.getElementById('orderModal');
            if (!orderModal) return;
            const h = document.getElementById('omHead');
            const r = document.getElementById('omRows');
            h.innerHTML = `
    <div><span class="font-medium">รหัส:</span> ${order.code} —
      <span class="font-medium">สถานะ:</span> ${order.status}</div>
    <div><span class="font-medium">ลูกค้า:</span> ${order.customer_name}
      ${order.phone?`<span class="ml-2 text-slate-500">(${order.phone})</span>`:''}</div>
    <div class="text-slate-500">${(order.address||'').replace(/\n/g,' ')}</div>
    <div class="text-slate-500">เวลา: ${order.created_at}</div>`;
            r.innerHTML = '';
            let sum = 0;
            for (const it of items) {
                const unitSatang = toSatang(it.price_baht);
                const lineSatang = it.line_total != null ? toSatang(it.line_total) : unitSatang * (Number(it.qty) || 0);
                sum += lineSatang;
                const tr = document.createElement('tr');
                tr.innerHTML = `
      <td class="p-2">${it.name||('Product #'+it.product_id)}</td>
      <td class="p-2 text-right">${it.qty}</td>
      <td class="p-2 text-right">${fmtTHB.format(unitSatang/100)}</td>
      <td class="p-2 text-right">${fmtTHB.format(lineSatang/100)}</td>`;
                r.appendChild(tr);
            }
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="p-2 text-right font-medium" colspan="3">รวมทั้งสิ้น</td>
                  <td class="p-2 text-right font-bold">${fmtTHB.format(sum/100)}</td>`;
            r.appendChild(tr);
            orderModal.classList.remove('hidden');
        }
    </script>

    <!-- Order Detail Modal -->
    <div id="orderModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-3xl bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-xl font-semibold">รายละเอียดคำสั่งซื้อ</h3>
                    <button id="btnCloseOrderModal" class="p-2 rounded-xl hover:bg-slate-100">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <div id="omHead" class="text-sm text-slate-700"></div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="p-2 text-left">สินค้า</th>
                                    <th class="p-2 text-right">จำนวน</th>
                                    <th class="p-2 text-right">ราคา/ชิ้น</th>
                                    <th class="p-2 text-right">รวม</th>
                                </tr>
                            </thead>
                            <tbody id="omRows"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>