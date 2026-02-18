<?php
/**
 * API Endpoint: Save Morning Check Result
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
    $input = json_decode(file_get_contents('php://input'), true);

    $checkId = $input['checkId'] ?? null;
    $status = $input['status'] ?? null;
    $notes = $input['notes'] ?? '';
    $checkDate = $input['checkDate'] ?? date('Y-m-d');

    if (!$checkId || !$status) {
        throw new Exception('Missing required fields: checkId and status');
    }

    if (!in_array($status, ['Red', 'Amber', 'Green'])) {
        throw new Exception('Invalid status. Must be Red, Amber, or Green');
    }

    if (($status === 'Red' || $status === 'Amber') && empty(trim($notes))) {
        throw new Exception('Notes are required for Red or Amber status');
    }

    // Validate date format
    $dateObj = DateTime::createFromFormat('Y-m-d', $checkDate);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $checkDate) {
        $checkDate = date('Y-m-d');
    }

    $conn = connectToDatabase();

    // Embed validated date directly - PDO ODBC has issues with date parameters
    $checkIdInt = (int)$checkId;

    // Check if result already exists for the selected date
    $sql = "SELECT ResultID FROM morningChecks_Results WHERE CheckID = $checkIdInt AND CheckDate = '$checkDate'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing result
        $sql = "UPDATE morningChecks_Results SET Status = :status, Notes = :notes, ModifiedDate = UTC_TIMESTAMP()
                WHERE CheckID = $checkIdInt AND CheckDate = '$checkDate'";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
        $stmt->execute();
    } else {
        // Insert new result
        $sql = "INSERT INTO morningChecks_Results (CheckID, CheckDate, Status, Notes, CreatedDate, ModifiedDate)
                VALUES ($checkIdInt, '$checkDate', :status, :notes, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
