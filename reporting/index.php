<?php
/**
 * Reports - Reporting dashboard (placeholder)
 * Redirects to logs.php for now until a full reports dashboard is implemented
 */
session_start();
require_once '../config.php';

// For now, redirect to logs page
// In the future, this will be a reports dashboard
header('Location: logs.php');
exit;
?>
