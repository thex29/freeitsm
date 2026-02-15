<?php
/**
 * Change Management Settings - Configure module behaviour
 */
session_start();
require_once '../../config.php';

if (!isset($_SESSION['analyst_id'])) {
    header('Location: ../../login.php');
    exit;
}

$analyst_name = $_SESSION['analyst_name'] ?? 'Analyst';
$current_page = 'settings';
$path_prefix = '../../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk - Change Management Settings</title>
    <link rel="stylesheet" href="../../assets/css/inbox.css">
    <style>
        body {
            overflow: auto;
            height: auto;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
        }

        /* Teal theme for tabs */
        .tab:hover { color: #00897b; }
        .tab.active { color: #00897b; border-bottom-color: #00897b; }

        .section-header h2 {
            margin: 0 0 8px;
            font-size: 18px;
            color: #333;
        }

        .field-group-heading {
            font-size: 13px;
            font-weight: 600;
            color: #00897b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 0 8px;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 0;
        }

        .field-group-heading:first-child {
            padding-top: 0;
        }

        .field-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .field-row:last-child {
            border-bottom: none;
        }

        .field-row-label {
            font-size: 14px;
            color: #333;
        }

        /* Toggle switch */
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            border-radius: 24px;
            transition: background 0.2s;
        }

        .toggle-slider:before {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: transform 0.2s;
        }

        .toggle-switch input:checked + .toggle-slider {
            background: #00897b;
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary { background: #00897b; color: white; }
        .btn-primary:hover { background: #00695c; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="tabs">
            <button class="tab active" data-tab="fields" onclick="switchTab('fields')">Form Fields</button>
        </div>

        <!-- Form Fields Tab -->
        <div class="tab-content active" id="fields-tab">
            <div class="section-header">
                <h2>Form Fields</h2>
            </div>
            <p style="color: #666; margin-bottom: 20px;">Control which fields appear on the change editor and detail view. Hidden fields will not be shown or required.</p>

            <div id="fieldSettings"></div>

            <div class="form-actions">
                <button class="btn btn-primary" onclick="saveSettings()">Save</button>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div class="toast" id="toast"></div>

    <script>
        const API_BASE = '../../api/change-management/';

        // Field configuration organised by section
        const FIELD_SECTIONS = [
            {
                id: 'general',
                label: 'General Information',
                fields: [
                    { id: 'title',       label: 'Title' },
                    { id: 'change_type', label: 'Change Type' },
                    { id: 'status',      label: 'Status' },
                    { id: 'priority',    label: 'Priority' },
                    { id: 'impact',      label: 'Impact' },
                    { id: 'category',    label: 'Category' }
                ]
            },
            {
                id: 'people',
                label: 'People',
                fields: [
                    { id: 'requester',   label: 'Requester' },
                    { id: 'assigned_to', label: 'Assigned To' },
                    { id: 'approver',    label: 'Approver' }
                ]
            },
            {
                id: 'schedule',
                label: 'Schedule',
                fields: [
                    { id: 'work_start',   label: 'Work Start' },
                    { id: 'work_end',     label: 'Work End' },
                    { id: 'outage_start', label: 'Outage Start' },
                    { id: 'outage_end',   label: 'Outage End' }
                ]
            },
            {
                id: 'details',
                label: 'Details',
                fields: [
                    { id: 'description', label: 'Description' },
                    { id: 'reason',      label: 'Reason for Change' },
                    { id: 'risk',        label: 'Risk Evaluation' },
                    { id: 'testplan',    label: 'Test Plan' },
                    { id: 'rollback',    label: 'Rollback Plan' },
                    { id: 'pir',         label: 'Post-Implementation Review' }
                ]
            },
            {
                id: 'attachments',
                label: 'Attachments',
                fields: [
                    { id: 'attachments', label: 'Attachments' }
                ]
            }
        ];

        // Current visibility state (default all visible)
        let fieldVisibility = {};

        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            const btn = document.querySelector('.tab[data-tab="' + tab + '"]');
            if (btn) btn.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(tab + '-tab').classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
        });

        async function loadSettings() {
            try {
                const res = await fetch(API_BASE + 'get_settings.php');
                const data = await res.json();
                if (data.success && data.settings && data.settings.field_visibility) {
                    fieldVisibility = data.settings.field_visibility;
                }
            } catch (e) {
                console.error(e);
            }
            renderFieldSettings();
        }

        function renderFieldSettings() {
            const container = document.getElementById('fieldSettings');
            let html = '';

            FIELD_SECTIONS.forEach(section => {
                html += `<div class="field-group-heading">${section.label}</div>`;
                section.fields.forEach(field => {
                    const isVisible = fieldVisibility[field.id] !== false;
                    html += `
                        <div class="field-row">
                            <span class="field-row-label">${field.label}</span>
                            <label class="toggle-switch">
                                <input type="checkbox" data-field="${field.id}" ${isVisible ? 'checked' : ''} onchange="toggleField('${field.id}', this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    `;
                });
            });

            container.innerHTML = html;
        }

        function toggleField(fieldId, visible) {
            fieldVisibility[fieldId] = visible;
        }

        async function saveSettings() {
            try {
                const res = await fetch(API_BASE + 'save_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ settings: { field_visibility: fieldVisibility } })
                });
                const data = await res.json();

                if (data.success) {
                    showToast('Settings saved');
                } else {
                    showToast('Error: ' + data.error, true);
                }
            } catch (e) {
                showToast('Failed to save settings', true);
            }
        }

        function showToast(message, isError) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast' + (isError ? ' toast-error' : '');
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>
