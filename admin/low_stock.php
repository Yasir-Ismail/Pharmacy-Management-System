<?php
/**
 * Low Stock Alerts
 * Pharmacy Management System
 */
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Low stock medicines
$stmt = $db->prepare("SELECT m.*, s.name as supplier_name FROM medicines m 
    LEFT JOIN suppliers s ON m.supplier_id = s.id 
    WHERE m.quantity > 0 AND m.quantity <= :threshold 
    ORDER BY m.quantity ASC");
$stmt->execute(['threshold' => LOW_STOCK_THRESHOLD]);
$lowStock = $stmt->fetchAll();

// Out of stock
$outOfStock = $db->query("SELECT m.*, s.name as supplier_name FROM medicines m 
    LEFT JOIN suppliers s ON m.supplier_id = s.id 
    WHERE m.quantity = 0 
    ORDER BY m.name ASC")->fetchAll();
?>

<h4 class="mb-3"><i class="bi bi-box-seam text-warning"></i> Low Stock Alerts</h4>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Low stock threshold is set to <strong><?= LOW_STOCK_THRESHOLD ?></strong> units. 
    Medicines at or below this level are flagged for reorder.
</div>

<?php if (empty($lowStock) && empty($outOfStock)): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> All medicines have adequate stock levels.
    </div>
<?php endif; ?>

<!-- Out of Stock -->
<?php if (!empty($outOfStock)): ?>
<div class="card border-dark mb-4">
    <div class="card-header bg-dark text-white fw-semibold">
        <i class="bi bi-x-octagon"></i> Out of Stock (<?= count($outOfStock) ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Purchase Price</th>
                        <th>Sale Price</th>
                        <th>Expiry Date</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($outOfStock as $m): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($m['name']) ?></td>
                        <td><code><?= h($m['batch_number']) ?></code></td>
                        <td>$<?= number_format($m['purchase_price'], 2) ?></td>
                        <td>$<?= number_format($m['sale_price'], 2) ?></td>
                        <td><?= h($m['expiry_date']) ?></td>
                        <td><?= h($m['supplier_name'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Low Stock -->
<?php if (!empty($lowStock)): ?>
<div class="card border-warning mb-4">
    <div class="card-header bg-warning text-dark fw-semibold">
        <i class="bi bi-exclamation-triangle"></i> Low Stock (<?= count($lowStock) ?>) — at or below <?= LOW_STOCK_THRESHOLD ?> units
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Current Stock</th>
                        <th>Purchase Price</th>
                        <th>Sale Price</th>
                        <th>Expiry Date</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStock as $m): 
                        $stockPct = ($m['quantity'] / LOW_STOCK_THRESHOLD) * 100;
                        $barColor = $stockPct <= 30 ? 'bg-danger' : ($stockPct <= 60 ? 'bg-warning' : 'bg-info');
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= h($m['name']) ?></td>
                        <td><code><?= h($m['batch_number']) ?></code></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-warning text-dark me-2"><?= $m['quantity'] ?></span>
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar <?= $barColor ?>" style="width: <?= min(100, $stockPct) ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td>$<?= number_format($m['purchase_price'], 2) ?></td>
                        <td>$<?= number_format($m['sale_price'], 2) ?></td>
                        <td><?= h($m['expiry_date']) ?></td>
                        <td><?= h($m['supplier_name'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
