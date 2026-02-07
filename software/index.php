<?php
/**
 * Software - View software inventory across all managed machines
 */
session_start();
require_once '../config.php';

$current_page = 'software';
$path_prefix = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk - Software</title>
    <link rel="stylesheet" href="../assets/css/inbox.css">
    <style>
        .software-container {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
            background-color: #fff;
        }

        .software-toolbar {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            background-color: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .software-toolbar h3 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search-box {
            width: 350px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .search-box:focus {
            outline: none;
            border-color: #5c6bc0;
            box-shadow: 0 0 0 2px rgba(92, 107, 192, 0.15);
        }

        .software-count {
            font-size: 13px;
            color: #888;
            white-space: nowrap;
        }

        .software-table-container {
            flex: 1;
            overflow-y: auto;
        }

        .software-table {
            width: 100%;
            border-collapse: collapse;
        }

        .software-table thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            padding: 12px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: #555;
            border-bottom: 2px solid #e0e0e0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            user-select: none;
            z-index: 1;
        }

        .software-table thead th:hover {
            background-color: #eee;
        }

        .software-table thead th.sort-active {
            color: #5c6bc0;
        }

        .software-table thead th .sort-icon {
            margin-left: 4px;
            font-size: 10px;
        }

        .software-table tbody tr.app-row {
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .software-table tbody tr.app-row:hover {
            background-color: #f5f5f5;
        }

        .software-table tbody tr.app-row.expanded {
            background-color: #e8eaf6;
            border-left: 3px solid #5c6bc0;
        }

        .software-table tbody td {
            padding: 10px 20px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #333;
        }

        .install-count-badge {
            display: inline-block;
            background-color: #e8eaf6;
            color: #3f51b5;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 13px;
            min-width: 28px;
            text-align: center;
        }

        /* Expand chevron */
        .expand-icon {
            display: inline-block;
            margin-right: 8px;
            color: #999;
            font-size: 11px;
            transition: transform 0.2s;
        }

        .app-row.expanded .expand-icon {
            transform: rotate(90deg);
            color: #5c6bc0;
        }

        /* Accordion detail row */
        .software-table tbody tr.detail-row {
            display: none;
        }

        .software-table tbody tr.detail-row.visible {
            display: table-row;
        }

        .software-table tbody tr.detail-row td {
            padding: 0;
            background-color: #fafafa;
            border-bottom: 2px solid #e0e0e0;
        }

        .detail-content {
            padding: 15px 20px 15px 48px;
        }

        .detail-content h4 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #555;
            font-weight: 600;
        }

        .machine-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .machine-table thead th {
            background-color: #f0f0f0;
            padding: 8px 15px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .machine-table tbody td {
            padding: 8px 15px;
            font-size: 13px;
            color: #333;
            border-bottom: 1px solid #eee;
        }

        .machine-table tbody tr:last-child td {
            border-bottom: none;
        }

        .machine-table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .machine-hostname {
            font-family: 'Consolas', 'Courier New', monospace;
            font-weight: 500;
        }

        .detail-loading {
            padding: 20px;
            text-align: center;
            color: #888;
            font-size: 13px;
        }

        .loading-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
        }

        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #5c6bc0;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-state {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
            color: #888;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-container software-container">
        <div class="software-toolbar">
            <h3>Software Inventory</h3>
            <div class="toolbar-right">
                <input type="text" class="search-box" id="softwareSearch"
                       placeholder="Search by application name or publisher..."
                       oninput="searchSoftware()">
                <span class="software-count" id="softwareCount"></span>
            </div>
        </div>
        <div class="software-table-container">
            <table class="software-table">
                <thead>
                    <tr>
                        <th onclick="sortBy('display_name')" id="thName">
                            Application Name <span class="sort-icon">&#9650;</span>
                        </th>
                        <th onclick="sortBy('publisher')" id="thPublisher">
                            Publisher <span class="sort-icon"></span>
                        </th>
                        <th onclick="sortBy('install_count')" id="thCount">
                            Installed On <span class="sort-icon"></span>
                        </th>
                    </tr>
                </thead>
                <tbody id="softwareTableBody">
                    <tr><td colspan="3">
                        <div class="loading-spinner"><div class="spinner"></div></div>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const API_BASE = '../api/software/';
        let allApps = [];
        let filteredApps = [];
        let expandedAppId = null;
        let searchTimeout = null;
        let sortColumn = 'display_name';
        let sortDirection = 'asc';

        document.addEventListener('DOMContentLoaded', function() {
            loadSoftware();
        });

        async function loadSoftware() {
            try {
                const response = await fetch(API_BASE + 'get_apps.php');
                const data = await response.json();
                if (data.success) {
                    allApps = data.apps;
                    filteredApps = [...allApps];
                    applySortAndRender();
                } else {
                    document.getElementById('softwareTableBody').innerHTML =
                        '<tr><td colspan="3"><div class="empty-state">Error loading software: ' + escapeHtml(data.error) + '</div></td></tr>';
                }
            } catch (error) {
                console.error('Error loading software:', error);
                document.getElementById('softwareTableBody').innerHTML =
                    '<tr><td colspan="3"><div class="empty-state">Failed to load software data</div></td></tr>';
            }
        }

        function searchSoftware() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const search = document.getElementById('softwareSearch').value.toLowerCase().trim();
                if (search === '') {
                    filteredApps = [...allApps];
                } else {
                    filteredApps = allApps.filter(app =>
                        (app.display_name || '').toLowerCase().includes(search) ||
                        (app.publisher || '').toLowerCase().includes(search)
                    );
                }
                expandedAppId = null;
                applySortAndRender();
            }, 300);
        }

        function sortBy(column) {
            if (sortColumn === column) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = column;
                sortDirection = 'asc';
            }
            applySortAndRender();
        }

        function applySortAndRender() {
            filteredApps.sort((a, b) => {
                let valA, valB;
                if (sortColumn === 'install_count') {
                    valA = parseInt(a[sortColumn]) || 0;
                    valB = parseInt(b[sortColumn]) || 0;
                } else {
                    valA = (a[sortColumn] || '').toString().toLowerCase();
                    valB = (b[sortColumn] || '').toString().toLowerCase();
                }
                if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
                if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
            renderTable();
            updateSortIndicators();
        }

        function renderTable() {
            const tbody = document.getElementById('softwareTableBody');
            const countEl = document.getElementById('softwareCount');

            countEl.textContent = filteredApps.length + ' application' + (filteredApps.length !== 1 ? 's' : '');

            if (filteredApps.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3"><div class="empty-state">No software found</div></td></tr>';
                return;
            }

            tbody.innerHTML = filteredApps.map(app => {
                const isExpanded = expandedAppId == app.id;
                return `
                    <tr class="app-row ${isExpanded ? 'expanded' : ''}" onclick="toggleDetail(${app.id})">
                        <td>
                            <span class="expand-icon">&#9654;</span>${escapeHtml(app.display_name)}
                        </td>
                        <td>${escapeHtml(app.publisher || '\u2014')}</td>
                        <td><span class="install-count-badge">${app.install_count}</span></td>
                    </tr>
                    <tr class="detail-row ${isExpanded ? 'visible' : ''}" id="detail-${app.id}">
                        <td colspan="3">
                            <div class="detail-content" id="detail-content-${app.id}">
                                <div class="detail-loading">Loading machines...</div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        async function toggleDetail(appId) {
            if (expandedAppId === appId) {
                expandedAppId = null;
                renderTable();
                return;
            }

            expandedAppId = appId;
            renderTable();

            try {
                const response = await fetch(API_BASE + 'get_app_machines.php?app_id=' + appId);
                const data = await response.json();
                const container = document.getElementById('detail-content-' + appId);

                if (!container) return;

                if (data.success && data.machines.length > 0) {
                    container.innerHTML = `
                        <h4>Installed on ${data.machines.length} machine${data.machines.length !== 1 ? 's' : ''}</h4>
                        <table class="machine-table">
                            <thead>
                                <tr>
                                    <th>Hostname</th>
                                    <th>Version</th>
                                    <th>Install Date</th>
                                    <th>Last Seen</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.machines.map(m => `
                                    <tr>
                                        <td class="machine-hostname">${escapeHtml(m.hostname)}</td>
                                        <td>${escapeHtml(m.display_version || '\u2014')}</td>
                                        <td>${escapeHtml(m.install_date || '\u2014')}</td>
                                        <td>${escapeHtml(m.last_seen || '\u2014')}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else if (data.success && data.machines.length === 0) {
                    container.innerHTML = '<div class="detail-loading">No machines found for this application</div>';
                } else {
                    container.innerHTML = '<div class="detail-loading">Error loading machine data</div>';
                }
            } catch (error) {
                console.error('Error loading machines:', error);
                const container = document.getElementById('detail-content-' + appId);
                if (container) {
                    container.innerHTML = '<div class="detail-loading">Failed to load machine data</div>';
                }
            }
        }

        function updateSortIndicators() {
            const columns = {
                'display_name': 'thName',
                'publisher': 'thPublisher',
                'install_count': 'thCount'
            };

            Object.entries(columns).forEach(([col, id]) => {
                const th = document.getElementById(id);
                const icon = th.querySelector('.sort-icon');
                if (col === sortColumn) {
                    th.classList.add('sort-active');
                    icon.textContent = sortDirection === 'asc' ? '\u25B2' : '\u25BC';
                } else {
                    th.classList.remove('sort-active');
                    icon.textContent = '';
                }
            });
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
