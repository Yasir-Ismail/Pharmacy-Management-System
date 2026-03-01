<?php
/**
 * New Sale
 * Pharmacy Management System
 */
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Handle sale submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = json_decode($_POST['sale_items'] ?? '[]', true);
    $notes = trim($_POST['notes'] ?? '');

    if (empty($items)) {
        setFlash('danger', 'Please add at least one item to the sale.');
        header('Location: /pharmacy-system/admin/new_sale.php');
        exit;
    }

    try {
        $db->beginTransaction();

        $grandTotal = 0;

        // Validate all items first
        foreach ($items as $item) {
            $medId = (int) $item['medicine_id'];
            $qty = (int) $item['quantity'];

            if ($qty <= 0) {
                throw new Exception("Invalid quantity for item.");
            }

            // Get medicine details with lock
            $stmt = $db->prepare("SELECT id, name, sale_price, quantity, expiry_date FROM medicines WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $medId]);
            $med = $stmt->fetch();

            if (!$med) {
                throw new Exception("Medicine not found (ID: $medId).");
            }

            // HARD RULE: Cannot sell expired medicine
            if ($med['expiry_date'] < date('Y-m-d')) {
                throw new Exception("Cannot sell EXPIRED medicine: {$med['name']} (Expired: {$med['expiry_date']}).");
            }

            if ($med['quantity'] < $qty) {
                throw new Exception("Insufficient stock for {$med['name']}. Available: {$med['quantity']}, Requested: $qty.");
            }

            $grandTotal += $med['sale_price'] * $qty;
        }

        // Create sale record
        $stmt = $db->prepare("INSERT INTO sales (sale_date, total_amount, created_by, notes) VALUES (NOW(), :total, :user_id, :notes)");
        $stmt->execute([
            'total' => $grandTotal,
            'user_id' => $_SESSION['user_id'],
            'notes' => $notes
        ]);
        $saleId = $db->lastInsertId();

        // Insert sale items and reduce stock
        foreach ($items as $item) {
            $medId = (int) $item['medicine_id'];
            $qty = (int) $item['quantity'];

            $stmt = $db->prepare("SELECT sale_price FROM medicines WHERE id = :id");
            $stmt->execute(['id' => $medId]);
            $med = $stmt->fetch();

            $lineTotal = $med['sale_price'] * $qty;

            // Insert sale item
            $stmt = $db->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, price, total) VALUES (:sid, :mid, :qty, :price, :total)");
            $stmt->execute([
                'sid' => $saleId,
                'mid' => $medId,
                'qty' => $qty,
                'price' => $med['sale_price'],
                'total' => $lineTotal
            ]);

            // Reduce stock
            $stmt = $db->prepare("UPDATE medicines SET quantity = quantity - :qty WHERE id = :id");
            $stmt->execute(['qty' => $qty, 'id' => $medId]);
        }

        $db->commit();
        setFlash('success', "Sale #$saleId completed successfully. Total: $" . number_format($grandTotal, 2));
        header('Location: /pharmacy-system/admin/sale_detail.php?id=' . $saleId);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', $e->getMessage());
        header('Location: /pharmacy-system/admin/new_sale.php');
        exit;
    }
}

// Get available medicines (not expired, in stock)
$medicines = $db->query("SELECT id, name, batch_number, sale_price, quantity, expiry_date 
    FROM medicines 
    WHERE quantity > 0 AND expiry_date >= CURDATE() 
    ORDER BY name ASC")->fetchAll();
?>

<h4 class="mb-3"><i class="bi bi-cart-plus"></i> New Sale</h4>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-dark text-white fw-semibold">
                <i class="bi bi-search"></i> Add Items
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Medicine</label>
                        <select class="form-select" id="medicineSelect">
                            <option value="">-- Select Medicine --</option>
                            <?php foreach ($medicines as $m): ?>
                                <option value="<?= $m['id'] ?>" 
                                    data-name="<?= h($m['name']) ?>"
                                    data-batch="<?= h($m['batch_number']) ?>"
                                    data-price="<?= $m['sale_price'] ?>"
                                    data-stock="<?= $m['quantity'] ?>"
                                    data-expiry="<?= $m['expiry_date'] ?>">
                                    <?= h($m['name']) ?> — Batch: <?= h($m['batch_number']) ?> — Stock: <?= $m['quantity'] ?> — $<?= number_format($m['sale_price'], 2) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Quantity</label>
                        <input type="number" class="form-control" id="itemQty" min="1" value="1">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary w-100" onclick="addItem()">
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                    </div>
                </div>
                <div id="stockInfo" class="mt-2 text-muted small"></div>
            </div>
        </div>

        <!-- Sale Items Table -->
        <div class="card">
            <div class="card-header fw-semibold">
                <i class="bi bi-list-check"></i> Sale Items
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover mb-0" id="saleTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Medicine</th>
                            <th>Batch</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="saleItems">
                        <tr id="emptyRow"><td colspan="7" class="text-center text-muted py-3">No items added yet.</td></tr>
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="5" class="text-end fw-bold fs-5">Grand Total:</td>
                            <td class="fw-bold fs-5" id="grandTotal">$0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-success text-white fw-semibold">
                <i class="bi bi-cash-stack"></i> Complete Sale
            </div>
            <div class="card-body">
                <form method="POST" id="saleForm" onsubmit="return submitSale()">
                    <input type="hidden" name="sale_items" id="saleItemsJson">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes (optional)</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Any notes..."></textarea>
                    </div>
                    <div class="mb-3 p-3 bg-light rounded text-center">
                        <span class="text-muted">Total Amount</span>
                        <h2 class="fw-bold text-success mb-0" id="totalDisplay">$0.00</h2>
                        <small class="text-muted" id="itemCountDisplay">0 items</small>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100" id="submitBtn" disabled>
                        <i class="bi bi-check-circle"></i> Complete Sale
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let saleItems = [];

document.getElementById('medicineSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (this.value) {
        const info = `Stock: ${opt.dataset.stock} | Price: $${parseFloat(opt.dataset.price).toFixed(2)} | Expiry: ${opt.dataset.expiry}`;
        document.getElementById('stockInfo').textContent = info;
        document.getElementById('itemQty').max = opt.dataset.stock;
        document.getElementById('itemQty').value = 1;
    } else {
        document.getElementById('stockInfo').textContent = '';
    }
});

function addItem() {
    const select = document.getElementById('medicineSelect');
    const qtyInput = document.getElementById('itemQty');
    
    if (!select.value) { alert('Please select a medicine.'); return; }
    
    const opt = select.options[select.selectedIndex];
    const medId = parseInt(select.value);
    const qty = parseInt(qtyInput.value);
    const stock = parseInt(opt.dataset.stock);
    const price = parseFloat(opt.dataset.price);
    const name = opt.dataset.name;
    const batch = opt.dataset.batch;

    if (qty <= 0) { alert('Quantity must be at least 1.'); return; }

    // Check total quantity already added for this medicine
    let existingQty = 0;
    saleItems.forEach(item => {
        if (item.medicine_id === medId) existingQty += item.quantity;
    });

    if (existingQty + qty > stock) {
        alert(`Insufficient stock for ${name}. Available: ${stock}, Already added: ${existingQty}, Trying to add: ${qty}`);
        return;
    }

    // Check if same medicine already in list - add to qty
    let found = false;
    saleItems.forEach(item => {
        if (item.medicine_id === medId) {
            item.quantity += qty;
            found = true;
        }
    });

    if (!found) {
        saleItems.push({
            medicine_id: medId,
            name: name,
            batch: batch,
            price: price,
            quantity: qty,
            stock: stock
        });
    }

    renderItems();
    select.value = '';
    qtyInput.value = 1;
    document.getElementById('stockInfo').textContent = '';
}

function removeItem(index) {
    saleItems.splice(index, 1);
    renderItems();
}

function renderItems() {
    const tbody = document.getElementById('saleItems');
    const emptyRow = document.getElementById('emptyRow');
    
    if (saleItems.length === 0) {
        tbody.innerHTML = '<tr id="emptyRow"><td colspan="7" class="text-center text-muted py-3">No items added yet.</td></tr>';
        updateTotals(0);
        return;
    }

    let html = '';
    let grandTotal = 0;

    saleItems.forEach((item, i) => {
        const lineTotal = item.price * item.quantity;
        grandTotal += lineTotal;
        html += `<tr>
            <td>${i + 1}</td>
            <td class="fw-semibold">${escapeHtml(item.name)}</td>
            <td><code>${escapeHtml(item.batch)}</code></td>
            <td>$${item.price.toFixed(2)}</td>
            <td>${item.quantity}</td>
            <td class="fw-semibold">$${lineTotal.toFixed(2)}</td>
            <td><button class="btn btn-sm btn-outline-danger" onclick="removeItem(${i})"><i class="bi bi-trash"></i></button></td>
        </tr>`;
    });

    tbody.innerHTML = html;
    updateTotals(grandTotal);
}

function updateTotals(total) {
    document.getElementById('grandTotal').textContent = '$' + total.toFixed(2);
    document.getElementById('totalDisplay').textContent = '$' + total.toFixed(2);
    document.getElementById('itemCountDisplay').textContent = saleItems.length + ' item(s)';
    document.getElementById('submitBtn').disabled = saleItems.length === 0;
}

function submitSale() {
    if (saleItems.length === 0) {
        alert('Please add at least one item.');
        return false;
    }

    const data = saleItems.map(item => ({
        medicine_id: item.medicine_id,
        quantity: item.quantity
    }));

    document.getElementById('saleItemsJson').value = JSON.stringify(data);
    
    return confirm('Complete this sale? Stock will be reduced immediately.');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
