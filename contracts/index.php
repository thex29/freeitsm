<?php
/**
 * Contracts Module - Dashboard
 */
session_start();
require_once '../config.php';

$current_page = 'dashboard';
$path_prefix = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk - Contracts</title>
    <link rel="stylesheet" href="../assets/css/inbox.css">
    <style>
        body { overflow: auto; height: auto; }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-align: center;
        }

        .stat-card .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }

        .stat-card .stat-label {
            font-size: 13px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card.warning .stat-number { color: #f59e0b; }

        .section-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        .section-card .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            border-bottom: 1px solid #eee;
        }

        .section-card .section-header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .section-card table {
            width: 100%;
            border-collapse: collapse;
        }

        .section-card table th {
            text-align: left;
            padding: 12px 24px;
            font-size: 12px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #eee;
            background: #fafafa;
        }

        .section-card table td {
            padding: 14px 24px;
            font-size: 14px;
            color: #333;
            border-bottom: 1px solid #f0f0f0;
        }

        .section-card table tr:last-child td { border-bottom: none; }
        .section-card table tr:hover { background: #fafafa; }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.active { background: #d4edda; color: #155724; }
        .status-badge.expired { background: #f8d7da; color: #721c24; }
        .status-badge.expiring { background: #fff3cd; color: #856404; }

        .btn-view, .btn-edit {
            background: none;
            border: 1px solid #ddd;
            color: #666;
            cursor: pointer;
            padding: 6px;
            margin-right: 4px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .btn-view:hover, .btn-edit:hover {
            background: #f0f0f0;
            border-color: #f59e0b;
            color: #f59e0b;
        }

        .btn-view svg, .btn-edit svg { width: 16px; height: 16px; }

        .add-contract-btn {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s;
        }

        .add-contract-btn:hover { background: #d97706; }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="statContracts">-</div>
                <div class="stat-label">Total Contracts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="statActive">-</div>
                <div class="stat-label">Active Contracts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="statSuppliers">-</div>
                <div class="stat-label">Suppliers</div>
            </div>
            <div class="stat-card warning" id="expiringCard">
                <div class="stat-number" id="statExpiring">-</div>
                <div class="stat-label">Expiring (90 days)</div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <h2>Recent Contracts</h2>
                <a href="edit.php" class="add-contract-btn">Add Contract</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Contract #</th>
                        <th>Title</th>
                        <th>Supplier</th>
                        <th>Owner</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="contractsList">
                    <tr><td colspan="7" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const API_BASE = '../api/contracts/';

        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadRecentContracts();
        });

        async function loadStats() {
            try {
                const response = await fetch(API_BASE + 'get_dashboard_stats.php');
                const data = await response.json();
                if (data.success) {
                    document.getElementById('statContracts').textContent = data.stats.contracts;
                    document.getElementById('statActive').textContent = data.stats.active_contracts;
                    document.getElementById('statSuppliers').textContent = data.stats.suppliers;
                    document.getElementById('statExpiring').textContent = data.stats.expiring_soon;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        async function loadRecentContracts() {
            try {
                const response = await fetch(API_BASE + 'get_contracts.php?limit=10');
                const data = await response.json();
                if (data.success) {
                    renderContracts(data.contracts);
                } else {
                    document.getElementById('contractsList').innerHTML =
                        '<tr><td colspan="7" class="empty-state" style="color:#d13438;">Error: ' + escapeHtml(data.error) + '</td></tr>';
                }
            } catch (error) {
                console.error('Error loading contracts:', error);
                document.getElementById('contractsList').innerHTML =
                    '<tr><td colspan="7" class="empty-state" style="color:#d13438;">Failed to load contracts</td></tr>';
            }
        }

        function renderContracts(contracts) {
            const tbody = document.getElementById('contractsList');

            if (contracts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No contracts yet. Click "Add Contract" to create one.</td></tr>';
                return;
            }

            tbody.innerHTML = contracts.map(c => {
                const status = getContractStatus(c.contract_end, c.is_active);
                return `
                    <tr>
                        <td><strong>${escapeHtml(c.contract_number)}</strong></td>
                        <td>${escapeHtml(c.title)}</td>
                        <td>${escapeHtml(c.supplier_name || '-')}</td>
                        <td>${escapeHtml(c.owner_name || '-')}</td>
                        <td>${formatDate(c.contract_end)}</td>
                        <td><span class="status-badge ${status.class}">${status.label}</span></td>
                        <td>
                            <a href="view.php?id=${c.id}" class="btn-view" title="View">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </a>
                            <a href="edit.php?id=${c.id}" class="btn-edit" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </a>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function getContractStatus(endDate, isActive) {
            if (!isActive) return { class: 'expired', label: 'Inactive' };
            if (!endDate) return { class: 'active', label: 'Active' };

            const end = new Date(endDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const daysLeft = Math.ceil((end - today) / (1000 * 60 * 60 * 24));

            if (daysLeft < 0) return { class: 'expired', label: 'Expired' };
            if (daysLeft <= 90) return { class: 'expiring', label: 'Expiring' };
            return { class: 'active', label: 'Active' };
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
