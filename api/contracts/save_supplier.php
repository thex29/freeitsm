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
    $legal_name = trim($data['legal_name'] ?? '');
    $trading_name = trim($data['trading_name'] ?? '');
    $is_active = $data['is_active'] ?? 1;

    if (empty($legal_name)) {
        throw new Exception('Legal name is required');
    }

    $conn = connectToDatabase();

    if ($id) {
        $sql = "UPDATE suppliers SET legal_name = ?, trading_name = ?, is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$legal_name, $trading_name ?: null, $is_active, $id]);
    } else {
        $sql = "INSERT INTO suppliers (legal_name, trading_name, is_active) OUTPUT INSERTED.id VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$legal_name, $trading_name ?: null, $is_active]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $id = $row['id'];
    }

    echo json_encode(['success' => true, 'id' => $id]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
