<?php
/**
 * API Endpoint: Get assets list
 * Returns assets with optional search filtering and user counts
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

// Get search parameter
$search = $_GET['search'] ?? '';

try {
    $conn = connectToDatabase();

    // Check if users_assets table exists
    $tableCheck = $conn->query("SELECT OBJECT_ID('users_assets', 'U') as table_exists");
    $tableExists = $tableCheck->fetch(PDO::FETCH_ASSOC)['table_exists'] !== null;

    // Build query with optional search - use LEFT JOIN instead of subquery for ODBC compatibility
    if ($tableExists) {
        $sql = "SELECT
                    a.id,
                    a.hostname,
                    a.manufacturer,
                    a.model,
                    a.memory,
                    a.service_tag,
                    a.operating_system,
                    a.feature_release,
                    a.build_number,
                    a.cpu_name,
                    a.speed,
                    a.bios_version,
                    COUNT(ua.user_id) as user_count
                FROM assets a
                LEFT JOIN users_assets ua ON ua.asset_id = a.id";
    } else {
        // Table doesn't exist yet, just return assets without user counts
        $sql = "SELECT
                    a.id,
                    a.hostname,
                    a.manufacturer,
                    a.model,
                    a.memory,
                    a.service_tag,
                    a.operating_system,
                    a.feature_release,
                    a.build_number,
                    a.cpu_name,
                    a.speed,
                    a.bios_version,
                    0 as user_count
                FROM assets a";
    }

    $params = [];

    if (!empty($search)) {
        $sql .= " WHERE a.hostname LIKE ?";
        $searchParam = '%' . $search . '%';
        $params = [$searchParam];
    }

    if ($tableExists) {
        $sql .= " GROUP BY a.id, a.hostname, a.manufacturer, a.model, a.memory, a.service_tag, a.operating_system, a.feature_release, a.build_number, a.cpu_name, a.speed, a.bios_version";
    }

    $sql .= " ORDER BY a.hostname ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'assets' => $assets,
        'users_assets_table_exists' => $tableExists
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
