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

/**
 * Get the list of modules an analyst is allowed to access.
 *
 * @param PDO $conn Database connection
 * @param int $analyst_id Analyst ID
 * @return array|null Null means all access; array of module_key strings if restricted
 */
function getAnalystAllowedModules($conn, $analyst_id) {
    $sql = "SELECT module_key FROM analyst_modules WHERE analyst_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$analyst_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($rows)) {
        return null; // No restrictions — full access
    }

    // Always include system module
    if (!in_array('system', $rows)) {
        $rows[] = 'system';
    }

    return $rows;
}
?>
