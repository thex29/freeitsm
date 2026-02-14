<?php
/**
 * API Endpoint: Update ticket owner (assigned analyst)
 */
session_start();
require_once '../../config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['analyst_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['ticket_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$ticketId = (int)$data['ticket_id'];
$ownerId = isset($data['owner_id']) && $data['owner_id'] !== '' ? (int)$data['owner_id'] : null;

try {
    $conn = connectToDatabase();

    // Check if ticket exists
    $checkSql = "SELECT id FROM tickets WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$ticketId]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }

    // If owner_id is provided, verify analyst exists
    if ($ownerId !== null) {
        $analystSql = "SELECT id FROM analysts WHERE id = ?";
        $analystStmt = $conn->prepare($analystSql);
        $analystStmt->execute([$ownerId]);
        if (!$analystStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Analyst not found']);
            exit;
        }
    }

    // Update ticket owner
    $sql = "UPDATE tickets SET owner_id = ?, updated_datetime = GETUTCDATE() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ownerId, $ticketId]);

    echo json_encode(['success' => true, 'message' => 'Ticket owner updated successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
