<?php
require_once __DIR__ . '/../config/db.php';
startSession();
requireLogin();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$expiredCount = getExpiredCount();
$expiringSoonCount = getExpiringSoonCount();
$lowStockCount = getLowStockCount();
$totalAlerts = $expiredCount + $expiringSoonCount + $lowStockCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/pharmacy-system/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="/pharmacy-system/admin/dashboard.php">
                <i class="bi bi-capsule"></i> <?= h(APP_NAME) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/pharmacy-system/admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'medicines' ? 'active' : '' ?>" href="/pharmacy-system/admin/medicines.php">
                            <i class="bi bi-capsule"></i> Medicines
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'new_sale' ? 'active' : '' ?>" href="/pharmacy-system/admin/new_sale.php">
                            <i class="bi bi-cart-plus"></i> New Sale
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'sales' ? 'active' : '' ?>" href="/pharmacy-system/admin/sales.php">
                            <i class="bi bi-receipt"></i> Sales History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>" href="/pharmacy-system/admin/reports.php">
                            <i class="bi bi-bar-chart"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array($currentPage, ['expiry_alerts', 'low_stock']) ? 'active' : '' ?>" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-exclamation-triangle"></i> Alerts
                            <?php if ($totalAlerts > 0): ?>
                                <span class="badge bg-danger"><?= $totalAlerts ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="/pharmacy-system/admin/expiry_alerts.php">
                                    <i class="bi bi-calendar-x text-danger"></i> Expiry Alerts
                                    <?php if ($expiredCount + $expiringSoonCount > 0): ?>
                                        <span class="badge bg-danger"><?= $expiredCount + $expiringSoonCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/pharmacy-system/admin/low_stock.php">
                                    <i class="bi bi-box-seam text-warning"></i> Low Stock
                                    <?php if ($lowStockCount > 0): ?>
                                        <span class="badge bg-warning text-dark"><?= $lowStockCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'suppliers' ? 'active' : '' ?>" href="/pharmacy-system/admin/suppliers.php">
                            <i class="bi bi-truck"></i> Suppliers
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= h($_SESSION['user_name'] ?? 'User') ?>
                            <span class="badge bg-info"><?= h($_SESSION['user_role'] ?? '') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item text-danger" href="/pharmacy-system/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <?php
        $flash = getFlash();
        if ($flash): ?>
            <div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= h($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
