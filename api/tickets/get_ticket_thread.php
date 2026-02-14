<?php
/**
 * API Endpoint: Get all emails for a ticket (for building reply thread)
 * Returns emails ordered by received_datetime ASC
 */
session_start();
require_once '../../config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['analyst_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$ticketId = $_GET['ticket_id'] ?? null;

if (!$ticketId) {
    echo json_encode(['success' => false, 'error' => 'Ticket ID is required']);
    exit;
}

try {
    $conn = connectToDatabase();

    $sql = "SELECT id, from_address, from_name, to_recipients, received_datetime,
                   body_content, direction
            FROM emails
            WHERE ticket_id = ?
            ORDER BY received_datetime ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticketId]);
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($emails as &$email) {
        if ($email['body_content']) {
            $email['body_content'] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $email['body_content']);
            $email['body_content'] = str_replace("\xEF\xBF\xBD", '', $email['body_content']);
        }
        if ($email['received_datetime']) {
            $email['received_datetime'] = date('Y-m-d\TH:i:s', strtotime($email['received_datetime']));
        }
    }

    echo json_encode(['success' => true, 'emails' => $emails]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
