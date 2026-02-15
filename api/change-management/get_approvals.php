<?php
/**
 * API Endpoint: Get changes pending approval
 * Filters: all, requested (by me), assigned (to me as approver)
 */
session_start();
require_once '../../config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['analyst_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$analystId = $_SESSION['analyst_id'];
$filter = $_GET['filter'] ?? 'all';

try {
    $conn = connectToDatabase();

    // Base query for Pending Approval changes
    $sql = "SELECT
                c.id,
                c.title,
                c.change_type,
                c.status,
                c.priority,
                c.impact,
                c.work_start_datetime,
                c.created_datetime,
                assigned.full_name as assigned_to_name,
                requester.full_name as requester_name,
                approver.full_name as approver_name
            FROM changes c
            LEFT JOIN analysts assigned ON c.assigned_to_id = assigned.id
            LEFT JOIN analysts requester ON c.requester_id = requester.id
            LEFT JOIN analysts approver ON c.approver_id = approver.id
            WHERE c.status = 'Pending Approval'";

    $params = [];

    if ($filter === 'requested') {
        $sql .= " AND c.requester_id = ?";
        $params[] = $analystId;
    } elseif ($filter === 'assigned') {
        $sql .= " AND c.approver_id = ?";
        $params[] = $analystId;
    }

    $sql .= " ORDER BY c.created_datetime DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $changes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get counts for all three filters in a single query
    $countSql = "SELECT
                    COUNT(*) as cnt_all,
                    SUM(CASE WHEN requester_id = ? THEN 1 ELSE 0 END) as cnt_requested,
                    SUM(CASE WHEN approver_id = ? THEN 1 ELSE 0 END) as cnt_assigned
                 FROM changes WHERE status = 'Pending Approval'";
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute([$analystId, $analystId]);
    $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'changes' => $changes,
        'counts' => [
            'all'       => (int)($countRow['cnt_all'] ?? 0),
            'requested' => (int)($countRow['cnt_requested'] ?? 0),
            'assigned'  => (int)($countRow['cnt_assigned'] ?? 0)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
