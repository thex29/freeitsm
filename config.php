<?php
/**
 * Configuration file for Service Desk Ticketing System
 *
 * Mailbox settings (Azure AD credentials, OAuth tokens, etc.) are now stored
 * in the target_mailboxes database table and managed via Settings > Mailboxes.
 */

// Load database credentials from secure location (outside web root)
// Update this path to match your db_config.php location
$db_config_path = 'C:\wamp64\db_config.php';
require_once($db_config_path);

// Timezone
date_default_timezone_set('America/New_York');

// SSL Certificate Verification
// WARNING: Setting this to false is INSECURE and should ONLY be used for testing!
// For production, configure php.ini with proper CA certificate bundle
// Download from: https://curl.se/ca/cacert.pem
define('SSL_VERIFY_PEER', false);

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
