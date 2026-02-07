<?php
/**
 * Logout handler - removes stored tokens
 */

require_once 'config.php';

if (file_exists(TOKEN_STORAGE_FILE)) {
    unlink(TOKEN_STORAGE_FILE);
}

echo json_encode(['success' => true]);
?>
