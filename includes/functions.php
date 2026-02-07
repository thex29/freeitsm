<?php
/**
 * Shared Functions
 * Include this file in any script that needs common functionality
 *
 * Usage: require_once '../includes/functions.php'; (from api folder)
 *        require_once 'includes/functions.php'; (from root folder)
 */

/**
 * Connect to SQL Server database using PDO with ODBC
 * Tries multiple drivers for compatibility
 *
 * @return PDO Database connection
 * @throws Exception If connection fails with all drivers
 */
function connectToDatabase() {
    $drivers = [
        'ODBC Driver 17 for SQL Server',
        'ODBC Driver 18 for SQL Server',
        'SQL Server Native Client 11.0',
        'SQL Server'
    ];

    foreach ($drivers as $driver) {
        try {
            $dsn = "odbc:Driver={{$driver}};Server=" . DB_SERVER . ";Database=" . DB_NAME;
            $conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            continue;
        }
    }

    throw new Exception('Database connection failed - no compatible ODBC driver found');
}
?>
