<?php
session_start();
require_once '../../config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['analyst_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $conn = connectToDatabase();

    $stats = [];

    $stmt = $conn->query("SELECT COUNT(*) FROM contracts");
    $stats['contracts'] = (int)$stmt->fetchColumn();

    $stmt = $conn->query("SELECT COUNT(*) FROM contracts WHERE is_active = 1 AND contract_end >= CAST(UTC_TIMESTAMP() AS date)");
    $stats['active_contracts'] = (int)$stmt->fetchColumn();

    $stmt = $conn->query("SELECT COUNT(*) FROM suppliers");
    $stats['suppliers'] = (int)$stmt->fetchColumn();

    $stmt = $conn->query("SELECT COUNT(*) FROM contacts");
    $stats['contacts'] = (int)$stmt->fetchColumn();

    // Contracts expiring within 90 days
    $stmt = $conn->query("SELECT COUNT(*) FROM contracts WHERE is_active = 1 AND contract_end BETWEEN CAST(UTC_TIMESTAMP() AS date) AND DATEADD(day, 90, CAST(UTC_TIMESTAMP() AS date))");
    $stats['expiring_soon'] = (int)$stmt->fetchColumn();

    echo json_encode(['success' => true, 'stats' => $stats]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
