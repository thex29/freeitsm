<?php
/**
 * Shared Waffle Menu Component
 * Cross-module navigation menu for ITSM system
 *
 * Required variables before including:
 *   $path_prefix - Path to root (e.g., '../' or '../../')
 *   $current_module - Current module identifier (tickets, assets, knowledge, changes, calendar, morning-checks, reporting)
 *
 * Optional variables:
 *   $analyst_name - User's display name (defaults to 'Analyst')
 */

$path_prefix = $path_prefix ?? '../';
$current_module = $current_module ?? '';
$analyst_name = $analyst_name ?? ($_SESSION['analyst_name'] ?? 'Analyst');

// Module definitions - add new modules here
$modules = [
    'tickets' => [
        'name' => 'Tickets',
        'path' => 'tickets/',
        'icon' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>'
    ],
    'assets' => [
        'name' => 'Assets',
        'path' => 'asset-management/',
        'icon' => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>'
    ],
    'knowledge' => [
        'name' => 'Knowledge',
        'path' => 'knowledge/',
        'icon' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>'
    ],
    'changes' => [
        'name' => 'Changes',
        'path' => 'change-management/',
        'icon' => '<polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line><line x1="4" y1="4" x2="9" y2="9"></line>'
    ],
    'calendar' => [
        'name' => 'Calendar',
        'path' => 'calendar/',
        'icon' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>'
    ],
    'morning-checks' => [
        'name' => 'Checks',
        'path' => 'morning-checks/',
        'icon' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
    ],
    'reporting' => [
        'name' => 'Reporting',
        'path' => 'reporting/',
        'icon' => '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>'
    ],
    'software' => [
        'name' => 'Software',
        'path' => 'software/',
        'icon' => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line>'
    ],
    'forms' => [
        'name' => 'Forms',
        'path' => 'forms/',
        'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>'
    ],
    'wiki' => [
        'name' => 'Wiki',
        'path' => 'system-wiki/',
        'icon' => '<circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>'
    ],
    'system' => [
        'name' => 'System',
        'path' => 'system/',
        'icon' => '<line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line>'
    ]
];
?>
<style>
    /* Waffle Menu Styles */
    .waffle-menu-container {
        position: relative;
        display: flex;
        align-items: center;
    }

    .waffle-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.15s;
        margin-right: 15px;
    }

    .waffle-btn:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .waffle-icon {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 3px;
        width: 18px;
        height: 18px;
    }

    .waffle-icon span {
        width: 4px;
        height: 4px;
        background-color: #fff;
        border-radius: 50%;
    }

    .waffle-panel {
        position: absolute;
        top: 100%;
        left: 0;
        margin-top: 8px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 6px 30px rgba(0, 0, 0, 0.25);
        padding: 20px;
        min-width: 280px;
        z-index: 1000;
        display: none;
    }

    .waffle-panel.active {
        display: block;
    }

    .waffle-panel-header {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .waffle-modules {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .waffle-module-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px 10px;
        border-radius: 8px;
        text-decoration: none;
        color: #333;
        transition: background-color 0.15s;
    }

    .waffle-module-link:hover {
        background-color: #f5f5f5;
    }

    .waffle-module-link.current {
        background-color: #e8f4fd;
    }

    .waffle-module-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
    }

    .waffle-module-icon svg {
        width: 24px;
        height: 24px;
        color: #fff;
    }

    .waffle-module-icon.tickets { background: linear-gradient(135deg, #0078d4, #106ebe); }
    .waffle-module-icon.assets { background: linear-gradient(135deg, #107c10, #0b5c0b); }
    .waffle-module-icon.knowledge { background: linear-gradient(135deg, #8764b8, #6b4fa2); }
    .waffle-module-icon.changes { background: linear-gradient(135deg, #00897b, #00695c); }
    .waffle-module-icon.calendar { background: linear-gradient(135deg, #ef6c00, #e65100); }
    .waffle-module-icon.morning-checks { background: linear-gradient(135deg, #00acc1, #00838f); }
    .waffle-module-icon.reporting { background: linear-gradient(135deg, #ca5010, #a5410a); }
    .waffle-module-icon.software { background: linear-gradient(135deg, #5c6bc0, #3f51b5); }
    .waffle-module-icon.forms { background: linear-gradient(135deg, #00897b, #00695c); }
    .waffle-module-icon.wiki { background: linear-gradient(135deg, #c62828, #b71c1c); }
    .waffle-module-icon.system { background: linear-gradient(135deg, #546e7a, #37474f); }

    .waffle-module-name {
        font-size: 12px;
        font-weight: 500;
        text-align: center;
    }

    /* Overlay to close waffle menu when clicking outside */
    .waffle-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 999;
        display: none;
    }

    .waffle-overlay.active {
        display: block;
    }

    /* Module title in header */
    .module-title {
        font-size: 14px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
        margin-right: 20px;
        padding: 6px 12px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    /* Module header colors */
    .header.tickets-header { background: linear-gradient(135deg, #0078d4, #106ebe); }
    .header.assets-header { background: linear-gradient(135deg, #107c10, #0b5c0b); }
    .header.knowledge-header { background: linear-gradient(135deg, #8764b8, #6b4fa2); }
    .header.changes-header { background: linear-gradient(135deg, #00897b, #00695c); }
    .header.calendar-header { background: linear-gradient(135deg, #ef6c00, #e65100); }
    .header.morning-checks-header { background: linear-gradient(135deg, #00acc1, #00838f); }
    .header.reporting-header { background: linear-gradient(135deg, #ca5010, #a5410a); }
    .header.software-header { background: linear-gradient(135deg, #5c6bc0, #3f51b5); }
    .header.forms-header { background: linear-gradient(135deg, #00897b, #00695c); }
    .header.wiki-header { background: linear-gradient(135deg, #c62828, #b71c1c); }
    .header.system-header { background: linear-gradient(135deg, #546e7a, #37474f); }
</style>

<div class="waffle-overlay" id="waffleOverlay" onclick="closeWaffleMenu()"></div>

<!-- Waffle Menu Button and Panel - to be placed inside .waffle-menu-container -->
<?php
/**
 * Output the waffle menu button and panel
 */
function renderWaffleMenuButton() {
    ?>
    <button class="waffle-btn" onclick="toggleWaffleMenu()" title="ITSM Modules">
        <div class="waffle-icon">
            <span></span><span></span><span></span>
            <span></span><span></span><span></span>
            <span></span><span></span><span></span>
        </div>
    </button>
    <?php
}

function renderWaffleMenuPanel($modules, $current_module, $path_prefix) {
    $allowed = $_SESSION['allowed_modules'] ?? null;
    ?>
    <div class="waffle-panel" id="wafflePanel">
        <div class="waffle-panel-header">ITSM Modules</div>
        <div class="waffle-modules">
            <?php foreach ($modules as $key => $module):
                if ($allowed !== null && !in_array($key, $allowed)) continue;
            ?>
            <a href="<?php echo $path_prefix . $module['path']; ?>" class="waffle-module-link <?php echo $key === $current_module ? 'current' : ''; ?>">
                <div class="waffle-module-icon <?php echo $key; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <?php echo $module['icon']; ?>
                    </svg>
                </div>
                <span class="waffle-module-name"><?php echo $module['name']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function renderWaffleMenuJS() {
    ?>
    <script>
    function toggleWaffleMenu() {
        const panel = document.getElementById('wafflePanel');
        const overlay = document.getElementById('waffleOverlay');
        const isActive = panel.classList.contains('active');

        if (isActive) {
            closeWaffleMenu();
        } else {
            panel.classList.add('active');
            overlay.classList.add('active');
        }
    }

    function closeWaffleMenu() {
        document.getElementById('wafflePanel').classList.remove('active');
        document.getElementById('waffleOverlay').classList.remove('active');
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeWaffleMenu();
        }
    });
    </script>
    <?php
}

function renderHeaderRight($analyst_name, $path_prefix) {
    ?>
    <div class="header-right">
        <div class="user-info">Welcome, <?php echo htmlspecialchars($analyst_name); ?></div>
        <button class="nav-btn logout" onclick="if(confirm('Are you sure you want to logout?')) window.location.href='<?php echo $path_prefix; ?>analyst_logout.php';" title="Logout">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Logout</span>
        </button>
    </div>
    <?php
}
?>
