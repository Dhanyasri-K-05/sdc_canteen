<?php
// bulk_order.php
// Single file: UI + Autocomplete + Stock fetch + Submit handling

// start session early so department (when implemented) can be used
//if (session_status() === PHP_SESSION_NONE) session_start();

$dbHost = "localhost";
$dbUser = "root";
$dbPass = "tiger";
$dbName = "food_ordering_system";

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    http_response_code(500);
    die("DB Connection error");
}

/* ============================================================
    AUTOCOMPLETE
============================================================ */
if (isset($_GET['q'])) {
    $q = $conn->real_escape_string($_GET['q']);
    $isToday = ($_GET['date'] ?? '') === date("Y-m-d");

    $res = $conn->query("
        SELECT id, name, quantity_available AS stock, price
        FROM food_items
        WHERE name LIKE '%$q%'
        LIMIT 3
    ");

    $out = [];
    while ($r = $res->fetch_assoc()) {
        if (!$isToday) unset($r['stock']);
        $out[] = $r;
    }

    header("Content-Type: application/json");
    echo json_encode($out);
    exit;
}

/* ============================================================
    BULK STOCK FETCH FOR DATE SWITCH
============================================================ */
if (isset($_GET['fetch_stocks'])) {
    $ids = explode(",", $_GET['ids'] ?? "");
    $ids = array_filter(array_map('intval', $ids));
    if (count($ids) == 0) {
        echo json_encode([]);
        exit;
    }

    $idList = implode(",", $ids);
    $map = [];
    $res = $conn->query("SELECT id, quantity_available AS stock, price FROM food_items WHERE id IN ($idList)");
    while ($r = $res->fetch_assoc()) {
        $map[$r['id']] = $r;
    }

    header("Content-Type: application/json");
    echo json_encode($map);
    exit;
}

/* ============================================================
    FETCH FULL ORDERS (FOR HISTORY PAGE)
============================================================ */
if (isset($_GET['fetch_full_orders'])) {

    $ordersRes = $conn->query("SELECT * FROM bulk_orders ORDER BY id DESC");
    $orders = [];

    while ($o = $ordersRes->fetch_assoc()) {

        $order_id = $o['id'];

        $itemsRes = $conn->query("
            SELECT b.*, f.name
            FROM bulk_order_items b
            JOIN food_items f ON f.id = b.item_id
            WHERE b.order_id = $order_id
        ");

        $items = [];
        while ($i = $itemsRes->fetch_assoc()) {
            $items[] = $i;
        }

        $o['items'] = $items;
        $orders[] = $o;
    }

    header("Content-Type: application/json");
    echo json_encode($orders);
    exit;
}

/* ============================================================
    FORM SUBMIT (NEW ORDER + MERGE EXISTING + STOCK REDUCTION)
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // detect AJAX/fetch requests so we can return JSON and avoid a full reload
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    $supply_date = $_POST['supply_date'] ?? '';
    $event_name  = trim($_POST['event_name'] ?? '');
    $department  = $_SESSION['department'] ?? ($_POST['department'] ?? 'CSE');
    $items       = json_decode($_POST['items'] ?? "[]", true);

    if (!$supply_date || !$event_name || !is_array($items) || count($items) === 0) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        } else {
            echo "<h3 style='color:red'>Missing required fields.</h3>";
        }
        exit;
    }

    // date validations
    $today = date("Y-m-d");
    if ($supply_date < $today) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Date cannot be in the past.']);
        } else {
            echo "<h3 style='color:red'>Date cannot be in the past.</h3>";
        }
        exit;
    }

    $isToday = ($supply_date === $today);

    // Begin transaction
    $conn->begin_transaction();

    try {
        /* -------- CHECK IF ORDER ALREADY EXISTS (MERGE LOGIC) -------- */
        $check = $conn->prepare("
            SELECT id FROM bulk_orders 
            WHERE supply_date = ? AND event_name = ? AND department = ?
            LIMIT 1
        ");
        $check->bind_param("sss", $supply_date, $event_name, $department);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Existing order → MERGE into it
            $check->bind_result($order_id);
            $check->fetch();

            // IMPORTANT: Reset confirmation since the order was updated
            $conn->query("UPDATE bulk_orders SET is_confirmed = 0 WHERE id = $order_id");
        } else {
            // New order
            $stmt = $conn->prepare("
        INSERT INTO bulk_orders (supply_date, event_name, department, is_confirmed)
        VALUES (?, ?, ?, 0)
    ");
            $stmt->bind_param("sss", $supply_date, $event_name, $department);
            $stmt->execute();
            $order_id = $stmt->insert_id;
        }



        /* -------- FETCH STOCKS FOR TODAY'S ORDERS -------- */
        $stocks = [];
        if ($isToday) {
            $ids = array_unique(array_column($items, "item_id"));
            if (count($ids) > 0) {
                $idList = implode(",", array_map('intval', $ids));
                $res = $conn->query("SELECT id, quantity_available AS stock, price FROM food_items WHERE id IN ($idList)");
                while ($r = $res->fetch_assoc()) {
                    $stocks[$r['id']] = $r;
                }
            }
        }

        /* -------- INSERT OR MERGE ITEMS -------- */
        foreach ($items as $it) {

            $iid = intval($it['item_id']);
            $qty = intval($it['qty']);
            $ppu = floatval($it['ppu']);
            $total = floatval($it['total']);

            if ($isToday) {
                $available = $stocks[$iid]['stock'] ?? 0;
                if ($qty > $available) {
                    $qty = $available;
                    $total = $qty * $ppu;
                }
            }

            if ($qty <= 0) continue;

            // check if item already exists in order
            $ex = $conn->prepare("
                SELECT id, quantity 
                FROM bulk_order_items 
                WHERE order_id = ? AND item_id = ?
                LIMIT 1
            ");
            $ex->bind_param("ii", $order_id, $iid);
            $ex->execute();
            $ex->store_result();

            if ($ex->num_rows > 0) {

                $ex->bind_result($row_id, $oldQty);
                $ex->fetch();

                $newQty = $oldQty + $qty;
                $newTotal = $newQty * $ppu;

                $upd = $conn->prepare("
                    UPDATE bulk_order_items
                    SET quantity = ?, total_price = ?
                    WHERE id = ?
                ");
                $upd->bind_param("idi", $newQty, $newTotal, $row_id);
                $upd->execute();
            } else {

                $ins = $conn->prepare("
                    INSERT INTO bulk_order_items (order_id, item_id, quantity, price_per_unit, total_price)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $ins->bind_param("iiidd", $order_id, $iid, $qty, $ppu, $total);
                $ins->execute();
            }

            // reduce stock only for today's orders
            if ($isToday) {
                $reduce = $conn->prepare("
                    UPDATE food_items
                    SET quantity_available = quantity_available - ?
                    WHERE id = ?
                ");
                $reduce->bind_param("ii", $qty, $iid);
                $reduce->execute();
            }
        }

        $conn->commit();
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Enquiry saved successfully.']);
        } else {
            echo "<h3 style='color:green'>Order saved successfully.</h3>";
        }
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error while saving order.']);
        } else {
            echo "<h3 style='color:red'>Server error while saving order.</h3>";
        }
        exit;
    }
}

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Bulk Order — Department</title>
    <style>
        :root {
            --bg: #fbfcfd;
            --card: #ffffff;
            --muted: #6b7280;
            --accent: #0f172a;
            --edge: #e6eef8;
            --success: #0f766e;
        }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: var(--bg);
            color: var(--accent);
            padding: 28px;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 6px 0;
        }

        p.lead {
            color: var(--muted);
            margin: 6px 0 18px 0;
        }

        .card {
            background: var(--card);
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.04);
            margin-bottom: 18px;
        }

        .row {
            display: flex;
            gap: 12px;
        }

        .col {
            flex: 1;
        }

        label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="date"],
        input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #eef2f7;
            background: #fff;
            font-size: 14px;
            outline: none;
        }

        .itemStock,
        .price,
        .total {
            background: #f5f6f8 !important;
            color: #6b7280;
            font-weight: 500;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        input[type="number"]:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.06);
            border-color: #e0e7ff;
        }

        .table-wrap {
            overflow-x: auto;
            margin-top: 12px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 14px;
        }

        table.items thead th {
            text-align: left;
            font-size: 13px;
            color: var(--muted);
            padding: 12px 8px;
            border-bottom: 1px solid #f0f4f8;
            vertical-align: bottom;
        }

        table.items tbody td {
            padding: 12px 8px;
            border-bottom: 1px solid #fbfdff;
            vertical-align: middle;
        }

        .item-input {
            width: 100%;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #f0f4f8;
        }

        .small-muted {
            color: var(--muted);
            font-size: 12px;
            margin-top: 6px;
        }

        .suggestions {
            position: absolute;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
            border: 1px solid #eef2f7;
            z-index: 40;
            max-width: 420px;
        }

        .suggestions div {
            padding: 8px 10px;
            cursor: pointer;
            font-size: 14px;
            color: #0f172a;
        }

        .suggestions div:hover {
            background: #f8fafc;
        }

        .btn {
            background: #0f172a;
            color: #fff;
            border: none;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn.secondary {
            background: #f1f5f9;
            color: var(--accent);
            border: 1px solid #e6eef8;
        }

        .muted-note {
            color: var(--muted);
            font-size: 13px;
            margin-top: 8px;
        }

        tfoot td {
            padding: 12px 8px;
            font-weight: 700;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .remove-btn {
            background: transparent;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-weight: 700;
        }

        /* Make table row inputs look like a connected segment */
        .table-row-input {
            border-radius: 0 !important;
            border-left: 1px solid #e5e7eb !important;
            border-right: 1px solid #e5e7eb !important;
        }

        /* Left-most cell input rounding */
        .table-row-input.first {
            border-top-left-radius: 8px !important;
            border-bottom-left-radius: 8px !important;
        }

        /* Right-most cell input rounding */
        .table-row-input.last {
            border-top-right-radius: 8px !important;
            border-bottom-right-radius: 8px !important;
        }

        /* Connected segment look for top form inputs */
        .form-segment {
            border-radius: 0 !important;
            border-left: 1px solid #e5e7eb !important;
            border-right: 1px solid #e5e7eb !important;
        }

        /* Left-most rounded */
        .form-segment.first {
            border-top-left-radius: 8px !important;
            border-bottom-left-radius: 8px !important;
        }

        /* Right-most rounded */
        .form-segment.last {
            border-top-right-radius: 8px !important;
            border-bottom-right-radius: 8px !important;
        }

        .toggle-btn {
            padding: 10px 16px;
            border-radius: 8px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            font-weight: 600;
        }

        .toggle-btn.active {
            background: #0f172a;
            color: white;
        }

        .order-card {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 12px;
            background: white;
            border: 2px solid transparent;
        }

        .order-card.confirmed {
            border-color: #4ade80;
            /* slight green */
        }

        .order-card.pending {
            border-color: #facc15;
            /* slight yellow */
        }

        .order-card .items-list {
            margin-top: 10px;
            padding-left: 4px;
            color: #475569;
            font-size: 13px;
            line-height: 1.45;
        }

        .order-card .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .order-card .total-line {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-weight: 700;
            font-size: 14px;
            text-align: right;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding-bottom: 8px;
        }

        .order-title {
            font-size: 15px;
            font-weight: 600;
        }

        .order-sub {
            font-size: 13px;
            color: #475569;
        }

        .expand-icon {
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .order-details {
            margin-top: 10px;
        }


        /* responsive */
        @media (max-width:720px) {
            .row {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div id="top-nav" style="display:flex; gap:12px; margin-bottom:20px;">
        <button class="toggle-btn" onclick="showSection('place')">Place Order</button>
        <button class="toggle-btn" onclick="showSection('history')">View Past Orders</button>
    </div>
    <div id="section-place">
        <!-- your entire current place-order container goes here -->

        <div class="container">
            <h1>Department Bulk Order</h1>
            <p class="lead">Place a bulk enquiry for your department. Date defaults to today. Future orders can be placed without stock restrictions.</p>

            <div class="card">
                <form id="orderForm" method="POST" novalidate>
                    <div class="row" style="align-items:end;">
                        <div class="col" style="max-width:220px;">
                            <label for="supply_date">Supply Date</label>
                            <input id="supply_date" name="supply_date" type="date" required class="form-segment first">
                        </div>

                        <div class="col">
                            <label for="event_name">Event Name</label>
                            <input id="event_name" name="event_name" type="text" placeholder="e.g., Dept Annual Meet" required class="form-segment">
                        </div>

                        <div style="width:160px;">
                            <label>Department</label>
                            <?php $dept_display = htmlspecialchars($_SESSION['department'] ?? 'CSE'); ?>
                            <input type="text" value="<?php echo $dept_display; ?>" disabled class="form-segment last">
                            <input type="hidden" name="department" id="department_input" value="<?php echo $dept_display; ?>">
                        </div>
                    </div>

                    <div class="muted-note">Start typing an item name in the table below to auto-suggest. For <strong>today</strong> stock is enforced.</div>

                    <div class="table-wrap card" style="margin-top:12px; padding:12px;">
                        <table class="items" id="itemsTable" aria-label="Order items">
                            <thead>
                                <tr id="tableHeadRow">
                                    <th style="width:40%;">Item</th>
                                    <th class="col-stock">Stock</th>
                                    <th style="width:12%;">Qty</th>
                                    <th style="width:14%;">Price</th>
                                    <th style="width:14%;">Total</th>
                                    <th class="center" style="width:6%;">✕</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- rows injected here -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="right">Grand Total</td>
                                    <td id="grandTotal">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <input type="hidden" id="items_json" name="items">
                    <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
                        <button type="button" class="btn secondary" id="addManualRow">Add Item</button>
                        <button type="submit" class="btn">Submit Enquiry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="section-history" style="display:none;">
        <input
            type="text"
            id="searchOrders"
            placeholder="Search orders..."
            style="width:100%; padding:10px 12px; margin-bottom:12px; border-radius:8px; border:1px solid #e5e7eb; font-size:14px;">

        <div class="card">
            <h2>Past Orders</h2>
            <div id="ordersList"></div>
        </div>
    </div>

    <script>
        function highlight(items, index) {
            items.forEach((el, i) => {
                el.style.background = (i === index) ? "#f1f5f9" : "white";
            });
        }

        (function() {
            // helpers
            const today = new Date();

            function toISODate(d) {
                return d.toISOString().slice(0, 10);
            }
            const todayStr = toISODate(today);

            // DOM
            const supplyDate = document.getElementById('supply_date');
            const itemsBody = document.getElementById('itemsBody');
            const itemsJson = document.getElementById('items_json');
            const grandTotalEl = document.getElementById('grandTotal');
            const addManualRowBtn = document.getElementById('addManualRow');

            // init date default = today, prevent past
            supplyDate.value = todayStr;
            supplyDate.min = todayStr;

            // state
            let showStockColumn = true; // depends on date
            let rowIdCounter = 0;

            // initialize one empty row
            function ensureOneRow() {
                if (itemsBody.children.length === 0) addRow();
            }

            // Build row
            function addRow(prefill = {}) {
                const rowId = ++rowIdCounter;
                const tr = document.createElement('tr');
                tr.dataset.rowId = rowId;

                // item cell (with suggestions)
                const tdItem = document.createElement('td');
                tdItem.style.position = 'relative';
                const inputName = document.createElement('input');
                inputName.type = 'text';
                inputName.className = 'item-input itemName table-row-input first';
                inputName.placeholder = 'Type to search item...';
                inputName.autocomplete = 'off';
                inputName.value = prefill.name || '';
                tdItem.appendChild(inputName);

                const suggBox = document.createElement('div');
                suggBox.className = 'suggestions';
                suggBox.style.display = 'none';
                tdItem.appendChild(suggBox);

                const hiddenId = document.createElement('input');
                hiddenId.type = 'hidden';
                hiddenId.className = 'itemId';
                hiddenId.value = prefill.item_id || '';
                tdItem.appendChild(hiddenId);

                // stock cell
                const tdStock = document.createElement('td');
                tdStock.className = 'col-stock';
                const inputStock = document.createElement('input');
                inputStock.type = 'text';
                inputStock.className = 'itemStock table-row-input';
                inputStock.disabled = true;
                inputStock.value = prefill.stock !== undefined ? prefill.stock : '';
                tdStock.appendChild(inputStock);

                // qty
                const tdQty = document.createElement('td');
                const inputQty = document.createElement('input');
                inputQty.type = 'number';
                inputQty.className = 'qty table-row-input';
                inputQty.min = 0;
                inputQty.value = prefill.qty !== undefined ? prefill.qty : 0;
                inputQty.style = "width:100%;";
                tdQty.appendChild(inputQty);

                // price
                const tdPrice = document.createElement('td');
                const inputPrice = document.createElement('input');
                inputPrice.type = 'text';
                inputPrice.className = 'price table-row-input';
                inputPrice.disabled = true;
                inputPrice.value = prefill.price !== undefined ? prefill.price : '';
                tdPrice.appendChild(inputPrice);

                // total
                const tdTotal = document.createElement('td');
                const inputTotal = document.createElement('input');
                inputTotal.type = 'text';
                inputTotal.className = 'total table-row-input last';
                inputTotal.disabled = true;
                inputTotal.value = prefill.total !== undefined ? prefill.total : '0.00';
                tdTotal.appendChild(inputTotal);

                // remove
                const tdRemove = document.createElement('td');
                tdRemove.className = 'center';
                const remBtn = document.createElement('button');
                remBtn.type = 'button';
                remBtn.className = 'remove-btn';
                remBtn.textContent = '×';
                tdRemove.appendChild(remBtn);

                tr.appendChild(tdItem);
                tr.appendChild(tdStock);
                tr.appendChild(tdQty);
                tr.appendChild(tdPrice);
                tr.appendChild(tdTotal);
                tr.appendChild(tdRemove);

                itemsBody.appendChild(tr);

                // event handlers
                let activeSuggFetch = null;
                inputName.addEventListener('input', function(e) {
                    const q = this.value.trim();
                    if (!q) {
                        suggBox.style.display = 'none';
                        return;
                    }

                    fetch(`?q=${encodeURIComponent(q)}&date=${supplyDate.value}`)
                        .then(r => r.json())
                        .then(list => {

                            suggBox.innerHTML = '';
                            let activeIndex = -1; // track highlighted suggestion (belongs OUTSIDE foreach)

                            if (!Array.isArray(list) || list.length === 0) {
                                suggBox.style.display = 'none';
                                return;
                            }

                            // Build suggestion list
                            list.forEach(it => {
                                const d = document.createElement('div');
                                d.innerHTML = `<strong>${escapeHtml(it.name)}</strong>` +
                                    (it.stock !== undefined ?
                                        ` <span style="color:var(--muted);font-size:12px">(Stock: ${it.stock})</span>` :
                                        ''
                                    );

                                d.addEventListener('click', () => {
                                    inputName.value = it.name;
                                    hiddenId.value = it.id;
                                    inputStock.value = (it.stock !== undefined ? it.stock : '');
                                    inputPrice.value = (it.price !== undefined ? it.price : '');
                                    inputQty.value = 0;
                                    inputTotal.value = '0.00';
                                    suggBox.style.display = 'none';
                                    updateGrandTotal();
                                    addRow();
                                });

                                suggBox.appendChild(d);
                            });

                            suggBox.style.display = 'block';

                            // Remove previous keydown listener (prevent stacking)
                            inputName.onkeydown = null;

                            // Keyboard navigation handler
                            inputName.onkeydown = function(e) {
                                const items = Array.from(suggBox.querySelectorAll("div"));
                                if (items.length === 0) return;

                                // Arrow DOWN
                                if (e.key === "ArrowDown") {
                                    e.preventDefault();
                                    activeIndex = (activeIndex + 1) % items.length;
                                    highlight(items, activeIndex);
                                }

                                // Arrow UP
                                if (e.key === "ArrowUp") {
                                    e.preventDefault();
                                    activeIndex = (activeIndex - 1 + items.length) % items.length;
                                    highlight(items, activeIndex);
                                }

                                // ENTER
                                if (e.key === "Enter") {
                                    e.preventDefault();
                                    let target = items[activeIndex] || items[0];
                                    target.click();
                                    suggBox.style.display = "none";
                                }
                            };
                        });
                });


                // click outside to hide suggestions
                document.addEventListener('click', function(ev) {
                    if (!tdItem.contains(ev.target)) suggBox.style.display = 'none';
                });

                // qty change -> update total (with stock enforcement when needed)
                inputQty.addEventListener('input', function() {
                    let qVal = Number(this.value) || 0;
                    const priceVal = parseFloat(inputPrice.value) || 0;
                    if (showStockColumn && inputStock.value !== '') {
                        const stockVal = Number(inputStock.value) || 0;
                        if (qVal > stockVal) {
                            // enforce if today
                            qVal = stockVal;
                            this.value = qVal;
                        }
                    }
                    inputTotal.value = (qVal * priceVal).toFixed(2);
                    updateGrandTotal();
                });

                // remove row
                remBtn.addEventListener('click', function() {
                    tr.remove();
                    updateGrandTotal();
                    ensureOneRow();
                });

                // when item id changes (user might paste id) - nothing else needed
                // prefill handlers done
                updateTableStockVisibility();
                return tr;
            }

            // escapeHtml helper
            function escapeHtml(s) {
                return String(s).replace(/[&<>"]/g, c => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;'
                } [c]));
            }

            // update grand total
            function updateGrandTotal() {
                let sum = 0;
                document.querySelectorAll('#itemsBody tr').forEach(tr => {
                    const t = parseFloat((tr.querySelector('.total') || {
                        value: 0
                    }).value) || 0;
                    sum += t;
                });
                grandTotalEl.textContent = sum.toFixed(2);
            }

            // update stock column show/hide
            function updateTableStockVisibility() {
                // showStockColumn value is already set based on date
                document.querySelectorAll('.col-stock').forEach(el => {
                    el.style.display = showStockColumn ? '' : 'none';
                });
                // adjust header visibility for mobile/responsive if needed (stock header has class col-stock)
                document.querySelectorAll('table.items thead th.col-stock').forEach(h => h.style.display = showStockColumn ? '' : 'none');
            }

            // date change behavior
            supplyDate.addEventListener('change', function() {
                const newDate = this.value;
                if (!newDate) return;
                if (newDate < todayStr) {
                    alert('Supply date cannot be in the past.');
                    this.value = todayStr;
                    return;
                }

                const wasShowingStock = showStockColumn;
                showStockColumn = (newDate === todayStr);

                if (!showStockColumn && wasShowingStock) {
                    // today -> future: keep items but reset qty to 0 and disable stock checks
                    document.querySelectorAll('#itemsBody tr').forEach(tr => {
                        const qty = tr.querySelector('.qty');
                        const total = tr.querySelector('.total');
                        qty.value = 0;
                        total.value = '0.00';
                    });
                    updateTableStockVisibility();
                    updateGrandTotal();
                } else if (showStockColumn && !wasShowingStock) {
                    // future -> today: re-fetch stock for selected items and cap qty if needed
                    // gather ids
                    const ids = [];
                    document.querySelectorAll('#itemsBody tr').forEach(tr => {
                        const iid = tr.querySelector('.itemId').value;
                        if (iid) ids.push(iid);
                    });
                    if (ids.length > 0) {
                        // fetch in bulk
                        fetch(`?fetch_stocks=1&ids=${encodeURIComponent(ids.join(','))}`)
                            .then(r => r.json())
                            .then(map => {
                                document.querySelectorAll('#itemsBody tr').forEach(tr => {
                                    const iid = tr.querySelector('.itemId').value;
                                    if (!iid) return;
                                    const data = map[iid];
                                    const stockEl = tr.querySelector('.itemStock');
                                    const qtyEl = tr.querySelector('.qty');
                                    const priceEl = tr.querySelector('.price');
                                    const totalEl = tr.querySelector('.total');

                                    if (data) {
                                        stockEl.value = data.stock;
                                        priceEl.value = data.price;
                                        // cap qty if needed
                                        let qv = Number(qtyEl.value) || 0;
                                        if (qv > data.stock) {
                                            qv = data.stock;
                                            qtyEl.value = qv;
                                        }
                                        totalEl.value = (qv * parseFloat(priceEl.value || 0)).toFixed(2);
                                    } else {
                                        // item not found: blank stock and set qty 0
                                        stockEl.value = '';
                                        qtyEl.value = 0;
                                        totalEl.value = '0.00';
                                    }
                                });
                                updateGrandTotal();
                                updateTableStockVisibility();
                            });
                    } else {
                        updateTableStockVisibility();
                    }
                } else {
                    // only visibility change (initial load or unchanged)
                    updateTableStockVisibility();
                }
            });

            // prevent duplicate addRow on date change bug: only add row when user presses 'Add Item' or when table empty
            addManualRowBtn.addEventListener('click', () => addRow());

            // ensure one row exists on load
            ensureOneRow();

            // form submit: serialize and validate client-side before sending
            const form = document.getElementById('orderForm');
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // collect items
                const rows = Array.from(document.querySelectorAll('#itemsBody tr'));
                const items = [];
                for (let tr of rows) {
                    const iid = tr.querySelector('.itemId').value;
                    const name = tr.querySelector('.itemName').value.trim();
                    const qty = Number(tr.querySelector('.qty').value) || 0;
                    const ppu = parseFloat(tr.querySelector('.price').value) || 0;
                    const total = parseFloat(tr.querySelector('.total').value) || 0;

                    if (!iid && !name) continue; // empty row

                    // require item selection (must have itemId)
                    if (!iid) {
                        alert('Please pick a valid item from suggestions for all rows.');
                        return;
                    }

                    // if today enforce qty <= stock
                    if (supplyDate.value === todayStr) {
                        const stock = Number(tr.querySelector('.itemStock').value) || 0;
                        if (qty > stock) {
                            alert('One of the quantities exceeds available stock for today. It will be capped on the server. Please review.');
                            // we allow submit; server will cap. Alternatively you can prevent submit.
                        }
                        if (qty === 0) {
                            // skip zero-qty for today
                            continue;
                        }
                    } else {
                        // future: qty can be zero, but we should skip zero qty items
                        if (qty === 0) continue;
                    }

                    items.push({
                        item_id: Number(iid),
                        qty: qty,
                        ppu: ppu,
                        total: total
                    });
                }

                if (items.length === 0) {
                    alert('Please add at least one item with quantity > 0.');
                    return;
                }

                // submit via AJAX so page doesn't reload; server returns JSON when called via XHR
                const payload = new FormData();
                payload.append('supply_date', supplyDate.value);
                payload.append('event_name', document.getElementById('event_name').value);
                payload.append('items', JSON.stringify(items));

                fetch('', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: payload
                    }).then(r => r.json())
                    .then(data => {
                        if (data && data.success) {
                            alert(data.message || 'Enquiry has been sent');
                            // reset form to default state
                            itemsBody.innerHTML = '';
                            ensureOneRow();
                            updateGrandTotal();
                            supplyDate.value = todayStr;
                        } else {
                            alert((data && data.message) ? data.message : 'Failed to save enquiry');
                        }
                    }).catch(err => {
                        console.error(err);
                        alert('Server error while sending enquiry');
                    });
            });

            // helper to update grand total when rows change or remove
            new MutationObserver(updateGrandTotal).observe(itemsBody, {
                childList: true,
                subtree: true
            });

            // ensure no duplicate auto-row adding when date changes
            // (we do not automatically add a row on date change; addRow only called manually or if table empty)
            // done.

            // utility: add initial event to create rows and wire autocomplete
            // already ensured one row at load

        })();
    </script>
    <script>
        function showSection(section) {
            document.getElementById('section-place').style.display = section === 'place' ? 'block' : 'none';
            document.getElementById('section-history').style.display = section === 'history' ? 'block' : 'none';

            // toggle button highlight
            document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
            if (section === 'place') document.querySelectorAll('.toggle-btn')[0].classList.add('active');
            else document.querySelectorAll('.toggle-btn')[1].classList.add('active');

            if (section === 'history') fetchOrders();
        }

        function fetchOrders() {
            fetch('?fetch_full_orders=1')
                .then(r => r.json())
                .then(list => {
                    const box = document.getElementById('ordersList');
                    box.innerHTML = '';

                    list.forEach(order => {

                        // Calculate grand total
                        let totalAmount = 0;
                        order.items.forEach(i => totalAmount += parseFloat(i.total_price));

                        // Build items HTML
                        let itemsHTML = '';
                        order.items.forEach(i => {
                            itemsHTML += `
                        <div class="item-row">
                            <span>${i.name} × ${i.quantity} @ ₹${i.price_per_unit}</span>
                            <span>₹${i.total_price}</span>
                        </div>
                    `;
                        });

                        // Create card DOM
                        const card = document.createElement('div');
                        card.className = 'order-card ' + (order.is_confirmed == 1 ? 'confirmed' : 'pending');

                        card.innerHTML = `
                    <div class="order-header">
                        <div>
                            <div class="order-title">${order.event_name}</div>
                            <div class="order-sub">Date: ${order.supply_date}</div>
                            <div class="order-sub">Status: <strong>${order.is_confirmed == 1 ? 'Confirmed' : 'Pending'}</strong></div>
                            <div class="order-sub">Total: ₹${totalAmount.toFixed(2)}</div>
                        </div>
                        <div class="expand-icon">▼</div>
                    </div>

                    <div class="order-details">
                        <div class="items-list">
                            ${itemsHTML}
                        </div>
                        <div class="total-line">
                            Grand Total: ₹${totalAmount.toFixed(2)}
                        </div>
                    </div>
                `;

                        // Append to list
                        box.appendChild(card);

                        // DOM refs for animation
                        let header = card.querySelector(".order-header");
                        let details = card.querySelector(".order-details");
                        let icon = card.querySelector(".expand-icon");

                        // animation setup
                        details.style.maxHeight = "0px";
                        details.style.overflow = "hidden";
                        details.style.transition = "max-height 0.35s ease, opacity 0.25s ease";
                        details.style.opacity = 0;

                        // toggle
                        header.addEventListener("click", () => {
                            const expanded = details.style.maxHeight !== "0px";

                            if (expanded) {
                                details.style.maxHeight = "0px";
                                details.style.opacity = 0;
                                icon.style.transform = "rotate(0deg)";
                            } else {
                                details.style.maxHeight = details.scrollHeight + "px";
                                details.style.opacity = 1;
                                icon.style.transform = "rotate(180deg)";
                            }
                        });
                    });
                });
        }

        document.getElementById("searchOrders").addEventListener("input", function() {
            const query = this.value.toLowerCase();

            document.querySelectorAll("#ordersList .order-card").forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? "block" : "none";
            });
        });
    </script>
</body>

</html>