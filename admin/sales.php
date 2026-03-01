<?php
/**
 * Sales History
 * Pharmacy Management System
 */
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Date filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Build query
$query = "SELECT s.id, s.sale_date, s.total_amount, s.notes, u.name as user_name,
          (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
          FROM sales s 
          LEFT JOIN users u ON s.created_by = u.id 
          WHERE DATE(s.sale_date) BETWEEN :date_from AND :date_to
          ORDER BY s.sale_date DESC";
$stmt = $db->prepare($query);
$stmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
$sales = $stmt->fetchAll();

// Summary
$totalAmount = array_sum(array_column($sales, 'total_amount'));
$totalSales = count($sales);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-receipt"></i> Sales History</h4>
    <a href="/pharmacy-system/admin/new_sale.php" class="btn btn-primary">
        <i class="bi bi-cart-plus"></i> New Sale
    </a>
</div>

<!-- Date Filter -->
<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-3">
        <label class="form-label fw-semibold">From</label>
        <input type="date" class="form-control" name="date_from" value="<?= h($dateFrom) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">To</label>
        <input type="date" class="form-control" name="date_to" value="<?= h($dateTo) ?>">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
    </div>
    <div class="col-md-4 text-end">
        <div class="alert alert-success py-2 mb-0 d-inline-block">
            <strong>Total: $<?= number_format($totalAmount, 2) ?></strong> from <strong><?= $totalSales ?></strong> sale(s)
        </div>
    </div>
</form>

<!-- Sales Table -->
<div class="table-responsive">
    <table class="table table-bordered table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>Sale #</th>
                <th>Date & Time</th>
                <th>Items</th>
                <th>Total Amount</th>
                <th>Sold By</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sales)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No sales found for the selected period.</td></tr>
            <?php else: ?>
                <?php foreach ($sales as $s): ?>
                <tr>
                    <td><strong>#<?= $s['id'] ?></strong></td>
                    <td><?= date('d M Y, h:i A', strtotime($s['sale_date'])) ?></td>
                    <td><span class="badge bg-secondary"><?= $s['item_count'] ?> item(s)</span></td>
                    <td class="fw-bold text-success">$<?= number_format($s['total_amount'], 2) ?></td>
                    <td><?= h($s['user_name'] ?? 'N/A') ?></td>
                    <td><?= h($s['notes'] ?: '—') ?></td>
                    <td>
                        <a href="/pharmacy-system/admin/sale_detail.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
