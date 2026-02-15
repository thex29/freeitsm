<?php
/**
 * Forms Module - Form List & Builder (unified single-page layout)
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
    <link rel="stylesheet" href="../assets/css/forms.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="forms-container">
        <!-- Sidebar with search and form list -->
        <div class="forms-sidebar">
            <div class="sidebar-section">
                <h3>Search Forms</h3>
                <div class="search-box">
                    <input type="text" id="formSearch" placeholder="Search by title..." onkeyup="filterForms()">
                </div>
            </div>
            <div class="sidebar-section">
                <button class="btn btn-primary btn-full" onclick="openNewForm()">+ New Form</button>
            </div>
            <div class="sidebar-section" style="flex: 1; overflow-y: auto;">
                <h3>Forms</h3>
                <div class="form-list" id="formList">
                    <div class="form-list-empty">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Main content area -->
        <div class="forms-main">
            <!-- Welcome / empty state -->
            <div class="forms-welcome" id="welcomeView">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <h3>Select a form or create a new one</h3>
                <p>Use the sidebar to browse your forms or click "New Form" to get started.</p>
            </div>

            <!-- Editor view -->
            <div id="editorView" style="display: none;">
                <div class="editor-toolbar">
                    <h2 id="editorTitle">New Form</h2>
                    <div class="editor-toolbar-actions">
                        <button class="btn btn-secondary" onclick="cancelEdit()">Cancel</button>
                        <button class="btn btn-primary" onclick="saveForm()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                            Save
                        </button>
                    </div>
                </div>

                <div class="save-message" id="saveMessage"></div>

                <!-- Title & Description (full width) -->
                <div class="form-settings-card">
                    <div class="field-group">
                        <label>Form Title</label>
                        <input type="text" id="formTitle" placeholder="Enter form title...">
                    </div>
                    <div class="field-group">
                        <label>Description</label>
                        <textarea id="formDesc" rows="2" placeholder="Optional description..."></textarea>
                    </div>
                </div>

                <!-- Tabs: Fields | Preview -->
                <div class="form-tabs">
                    <button class="form-tab active" onclick="switchFormTab('fields')" id="tabFields">Fields</button>
                    <button class="form-tab" onclick="switchFormTab('preview')" id="tabPreview">Preview</button>
                </div>

                <!-- Fields tab -->
                <div class="form-tab-content active" id="tabContentFields">
                    <div class="fields-header">
                        <h3>Form Fields</h3>
                        <div class="add-field-btn">
                            <button class="btn btn-secondary" onclick="toggleAddMenu()" id="addFieldBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                Add
                            </button>
                            <div class="add-field-menu" id="addFieldMenu">
                                <button onclick="addField('text')"><span class="field-type-badge text">Abc</span> Text Input</button>
                                <button onclick="addField('textarea')"><span class="field-type-badge textarea">Txt</span> Text Area</button>
                                <button onclick="addField('checkbox')"><span class="field-type-badge checkbox">Chk</span> Checkbox</button>
                                <button onclick="addField('dropdown')"><span class="field-type-badge dropdown">Sel</span> Dropdown</button>
                            </div>
                        </div>
                    </div>
                    <ul class="field-list" id="fieldList">
                        <li class="no-fields">No fields added yet. Click "Add" to start building your form.</li>
                    </ul>
                </div>

                <!-- Preview tab -->
                <div class="form-tab-content" id="tabContentPreview">
                    <div id="previewContent">
                        <p class="preview-empty">Add fields to see a preview</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete confirmation -->
    <div class="confirm-overlay" id="confirmOverlay" onclick="if(event.target===this)closeConfirm()">
        <div class="confirm-box">
            <h3>Delete Form</h3>
            <p>This will permanently delete this form and all its submissions. Are you sure?</p>
            <div class="confirm-actions">
                <button class="btn btn-secondary" onclick="closeConfirm()">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../api/forms/';
        let allForms = [];
        let currentFormId = null;
        let fields = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadForms();

            // Check URL for direct form editing
            const params = new URLSearchParams(window.location.search);
            const editId = params.get('id');
            if (editId) {
                openEditForm(parseInt(editId));
            }

            // Close add field menu on outside click
            document.addEventListener('click', function(e) {
                const menu = document.getElementById('addFieldMenu');
                if (menu && !e.target.closest('.add-field-btn')) {
                    menu.classList.remove('open');
                }
            });
        });

        // ========== Form List ==========

        async function loadForms() {
            try {
                const res = await fetch(API_BASE + 'get_forms.php');
                const data = await res.json();
                if (data.success) {
                    allForms = data.forms;
                    renderFormList(allForms);
                }
            } catch (e) {
                console.error(e);
            }
        }

        function renderFormList(forms) {
            const list = document.getElementById('formList');

            if (forms.length === 0) {
                list.innerHTML = '<div class="form-list-empty">No forms found</div>';
                return;
            }

            list.innerHTML = forms.map(f => `
                <div class="form-list-item ${currentFormId == f.id ? 'active' : ''}" onclick="openEditForm(${f.id})">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="form-list-item-title">${esc(f.title)}</div>
                        <span class="form-list-item-status ${f.is_active == 1 ? 'active' : 'inactive'}">${f.is_active == 1 ? 'Active' : 'Inactive'}</span>
                    </div>
                    <div class="form-list-item-meta">
                        <span>${f.field_count} field${f.field_count != 1 ? 's' : ''}</span>
                        <span>${f.submission_count} submission${f.submission_count != 1 ? 's' : ''}</span>
                    </div>
                    <div class="form-list-item-actions">
                        <a href="fill.php?id=${f.id}" class="btn btn-primary" onclick="event.stopPropagation()">Fill In</a>
                        <a href="submissions.php?id=${f.id}" class="btn btn-secondary" onclick="event.stopPropagation()">Submissions</a>
                        <button class="btn btn-danger" onclick="event.stopPropagation(); confirmDelete(${f.id})" style="margin-left: auto; padding: 4px 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function filterForms() {
            const query = document.getElementById('formSearch').value.toLowerCase();
            const filtered = allForms.filter(f => f.title.toLowerCase().includes(query));
            renderFormList(filtered);
        }

        // ========== Editor ==========

        function openNewForm() {
            currentFormId = null;
            fields = [];
            document.getElementById('editorTitle').textContent = 'New Form';
            document.getElementById('formTitle').value = '';
            document.getElementById('formDesc').value = '';
            hideSaveMessage();
            renderFields();
            updatePreview();
            switchFormTab('fields');
            showEditor();
            renderFormList(allForms); // Remove active highlight
            history.replaceState(null, '', './');
        }

        async function openEditForm(id) {
            currentFormId = id;
            document.getElementById('editorTitle').textContent = 'Edit Form';
            hideSaveMessage();
            showEditor();
            renderFormList(allForms); // Update active highlight

            try {
                const res = await fetch(API_BASE + 'get_form.php?id=' + id);
                const data = await res.json();
                if (data.success) {
                    document.getElementById('formTitle').value = data.form.title;
                    document.getElementById('formDesc').value = data.form.description || '';
                    fields = data.form.fields.map(f => ({
                        field_type: f.field_type,
                        label: f.label,
                        options: f.options ? JSON.parse(f.options) : [],
                        is_required: f.is_required == 1
                    }));
                    renderFields();
                    updatePreview();
                    switchFormTab('fields');
                    history.replaceState(null, '', './?id=' + id);
                }
            } catch (e) {
                console.error(e);
            }
        }

        function showEditor() {
            document.getElementById('welcomeView').style.display = 'none';
            document.getElementById('editorView').style.display = 'block';
        }

        function cancelEdit() {
            currentFormId = null;
            document.getElementById('editorView').style.display = 'none';
            document.getElementById('welcomeView').style.display = 'flex';
            renderFormList(allForms);
            history.replaceState(null, '', './');
        }

        // ========== Tabs ==========

        function switchFormTab(tab) {
            document.getElementById('tabFields').classList.toggle('active', tab === 'fields');
            document.getElementById('tabPreview').classList.toggle('active', tab === 'preview');
            document.getElementById('tabContentFields').classList.toggle('active', tab === 'fields');
            document.getElementById('tabContentPreview').classList.toggle('active', tab === 'preview');

            if (tab === 'preview') {
                updatePreview();
            }
        }

        // ========== Field Management ==========

        function toggleAddMenu() {
            document.getElementById('addFieldMenu').classList.toggle('open');
        }

        function addField(type) {
            document.getElementById('addFieldMenu').classList.remove('open');
            fields.push({
                field_type: type,
                label: '',
                options: type === 'dropdown' ? ['Option 1'] : [],
                is_required: false
            });
            renderFields();
            updatePreview();
            setTimeout(() => {
                const inputs = document.querySelectorAll('.field-label-input');
                if (inputs.length) inputs[inputs.length - 1].focus();
            }, 50);
        }

        function renderFields() {
            const list = document.getElementById('fieldList');

            if (fields.length === 0) {
                list.innerHTML = '<li class="no-fields">No fields added yet. Click "Add" to start building your form.</li>';
                return;
            }

            list.innerHTML = fields.map((f, i) => {
                let optionsHtml = '';
                if (f.field_type === 'dropdown') {
                    optionsHtml = `
                        <div class="field-options">
                            <div class="field-options-label">Dropdown Options</div>
                            ${(f.options || []).map((opt, oi) => `
                                <div class="option-item" draggable="true"
                                     ondragstart="onOptDragStart(event, ${i}, ${oi})"
                                     ondragend="onOptDragEnd(event)"
                                     ondragover="onOptDragOver(event, ${i}, ${oi})"
                                     ondrop="onOptDrop(event, ${i}, ${oi})">
                                    <span class="option-drag" title="Drag to reorder">⠿</span>
                                    <input type="text" value="${esc(opt)}"
                                           onchange="updateOption(${i}, ${oi}, this.value)"
                                           onkeydown="onOptionKeydown(event, ${i}, ${oi})"
                                           placeholder="Option ${oi + 1}">
                                    <button class="option-remove" onclick="removeOption(${i}, ${oi})">&times;</button>
                                </div>
                            `).join('')}
                            <button class="add-option-btn" onclick="addOption(${i})">+ Add Option</button>
                        </div>`;
                }

                return `
                    <li class="field-item" data-index="${i}" draggable="true"
                        ondragstart="onFieldDragStart(event, ${i})"
                        ondragend="onFieldDragEnd(event)"
                        ondragover="onFieldDragOver(event, ${i})"
                        ondrop="onFieldDrop(event, ${i})">
                        <div class="field-item-header">
                            <span class="field-drag" title="Drag to reorder">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                            </span>
                            <span class="field-type-badge ${f.field_type}">${typeName(f.field_type)}</span>
                            <input type="text" class="field-label-input" value="${esc(f.label)}" placeholder="Field label..." onchange="updateLabel(${i}, this.value)">
                            <div class="field-controls">
                                <label class="field-required-toggle">
                                    <input type="checkbox" ${f.is_required ? 'checked' : ''} onchange="toggleRequired(${i}, this.checked)">
                                    Required
                                </label>
                                <button class="field-delete-btn" onclick="deleteField(${i})" title="Remove field">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                        </div>
                        ${optionsHtml}
                    </li>`;
            }).join('');
        }

        function typeName(t) {
            return { text: 'Text', textarea: 'Textarea', checkbox: 'Checkbox', dropdown: 'Dropdown' }[t] || t;
        }

        function updateLabel(i, val) { fields[i].label = val; updatePreview(); }
        function toggleRequired(i, val) { fields[i].is_required = val; updatePreview(); }

        function deleteField(i) {
            fields.splice(i, 1);
            renderFields();
            updatePreview();
        }

        function addOption(fi) {
            fields[fi].options.push('');
            renderFields();
            setTimeout(() => {
                const items = document.querySelectorAll(`.field-item[data-index="${fi}"] .option-item input[type="text"]`);
                if (items.length) items[items.length - 1].focus();
            }, 50);
        }

        function updateOption(fi, oi, val) {
            fields[fi].options[oi] = val;
            updatePreview();
        }

        function removeOption(fi, oi) {
            fields[fi].options.splice(oi, 1);
            renderFields();
            updatePreview();
        }

        function onOptionKeydown(e, fi, oi) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Save current value first
                fields[fi].options[oi] = e.target.value;
                addOption(fi);
            }
        }

        // ========== Field Drag & Drop ==========

        let dragFieldIndex = null;

        function onFieldDragStart(e, i) {
            // Only allow drag from the handle
            if (!e.target.closest('.field-drag')) {
                e.preventDefault();
                return;
            }
            dragFieldIndex = i;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', 'field');
            requestAnimationFrame(() => e.target.closest('.field-item').classList.add('dragging'));
        }

        function onFieldDragEnd(e) {
            dragFieldIndex = null;
            document.querySelectorAll('.field-item').forEach(el => {
                el.classList.remove('dragging', 'drag-over-top', 'drag-over-bottom');
            });
        }

        function onFieldDragOver(e, i) {
            if (dragFieldIndex === null || dragFieldIndex === i) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const rect = e.currentTarget.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            // Clear all indicators
            document.querySelectorAll('.field-item').forEach(el => {
                el.classList.remove('drag-over-top', 'drag-over-bottom');
            });
            if (e.clientY < midY) {
                e.currentTarget.classList.add('drag-over-top');
            } else {
                e.currentTarget.classList.add('drag-over-bottom');
            }
        }

        function onFieldDrop(e, i) {
            e.preventDefault();
            if (dragFieldIndex === null || dragFieldIndex === i) return;
            const rect = e.currentTarget.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            let targetIndex = e.clientY < midY ? i : i + 1;
            // Adjust if dragging from before the target
            if (dragFieldIndex < targetIndex) targetIndex--;
            const [moved] = fields.splice(dragFieldIndex, 1);
            fields.splice(targetIndex, 0, moved);
            dragFieldIndex = null;
            renderFields();
            updatePreview();
        }

        // ========== Option Drag & Drop ==========

        let dragOptFieldIndex = null;
        let dragOptIndex = null;

        function onOptDragStart(e, fi, oi) {
            if (!e.target.closest('.option-drag')) {
                e.preventDefault();
                return;
            }
            e.stopPropagation(); // Don't trigger field drag
            dragOptFieldIndex = fi;
            dragOptIndex = oi;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', 'option');
            requestAnimationFrame(() => e.target.closest('.option-item').classList.add('dragging'));
        }

        function onOptDragEnd(e) {
            dragOptFieldIndex = null;
            dragOptIndex = null;
            document.querySelectorAll('.option-item').forEach(el => {
                el.classList.remove('dragging', 'drag-over-top', 'drag-over-bottom');
            });
        }

        function onOptDragOver(e, fi, oi) {
            if (dragOptFieldIndex !== fi || dragOptIndex === null || dragOptIndex === oi) return;
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = 'move';
            const rect = e.currentTarget.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            // Clear indicators within this field's options
            e.currentTarget.closest('.field-options').querySelectorAll('.option-item').forEach(el => {
                el.classList.remove('drag-over-top', 'drag-over-bottom');
            });
            if (e.clientY < midY) {
                e.currentTarget.classList.add('drag-over-top');
            } else {
                e.currentTarget.classList.add('drag-over-bottom');
            }
        }

        function onOptDrop(e, fi, oi) {
            e.preventDefault();
            e.stopPropagation();
            if (dragOptFieldIndex !== fi || dragOptIndex === null || dragOptIndex === oi) return;
            const rect = e.currentTarget.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            let targetIndex = e.clientY < midY ? oi : oi + 1;
            if (dragOptIndex < targetIndex) targetIndex--;
            const opts = fields[fi].options;
            const [moved] = opts.splice(dragOptIndex, 1);
            opts.splice(targetIndex, 0, moved);
            dragOptFieldIndex = null;
            dragOptIndex = null;
            renderFields();
            updatePreview();
        }

        // ========== Preview ==========

        function updatePreview() {
            const title = document.getElementById('formTitle').value || 'Untitled Form';
            const desc = document.getElementById('formDesc').value;
            const preview = document.getElementById('previewContent');

            if (fields.length === 0) {
                preview.innerHTML = '<p class="preview-empty">Add fields to see a preview</p>';
                return;
            }

            let html = `<img src="../assets/images/CompanyLogo.png" alt="Company Logo" class="preview-logo">`;
            html += `<p class="preview-title">${esc(title)}</p>`;
            if (desc) html += `<p class="preview-desc">${esc(desc)}</p>`;

            html += fields.map(f => {
                const reqStar = f.is_required ? '<span class="required-star">*</span>' : '';
                const label = esc(f.label || 'Untitled field');

                switch (f.field_type) {
                    case 'text':
                        return `<div class="preview-field"><label>${label}${reqStar}</label><input type="text" disabled placeholder="Text input..."></div>`;
                    case 'textarea':
                        return `<div class="preview-field"><label>${label}${reqStar}</label><textarea disabled placeholder="Text area..."></textarea></div>`;
                    case 'checkbox':
                        return `<div class="preview-field"><div class="checkbox-row"><input type="checkbox" disabled> <label>${label}${reqStar}</label></div></div>`;
                    case 'dropdown':
                        const opts = (f.options || []).filter(o => o).map(o => `<option>${esc(o)}</option>`).join('');
                        return `<div class="preview-field"><label>${label}${reqStar}</label><select disabled><option value="">Select...</option>${opts}</select></div>`;
                    default:
                        return '';
                }
            }).join('');

            preview.innerHTML = html;
        }

        // Update preview on title/desc change
        document.getElementById('formTitle').addEventListener('input', updatePreview);
        document.getElementById('formDesc').addEventListener('input', updatePreview);

        // ========== Save ==========

        async function saveForm() {
            const title = document.getElementById('formTitle').value.trim();
            if (!title) {
                showMessage('Please enter a form title', 'error');
                return;
            }

            const validFields = fields.filter(f => f.label.trim());
            if (validFields.length === 0) {
                showMessage('Please add at least one field with a label', 'error');
                return;
            }

            const payload = {
                title: title,
                description: document.getElementById('formDesc').value.trim(),
                fields: validFields.map(f => ({
                    field_type: f.field_type,
                    label: f.label.trim(),
                    options: f.field_type === 'dropdown' ? f.options.filter(o => o.trim()) : null,
                    is_required: f.is_required ? 1 : 0
                }))
            };

            if (currentFormId) payload.id = parseInt(currentFormId);

            try {
                const res = await fetch(API_BASE + 'save_form.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    if (!currentFormId) {
                        currentFormId = data.form_id;
                        document.getElementById('editorTitle').textContent = 'Edit Form';
                    }
                    history.replaceState(null, '', './?id=' + currentFormId);
                    showMessage('Form saved', 'success');
                    await loadForms();
                    renderFormList(allForms);
                } else {
                    showMessage('Error: ' + data.error, 'error');
                }
            } catch (e) {
                showMessage('Failed to save form', 'error');
            }
        }

        function showMessage(text, type) {
            const el = document.getElementById('saveMessage');
            el.textContent = text;
            el.className = 'save-message ' + type;
            if (type === 'success') {
                setTimeout(() => { el.style.display = 'none'; }, 3000);
            }
        }

        function hideSaveMessage() {
            const el = document.getElementById('saveMessage');
            el.className = 'save-message';
            el.style.display = 'none';
        }

        // ========== Delete ==========

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
                    // If we just deleted the form we're editing, go back to welcome
                    if (deleteFormId == currentFormId) {
                        cancelEdit();
                    }
                    await loadForms();
                    renderFormList(allForms);
                }
            } catch (e) {
                console.error(e);
            }
        });

        // ========== Utility ==========

        function esc(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
