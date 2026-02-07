<?php
/**
 * API Endpoint: Get software applications list
 * Returns all applications with publisher and install counts
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

    $sql = "SELECT
                a.id,
                a.display_name,
                a.publisher,
                COUNT(DISTINCT d.host_id) as install_count
            FROM software_inventory_apps a
            LEFT JOIN software_inventory_detail d ON d.app_id = a.id
            GROUP BY a.id, a.display_name, a.publisher
            ORDER BY a.display_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'apps' => $apps
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
