<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../classes/FoodItem.php');
require_once(__DIR__ . '/../config/session.php');

// Require cashier or admin role
requireRole('cashier', 'admin');

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
$foodItem = new FoodItem($db);

// Fetch all active food items
$food_items = $foodItem->getAllItems();

// Generate bill number
$bill_number = 'BILL' . date('Ymd') . rand(1000, 9999);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canteen Billing System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            height: 100vh;
            overflow: hidden;
        }

        .container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: white;
        }

        /* Header */
        .header {
            background: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
        }

        .header-info {
            display: flex;
            gap: 30px;
            font-size: 13px;
        }

        .header-info div {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .header-info label {
            color: #bdc3c7;
            font-size: 11px;
        }

        .header-info span {
            font-weight: bold;
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 450px;
            flex: 1;
            overflow: hidden;
        }

        /* Left Section - Item Entry */
        .left-section {
            padding: 20px;
            border-right: 2px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Search Box with Dropdown - Inside table */
        .search-container {
            position: relative;
        }

        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 8px 12px;
            border: 2px solid #3498db;
            border-radius: 5px;
            background: white;
        }

        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 14px;
            padding: 4px;
        }

        .search-icon {
            color: #3498db;
            font-size: 16px;
        }

        /* Dropdown */
        .dropdown {
            position: absolute;
            top: calc(100% + 2px);
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #3498db;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .dropdown.show {
            display: block;
        }

        .dropdown-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #ecf0f1;
            transition: background 0.2s;
        }

        .dropdown-item:hover,
        .dropdown-item.highlighted {
            background: #e8f4f8;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .item-code {
            font-size: 12px;
            color: #7f8c8d;
        }

        .item-name {
            font-weight: bold;
            color: #2c3e50;
        }

        .item-price {
            color: #27ae60;
            font-weight: bold;
        }

        .item-stock {
            font-size: 12px;
            color: #95a5a6;
        }

        /* Cart Table */
        .cart-table-container {
            flex: 1;
            overflow-y: auto;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }

        .search-row td {
            padding: 8px 12px !important;
            background: white;
        }

        .search-row input.item-search {
            width: 100%;
            padding: 8px;
            border: 2px solid #3498db;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-row input.item-search:focus {
            outline: none;
            border-color: #2980b9;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        .search-row .qty-input {
            width: 100%;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table thead {
            background: #34495e;
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .cart-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        .cart-table tbody tr {
            border-bottom: 1px solid #ecf0f1;
        }

        .cart-table tbody tr:hover {
            background: #f8f9fa;
        }

        .cart-table td {
            padding: 12px;
            font-size: 14px;
        }

        .qty-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
        }

        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }

        .remove-btn:hover {
            background: #c0392b;
        }

        .empty-cart {
            text-align: center;
            padding: 50px;
            color: #95a5a6;
        }

        /* Right Section - Totals & Payment */
        .right-section {
            background: #ecf0f1;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        /* Totals Box */
        .totals-box {
            background: white;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
        }

        .total-row.grand-total {
            border-top: 2px solid #2c3e50;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        /* Payment Section */
        .payment-section {
            background: white;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .payment-section h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 16px;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .payment-btn {
            padding: 12px;
            border: 2px solid #bdc3c7;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .payment-btn:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .payment-btn.active {
            background: #2ecc71;
            color: white;
            border-color: #2ecc71;
        }

        .cash-input-group {
            margin-bottom: 15px;
        }

        .cash-input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .cash-input-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 18px;
            text-align: right;
        }

        .change-box {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }

        .change-box.negative {
            background: #f8d7da;
            border-color: #f5c6cb;
        }

        .change-label {
            font-size: 14px;
            color: #155724;
            margin-bottom: 5px;
        }

        .change-box.negative .change-label {
            color: #721c24;
        }

        .change-amount {
            font-size: 28px;
            font-weight: bold;
            color: #155724;
        }

        .change-box.negative .change-amount {
            color: #721c24;
        }

        /* Action Buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn {
            padding: 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-clear {
            background: #e74c3c;
            color: white;
        }

        .btn-clear:hover {
            background: #c0392b;
        }

        .btn-print {
            background: #2ecc71;
            color: white;
        }

        .btn-print:hover {
            background: #27ae60;
        }

        .btn-print:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        /* Keyboard Shortcuts Bar */
        .shortcuts-bar {
            background: #34495e;
            color: white;
            padding: 10px 20px;
            display: flex;
            gap: 25px;
            font-size: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .shortcut-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .shortcut-key {
            background: #2c3e50;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-family: monospace;
            border: 1px solid #1a252f;
        }

        /* WebSocket Status */
        .ws-status {
            position: fixed;
            bottom: 60px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .ws-connected {
            background: #2ecc71;
            color: white;
        }

        .ws-disconnected {
            background: #e74c3c;
            color: white;
        }

        /* Scrollbar */
        .cart-table-container::-webkit-scrollbar,
        .dropdown::-webkit-scrollbar {
            width: 8px;
        }

        .cart-table-container::-webkit-scrollbar-track,
        .dropdown::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .cart-table-container::-webkit-scrollbar-thumb,
        .dropdown::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .cart-table-container::-webkit-scrollbar-thumb:hover,
        .dropdown::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header no-print">
            <h1>CANTEEN BILLING</h1>
            <div class="header-info">
                <div>
                    <label>CASHIER</label>
                    <span><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                </div>
                <div>
                    <label>BILL NO</label>
                    <span id="billNumber"><?php echo $bill_number; ?></span>
                </div>
                <div>
                    <label>DATE & TIME</label>
                    <span id="currentDateTime"><?php echo date('d/m/Y h:i A'); ?></span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Left Section -->
            <div class="left-section">
                <!-- Cart Table -->
                <div class="cart-table-container">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">S.No</th>
                                <th>Item Name</th>
                                <th style="width: 100px;">Price</th>
                                <th style="width: 80px;">Qty</th>
                                <th style="width: 120px;">Amount</th>
                                <th style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody">
                            <tr class="search-row">
                                <td>1</td>
                                <td>
                                    <div class="search-container">
                                        <input type="text"
                                            class="item-search"
                                            placeholder="Enter item name or code..."
                                            autocomplete="off"
                                            data-row="0">
                                        <div class="dropdown">
                                            <?php foreach ($food_items as $item): ?>
                                                <div class="dropdown-item"
                                                    data-id="<?php echo $item['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                    data-price="<?php echo $item['price']; ?>"
                                                    data-stock="<?php echo $item['quantity_available']; ?>"
                                                    data-category="<?php echo $item['category']; ?>">
                                                    <div class="item-code">Code: <?php echo $item['id']; ?></div>
                                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                    <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                                                        <span class="item-price">₹<?php echo number_format($item['price'], 2); ?></span>
                                                        <span class="item-stock">Stock: <?php echo $item['quantity_available']; ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="price-cell">₹0.00</td>
                                <td>
                                    <input type="number"
                                        class="qty-input"
                                        value="1"
                                        min="1"
                                        data-row="0"
                                        disabled>
                                </td>
                                <td class="amount-cell"><strong>₹0.00</strong></td>
                                <td>
                                    <button class="remove-btn" onclick="removeRow(0)" style="visibility: hidden;">X</button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="empty-cart">
                                    <p style="font-size: 14px; color: #7f8c8d;">Start typing to search items...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Section -->
            <div class="right-section">
                <!-- Totals Box -->
                <div class="totals-box">
                    <div class="total-row">
                        <span>TOTAL:</span>
                        <span id="grandTotal">₹0.00</span>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="payment-section">
                    <h3>💳 PAYMENT METHOD</h3>
                    <div class="payment-methods">
                        <button class="payment-btn active" data-method="cash">💵 CASH</button>
                        <button class="payment-btn" data-method="gpay">📱 GPAY/UPI</button>
                    </div>

                    <div id="cashInputSection">
                        <div class="cash-input-group">
                            <label>Amount Received (₹):</label>
                            <input type="number"
                                id="amountReceived"
                                placeholder="0.00"
                                step="0.01"
                                min="0">
                        </div>

                        <div class="change-box" id="changeBox" style="display: none;">
                            <div class="change-label">CHANGE TO RETURN:</div>
                            <div class="change-amount" id="changeAmount">₹0.00</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn btn-clear" id="clearBtn">🗑️ CLEAR</button>
                    <button class="btn btn-print" id="printBtn" disabled>🖨️ PRINT BILL</button>
                </div>
            </div>
        </div>

        <!-- Keyboard Shortcuts Bar -->
        <div class="shortcuts-bar no-print">
            <div class="shortcut-item">
                <span class="shortcut-key">F2</span>
                <span>Search Item</span>
            </div>
            <div class="shortcut-item">
                <span class="shortcut-key">Enter</span>
                <span>Add to Cart</span>
            </div>
            <div class="shortcut-item">
                <span class="shortcut-key">F8</span>
                <span>Payment</span>
            </div>
            <div class="shortcut-item">
                <span class="shortcut-key">F9</span>
                <span>Print Bill</span>
            </div>
            <div class="shortcut-item">
                <span class="shortcut-key">F10</span>
                <span>Clear Cart</span>
            </div>
            <div class="shortcut-item">
                <span class="shortcut-key">↑ ↓</span>
                <span>Navigate Items</span>
            </div>
        </div>
    </div>

    <!-- WebSocket Status -->
    <div id="wsStatus" class="ws-status ws-disconnected">
        ● Connecting...
    </div>

    <script>
        let cart = [];
        let currentPaymentMethod = 'cash';
        let ws;
        let isConnected = false;
        let selectedDropdownIndex = -1;
        let currentRow = 0;
        let rowData = {}; // Store data for each row: {rowIndex: {id, name, price, stock, quantity}}

        const foodItems = <?php echo json_encode($food_items); ?>;

        // Initialize search listeners
        function initializeSearchListeners() {
            const searchInputs = document.querySelectorAll('.item-search');

            searchInputs.forEach(input => {
                const rowIndex = parseInt(input.dataset.row);
                const dropdown = input.nextElementSibling;
                const dropdownItems = dropdown.querySelectorAll('.dropdown-item');
                const qtyInput = input.closest('tr').querySelector('.qty-input');

                input.addEventListener('focus', () => {
                    if (input.value.length > 0) {
                        dropdown.classList.add('show');
                    }
                });

                input.addEventListener('input', (e) => {
                    const search = e.target.value.toLowerCase();
                    let hasVisibleItems = false;
                    selectedDropdownIndex = -1;

                    dropdownItems.forEach((item) => {
                        const name = item.dataset.name.toLowerCase();
                        const id = item.dataset.id;

                        if (name.includes(search) || id.includes(search) || search === '') {
                            item.style.display = 'block';
                            hasVisibleItems = true;
                        } else {
                            item.style.display = 'none';
                        }
                        item.classList.remove('highlighted');
                    });

                    if (search.length > 0 && hasVisibleItems) {
                        dropdown.classList.add('show');
                    } else {
                        dropdown.classList.remove('show');
                    }
                });

                // Keyboard navigation
                input.addEventListener('keydown', (e) => {
                    const visibleItems = Array.from(dropdownItems).filter(item => item.style.display !== 'none');

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (visibleItems.length === 0) return;

                        selectedDropdownIndex = (selectedDropdownIndex + 1) % visibleItems.length;
                        highlightDropdownItem(visibleItems);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (visibleItems.length === 0) return;

                        selectedDropdownIndex = selectedDropdownIndex <= 0 ? visibleItems.length - 1 : selectedDropdownIndex - 1;
                        highlightDropdownItem(visibleItems);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (visibleItems.length > 0 && !rowData[rowIndex]) {
                            const itemToSelect = selectedDropdownIndex >= 0 ? visibleItems[selectedDropdownIndex] : visibleItems[0];
                            selectItem(rowIndex, itemToSelect);
                            dropdown.classList.remove('show');
                            qtyInput.disabled = false;
                            qtyInput.focus();
                            qtyInput.select();
                        }
                    } else if (e.key === 'Escape') {
                        dropdown.classList.remove('show');
                        selectedDropdownIndex = -1;
                    }
                });

                // Click to select item
                dropdownItems.forEach(item => {
                    item.addEventListener('click', () => {
                        selectItem(rowIndex, item);
                        dropdown.classList.remove('show');
                        qtyInput.disabled = false;
                        qtyInput.focus();
                        qtyInput.select();
                    });
                });

                // Quantity input enter key
                if (qtyInput) {
                    qtyInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            if (rowData[rowIndex]) {
                                const data = rowData[rowIndex];

                                // Update the quantity in rowData
                                const quantity = parseInt(qtyInput.value) || 1;
                                rowData[rowIndex].quantity = Math.min(quantity, rowData[rowIndex].stock);

                                // Update or add to cart
                                const existingItem = cart.find(item => item.id === data.id);
                                if (existingItem) {
                                    existingItem.quantity = rowData[rowIndex].quantity;
                                } else {
                                    cart.push({
                                        ...rowData[rowIndex]
                                    });
                                }

                                updateTotals();
                                calculateChange();

                                // Remove empty cart message if exists
                                const emptyRow = document.querySelector('.empty-cart');
                                if (emptyRow) {
                                    emptyRow.closest('tr').remove();
                                }

                                addNewRow();
                            }
                        }
                    });

                    qtyInput.addEventListener('input', (e) => {
                        if (rowData[rowIndex]) {
                            updateRowDisplay(rowIndex);

                            // Also update cart if item exists
                            const data = rowData[rowIndex];
                            const existingItem = cart.find(item => item.id === data.id);
                            if (existingItem) {
                                const quantity = parseInt(qtyInput.value) || 1;
                                const newQty = Math.min(quantity, rowData[rowIndex].stock);
                                existingItem.quantity = newQty;
                                rowData[rowIndex].quantity = newQty;
                                updateTotals();
                                calculateChange();
                            }
                        }
                    });
                }

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (input && !input.contains(e.target) && dropdown && !dropdown.contains(e.target)) {
                        dropdown.classList.remove('show');
                    }
                });
            });
        }

        function selectItem(rowIndex, item) {
            const id = item.dataset.id;
            const name = item.dataset.name;
            const price = parseFloat(item.dataset.price);
            const stock = parseInt(item.dataset.stock);

            if (stock <= 0) {
                alert('This item is out of stock!');
                return;
            }

            // Check if item already exists in another row
            for (let existingRowIndex in rowData) {
                if (rowData[existingRowIndex].id === id && existingRowIndex != rowIndex) {
                    alert(`Item "${name}" is already added in Row ${parseInt(existingRowIndex) + 1}. Focus moved to that row.`);

                    // Clear current row
                    const currentRow = document.querySelector(`.item-search[data-row="${rowIndex}"]`).closest('tr');
                    const currentInput = currentRow.querySelector('.item-search');
                    currentInput.value = '';

                    // Focus on existing row's quantity input
                    const existingRow = document.querySelector(`.item-search[data-row="${existingRowIndex}"]`).closest('tr');
                    const existingQtyInput = existingRow.querySelector('.qty-input');
                    existingQtyInput.focus();
                    existingQtyInput.select();

                    return;
                }
            }

            rowData[rowIndex] = {
                id,
                name,
                price,
                stock,
                quantity: 1
            };

            const row = document.querySelector(`.item-search[data-row="${rowIndex}"]`).closest('tr');
            const itemInput = row.querySelector('.item-search');
            const priceCell = row.querySelector('.price-cell');
            const qtyInput = row.querySelector('.qty-input');
            const removeBtn = row.querySelector('.remove-btn');

            itemInput.value = name;
            itemInput.disabled = true;
            priceCell.textContent = `₹${price.toFixed(2)}`;
            qtyInput.max = stock;
            removeBtn.style.visibility = 'visible';

            updateRowDisplay(rowIndex);
        }

        function updateRowDisplay(rowIndex) {
            if (!rowData[rowIndex]) return;

            const row = document.querySelector(`.item-search[data-row="${rowIndex}"]`).closest('tr');
            const qtyInput = row.querySelector('.qty-input');
            const amountCell = row.querySelector('.amount-cell');

            const quantity = parseInt(qtyInput.value) || 1;
            rowData[rowIndex].quantity = Math.min(quantity, rowData[rowIndex].stock);
            qtyInput.value = rowData[rowIndex].quantity;

            const amount = rowData[rowIndex].price * rowData[rowIndex].quantity;
            amountCell.innerHTML = `<strong>₹${amount.toFixed(2)}</strong>`;
        }

        function addRowToCart(rowIndex) {
            if (!rowData[rowIndex]) return;

            const data = rowData[rowIndex];

            // Check if item already exists in cart (from another row that was already added)
            const existingItem = cart.find(item => item.id === data.id);
            if (existingItem) {
                // Update quantity in cart
                existingItem.quantity = data.quantity;
            } else {
                // Add new item to cart
                cart.push({
                    ...data
                });
            }

            updateTotals();
            calculateChange();

            // Remove empty cart message if exists
            const emptyRow = document.querySelector('.empty-cart');
            if (emptyRow) {
                emptyRow.closest('tr').remove();
            }
        }

        function addNewRow() {
            currentRow++;
            const tbody = document.getElementById('cartTableBody');

            const newRow = document.createElement('tr');
            newRow.classList.add('search-row');
            newRow.innerHTML = `
                <td>${currentRow + 1}</td>
                <td>
                    <div class="search-container">
                        <input type="text"
                            class="item-search"
                            placeholder="Enter item name or code..."
                            autocomplete="off"
                            data-row="${currentRow}">
                        <div class="dropdown">
                            ${Array.from(document.querySelectorAll('.dropdown-item')).map(item => item.outerHTML).join('')}
                        </div>
                    </div>
                </td>
                <td class="price-cell">₹0.00</td>
                <td>
                    <input type="number" 
                           class="qty-input" 
                           value="1" 
                           min="1"
                           data-row="${currentRow}"
                           disabled>
                </td>
                <td class="amount-cell"><strong>₹0.00</strong></td>
                <td>
                    <button class="remove-btn" onclick="removeRow(${currentRow})" style="visibility: hidden;">X</button>
                </td>
            `;

            tbody.appendChild(newRow);

            // Initialize listeners for the new row
            initializeSearchListeners();

            // Focus on the new row's search input
            setTimeout(() => {
                const newInput = document.querySelector(`.item-search[data-row="${currentRow}"]`);
                if (newInput) newInput.focus();
            }, 50);

            // Enable print button if cart has items
            if (cart.length > 0) {
                document.getElementById('printBtn').disabled = false;
            }
        }

        window.removeRow = function(rowIndex) {
            if (!rowData[rowIndex]) return;

            const data = rowData[rowIndex];

            // Remove from cart
            const cartIndex = cart.findIndex(item => item.id === data.id);
            if (cartIndex !== -1) {
                cart.splice(cartIndex, 1);
            }

            // Remove row data
            delete rowData[rowIndex];

            // Remove the row from DOM
            const row = document.querySelector(`.item-search[data-row="${rowIndex}"]`);
            if (row) {
                const tr = row.closest('tr');
                tr.remove();
            }

            updateTotals();
            calculateChange();

            // Update row numbers
            updateRowNumbers();

            // If no rows left except empty message, add first row
            const searchRows = document.querySelectorAll('.search-row');
            if (searchRows.length === 0) {
                document.getElementById('printBtn').disabled = true;
                const tbody = document.getElementById('cartTableBody');
                tbody.innerHTML = `
                    <tr class="search-row">
                        <td>1</td>
                        <td>
                            <div class="search-container">
                                <input type="text"
                                    class="item-search"
                                    placeholder="Enter item name or code..."
                                    autocomplete="off"
                                    data-row="0">
                                <div class="dropdown">
                                    ${Array.from(document.querySelectorAll('.dropdown-item')).map(item => item.outerHTML).join('')}
                                </div>
                            </div>
                        </td>
                        <td class="price-cell">₹0.00</td>
                        <td>
                            <input type="number" 
                                   class="qty-input" 
                                   value="1" 
                                   min="1"
                                   data-row="0"
                                   disabled>
                        </td>
                        <td class="amount-cell"><strong>₹0.00</strong></td>
                        <td>
                            <button class="remove-btn" onclick="removeRow(0)" style="visibility: hidden;">X</button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" class="empty-cart">
                            <p style="font-size: 14px; color: #7f8c8d;">Start typing to search items...</p>
                        </td>
                    </tr>
                `;
                currentRow = 0;
                rowData = {};
                initializeSearchListeners();
            }

            // If cart is empty, show empty message
            if (cart.length === 0) {
                document.getElementById('printBtn').disabled = true;
                const emptyExists = document.querySelector('.empty-cart');
                if (!emptyExists) {
                    const tbody = document.getElementById('cartTableBody');
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = `
                        <td colspan="6" class="empty-cart">
                            <p style="font-size: 14px; color: #7f8c8d;">Start typing to search items...</p>
                        </td>
                    `;
                    tbody.appendChild(emptyRow);
                }
            }
        };

        function updateRowNumbers() {
            const rows = document.querySelectorAll('.search-row');
            rows.forEach((row, index) => {
                row.querySelector('td:first-child').textContent = index + 1;
            });
        }

        // Update date time
        function updateDateTime() {
            const now = new Date();
            const options = {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            };
            document.getElementById('currentDateTime').textContent = now.toLocaleString('en-GB', options);
        }
        setInterval(updateDateTime, 1000);

        function highlightDropdownItem(visibleItems) {
            visibleItems.forEach((item, index) => {
                if (index === selectedDropdownIndex) {
                    item.classList.add('highlighted');
                    item.scrollIntoView({
                        block: 'nearest',
                        behavior: 'smooth'
                    });
                } else {
                    item.classList.remove('highlighted');
                }
            });
        }

        function addItemToCart(item) {
            // This function is no longer used but kept for compatibility
        }

        // Global keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // F2 - Focus on current/first search input
            if (e.key === 'F2') {
                e.preventDefault();
                const searchInputs = document.querySelectorAll('.item-search:not([disabled])');
                if (searchInputs.length > 0) {
                    const lastInput = searchInputs[searchInputs.length - 1];
                    lastInput.focus();
                    lastInput.select();
                }
            }
            // F8 - Go to payment
            else if (e.key === 'F8') {
                e.preventDefault();
                if (cart.length > 0) {
                    if (currentPaymentMethod === 'cash') {
                        document.getElementById('amountReceived').focus();
                        document.getElementById('amountReceived').select();
                    } else {
                        document.getElementById('printBtn').focus();
                    }
                }
            }
            // F9 - Print bill
            else if (e.key === 'F9') {
                e.preventDefault();
                document.getElementById('printBtn').click();
            }
            // F10 - Clear cart
            else if (e.key === 'F10') {
                e.preventDefault();
                document.getElementById('clearBtn').click();
            }
        });

        // Render cart - simplified version
        function renderCart() {
            // This function is no longer needed but kept for compatibility
            updateTotals();
            calculateChange();
        }

        // Update quantity - modified for new structure
        window.updateQuantity = function(id, newQty) {
            const quantity = parseInt(newQty);
            const item = cart.find(i => i.id === id);

            if (!item) return;

            if (quantity <= 0) {
                cart = cart.filter(i => i.id !== id);
            } else if (quantity <= item.stock) {
                item.quantity = quantity;
            } else {
                alert('Stock limit reached!');
            }

            updateTotals();
            calculateChange();
        };

        // Remove item - simplified
        window.removeItem = function(id) {
            cart = cart.filter(item => item.id !== id);
            updateTotals();
            calculateChange();
        };

        // Update totals
        function updateTotals() {
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('grandTotal').textContent = `₹${total.toFixed(2)}`;
        }

        // Payment method selection
        document.querySelectorAll('.payment-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.payment-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentPaymentMethod = this.dataset.method;

                const cashSection = document.getElementById('cashInputSection');
                if (currentPaymentMethod === 'cash') {
                    cashSection.style.display = 'block';
                } else {
                    cashSection.style.display = 'none';
                }
            });
        });

        // Calculate change
        document.getElementById('amountReceived').addEventListener('input', calculateChange);

        // Auto-focus amount received when items are in cart
        function autoFocusPayment() {
            if (cart.length > 0 && currentPaymentMethod === 'cash') {
                setTimeout(() => {
                    document.getElementById('amountReceived').focus();
                }, 100);
            }
        }

        function calculateChange() {
            if (currentPaymentMethod !== 'cash') return;

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const received = parseFloat(document.getElementById('amountReceived').value) || 0;
            const change = received - total;

            const changeBox = document.getElementById('changeBox');
            const changeAmount = document.getElementById('changeAmount');

            if (received > 0) {
                changeBox.style.display = 'block';
                changeAmount.textContent = `₹${Math.abs(change).toFixed(2)}`;

                if (change < 0) {
                    changeBox.classList.add('negative');
                    changeBox.querySelector('.change-label').textContent = 'AMOUNT PENDING:';
                } else {
                    changeBox.classList.remove('negative');
                    changeBox.querySelector('.change-label').textContent = 'CHANGE TO RETURN:';
                }
            } else {
                changeBox.style.display = 'none';
            }
        }

        // Clear cart
        document.getElementById('clearBtn').addEventListener('click', () => {
            if (confirm('Clear all items from cart?')) {
                cart = [];
                rowData = {};
                currentRow = 0;
                document.getElementById('amountReceived').value = '';

                // Reset tbody
                const tbody = document.getElementById('cartTableBody');
                tbody.innerHTML = `
                    <tr class="search-row">
                        <td>1</td>
                        <td>
                            <div class="search-container">
                                <input type="text"
                                    class="item-search"
                                    placeholder="Enter item name or code..."
                                    autocomplete="off"
                                    data-row="0">
                                <div class="dropdown">
                                    ${Array.from(document.querySelectorAll('.dropdown-item')).map(item => item.outerHTML).join('')}
                                </div>
                            </div>
                        </td>
                        <td class="price-cell">₹0.00</td>
                        <td>
                            <input type="number" 
                                   class="qty-input" 
                                   value="1" 
                                   min="1"
                                   data-row="0"
                                   disabled>
                        </td>
                        <td class="amount-cell"><strong>₹0.00</strong></td>
                        <td>
                            <button class="remove-btn" onclick="removeRow(0)" style="visibility: hidden;">X</button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" class="empty-cart">
                            <p style="font-size: 14px; color: #7f8c8d;">Start typing to search items...</p>
                        </td>
                    </tr>
                `;

                initializeSearchListeners();
                updateTotals();

                setTimeout(() => {
                    const searchInput = document.querySelector('.item-search[data-row="0"]');
                    if (searchInput) searchInput.focus();
                }, 50);
            }
        });

        // Print bill
        document.getElementById('printBtn').addEventListener('click', async () => {
            if (cart.length === 0) return;

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

            if (currentPaymentMethod === 'cash') {
                const received = parseFloat(document.getElementById('amountReceived').value) || 0;
                if (received < total) {
                    alert('Amount received is less than total amount!');
                    return;
                }
            }

            const printBtn = document.getElementById('printBtn');
            printBtn.disabled = true;
            printBtn.textContent = '⏳ Processing...';

            const orderData = {
                bill_number: document.getElementById('billNumber').textContent,
                items: cart.map(item => ({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    quantity: item.quantity
                })),
                total_amount: total,
                payment_method: currentPaymentMethod,
                amount_received: currentPaymentMethod === 'cash' ? parseFloat(document.getElementById('amountReceived').value) : total
            };

            try {
                const response = await fetch('./process_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });

                const result = await response.json();

                if (result.success) {
                    // Print receipt
                    window.open('../cashier/print_receipt.php?order_id=' + result.order_id, '_blank');

                    alert('Order completed successfully!\nBill Number: ' + orderData.bill_number);

                    // Reset
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Error: ' + result.message);
                    printBtn.disabled = false;
                    printBtn.textContent = '🖨️ PRINT BILL';
                }
            } catch (error) {
                alert('Error processing order: ' + error.message);
                printBtn.disabled = false;
                printBtn.textContent = '🖨️ PRINT BILL';
            }
        });

        // WebSocket for real-time stock updates
        function connectWebSocket() {
            try {
                ws = new WebSocket('ws://localhost:8080');

                ws.onopen = function() {
                    isConnected = true;
                    document.getElementById('wsStatus').className = 'ws-status ws-connected';
                    document.getElementById('wsStatus').textContent = '● Live Updates Active';
                };

                ws.onmessage = function(event) {
                    const message = JSON.parse(event.data);
                    if (message.type === 'stock_update' || message.type === 'initial_stock') {
                        updateStockFromWS(message.data);
                    }
                };

                ws.onerror = function() {
                    isConnected = false;
                    document.getElementById('wsStatus').className = 'ws-status ws-disconnected';
                    document.getElementById('wsStatus').textContent = '● Disconnected';
                };

                ws.onclose = function() {
                    isConnected = false;
                    document.getElementById('wsStatus').className = 'ws-status ws-disconnected';
                    document.getElementById('wsStatus').textContent = '● Reconnecting...';
                    setTimeout(connectWebSocket, 3000);
                };
            } catch (error) {
                setTimeout(connectWebSocket, 3000);
            }
        }

        function updateStockFromWS(items) {
            items.forEach(item => {
                const dropdownItem = document.querySelector(`.dropdown-item[data-id="${item.id}"]`);
                if (dropdownItem) {
                    dropdownItem.dataset.stock = item.quantity_available;
                    const stockSpan = dropdownItem.querySelector('.item-stock');
                    if (stockSpan) {
                        stockSpan.textContent = `Stock: ${item.quantity_available}`;
                    }
                }

                // Update cart if item exists
                const cartItem = cart.find(c => c.id == item.id);
                if (cartItem) {
                    cartItem.stock = item.quantity_available;
                    if (cartItem.quantity > item.quantity_available) {
                        if (item.quantity_available === 0) {
                            cart = cart.filter(c => c.id != item.id);
                        } else {
                            cartItem.quantity = item.quantity_available;
                        }
                        renderCart();
                    }
                }
            });
        }

        // Initialize
        window.addEventListener('load', () => {
            connectWebSocket();
            initializeSearchListeners();
            const searchInput = document.querySelector('.item-search[data-row="0"]');
            if (searchInput) searchInput.focus();
        });
    </script>
</body>

</html>