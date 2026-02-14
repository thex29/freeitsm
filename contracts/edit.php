<?php
/**
 * Contracts Module - Add/Edit Contract
 */
session_start();
require_once '../config.php';

$current_page = 'dashboard';
$path_prefix = '../';
$contract_id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk - <?php echo $contract_id ? 'Edit' : 'Add'; ?> Contract</title>
    <link rel="stylesheet" href="../assets/css/inbox.css">
    <style>
        body { overflow: auto; height: auto; }

        .form-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 30px;
        }

        .form-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        .form-card-header {
            padding: 20px 30px;
            border-bottom: 1px solid #eee;
        }

        .form-card-header h2 { margin: 0; font-size: 20px; color: #333; }

        .form-card-body { padding: 30px; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; color: #333; }

        .form-group input, .form-group select {
            width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px;
            font-size: 14px; box-sizing: border-box; transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none; border-color: #f59e0b; box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.1);
        }

        .form-hint { font-size: 12px; color: #888; margin-top: 4px; }

        .form-actions {
            display: flex; align-items: center; gap: 12px;
            padding-top: 20px; border-top: 1px solid #eee; margin-top: 10px;
        }

        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background-color: #f59e0b; color: white; }
        .btn-primary:hover { background-color: #d97706; }
        .btn-secondary { background: #e0e0e0; color: #333; }
        .btn-secondary:hover { background: #d0d0d0; }

        .save-message { font-size: 13px; font-weight: 500; opacity: 0; transition: opacity 0.3s; }
        .save-message.visible { opacity: 1; }
        .save-message.success { color: #155724; }
        .save-message.error { color: #dc3545; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="form-container">
        <div class="form-card">
            <div class="form-card-header">
                <h2 id="pageTitle"><?php echo $contract_id ? 'Edit Contract' : 'Add Contract'; ?></h2>
            </div>
            <div class="form-card-body">
                <form id="contractForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contractNumber">Contract Number *</label>
                            <input type="text" id="contractNumber" required placeholder="e.g. CON-001">
                        </div>
                        <div class="form-group">
                            <label for="supplierId">Supplier</label>
                            <select id="supplierId">
                                <option value="">-- Select Supplier --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" required placeholder="e.g. Annual Support Agreement">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ownerId">Contract Owner</label>
                            <select id="ownerId">
                                <option value="">-- Select Owner --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="noticePeriod">Notice Period (days)</label>
                            <input type="number" id="noticePeriod" min="0" placeholder="e.g. 90">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contractStart">Start Date</label>
                            <input type="date" id="contractStart">
                        </div>
                        <div class="form-group">
                            <label for="contractEnd">End Date</label>
                            <input type="date" id="contractEnd">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="saveBtn">Save Contract</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <span class="save-message" id="saveMessage"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../api/contracts/';
        const TICKETS_API = '../api/tickets/';
        const contractId = <?php echo json_encode($contract_id); ?>;

        document.addEventListener('DOMContentLoaded', async function() {
            await loadSuppliers();
            await loadAnalysts();
            if (contractId) await loadContract();
        });

        async function loadSuppliers() {
            try {
                const response = await fetch(API_BASE + 'get_suppliers.php');
                const data = await response.json();
                if (data.success) {
                    const select = document.getElementById('supplierId');
                    select.innerHTML = '<option value="">-- Select Supplier --</option>' +
                        data.suppliers.filter(s => s.is_active).map(s =>
                            `<option value="${s.id}">${escapeHtml(s.legal_name)}</option>`
                        ).join('');
                }
            } catch (error) { console.error('Error loading suppliers:', error); }
        }

        async function loadAnalysts() {
            try {
                const response = await fetch(TICKETS_API + 'get_analysts.php');
                const data = await response.json();
                if (data.success) {
                    const select = document.getElementById('ownerId');
                    select.innerHTML = '<option value="">-- Select Owner --</option>' +
                        data.analysts.filter(a => a.is_active).map(a =>
                            `<option value="${a.id}">${escapeHtml(a.full_name)}</option>`
                        ).join('');
                }
            } catch (error) { console.error('Error loading analysts:', error); }
        }

        async function loadContract() {
            try {
                const response = await fetch(API_BASE + 'get_contract.php?id=' + contractId);
                const data = await response.json();
                if (data.success) {
                    const c = data.contract;
                    document.getElementById('contractNumber').value = c.contract_number;
                    document.getElementById('title').value = c.title;
                    document.getElementById('supplierId').value = c.supplier_id || '';
                    document.getElementById('ownerId').value = c.contract_owner_id || '';
                    document.getElementById('contractStart').value = c.contract_start || '';
                    document.getElementById('contractEnd').value = c.contract_end || '';
                    document.getElementById('noticePeriod').value = c.notice_period_days || '';
                }
            } catch (error) { console.error('Error loading contract:', error); }
        }

        document.getElementById('contractForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            const payload = {
                contract_number: document.getElementById('contractNumber').value.trim(),
                title: document.getElementById('title').value.trim(),
                supplier_id: document.getElementById('supplierId').value || null,
                contract_owner_id: document.getElementById('ownerId').value || null,
                contract_start: document.getElementById('contractStart').value || null,
                contract_end: document.getElementById('contractEnd').value || null,
                notice_period_days: document.getElementById('noticePeriod').value || null,
                is_active: 1
            };
            if (contractId) payload.id = parseInt(contractId);

            try {
                const response = await fetch(API_BASE + 'save_contract.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.success) {
                    if (contractId) {
                        showMessage('Contract saved successfully', 'success');
                    } else {
                        window.location.href = 'view.php?id=' + data.id;
                    }
                } else {
                    showMessage('Error: ' + data.error, 'error');
                }
            } catch (error) {
                showMessage('Failed to save contract', 'error');
            }

            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Contract';
        });

        function showMessage(text, type) {
            const el = document.getElementById('saveMessage');
            el.textContent = text;
            el.className = 'save-message visible ' + type;
            setTimeout(() => el.classList.remove('visible'), 3000);
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
