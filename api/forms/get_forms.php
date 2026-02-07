<?php
/**
 * API: Get all forms with field count and submission count
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
    $conn = connectToDatabase();

    $sql = "SELECT f.id, f.title, f.description, f.is_active,
                   f.created_by, a.full_name as created_by_name,
                   CONVERT(VARCHAR(19), f.created_date, 120) as created_date,
                   CONVERT(VARCHAR(19), f.modified_date, 120) as modified_date,
                   (SELECT COUNT(*) FROM form_fields WHERE form_id = f.id) as field_count,
                   (SELECT COUNT(*) FROM form_submissions WHERE form_id = f.id) as submission_count
            FROM forms f
            LEFT JOIN analysts a ON f.created_by = a.id
            ORDER BY f.modified_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'forms' => $forms]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
