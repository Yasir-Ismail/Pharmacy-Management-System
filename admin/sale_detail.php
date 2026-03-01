<?php
/**
 * Sale Detail View
 * Pharmacy Management System
 */
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

$saleId = (int) ($_GET['id'] ?? 0);
if ($saleId <= 0) {
    setFlash('danger', 'Invalid sale ID.');
    header('Location: /pharmacy-system/admin/sales.php');
    exit;
}

// Get sale
$stmt = $db->prepare("SELECT s.*, u.name as user_name FROM sales s LEFT JOIN users u ON s.created_by = u.id WHERE s.id = :id");
$stmt->execute(['id' => $saleId]);
$sale = $stmt->fetch();

if (!$sale) {
    setFlash('danger', 'Sale not found.');
    header('Location: /pharmacy-system/admin/sales.php');
    exit;
}

// Get sale items
$stmt = $db->prepare("SELECT si.*, m.name as medicine_name, m.batch_number 
    FROM sale_items si 
    JOIN medicines m ON si.medicine_id = m.id 
    WHERE si.sale_id = :sid 
    ORDER BY si.id");
$stmt->execute(['sid' => $saleId]);
$items = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-receipt"></i> Sale #<?= $saleId ?></h4>
    <div>
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> Print
        </button>
        <a href="/pharmacy-system/admin/sales.php" class="btn btn-outline-dark">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-dark text-white fw-semibold">
                Sale Items
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Medicine</th>
                            <th>Batch</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= h($item['medicine_name']) ?></td>
                            <td><code><?= h($item['batch_number']) ?></code></td>
                            <td class="text-end">$<?= number_format($item['price'], 2) ?></td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-end fw-semibold">$<?= number_format($item['total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="5" class="text-end fw-bold fs-5">Grand Total:</td>
                            <td class="text-end fw-bold fs-5">$<?= number_format($sale['total_amount'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold">Sale Details</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Sale ID:</td>
                        <td class="fw-bold">#<?= $saleId ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Date:</td>
                        <td><?= date('d M Y', strtotime($sale['sale_date'])) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Time:</td>
                        <td><?= date('h:i A', strtotime($sale['sale_date'])) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Sold By:</td>
                        <td><?= h($sale['user_name'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Items:</td>
                        <td><?= count($items) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total:</td>
                        <td class="fw-bold text-success fs-5">$<?= number_format($sale['total_amount'], 2) ?></td>
                    </tr>
                    <?php if (!empty($sale['notes'])): ?>
                    <tr>
                        <td class="text-muted">Notes:</td>
                        <td><?= h($sale['notes']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
