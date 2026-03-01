<?php
/**
 * Reports
 * Pharmacy Management System
 */
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

$reportType = $_GET['type'] ?? 'daily';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');
$exportCsv = isset($_GET['export']);

// Build report data based on type
$reportData = [];
$reportTitle = '';
$columns = [];

switch ($reportType) {
    case 'daily':
        $reportTitle = 'Daily Sales Report — ' . date('d M Y', strtotime($dateFrom));
        $columns = ['Sale #', 'Time', 'Items', 'Total Amount', 'Sold By'];
        $stmt = $db->prepare("SELECT s.id, s.sale_date, s.total_amount, u.name as user_name,
            (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
            FROM sales s LEFT JOIN users u ON s.created_by = u.id
            WHERE DATE(s.sale_date) = :dt ORDER BY s.sale_date ASC");
        $stmt->execute(['dt' => $dateFrom]);
        $reportData = $stmt->fetchAll();
        break;

    case 'monthly':
        $reportTitle = 'Monthly Sales Report — ' . date('F Y', strtotime($month . '-01'));
        $columns = ['Date', 'Sales Count', 'Total Amount'];
        $stmt = $db->prepare("SELECT DATE(sale_date) as sale_day, COUNT(*) as sale_count, SUM(total_amount) as total
            FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = :mn
            GROUP BY DATE(sale_date) ORDER BY sale_day ASC");
        $stmt->execute(['mn' => $month]);
        $reportData = $stmt->fetchAll();
        break;

    case 'daterange':
        $reportTitle = "Sales Report — " . date('d M Y', strtotime($dateFrom)) . " to " . date('d M Y', strtotime($dateTo));
        $columns = ['Date', 'Sales Count', 'Total Amount'];
        $stmt = $db->prepare("SELECT DATE(sale_date) as sale_day, COUNT(*) as sale_count, SUM(total_amount) as total
            FROM sales WHERE DATE(sale_date) BETWEEN :df AND :dt
            GROUP BY DATE(sale_date) ORDER BY sale_day ASC");
        $stmt->execute(['df' => $dateFrom, 'dt' => $dateTo]);
        $reportData = $stmt->fetchAll();
        break;

    case 'expired':
        $reportTitle = 'Expired Medicines Report';
        $columns = ['Medicine', 'Batch', 'Expiry Date', 'Stock', 'Purchase Value', 'Sale Value'];
        $reportData = $db->query("SELECT name, batch_number, expiry_date, quantity, 
            purchase_price * quantity as purchase_value, sale_price * quantity as sale_value
            FROM medicines WHERE expiry_date < CURDATE() AND quantity > 0 ORDER BY expiry_date ASC")->fetchAll();
        break;

    case 'lowstock':
        $reportTitle = 'Low Stock Medicines Report';
        $columns = ['Medicine', 'Batch', 'Current Stock', 'Expiry Date', 'Supplier'];
        $stmt = $db->prepare("SELECT m.name, m.batch_number, m.quantity, m.expiry_date, s.name as supplier_name
            FROM medicines m LEFT JOIN suppliers s ON m.supplier_id = s.id
            WHERE m.quantity > 0 AND m.quantity <= :threshold ORDER BY m.quantity ASC");
        $stmt->execute(['threshold' => LOW_STOCK_THRESHOLD]);
        $reportData = $stmt->fetchAll();
        break;
}

// CSV Export
if ($exportCsv && !empty($reportData)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $columns);
    foreach ($reportData as $row) {
        fputcsv($output, array_values($row));
    }
    fclose($output);
    exit;
}
?>

<h4 class="mb-3"><i class="bi bi-bar-chart"></i> Reports</h4>

<!-- Report Type Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $reportType === 'daily' ? 'active' : '' ?>" href="?type=daily">
            <i class="bi bi-calendar-day"></i> Daily Sales
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $reportType === 'monthly' ? 'active' : '' ?>" href="?type=monthly">
            <i class="bi bi-calendar-month"></i> Monthly Sales
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $reportType === 'daterange' ? 'active' : '' ?>" href="?type=daterange">
            <i class="bi bi-calendar-range"></i> Date Range
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $reportType === 'expired' ? 'active text-danger' : '' ?>" href="?type=expired">
            <i class="bi bi-exclamation-octagon"></i> Expired Medicines
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $reportType === 'lowstock' ? 'active text-warning' : '' ?>" href="?type=lowstock">
            <i class="bi bi-box-seam"></i> Low Stock
        </a>
    </li>
</ul>

<!-- Filters -->
<?php if ($reportType === 'daily'): ?>
<form method="GET" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="type" value="daily">
    <div class="col-md-3">
        <label class="form-label fw-semibold">Date</label>
        <input type="date" class="form-control" name="date_from" value="<?= h($dateFrom) ?>">
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Show</button>
    </div>
</form>
<?php elseif ($reportType === 'monthly'): ?>
<form method="GET" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="type" value="monthly">
    <div class="col-md-3">
        <label class="form-label fw-semibold">Month</label>
        <input type="month" class="form-control" name="month" value="<?= h($month) ?>">
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Show</button>
    </div>
</form>
<?php elseif ($reportType === 'daterange'): ?>
<form method="GET" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="type" value="daterange">
    <div class="col-md-3">
        <label class="form-label fw-semibold">From</label>
        <input type="date" class="form-control" name="date_from" value="<?= h($dateFrom) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">To</label>
        <input type="date" class="form-control" name="date_to" value="<?= h($dateTo) ?>">
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Show</button>
    </div>
</form>
<?php endif; ?>

<!-- Report Title & Export -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><?= h($reportTitle) ?></h5>
    <?php if (!empty($reportData)): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
    </a>
    <?php endif; ?>
</div>

<!-- Report Table -->
<div class="table-responsive">
    <table class="table table-bordered table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <?php foreach ($columns as $col): ?>
                    <th><?= h($col) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reportData)): ?>
                <tr><td colspan="<?= count($columns) ?>" class="text-center text-muted py-3">No data found for this report.</td></tr>
            <?php else: ?>
                <?php if ($reportType === 'daily'): ?>
                    <?php $dayTotal = 0; foreach ($reportData as $r): $dayTotal += $r['total_amount']; ?>
                    <tr>
                        <td><a href="/pharmacy-system/admin/sale_detail.php?id=<?= $r['id'] ?>">#<?= $r['id'] ?></a></td>
                        <td><?= date('h:i A', strtotime($r['sale_date'])) ?></td>
                        <td><?= $r['item_count'] ?></td>
                        <td class="fw-semibold">$<?= number_format($r['total_amount'], 2) ?></td>
                        <td><?= h($r['user_name'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Day Total:</td>
                            <td class="fw-bold">$<?= number_format($dayTotal, 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>

                <?php elseif ($reportType === 'monthly' || $reportType === 'daterange'): ?>
                    <?php $periodTotal = 0; $periodCount = 0; foreach ($reportData as $r): 
                        $periodTotal += $r['total']; $periodCount += $r['sale_count']; ?>
                    <tr>
                        <td><?= date('d M Y (D)', strtotime($r['sale_day'])) ?></td>
                        <td><?= $r['sale_count'] ?></td>
                        <td class="fw-semibold">$<?= number_format($r['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td class="text-end fw-bold">Total:</td>
                            <td class="fw-bold"><?= $periodCount ?></td>
                            <td class="fw-bold">$<?= number_format($periodTotal, 2) ?></td>
                        </tr>
                    </tfoot>

                <?php elseif ($reportType === 'expired'): ?>
                    <?php $totalPurchaseLoss = 0; $totalSaleLoss = 0; foreach ($reportData as $r): 
                        $totalPurchaseLoss += $r['purchase_value']; $totalSaleLoss += $r['sale_value']; ?>
                    <tr class="table-danger">
                        <td class="fw-semibold"><?= h($r['name']) ?></td>
                        <td><code><?= h($r['batch_number']) ?></code></td>
                        <td><?= h($r['expiry_date']) ?></td>
                        <td><?= $r['quantity'] ?></td>
                        <td>$<?= number_format($r['purchase_value'], 2) ?></td>
                        <td>$<?= number_format($r['sale_value'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-danger">
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Total Loss:</td>
                            <td class="fw-bold">$<?= number_format($totalPurchaseLoss, 2) ?></td>
                            <td class="fw-bold">$<?= number_format($totalSaleLoss, 2) ?></td>
                        </tr>
                    </tfoot>

                <?php elseif ($reportType === 'lowstock'): ?>
                    <?php foreach ($reportData as $r): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($r['name']) ?></td>
                        <td><code><?= h($r['batch_number']) ?></code></td>
                        <td><span class="badge bg-warning text-dark"><?= $r['quantity'] ?></span></td>
                        <td><?= h($r['expiry_date']) ?></td>
                        <td><?= h($r['supplier_name'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>

                <?php endif; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
