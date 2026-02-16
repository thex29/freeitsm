<?php
/**
 * System - Preferences (per-browser settings)
 */
session_start();
require_once '../../config.php';

$current_page = 'preferences';
$path_prefix = '../../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk - Preferences</title>
    <link rel="stylesheet" href="../../assets/css/inbox.css">
    <script src="../../assets/js/toast.js"></script>
    <style>
        body { overflow: auto; height: auto; }

        .prefs-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 30px;
        }

        .prefs-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 30px;
        }

        .prefs-card h2 {
            margin: 0 0 6px 0;
            font-size: 20px;
            color: #333;
        }

        .prefs-card .subtitle {
            margin: 0 0 30px 0;
            font-size: 13px;
            color: #888;
        }

        .pref-section {
            margin-bottom: 32px;
        }

        .pref-section:last-child { margin-bottom: 0; }

        .pref-section h3 {
            margin: 0 0 6px 0;
            font-size: 15px;
            color: #333;
        }

        .pref-section p {
            margin: 0 0 16px 0;
            font-size: 13px;
            color: #666;
        }

        .position-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 4px;
            width: 192px;
            height: 128px;
            background: #f0f0f0;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 8px;
        }

        .position-cell {
            background: #e0e0e0;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .position-cell:hover { background: #d0d0d0; }
        .position-cell.active { background: #546e7a; }
        .position-cell.active .position-dot { background: #fff; }

        .position-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #bbb;
            transition: all 0.15s;
        }

        .anim-toggle {
            display: flex;
            gap: 0;
            border: 2px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            width: fit-content;
        }

        .anim-option {
            padding: 8px 20px;
            font-size: 13px;
            font-weight: 500;
            color: #666;
            background: #f5f5f5;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
        }

        .anim-option:not(:last-child) { border-right: 1px solid #ddd; }
        .anim-option:hover { background: #e8e8e8; }
        .anim-option.active { background: #546e7a; color: #fff; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="prefs-container">
        <div class="prefs-card">
            <h2>Preferences</h2>
            <p class="subtitle">Personal settings saved to this browser.</p>

            <div class="pref-section">
                <h3>Notification Position</h3>
                <p>Choose where notifications appear on your screen.</p>
                <div class="position-grid" id="toastPositionGrid"></div>
            </div>

            <div class="pref-section">
                <h3>Animation Style</h3>
                <p>How notifications enter and exit the screen.</p>
                <div class="anim-toggle" id="animToggle">
                    <button class="anim-option" data-anim="slide">Slide</button>
                    <button class="anim-option" data-anim="fade">Fade</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const positions = [
            { key: 'top-left', label: 'Top left' },
            { key: 'top-center', label: 'Top centre' },
            { key: 'top-right', label: 'Top right' },
            { key: 'middle-left', label: 'Middle left' },
            { key: 'middle-center', label: 'Middle centre' },
            { key: 'middle-right', label: 'Middle right' },
            { key: 'bottom-left', label: 'Bottom left' },
            { key: 'bottom-center', label: 'Bottom centre' },
            { key: 'bottom-right', label: 'Bottom right' }
        ];

        const grid = document.getElementById('toastPositionGrid');
        const current = localStorage.getItem('toast_position') || 'bottom-right';

        positions.forEach(pos => {
            const cell = document.createElement('div');
            cell.className = 'position-cell' + (pos.key === current ? ' active' : '');
            cell.title = pos.label;
            cell.dataset.pos = pos.key;

            const dot = document.createElement('div');
            dot.className = 'position-dot';
            cell.appendChild(dot);

            cell.addEventListener('click', function() {
                localStorage.setItem('toast_position', pos.key);
                grid.querySelectorAll('.position-cell').forEach(c => c.classList.remove('active'));
                cell.classList.add('active');
                showToast('Notifications will appear here', 'info');
            });

            grid.appendChild(cell);
        });

        // Animation toggle
        const currentAnim = localStorage.getItem('toast_animation') || 'slide';
        document.querySelectorAll('.anim-option').forEach(btn => {
            if (btn.dataset.anim === currentAnim) btn.classList.add('active');
            btn.addEventListener('click', function() {
                localStorage.setItem('toast_animation', btn.dataset.anim);
                document.querySelectorAll('.anim-option').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                showToast('Preview: ' + btn.dataset.anim + ' animation', 'info');
            });
        });
    </script>
</body>
</html>
