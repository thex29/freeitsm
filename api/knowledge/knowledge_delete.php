<?php
/**
 * API Endpoint: Delete knowledge base article
 */
session_start();
require_once '../../config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['analyst_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'Article ID required']);
    exit;
}

$articleId = (int)$input['id'];

try {
    $conn = connectToDatabase();

    // Delete the article (tags will be cleaned up by CASCADE)
    $sql = "DELETE FROM knowledge_articles WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$articleId]);

    // Clean up orphaned tags (tags with no articles)
    $cleanupSql = "DELETE FROM knowledge_tags
                   WHERE id NOT IN (SELECT DISTINCT tag_id FROM knowledge_article_tags)";
    $conn->exec($cleanupSql);

    echo json_encode([
        'success' => true,
        'message' => 'Article deleted successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
