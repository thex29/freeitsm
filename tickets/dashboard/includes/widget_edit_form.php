<!-- Widget Edit Form (shared partial) — used by library.php and index.php -->
<input type="hidden" id="editId">
<div class="edit-form">
    <div class="form-group">
        <label>Title</label>
        <input type="text" id="editTitle" maxlength="100" placeholder="e.g. Tickets by status">
    </div>
    <div class="form-group">
        <label>Chart type</label>
        <select id="editChartType">
            <option value="bar">Bar</option>
            <option value="doughnut">Doughnut</option>
            <option value="pie">Pie</option>
            <option value="line">Line</option>
        </select>
    </div>
    <div class="form-group full-width">
        <label>Description</label>
        <textarea id="editDescription" maxlength="255" placeholder="Brief description of what this widget shows"></textarea>
    </div>
    <div class="form-group">
        <label>Aggregate property</label>
        <select id="editProperty">
            <optgroup label="Categorical">
                <option value="status">Status</option>
                <option value="priority">Priority</option>
                <option value="department">Department</option>
                <option value="ticket_type">Ticket type</option>
                <option value="analyst">Assigned analyst</option>
                <option value="owner">Owner</option>
                <option value="origin">Origin</option>
                <option value="first_time_fix">First time fix</option>
                <option value="training_provided">Training provided</option>
            </optgroup>
            <optgroup label="Time series">
                <option value="created">Created</option>
                <option value="closed">Closed</option>
            </optgroup>
            <optgroup label="Comparison">
                <option value="created_vs_closed">Created vs closed</option>
            </optgroup>
        </select>
    </div>
    <div class="form-group" id="timeGroupingGroup" style="display:none;">
        <label>Time grouping</label>
        <select id="editTimeGrouping">
            <option value="day">Day</option>
            <option value="month">Month</option>
            <option value="year">Year</option>
        </select>
    </div>
    <div class="form-group" id="seriesGroup">
        <label>Series breakdown</label>
        <select id="editSeries">
            <option value="">None (single series)</option>
            <option value="status">By status</option>
            <option value="priority">By priority</option>
        </select>
    </div>
    <div class="form-group">
        <label>Date range</label>
        <select id="editDateRange">
            <option value="">All time</option>
            <option value="7d">Last 7 days</option>
            <option value="30d">Last 30 days</option>
            <option value="this_month">This month</option>
            <option value="3m">Last 3 months</option>
            <option value="6m">Last 6 months</option>
            <option value="12m">Last 12 months</option>
            <option value="this_year">This year</option>
        </select>
    </div>
    <div class="form-group full-width" id="deptFilterGroup">
        <label>Department filter</label>
        <div id="deptCheckboxes" style="display:flex;flex-wrap:wrap;gap:8px;padding:4px 0;"></div>
        <div style="font-size:11px;color:#888;margin-top:4px;">Leave all unchecked to include all departments</div>
    </div>
    <div class="form-group checkbox-group" id="filterableGroup">
        <input type="checkbox" id="editFilterable" checked>
        <label for="editFilterable">Allow status filtering</label>
    </div>
</div>
