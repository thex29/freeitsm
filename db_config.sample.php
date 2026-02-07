<?php
/**
 * Database Configuration SAMPLE - Store outside web root for security
 *
 * SETUP INSTRUCTIONS:
 * 1. Copy this file to: C:\wamp64\db_config.php (outside the web root)
 * 2. Update the values below with your actual database credentials
 * 3. Make sure config.php points to this file location
 */

// SQL Server Connection Settings
define('DB_SERVER', 'YOUR_SERVER\SQLEXPRESS');     // e.g., 'localhost\SQLEXPRESS'
define('DB_NAME', 'FREEITSM');                      // Database name
define('DB_USERNAME', 'your_sql_username');         // SQL Server username
define('DB_PASSWORD', 'your_secure_password');      // SQL Server password
?>
