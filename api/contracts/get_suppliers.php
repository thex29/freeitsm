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

    $sql = "SELECT id, legal_name, trading_name, is_active, created_datetime FROM suppliers ORDER BY legal_name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($suppliers as &$s) {
        $s['is_active'] = (bool)$s['is_active'];
    }

    echo json_encode(['success' => true, 'suppliers' => $suppliers]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
