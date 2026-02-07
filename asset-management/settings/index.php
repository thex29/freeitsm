<?php
/**
 * Asset Management - Settings
 */
session_start();
require_once '../../config.php';

$current_page = 'settings';
$path_prefix = '../../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk - Asset Settings</title>
    <link rel="stylesheet" href="../../assets/css/inbox.css">
    <style>
        .settings-container {
            flex: 1;
            overflow-y: auto;
            background-color: #f5f7fa;
        }

        .settings-content {
            max-width: 700px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .settings-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .settings-section-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e0e0e0;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .settings-section-header svg {
            color: #107c10;
            flex-shrink: 0;
        }

        .settings-section-header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .settings-section-body {
            padding: 25px;
        }

        .settings-description {
            font-size: 13px;
            color: #666;
            margin: 0 0 20px 0;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 13px;
            color: #333;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-input:focus {
            outline: none;
            border-color: #107c10;
            box-shadow: 0 0 0 2px rgba(16, 124, 16, 0.1);
        }

        .form-hint {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.15s;
        }

        .btn-primary {
            background-color: #107c10;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0b5c0b;
        }

        .btn-primary:disabled {
            background-color: #999;
            cursor: not-allowed;
        }

        .btn-test {
            background-color: #0078d4;
            color: white;
        }

        .btn-test:hover {
            background-color: #106ebe;
        }

        .save-message {
            font-size: 13px;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .save-message.visible {
            opacity: 1;
        }

        .save-message.success {
            color: #107c10;
        }

        .save-message.error {
            color: #dc3545;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-input {
            padding-right: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #888;
            font-size: 13px;
            padding: 4px;
        }

        .password-toggle:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="main-container settings-container">
        <div class="settings-content">
            <div class="settings-section">
                <div class="settings-section-header">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                        <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                        <line x1="6" y1="6" x2="6.01" y2="6"></line>
                        <line x1="6" y1="18" x2="6.01" y2="18"></line>
                    </svg>
                    <h2>vCenter Integration</h2>
                </div>
                <div class="settings-section-body">
                    <p class="settings-description">
                        Connect to a VMware vCenter Server to import virtual machine inventory data.
                    </p>
                    <form id="vcenterForm" onsubmit="saveSettings(event)">
                        <div class="form-group">
                            <label class="form-label" for="vcenterServer">vCenter Server</label>
                            <input type="text" class="form-input" id="vcenterServer" placeholder="e.g. vcenter.company.local">
                            <div class="form-hint">Hostname or IP address of the vCenter Server</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="vcenterUser">vCenter User</label>
                            <input type="text" class="form-input" id="vcenterUser" placeholder="e.g. administrator@vsphere.local">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="vcenterPassword">vCenter Password</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-input" id="vcenterPassword" placeholder="Enter password">
                                <button type="button" class="password-toggle" onclick="togglePassword()">Show</button>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="saveBtn">Save Settings</button>
                            <span class="save-message" id="saveMessage"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../../api/settings/';

        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
        });

        async function loadSettings() {
            try {
                const response = await fetch(API_BASE + 'get_system_settings.php');
                const data = await response.json();

                if (data.success && data.settings) {
                    document.getElementById('vcenterServer').value = data.settings.vcenter_server || '';
                    document.getElementById('vcenterUser').value = data.settings.vcenter_user || '';
                    document.getElementById('vcenterPassword').value = data.settings.vcenter_password || '';
                }
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        }

        async function saveSettings(e) {
            e.preventDefault();

            const saveBtn = document.getElementById('saveBtn');
            const messageEl = document.getElementById('saveMessage');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            try {
                const response = await fetch(API_BASE + 'save_system_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        settings: {
                            vcenter_server: document.getElementById('vcenterServer').value.trim(),
                            vcenter_user: document.getElementById('vcenterUser').value.trim(),
                            vcenter_password: document.getElementById('vcenterPassword').value
                        }
                    })
                });
                const data = await response.json();

                if (data.success) {
                    showMessage('Settings saved successfully', 'success');
                } else {
                    showMessage('Error: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error saving settings:', error);
                showMessage('Failed to save settings', 'error');
            }

            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Settings';
        }

        function showMessage(text, type) {
            const el = document.getElementById('saveMessage');
            el.textContent = text;
            el.className = 'save-message visible ' + type;
            setTimeout(() => {
                el.classList.remove('visible');
            }, 3000);
        }

        function togglePassword() {
            const input = document.getElementById('vcenterPassword');
            const btn = input.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Hide';
            } else {
                input.type = 'password';
                btn.textContent = 'Show';
            }
        }
    </script>
</body>
</html>
