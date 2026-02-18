<?php
/**
 * API Endpoint: Log system event
 * Records various system events (logins, email imports, etc.)
 */
require_once '../../config.php';
require_once '../../includes/functions.php';

// Note: This endpoint doesn't require session for login logging
// but we'll check for other event types

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$logType = $data['log_type'] ?? null;
$details = $data['details'] ?? null;
$analystId = $data['analyst_id'] ?? null;

if (!$logType || !$details) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $conn = connectToDatabase();

    $sql = "INSERT INTO system_logs (log_type, analyst_id, details, created_datetime)
            VALUES (?, ?, ?, UTC_TIMESTAMP())";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$logType, $analystId, json_encode($details)]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
