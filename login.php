<?php
/**
 * Login page for Service Desk Ticketing System
 */
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

/**
 * Log login attempt to system_logs
 */
function logLoginAttempt($conn, $analystId, $username, $success) {
    try {
        $details = json_encode([
            'username' => $username,
            'success' => $success,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        $sql = "INSERT INTO system_logs (log_type, analyst_id, details, created_datetime)
                VALUES ('login', ?, ?, GETDATE())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$analystId, $details]);
    } catch (Exception $e) {
        // Silently fail - don't break login if logging fails
        error_log('Failed to log login attempt: ' . $e->getMessage());
    }
}

// If already logged in, redirect to inbox
if (isset($_SESSION['analyst_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            // Connect to database
            $drivers = [
                'ODBC Driver 17 for SQL Server',
                'ODBC Driver 18 for SQL Server',
                'SQL Server Native Client 11.0',
                'SQL Server'
            ];

            $conn = null;
            $lastException = null;
            foreach ($drivers as $driver) {
                try {
                    $dsn = "odbc:Driver={{$driver}};Server=" . DB_SERVER . ";Database=" . DB_NAME;
                    $conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    break;
                } catch (PDOException $e) {
                    $lastException = $e;
                    continue;
                }
            }

            if (!$conn) {
                throw new Exception('Database connection failed: ' . $lastException->getMessage());
            }

            // Query for user
            $sql = "SELECT id, username, password_hash, full_name, email FROM analysts WHERE username = ? AND is_active = 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username]);
            $analyst = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($analyst && password_verify($password, $analyst['password_hash'])) {
                // Login successful
                $_SESSION['analyst_id'] = $analyst['id'];
                $_SESSION['analyst_username'] = $analyst['username'];
                $_SESSION['analyst_name'] = $analyst['full_name'];
                $_SESSION['analyst_email'] = $analyst['email'];

                // Update last login time
                $updateSql = "UPDATE analysts SET last_login_datetime = GETDATE() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$analyst['id']]);

                // Load module permissions
                $_SESSION['allowed_modules'] = getAnalystAllowedModules($conn, $analyst['id']);

                // Log successful login
                logLoginAttempt($conn, $analyst['id'], $username, true);

                header('Location: index.php');
                exit;
            } else {
                // Log failed login
                logLoginAttempt($conn, null, $username, false);
                $error = 'Invalid username or password';
            }

        } catch (Exception $e) {
            $error = 'Login error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header img {
            width: 250px;
            height: auto;
            margin-bottom: 25px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .login-button:hover {
            transform: translateY(-2px);
        }

        .login-button:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="assets/images/CompanyLogo.png" alt="Company Logo">
            <h1>ITSM Login</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-button">Sign In</button>
        </form>
    </div>
</body>
</html>
