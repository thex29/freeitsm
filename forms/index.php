<?php
/**
 * Forms Module - Form List
 */
session_start();
require_once '../config.php';

$current_page = 'forms';
$path_prefix = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk - Forms</title>
    <link rel="stylesheet" href="../assets/css/inbox.css">
    <style>
        .forms-container {
            flex: 1;
            overflow-y: auto;
            background-color: #f5f7fa;
        }

        .forms-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 25px;
        }

        .forms-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .forms-toolbar h2 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }

        .btn {
            padding: 9px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.15s;
        }

        .btn-primary {
            background-color: #00897b;
            color: white;
        }

        .btn-primary:hover {
            background-color: #00695c;
        }

        .forms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }

        .form-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            padding: 20px;
            transition: box-shadow 0.15s;
        }

        .form-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .form-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .form-card-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .form-card-status {
            font-size: 11px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 10px;
            flex-shrink: 0;
        }

        .form-card-status.active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .form-card-status.inactive {
            background: #f5f5f5;
            color: #999;
        }

        .form-card-desc {
            font-size: 13px;
            color: #666;
            margin: 0 0 14px 0;
            line-height: 1.4;
            min-height: 18px;
        }

        .form-card-meta {
            display: flex;
            gap: 16px;
            font-size: 12px;
            color: #999;
            margin-bottom: 14px;
        }

        .form-card-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .form-card-actions {
            display: flex;
            gap: 8px;
            border-top: 1px solid #f0f0f0;
            padding-top: 12px;
        }

        .form-card-actions .btn {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
        }

        .btn-fill {
            background: #00897b;
            color: white;
        }

        .btn-fill:hover {
            background: #00695c;
        }

        .btn-edit {
            background: #f5f7fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-edit:hover {
            background: #eef0f2;
        }

        .btn-submissions {
            background: #f5f7fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-submissions:hover {
            background: #eef0f2;
        }

        .btn-delete {
            background: none;
            color: #d32f2f;
            margin-left: auto;
            padding: 6px 8px;
        }

        .btn-delete:hover {
            background: #ffebee;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .empty-state h3 {
            margin: 15px 0 8px;
            font-size: 16px;
            color: #666;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        .confirm-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .confirm-overlay.open { display: flex; }

        .confirm-box {
            background: #fff;
            border-radius: 8px;
            padding: 24px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }

        .confirm-box h3 { margin: 0 0 8px; font-size: 16px; }
        .confirm-box p { margin: 0 0 20px; font-size: 14px; color: #666; }

        .confirm-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-cancel {
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-danger {
            background: #d32f2f;
            color: white;
        }

        .btn-danger:hover {
            background: #b71c1c;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-container forms-container">
        <div class="forms-content">
            <div class="forms-toolbar">
                <h2>Forms</h2>
                <a href="builder.php" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    New Form
                </a>
            </div>

            <div class="forms-grid" id="formsGrid">
                <div style="text-align:center;padding:40px;color:#888;grid-column:1/-1">Loading...</div>
            </div>
        </div>
    </div>

    <div class="confirm-overlay" id="confirmOverlay" onclick="if(event.target===this)closeConfirm()">
        <div class="confirm-box">
            <h3>Delete Form</h3>
            <p>This will permanently delete this form and all its submissions. Are you sure?</p>
            <div class="confirm-actions">
                <button class="btn btn-cancel" onclick="closeConfirm()">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../api/forms/';

        document.addEventListener('DOMContentLoaded', loadForms);

        async function loadForms() {
            try {
                const res = await fetch(API_BASE + 'get_forms.php');
                const data = await res.json();

                if (data.success) {
                    renderForms(data.forms);
                } else {
                    document.getElementById('formsGrid').innerHTML = '<div style="text-align:center;padding:40px;color:#c00;grid-column:1/-1">Error: ' + data.error + '</div>';
                }
            } catch (e) {
                console.error(e);
            }
        }

        function renderForms(forms) {
            const grid = document.getElementById('formsGrid');

            if (forms.length === 0) {
                grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <h3>No forms yet</h3>
                    <p>Click "New Form" to create your first form</p>
                </div>`;
                return;
            }

            grid.innerHTML = forms.map(f => `
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">${esc(f.title)}</h3>
                        <span class="form-card-status ${f.is_active == 1 ? 'active' : 'inactive'}">${f.is_active == 1 ? 'Active' : 'Inactive'}</span>
                    </div>
                    <p class="form-card-desc">${esc(f.description || 'No description')}</p>
                    <div class="form-card-meta">
                        <span>${f.field_count} field${f.field_count != 1 ? 's' : ''}</span>
                        <span>${f.submission_count} submission${f.submission_count != 1 ? 's' : ''}</span>
                        <span>by ${esc(f.created_by_name || 'Unknown')}</span>
                    </div>
                    <div class="form-card-actions">
                        <a href="fill.php?id=${f.id}" class="btn btn-fill">Fill In</a>
                        <a href="builder.php?id=${f.id}" class="btn btn-edit">Edit</a>
                        <a href="submissions.php?id=${f.id}" class="btn btn-submissions">Submissions (${f.submission_count})</a>
                        <button class="btn btn-delete" onclick="confirmDelete(${f.id})" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        let deleteFormId = null;

        function confirmDelete(id) {
            deleteFormId = id;
            document.getElementById('confirmOverlay').classList.add('open');
        }

        function closeConfirm() {
            document.getElementById('confirmOverlay').classList.remove('open');
            deleteFormId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
            if (!deleteFormId) return;
            try {
                const res = await fetch(API_BASE + 'delete_form.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: deleteFormId })
                });
                const data = await res.json();
                if (data.success) {
                    closeConfirm();
                    loadForms();
                }
            } catch (e) {
                console.error(e);
            }
        });

        function esc(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
