<?php
/**
 * API Endpoint: Get file list with optional filters
 * Query params: ?folder=api/tickets&type=PHP&search=get_users
 */
session_start();
require_once '../../config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['analyst_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$folder = isset($_GET['folder']) ? trim($_GET['folder']) : null;
$type = isset($_GET['type']) ? trim($_GET['type']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

try {
    $conn = connectToDatabase();

    $scanStmt = $conn->prepare("SELECT TOP 1 id FROM wiki_scan_runs WHERE status = 'completed' ORDER BY id DESC");
    $scanStmt->execute();
    $scan = $scanStmt->fetch(PDO::FETCH_ASSOC);

    if (!$scan) {
        echo json_encode(['success' => true, 'files' => []]);
        exit;
    }

    $scanId = (int)$scan['id'];

    $sql = "SELECT f.id, f.file_path, f.file_name, f.folder_path, f.file_type,
                   f.file_size_bytes, f.line_count, f.last_modified, f.description,
                   (SELECT COUNT(*) FROM wiki_functions WHERE file_id = f.id) as function_count,
                   (SELECT COUNT(*) FROM wiki_dependencies WHERE file_id = f.id) as dependency_count
            FROM wiki_files f
            WHERE f.scan_id = $scanId";
    $params = [];

    if ($folder !== null) {
        if ($folder === '') {
            // Root files only
            $sql .= " AND (f.folder_path = '' OR f.folder_path IS NULL)";
        } else {
            // Files in this folder AND all subfolders
            $sql .= " AND (f.folder_path = CAST(? AS NVARCHAR(500)) OR f.folder_path LIKE CAST(? AS NVARCHAR(500)))";
            $params[] = $folder;
            $params[] = $folder . '/%';
        }
    }

    if ($type) {
        $sql .= " AND f.file_type = CAST(? AS NVARCHAR(10))";
        $params[] = strtoupper($type);
    }

    if ($search) {
        $sql .= " AND (f.file_name LIKE CAST(? AS NVARCHAR(255)) OR f.file_path LIKE CAST(? AS NVARCHAR(500)) OR f.description LIKE CAST(? AS NVARCHAR(MAX)))";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $sql .= " ORDER BY f.folder_path, f.file_name";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast numeric fields
    foreach ($files as &$f) {
        $f['id'] = (int)$f['id'];
        $f['file_size_bytes'] = (int)$f['file_size_bytes'];
        $f['line_count'] = (int)$f['line_count'];
        $f['function_count'] = (int)$f['function_count'];
        $f['dependency_count'] = (int)$f['dependency_count'];
    }

    echo json_encode(['success' => true, 'files' => $files]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
