<?php
/**
 * Contracts Module - Suppliers
 */
session_start();
require_once '../../config.php';

$current_page = 'suppliers';
$path_prefix = '../../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk - Suppliers</title>
    <link rel="stylesheet" href="../../assets/css/inbox.css">
    <style>
        body { overflow: auto; height: auto; }

        .tab-content .action-btn {
            background: none; border: 1px solid #ddd; color: #666; cursor: pointer;
            padding: 6px; margin-right: 4px; border-radius: 4px;
            display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;
        }
        .tab-content .action-btn:hover { background: #f0f0f0; border-color: #f59e0b; color: #f59e0b; }
        .tab-content .action-btn.delete { color: #d13438; }
        .tab-content .action-btn.delete:hover { background: #fdf3f3; border-color: #d13438; color: #a00; }
        .tab-content .action-btn svg { width: 16px; height: 16px; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-badge.active { background: #d4edda; color: #155724; }
        .status-badge.inactive { background: #f8d7da; color: #721c24; }

        .modal-content { padding: 30px; max-width: 500px; }
        .modal-header { font-size: 20px; font-weight: 600; margin-bottom: 20px; color: #333; padding: 0; border-bottom: none; }
        .modal .form-group { margin-bottom: 15px; }
        .modal .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px; color: #333; }
        .modal .form-group input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        .modal .form-group input:focus { outline: none; border-color: #f59e0b; box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.1); }
        .modal .checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; }
        .modal .checkbox-label input[type="checkbox"] { width: auto; }
        .modal-actions { margin-top: 20px; }

        .btn-primary { background-color: #f59e0b; color: white; }
        .btn-primary:hover { background-color: #d97706; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="tab-content active">
            <div class="section-header">
                <h2>Suppliers</h2>
                <button class="add-btn" onclick="openModal()">Add</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Legal Name</th>
                        <th>Trading Name</th>
                        <th>Contracts</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="suppliersList">
                    <tr><td colspan="5" style="text-align: center; padding: 20px; color: #999;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">Add Supplier</div>
            <form id="editForm">
                <input type="hidden" id="itemId">
                <div class="form-group">
                    <label for="legalName">Legal Name</label>
                    <input type="text" id="legalName" required>
                </div>
                <div class="form-group">
                    <label for="tradingName">Trading Name</label>
                    <input type="text" id="tradingName" placeholder="If different from legal name">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="itemActive" checked>
                        Active
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '../../api/contracts/';
        let suppliers = [];

        document.addEventListener('DOMContentLoaded', loadSuppliers);

        async function loadSuppliers() {
            try {
                const response = await fetch(API_BASE + 'get_suppliers.php');
                const data = await response.json();
                if (data.success) {
                    suppliers = data.suppliers;
                    renderSuppliers();
                } else {
                    document.getElementById('suppliersList').innerHTML =
                        '<tr><td colspan="5" style="text-align:center;padding:20px;color:#d13438;">Error: ' + escapeHtml(data.error) + '</td></tr>';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function renderSuppliers() {
            const tbody = document.getElementById('suppliersList');
            if (suppliers.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#999;">No suppliers yet. Click Add to create one.</td></tr>';
                return;
            }
            tbody.innerHTML = suppliers.map(s => `
                <tr>
                    <td><strong>${escapeHtml(s.legal_name)}</strong></td>
                    <td>${escapeHtml(s.trading_name || '-')}</td>
                    <td>-</td>
                    <td><span class="status-badge ${s.is_active ? 'active' : 'inactive'}">${s.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>
                        <button class="action-btn" onclick="editSupplier(${s.id})" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </button>
                        <button class="action-btn delete" onclick="deleteSupplier(${s.id}, '${escapeHtml(s.legal_name).replace(/'/g, "\\'")}')" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function openModal(supplier = null) {
            document.getElementById('modalTitle').textContent = supplier ? 'Edit Supplier' : 'Add Supplier';
            document.getElementById('itemId').value = supplier ? supplier.id : '';
            document.getElementById('legalName').value = supplier ? supplier.legal_name : '';
            document.getElementById('tradingName').value = supplier ? (supplier.trading_name || '') : '';
            document.getElementById('itemActive').checked = supplier ? supplier.is_active : true;
            document.getElementById('editModal').classList.add('active');
        }

        function closeModal() { document.getElementById('editModal').classList.remove('active'); }

        function editSupplier(id) {
            const s = suppliers.find(x => x.id == id);
            if (s) openModal(s);
        }

        async function deleteSupplier(id, name) {
            if (!confirm('Delete "' + name + '"? Contracts and contacts linked to this supplier will have the supplier cleared.')) return;
            try {
                const response = await fetch(API_BASE + 'delete_supplier.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await response.json();
                if (data.success) loadSuppliers();
                else alert('Error: ' + data.error);
            } catch (error) { alert('Failed to delete supplier'); }
        }

        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('itemId').value;
            const payload = {
                legal_name: document.getElementById('legalName').value.trim(),
                trading_name: document.getElementById('tradingName').value.trim(),
                is_active: document.getElementById('itemActive').checked ? 1 : 0
            };
            if (id) payload.id = parseInt(id);
            try {
                const response = await fetch(API_BASE + 'save_supplier.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.success) { closeModal(); loadSuppliers(); }
                else alert('Error: ' + data.error);
            } catch (error) { alert('Failed to save supplier'); }
        });

        let modalMouseDownTarget = null;
        document.getElementById('editModal').addEventListener('mousedown', function(e) { modalMouseDownTarget = e.target; });
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this && modalMouseDownTarget === this) closeModal();
        });

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
