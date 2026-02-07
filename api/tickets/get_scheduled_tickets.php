<?php
/**
 * API Endpoint: Get scheduled tickets for calendar view
 */
session_start();
require_once '../../config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['analyst_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));

// Calculate date range for the month (include some days before/after for calendar view)
$startDate = date('Y-m-d', strtotime("$year-$month-01 -7 days"));
$endDate = date('Y-m-d', strtotime("$year-$month-01 +40 days"));

try {
    $conn = connectToDatabase();

    $sql = "SELECT
                t.id,
                t.ticket_number,
                t.subject,
                t.status,
                t.priority,
                t.work_start_datetime,
                t.requester_name,
                t.requester_email,
                d.name as department_name,
                a.full_name as owner_name
            FROM tickets t
            LEFT JOIN departments d ON d.id = t.department_id
            LEFT JOIN analysts a ON a.id = t.owner_id
            WHERE t.work_start_datetime IS NOT NULL
              AND t.work_start_datetime >= ?
              AND t.work_start_datetime < ?
              AND t.status != 'Closed'
            ORDER BY t.work_start_datetime ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format datetime for JavaScript
    foreach ($tickets as &$ticket) {
        if ($ticket['work_start_datetime']) {
            $ticket['work_start_datetime'] = date('Y-m-d\TH:i:s', strtotime($ticket['work_start_datetime']));
        }
    }

    echo json_encode([
        'success' => true,
        'tickets' => $tickets
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
