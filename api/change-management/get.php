<?php
/**
 * API Endpoint: Get single change by ID with attachments
 */
session_start();
require_once '../../config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['analyst_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$changeId = (int)($_GET['id'] ?? 0);

if (!$changeId) {
    echo json_encode(['success' => false, 'error' => 'Change ID required']);
    exit;
}

try {
    $conn = connectToDatabase();

    $sql = "SELECT
                c.id,
                c.title,
                c.change_type,
                c.status,
                c.priority,
                c.impact,
                c.category,
                c.requester_id,
                c.assigned_to_id,
                c.approver_id,
                c.approval_datetime,
                c.work_start_datetime,
                c.work_end_datetime,
                c.outage_start_datetime,
                c.outage_end_datetime,
                CAST(c.description AS NVARCHAR(MAX)) as description,
                CAST(c.reason_for_change AS NVARCHAR(MAX)) as reason_for_change,
                CAST(c.risk_evaluation AS NVARCHAR(MAX)) as risk_evaluation,
                CAST(c.test_plan AS NVARCHAR(MAX)) as test_plan,
                CAST(c.rollback_plan AS NVARCHAR(MAX)) as rollback_plan,
                CAST(c.post_implementation_review AS NVARCHAR(MAX)) as post_implementation_review,
                c.created_by_id,
                c.created_datetime,
                c.modified_datetime,
                requester.full_name as requester_name,
                assigned.full_name as assigned_to_name,
                approver.full_name as approver_name,
                creator.full_name as created_by_name
            FROM changes c
            LEFT JOIN analysts requester ON c.requester_id = requester.id
            LEFT JOIN analysts assigned ON c.assigned_to_id = assigned.id
            LEFT JOIN analysts approver ON c.approver_id = approver.id
            LEFT JOIN analysts creator ON c.created_by_id = creator.id
            WHERE c.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$changeId]);
    $change = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$change) {
        echo json_encode(['success' => false, 'error' => 'Change not found']);
        exit;
    }

    // Get attachments
    $attSql = "SELECT id, file_name, file_size, file_type, uploaded_datetime
               FROM change_attachments
               WHERE change_id = ?
               ORDER BY uploaded_datetime DESC";
    $attStmt = $conn->prepare($attSql);
    $attStmt->execute([$changeId]);
    $change['attachments'] = $attStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'change' => $change
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
