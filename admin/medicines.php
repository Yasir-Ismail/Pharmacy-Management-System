<?php
/**
 * Medicine Management - List, Add, Edit, Delete
 * Pharmacy Management System
 */
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int) $_GET['delete'];
    try {
        // Check if medicine is in any sale
        $check = $db->prepare("SELECT COUNT(*) FROM sale_items WHERE medicine_id = :id");
        $check->execute(['id' => $id]);
        if ($check->fetchColumn() > 0) {
            setFlash('danger', 'Cannot delete: This medicine has sales records.');
        } else {
            $stmt = $db->prepare("DELETE FROM medicines WHERE id = :id");
            $stmt->execute(['id' => $id]);
            setFlash('success', 'Medicine deleted successfully.');
        }
    } catch (PDOException $e) {
        setFlash('danger', 'Error deleting medicine.');
    }
    header('Location: /pharmacy-system/admin/medicines.php');
    exit;
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $batch_number = trim($_POST['batch_number'] ?? '');
    $expiry_date = trim($_POST['expiry_date'] ?? '');
    $purchase_price = floatval($_POST['purchase_price'] ?? 0);
    $sale_price = floatval($_POST['sale_price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $supplier_id = !empty($_POST['supplier_id']) ? (int) $_POST['supplier_id'] : null;

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Medicine name is required.';
    if (empty($batch_number)) $errors[] = 'Batch number is required.';
    if (empty($expiry_date)) $errors[] = 'Expiry date is required.';
    if ($purchase_price <= 0) $errors[] = 'Purchase price must be positive.';
    if ($sale_price <= 0) $errors[] = 'Sale price must be positive.';
    if ($quantity < 0) $errors[] = 'Quantity cannot be negative.';

    if (empty($errors)) {
        try {
            if ($id > 0) {
                // Update
                $stmt = $db->prepare("UPDATE medicines SET name = :name, batch_number = :batch, expiry_date = :exp, 
                    purchase_price = :pp, sale_price = :sp, quantity = :qty, supplier_id = :sid WHERE id = :id");
                $stmt->execute([
                    'name' => $name, 'batch' => $batch_number, 'exp' => $expiry_date,
                    'pp' => $purchase_price, 'sp' => $sale_price, 'qty' => $quantity,
                    'sid' => $supplier_id, 'id' => $id
                ]);
                setFlash('success', 'Medicine updated successfully.');
            } else {
                // Insert
                $stmt = $db->prepare("INSERT INTO medicines (name, batch_number, expiry_date, purchase_price, sale_price, quantity, supplier_id) 
                    VALUES (:name, :batch, :exp, :pp, :sp, :qty, :sid)");
                $stmt->execute([
                    'name' => $name, 'batch' => $batch_number, 'exp' => $expiry_date,
                    'pp' => $purchase_price, 'sp' => $sale_price, 'qty' => $quantity, 'sid' => $supplier_id
                ]);
                setFlash('success', 'Medicine added successfully.');
            }
        } catch (PDOException $e) {
            setFlash('danger', 'Database error: ' . $e->getMessage());
        }
        header('Location: /pharmacy-system/admin/medicines.php');
        exit;
    }
}

// Get suppliers for dropdown
$suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();

// Get medicine for editing
$editMedicine = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM medicines WHERE id = :id");
    $stmt->execute(['id' => (int)$_GET['edit']]);
    $editMedicine = $stmt->fetch();
}

// Search / Filter
$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if (!empty($search)) {
    $where = "WHERE m.name LIKE :search OR m.batch_number LIKE :search2";
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countQuery = "SELECT COUNT(*) FROM medicines m $where";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$query = "SELECT m.*, s.name as supplier_name FROM medicines m LEFT JOIN suppliers s ON m.supplier_id = s.id $where ORDER BY m.name ASC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

$today = date('Y-m-d');
$warningDate = date('Y-m-d', strtotime("+".EXPIRY_WARNING_DAYS." days"));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-capsule"></i> Medicine Management</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#medicineModal" onclick="resetForm()">
        <i class="bi bi-plus-lg"></i> Add Medicine
    </button>
</div>

<!-- Search -->
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="input-group">
            <input type="text" class="form-control" name="search" value="<?= h($search) ?>" placeholder="Search medicine or batch...">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if (!empty($search)): ?>
                <a href="/pharmacy-system/admin/medicines.php" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-8 text-end text-muted">
        Showing <?= count($medicines) ?> of <?= $totalRows ?> medicines
    </div>
</form>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= h($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Medicine Table -->
<div class="table-responsive">
    <table class="table table-bordered table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Medicine Name</th>
                <th>Batch</th>
                <th>Expiry Date</th>
                <th>Purchase Price</th>
                <th>Sale Price</th>
                <th>Stock</th>
                <th>Supplier</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($medicines)): ?>
                <tr><td colspan="10" class="text-center text-muted py-3">No medicines found.</td></tr>
            <?php else: ?>
                <?php foreach ($medicines as $i => $m): 
                    $isExpired = $m['expiry_date'] < $today;
                    $isExpiringSoon = !$isExpired && $m['expiry_date'] <= $warningDate;
                    $isLowStock = $m['quantity'] > 0 && $m['quantity'] <= LOW_STOCK_THRESHOLD;
                    $isOutOfStock = $m['quantity'] == 0;
                    $rowClass = $isExpired ? 'table-danger' : ($isExpiringSoon ? 'table-warning' : '');
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= $offset + $i + 1 ?></td>
                    <td class="fw-semibold"><?= h($m['name']) ?></td>
                    <td><code><?= h($m['batch_number']) ?></code></td>
                    <td>
                        <?= h($m['expiry_date']) ?>
                        <?php if ($isExpired): ?>
                            <span class="badge bg-danger">EXPIRED</span>
                        <?php elseif ($isExpiringSoon): ?>
                            <span class="badge bg-warning text-dark">EXPIRING SOON</span>
                        <?php endif; ?>
                    </td>
                    <td>$<?= number_format($m['purchase_price'], 2) ?></td>
                    <td>$<?= number_format($m['sale_price'], 2) ?></td>
                    <td>
                        <?php if ($isOutOfStock): ?>
                            <span class="badge bg-dark">OUT OF STOCK</span>
                        <?php elseif ($isLowStock): ?>
                            <span class="badge bg-warning text-dark"><?= $m['quantity'] ?> (Low)</span>
                        <?php else: ?>
                            <span class="badge bg-success"><?= $m['quantity'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($m['supplier_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($isExpired): ?>
                            <span class="text-danger fw-bold"><i class="bi bi-exclamation-octagon"></i> Expired</span>
                        <?php elseif ($isExpiringSoon): ?>
                            <span class="text-warning fw-bold"><i class="bi bi-clock-history"></i> Expiring</span>
                        <?php elseif ($isOutOfStock): ?>
                            <span class="text-dark"><i class="bi bi-x-circle"></i> No Stock</span>
                        <?php else: ?>
                            <span class="text-success"><i class="bi bi-check-circle"></i> OK</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editMedicine(<?= htmlspecialchars(json_encode($m), ENT_QUOTES) ?>)" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if (isAdmin()): ?>
                            <a href="?delete=<?= $m['id'] ?>" class="btn btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete <?= h($m['name']) ?>? This cannot be undone.')" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
        </li>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<!-- Add/Edit Modal -->
<div class="modal fade" id="medicineModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="medicineForm">
                <input type="hidden" name="id" id="med_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="bi bi-plus-circle"></i> Add Medicine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Medicine Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="med_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Batch Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="batch_number" id="med_batch" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="expiry_date" id="med_expiry" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Purchase Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="purchase_price" id="med_pp" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sale Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="sale_price" id="med_sp" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Quantity in Stock <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" id="med_qty" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select class="form-select" name="supplier_id" id="med_supplier">
                                <option value="">-- None --</option>
                                <?php foreach ($suppliers as $sup): ?>
                                    <option value="<?= $sup['id'] ?>"><?= h($sup['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editMedicine): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    editMedicine(<?= json_encode($editMedicine) ?>);
});
</script>
<?php endif; ?>

<script>
function resetForm() {
    document.getElementById('med_id').value = '';
    document.getElementById('medicineForm').reset();
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-circle"></i> Add Medicine';
}

function editMedicine(med) {
    document.getElementById('med_id').value = med.id;
    document.getElementById('med_name').value = med.name;
    document.getElementById('med_batch').value = med.batch_number;
    document.getElementById('med_expiry').value = med.expiry_date;
    document.getElementById('med_pp').value = med.purchase_price;
    document.getElementById('med_sp').value = med.sale_price;
    document.getElementById('med_qty').value = med.quantity;
    document.getElementById('med_supplier').value = med.supplier_id || '';
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil"></i> Edit Medicine';
    
    var modal = new bootstrap.Modal(document.getElementById('medicineModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
