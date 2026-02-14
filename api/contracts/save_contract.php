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
    $data = json_decode(file_get_contents('php://input'), true);

    $id = $data['id'] ?? null;
    $contract_number = trim($data['contract_number'] ?? '');
    $title = trim($data['title'] ?? '');
    $supplier_id = $data['supplier_id'] ?? null;
    $contract_owner_id = $data['contract_owner_id'] ?? null;
    $contract_start = $data['contract_start'] ?? null;
    $contract_end = $data['contract_end'] ?? null;
    $notice_period_days = $data['notice_period_days'] ?? null;
    $is_active = $data['is_active'] ?? 1;

    if (empty($contract_number)) {
        throw new Exception('Contract number is required');
    }
    if (empty($title)) {
        throw new Exception('Title is required');
    }

    $conn = connectToDatabase();

    if ($id) {
        $sql = "UPDATE contracts SET contract_number = ?, title = ?, supplier_id = ?, contract_owner_id = ?,
                contract_start = ?, contract_end = ?, notice_period_days = ?, is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $contract_number, $title, $supplier_id ?: null, $contract_owner_id ?: null,
            $contract_start ?: null, $contract_end ?: null, $notice_period_days ?: null, $is_active, $id
        ]);
    } else {
        $sql = "INSERT INTO contracts (contract_number, title, supplier_id, contract_owner_id, contract_start, contract_end, notice_period_days, is_active)
                OUTPUT INSERTED.id VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $contract_number, $title, $supplier_id ?: null, $contract_owner_id ?: null,
            $contract_start ?: null, $contract_end ?: null, $notice_period_days ?: null, $is_active
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $id = $row['id'];
    }

    echo json_encode(['success' => true, 'id' => $id]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
