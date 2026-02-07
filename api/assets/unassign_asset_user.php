<?php
/**
 * API Endpoint: Remove a user from an asset
 * Deletes the users_assets record
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

$assetId = $data['asset_id'] ?? null;
$userId = $data['user_id'] ?? null;

if (!$assetId || !$userId) {
    echo json_encode(['success' => false, 'error' => 'Asset ID and User ID are required']);
    exit;
}

try {
    $conn = connectToDatabase();

    // Delete the assignment
    $sql = "DELETE FROM users_assets WHERE asset_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$assetId, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'User removed from asset successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Assignment not found'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
