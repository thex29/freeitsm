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
    $id = $_GET['id'] ?? null;
    if (!$id) {
        throw new Exception('ID is required');
    }

    $conn = connectToDatabase();

    $sql = "SELECT c.id, c.contract_number, c.title, c.supplier_id, c.contract_owner_id,
                   c.contract_start, c.contract_end, c.notice_period_days, c.is_active, c.created_datetime,
                   s.legal_name AS supplier_name, s.trading_name AS supplier_trading_name,
                   a.full_name AS owner_name
            FROM contracts c
            LEFT JOIN suppliers s ON c.supplier_id = s.id
            LEFT JOIN analysts a ON c.contract_owner_id = a.id
            WHERE c.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract) {
        throw new Exception('Contract not found');
    }

    $contract['is_active'] = (bool)$contract['is_active'];

    echo json_encode(['success' => true, 'contract' => $contract]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
