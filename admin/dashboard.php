<?php
/**
 * Dashboard
 * Pharmacy Management System
 */
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Total medicines
$totalMedicines = $db->query("SELECT COUNT(*) FROM medicines")->fetchColumn();

// Total stock value (purchase)
$stockValue = $db->query("SELECT COALESCE(SUM(purchase_price * quantity), 0) FROM medicines")->fetchColumn();

// Total stock value (sale)
$stockSaleValue = $db->query("SELECT COALESCE(SUM(sale_price * quantity), 0) FROM medicines")->fetchColumn();

// Today's sales
$todaySales = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetchColumn();
$todaySaleCount = $db->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetchColumn();

// This month's sales
$monthSales = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE YEAR(sale_date) = YEAR(CURDATE()) AND MONTH(sale_date) = MONTH(CURDATE())")->fetchColumn();

// Out of stock
$outOfStock = $db->query("SELECT COUNT(*) FROM medicines WHERE quantity = 0")->fetchColumn();

// Expired medicines list (top 5)
$expiredMeds = $db->query("SELECT id, name, batch_number, expiry_date, quantity FROM medicines WHERE expiry_date < CURDATE() AND quantity > 0 ORDER BY expiry_date ASC LIMIT 5")->fetchAll();

// Expiring soon (top 5)
$stmt = $db->prepare("SELECT id, name, batch_number, expiry_date, quantity FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY) AND quantity > 0 ORDER BY expiry_date ASC LIMIT 5");
$stmt->execute(['days' => EXPIRY_WARNING_DAYS]);
$expiringSoonMeds = $stmt->fetchAll();

// Low stock (top 5)
$stmt = $db->prepare("SELECT id, name, batch_number, quantity FROM medicines WHERE quantity > 0 AND quantity <= :threshold ORDER BY quantity ASC LIMIT 5");
$stmt->execute(['threshold' => LOW_STOCK_THRESHOLD]);
$lowStockMeds = $stmt->fetchAll();

// Recent sales (last 5)
$recentSales = $db->query("SELECT s.id, s.sale_date, s.total_amount, u.name as user_name FROM sales s LEFT JOIN users u ON s.created_by = u.id ORDER BY s.sale_date DESC LIMIT 5")->fetchAll();
?>

<h4 class="mb-3"><i class="bi bi-speedometer2"></i> Dashboard</h4>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Medicines</h6>
                        <h3 class="fw-bold text-primary mb-0"><?= $totalMedicines ?></h3>
                    </div>
                    <i class="bi bi-capsule fs-1 text-primary opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Today's Sales</h6>
                        <h3 class="fw-bold text-success mb-0">$<?= number_format($todaySales, 2) ?></h3>
                        <small class="text-muted"><?= $todaySaleCount ?> sale(s)</small>
                    </div>
                    <i class="bi bi-cash-stack fs-1 text-success opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Monthly Sales</h6>
                        <h3 class="fw-bold text-info mb-0">$<?= number_format($monthSales, 2) ?></h3>
                    </div>
                    <i class="bi bi-graph-up fs-1 text-info opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-secondary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Stock Value (Cost)</h6>
                        <h3 class="fw-bold text-secondary mb-0">$<?= number_format($stockValue, 2) ?></h3>
                    </div>
                    <i class="bi bi-box-seam fs-1 text-secondary opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Summary Row -->
<div class="row g-3 mb-4">
    <?php if ($expiredCount > 0): ?>
    <div class="col-md-4">
        <div class="alert alert-danger mb-0 d-flex align-items-center">
            <i class="bi bi-exclamation-octagon fs-3 me-3"></i>
            <div>
                <strong><?= $expiredCount ?> Expired Medicine(s)</strong><br>
                <a href="/pharmacy-system/admin/expiry_alerts.php" class="text-danger">View &amp; take action &rarr;</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($expiringSoonCount > 0): ?>
    <div class="col-md-4">
        <div class="alert alert-warning mb-0 d-flex align-items-center">
            <i class="bi bi-clock-history fs-3 me-3"></i>
            <div>
                <strong><?= $expiringSoonCount ?> Expiring Soon (&le;<?= EXPIRY_WARNING_DAYS ?> days)</strong><br>
                <a href="/pharmacy-system/admin/expiry_alerts.php" class="text-warning">Review now &rarr;</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($lowStockCount > 0): ?>
    <div class="col-md-4">
        <div class="alert alert-info mb-0 d-flex align-items-center">
            <i class="bi bi-box-seam fs-3 me-3"></i>
            <div>
                <strong><?= $lowStockCount ?> Low Stock Item(s) (&le;<?= LOW_STOCK_THRESHOLD ?>)</strong><br>
                <a href="/pharmacy-system/admin/low_stock.php" class="text-info">Reorder now &rarr;</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($outOfStock > 0): ?>
    <div class="col-md-4">
        <div class="alert alert-dark mb-0 d-flex align-items-center">
            <i class="bi bi-x-octagon fs-3 me-3"></i>
            <div>
                <strong><?= $outOfStock ?> Out of Stock</strong><br>
                <small>These medicines need restocking.</small>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <!-- Expired Medicines -->
    <?php if (!empty($expiredMeds)): ?>
    <div class="col-lg-6">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white fw-semibold">
                <i class="bi bi-exclamation-octagon"></i> Expired Medicines
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Medicine</th><th>Batch</th><th>Expired On</th><th>Qty</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiredMeds as $m): ?>
                        <tr class="table-danger">
                            <td class="fw-semibold"><?= h($m['name']) ?></td>
                            <td><?= h($m['batch_number']) ?></td>
                            <td><?= h($m['expiry_date']) ?></td>
                            <td><?= $m['quantity'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <a href="/pharmacy-system/admin/expiry_alerts.php" class="btn btn-outline-danger btn-sm">View All &rarr;</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Expiring Soon -->
    <?php if (!empty($expiringSoonMeds)): ?>
    <div class="col-lg-6">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark fw-semibold">
                <i class="bi bi-clock-history"></i> Expiring Soon (next <?= EXPIRY_WARNING_DAYS ?> days)
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Medicine</th><th>Batch</th><th>Expires On</th><th>Qty</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiringSoonMeds as $m): ?>
                        <tr class="table-warning">
                            <td class="fw-semibold"><?= h($m['name']) ?></td>
                            <td><?= h($m['batch_number']) ?></td>
                            <td><?= h($m['expiry_date']) ?></td>
                            <td><?= $m['quantity'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <a href="/pharmacy-system/admin/expiry_alerts.php" class="btn btn-outline-warning btn-sm">View All &rarr;</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Low Stock -->
    <?php if (!empty($lowStockMeds)): ?>
    <div class="col-lg-6">
        <div class="card border-info">
            <div class="card-header bg-info text-white fw-semibold">
                <i class="bi bi-box-seam"></i> Low Stock Items
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Medicine</th><th>Batch</th><th>Stock</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockMeds as $m): ?>
                        <tr>
                            <td class="fw-semibold"><?= h($m['name']) ?></td>
                            <td><?= h($m['batch_number']) ?></td>
                            <td><span class="badge bg-warning text-dark"><?= $m['quantity'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <a href="/pharmacy-system/admin/low_stock.php" class="btn btn-outline-info btn-sm">View All &rarr;</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Sales -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-dark text-white fw-semibold">
                <i class="bi bi-receipt"></i> Recent Sales
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentSales)): ?>
                    <p class="text-muted text-center py-3 mb-0">No sales recorded yet.</p>
                <?php else: ?>
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Date</th><th>Amount</th><th>By</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSales as $s): ?>
                        <tr>
                            <td><a href="/pharmacy-system/admin/sale_detail.php?id=<?= $s['id'] ?>">#<?= $s['id'] ?></a></td>
                            <td><?= date('d M Y H:i', strtotime($s['sale_date'])) ?></td>
                            <td class="fw-semibold">$<?= number_format($s['total_amount'], 2) ?></td>
                            <td><?= h($s['user_name'] ?? 'N/A') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <div class="card-footer text-end">
                <a href="/pharmacy-system/admin/sales.php" class="btn btn-outline-dark btn-sm">All Sales &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
