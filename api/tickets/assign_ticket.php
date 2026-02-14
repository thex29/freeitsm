<?php
/**
 * API Endpoint: Assign ticket to department and/or ticket type
 */
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

    $ticket_id = $data['ticket_id'] ?? null;
    $department_id = $data['department_id'] ?? null;
    $ticket_type_id = $data['ticket_type_id'] ?? null;
    $status = $data['status'] ?? null;
    $origin_id = array_key_exists('origin_id', $data) ? $data['origin_id'] : null;
    $first_time_fix = array_key_exists('first_time_fix', $data) ? $data['first_time_fix'] : null;
    $it_training_provided = array_key_exists('it_training_provided', $data) ? $data['it_training_provided'] : null;

    if (!$ticket_id) {
        throw new Exception('Ticket ID is required');
    }

    $conn = connectToDatabase();

    // Build dynamic SQL based on what's being updated
    $updates = [];
    $params = [];

    if ($department_id !== null) {
        $updates[] = "department_id = ?";
        $params[] = $department_id === '' ? null : $department_id;
    }

    if ($ticket_type_id !== null) {
        $updates[] = "ticket_type_id = ?";
        $params[] = $ticket_type_id === '' ? null : $ticket_type_id;
    }

    if ($status !== null) {
        $updates[] = "status = ?";
        $params[] = $status;
    }

    if (array_key_exists('origin_id', $data)) {
        $updates[] = "origin_id = ?";
        $params[] = $origin_id === '' ? null : $origin_id;
    }

    if (array_key_exists('first_time_fix', $data)) {
        $updates[] = "first_time_fix = ?";
        $params[] = $first_time_fix;
    }

    if (array_key_exists('it_training_provided', $data)) {
        $updates[] = "it_training_provided = ?";
        $params[] = $it_training_provided;
    }

    if (empty($updates)) {
        throw new Exception('No updates specified');
    }

    // Add assignment tracking
    if ($department_id || $ticket_type_id || $status) {
        $updates[] = "assigned_analyst_id = ?";
        $params[] = $_SESSION['analyst_id'];
    }

    // Always update the updated_datetime
    $updates[] = "updated_datetime = GETUTCDATE()";

    $params[] = $ticket_id;

    $sql = "UPDATE tickets SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
