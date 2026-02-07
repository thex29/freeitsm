<?php
/**
 * Manage Morning Checks - Add/Edit/Delete check definitions
 */
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['analyst_id'])) {
    header('Location: ../login.php');
    exit;
}

$analyst_name = $_SESSION['analyst_name'] ?? 'Analyst';
$current_page = 'manage';
$path_prefix = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk - Manage Checks</title>
    <link rel="stylesheet" href="../assets/css/inbox.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Override body padding for header */
        body {
            padding-top: 0;
        }
        .container {
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="manage-section">
            <div class="add-check-form">
                <h2>Add new check</h2>
                <form id="addCheckForm">
                    <div class="form-group">
                        <label for="checkName">Check name *</label>
                        <input type="text" id="checkName" name="checkName" required>
                    </div>
                    <div class="form-group">
                        <label for="checkDescription">Description</label>
                        <textarea id="checkDescription" name="checkDescription" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="sortOrder">Sort order</label>
                        <input type="number" id="sortOrder" name="sortOrder" value="0">
                    </div>
                    <button type="submit" class="btn-primary">Add check</button>
                </form>
            </div>

            <div class="checks-list">
                <h2>Existing checks</h2>
                <table id="checksListTable">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Check name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="checksListBody">
                        <tr>
                            <td colspan="5" class="loading">Loading checks...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit check</h2>
            <form id="editCheckForm">
                <input type="hidden" id="editCheckId">
                <div class="form-group">
                    <label for="editCheckName">Check name *</label>
                    <input type="text" id="editCheckName" name="editCheckName" required>
                </div>
                <div class="form-group">
                    <label for="editCheckDescription">Description</label>
                    <textarea id="editCheckDescription" name="editCheckDescription" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="editSortOrder">Sort order</label>
                    <input type="number" id="editSortOrder" name="editSortOrder">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="editIsActive" name="editIsActive">
                        Active
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Save changes</button>
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '../api/morning-checks/';

        // Load all checks
        async function loadChecks() {
            try {
                const response = await fetch(`${API_BASE}get_all_checks.php`);
                const data = await response.json();

                if (data.error) {
                    document.getElementById('checksListBody').innerHTML =
                        `<tr><td colspan="5" class="error">Error: ${data.error}</td></tr>`;
                    return;
                }

                displayChecksList(data);
            } catch (error) {
                document.getElementById('checksListBody').innerHTML =
                    `<tr><td colspan="5" class="error">Error loading checks: ${error.message}</td></tr>`;
            }
        }

        function displayChecksList(checks) {
            const tbody = document.getElementById('checksListBody');

            if (checks.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="no-data">No checks defined yet.</td></tr>';
                return;
            }

            tbody.innerHTML = checks.map(check => `
                <tr>
                    <td>${check.SortOrder}</td>
                    <td><strong>${escapeHtml(check.CheckName)}</strong></td>
                    <td>${escapeHtml(check.CheckDescription || '')}</td>
                    <td>
                        <span class="badge ${check.IsActive ? 'badge-active' : 'badge-inactive'}">
                            ${check.IsActive ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td class="actions">
                        <button class="btn-edit" onclick="editCheck(${check.CheckID})">Edit</button>
                        <button class="btn-delete" onclick="deleteCheck(${check.CheckID}, '${escapeHtml(check.CheckName)}')">Delete</button>
                    </td>
                </tr>
            `).join('');
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Add new check
        document.getElementById('addCheckForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                checkName: document.getElementById('checkName').value.trim(),
                checkDescription: document.getElementById('checkDescription').value.trim(),
                sortOrder: parseInt(document.getElementById('sortOrder').value)
            };

            try {
                const response = await fetch(`${API_BASE}add_check.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Check added successfully', 'success');
                    document.getElementById('addCheckForm').reset();
                    loadChecks();
                } else {
                    showNotification('Error: ' + data.error, 'error');
                }
            } catch (error) {
                showNotification('Error adding check: ' + error.message, 'error');
            }
        });

        // Edit check
        let checksCache = [];

        async function editCheck(checkId) {
            try {
                const response = await fetch(`${API_BASE}get_all_checks.php`);
                checksCache = await response.json();

                const check = checksCache.find(c => c.CheckID === checkId);

                if (!check) {
                    showNotification('Check not found', 'error');
                    return;
                }

                document.getElementById('editCheckId').value = check.CheckID;
                document.getElementById('editCheckName').value = check.CheckName;
                document.getElementById('editCheckDescription').value = check.CheckDescription || '';
                document.getElementById('editSortOrder').value = check.SortOrder;
                document.getElementById('editIsActive').checked = check.IsActive;

                document.getElementById('editModal').style.display = 'block';
            } catch (error) {
                showNotification('Error loading check: ' + error.message, 'error');
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('editCheckForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                checkId: parseInt(document.getElementById('editCheckId').value),
                checkName: document.getElementById('editCheckName').value.trim(),
                checkDescription: document.getElementById('editCheckDescription').value.trim(),
                sortOrder: parseInt(document.getElementById('editSortOrder').value),
                isActive: document.getElementById('editIsActive').checked
            };

            try {
                const response = await fetch(`${API_BASE}update_check.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Check updated successfully', 'success');
                    closeEditModal();
                    loadChecks();
                } else {
                    showNotification('Error: ' + data.error, 'error');
                }
            } catch (error) {
                showNotification('Error updating check: ' + error.message, 'error');
            }
        });

        // Delete check
        async function deleteCheck(checkId, checkName) {
            if (!confirm(`Are you sure you want to delete "${checkName}"?\n\nThis will also delete all associated results.`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}delete_check.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ checkId: checkId })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Check deleted successfully', 'success');
                    loadChecks();
                } else {
                    showNotification('Error: ' + data.error, 'error');
                }
            } catch (error) {
                showNotification('Error deleting check: ' + error.message, 'error');
            }
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('show');
            }, 10);

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadChecks();
        });
    </script>
</body>
</html>
