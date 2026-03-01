<?php
/**
 * Expiry Alerts
 * Pharmacy Management System
 */
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$today = date('Y-m-d');

// Expired medicines
$expired = $db->query("SELECT m.*, s.name as supplier_name FROM medicines m 
    LEFT JOIN suppliers s ON m.supplier_id = s.id 
    WHERE m.expiry_date < CURDATE() AND m.quantity > 0 
    ORDER BY m.expiry_date ASC")->fetchAll();

// Expiring soon
$stmt = $db->prepare("SELECT m.*, s.name as supplier_name FROM medicines m 
    LEFT JOIN suppliers s ON m.supplier_id = s.id 
    WHERE m.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY) AND m.quantity > 0 
    ORDER BY m.expiry_date ASC");
$stmt->execute(['days' => EXPIRY_WARNING_DAYS]);
$expiringSoon = $stmt->fetchAll();

$totalExpiredValue = 0;
foreach ($expired as $m) {
    $totalExpiredValue += $m['purchase_price'] * $m['quantity'];
}
?>

<h4 class="mb-3"><i class="bi bi-calendar-x text-danger"></i> Expiry Alerts</h4>

<?php if (empty($expired) && empty($expiringSoon)): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> No expiry concerns. All medicines are within safe dates.
    </div>
<?php endif; ?>

<!-- Expired Medicines -->
<?php if (!empty($expired)): ?>
<div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-exclamation-octagon"></i> EXPIRED Medicines (<?= count($expired) ?>)</span>
        <span>Loss Value: $<?= number_format($totalExpiredValue, 2) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="alert alert-danger m-3 mb-0">
            <i class="bi bi-shield-exclamation"></i> <strong>These medicines CANNOT be sold.</strong> The system blocks all sales of expired stock.
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Expired On</th>
                        <th>Days Ago</th>
                        <th>Stock</th>
                        <th>Loss (Cost)</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expired as $m): 
                        $daysAgo = (int)((strtotime($today) - strtotime($m['expiry_date'])) / 86400);
                        $loss = $m['purchase_price'] * $m['quantity'];
                    ?>
                    <tr class="table-danger">
                        <td class="fw-semibold"><?= h($m['name']) ?></td>
                        <td><code><?= h($m['batch_number']) ?></code></td>
                        <td class="fw-bold"><?= h($m['expiry_date']) ?></td>
                        <td><span class="badge bg-danger"><?= $daysAgo ?> day(s) ago</span></td>
                        <td><?= $m['quantity'] ?></td>
                        <td class="fw-bold text-danger">$<?= number_format($loss, 2) ?></td>
                        <td><?= h($m['supplier_name'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Expiring Soon -->
<?php if (!empty($expiringSoon)): ?>
<div class="card border-warning mb-4">
    <div class="card-header bg-warning text-dark fw-semibold">
        <i class="bi bi-clock-history"></i> Expiring Within <?= EXPIRY_WARNING_DAYS ?> Days (<?= count($expiringSoon) ?>)
    </div>
    <div class="card-body p-0">
        <div class="alert alert-warning m-3 mb-0">
            <i class="bi bi-info-circle"></i> These medicines should be sold first or returned to supplier before expiry.
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Expires On</th>
                        <th>Days Left</th>
                        <th>Stock</th>
                        <th>Sale Price</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiringSoon as $m): 
                        $daysLeft = (int)((strtotime($m['expiry_date']) - strtotime($today)) / 86400);
                    ?>
                    <tr class="<?= $daysLeft <= 7 ? 'table-danger' : 'table-warning' ?>">
                        <td class="fw-semibold"><?= h($m['name']) ?></td>
                        <td><code><?= h($m['batch_number']) ?></code></td>
                        <td class="fw-bold"><?= h($m['expiry_date']) ?></td>
                        <td>
                            <span class="badge <?= $daysLeft <= 7 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                <?= $daysLeft ?> day(s)
                            </span>
                        </td>
                        <td><?= $m['quantity'] ?></td>
                        <td>$<?= number_format($m['sale_price'], 2) ?></td>
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
