<?php
/**
 * Supplier Management
 * Pharmacy Management System
 */
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$db = getDB();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $check = $db->prepare("SELECT COUNT(*) FROM medicines WHERE supplier_id = :id");
        $check->execute(['id' => $id]);
        if ($check->fetchColumn() > 0) {
            setFlash('danger', 'Cannot delete: This supplier has medicines linked to it.');
        } else {
            $stmt = $db->prepare("DELETE FROM suppliers WHERE id = :id");
            $stmt->execute(['id' => $id]);
            setFlash('success', 'Supplier deleted successfully.');
        }
    } catch (PDOException $e) {
        setFlash('danger', 'Error deleting supplier.');
    }
    header('Location: /pharmacy-system/admin/suppliers.php');
    exit;
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        setFlash('danger', 'Supplier name is required.');
    } else {
        try {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE suppliers SET name = :name, phone = :phone, email = :email, address = :address WHERE id = :id");
                $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address, 'id' => $id]);
                setFlash('success', 'Supplier updated successfully.');
            } else {
                $stmt = $db->prepare("INSERT INTO suppliers (name, phone, email, address) VALUES (:name, :phone, :email, :address)");
                $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address]);
                setFlash('success', 'Supplier added successfully.');
            }
        } catch (PDOException $e) {
            setFlash('danger', 'Database error.');
        }
    }
    header('Location: /pharmacy-system/admin/suppliers.php');
    exit;
}

$suppliers = $db->query("SELECT s.*, (SELECT COUNT(*) FROM medicines WHERE supplier_id = s.id) as med_count FROM suppliers s ORDER BY s.name")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-truck"></i> Suppliers</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="resetSupplierForm()">
        <i class="bi bi-plus-lg"></i> Add Supplier
    </button>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Address</th>
                <th>Medicines</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suppliers)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No suppliers found.</td></tr>
            <?php else: ?>
                <?php foreach ($suppliers as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= h($s['name']) ?></td>
                    <td><?= h($s['phone'] ?: '—') ?></td>
                    <td><?= h($s['email'] ?: '—') ?></td>
                    <td><?= h($s['address'] ?: '—') ?></td>
                    <td><span class="badge bg-secondary"><?= $s['med_count'] ?></span></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick='editSupplier(<?= json_encode($s) ?>)' title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="?delete=<?= $s['id'] ?>" class="btn btn-outline-danger" 
                               onclick="return confirm('Delete this supplier?')" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="supplierForm">
                <input type="hidden" name="id" id="sup_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="supModalTitle"><i class="bi bi-plus-circle"></i> Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="sup_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" class="form-control" name="phone" id="sup_phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" name="email" id="sup_email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea class="form-control" name="address" id="sup_address" rows="2"></textarea>
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

<script>
function resetSupplierForm() {
    document.getElementById('sup_id').value = '';
    document.getElementById('supplierForm').reset();
    document.getElementById('supModalTitle').innerHTML = '<i class="bi bi-plus-circle"></i> Add Supplier';
}

function editSupplier(s) {
    document.getElementById('sup_id').value = s.id;
    document.getElementById('sup_name').value = s.name;
    document.getElementById('sup_phone').value = s.phone || '';
    document.getElementById('sup_email').value = s.email || '';
    document.getElementById('sup_address').value = s.address || '';
    document.getElementById('supModalTitle').innerHTML = '<i class="bi bi-pencil"></i> Edit Supplier';
    new bootstrap.Modal(document.getElementById('supplierModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
