<?php
/**
 * Root index - redirect to login or dashboard
 */
require_once __DIR__ . '/config/db.php';
startSession();

if (isLoggedIn()) {
    header('Location: /pharmacy-system/admin/dashboard.php');
} else {
    header('Location: /pharmacy-system/auth/login.php');
}
exit;
