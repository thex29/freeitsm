<?php
/**
 * Calendar - View scheduled tickets
 */
session_start();
require_once '../config.php';

$current_page = 'calendar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Desk - Calendar</title>
    <link rel="stylesheet" href="../assets/css/inbox.css">
    <link rel="stylesheet" href="../assets/css/calendar.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="calendar-container">
        <div class="calendar-header">
            <button class="btn btn-secondary" onclick="changeMonth(-1)">&lt; Previous</button>
            <h2 id="calendarTitle"></h2>
            <button class="btn btn-secondary" onclick="changeMonth(1)">Next &gt;</button>
            <button class="btn btn-primary" onclick="goToToday()" style="margin-left: 20px;">Today</button>
        </div>

        <div class="calendar-weekdays">
            <div class="weekday">Monday</div>
            <div class="weekday">Tuesday</div>
            <div class="weekday">Wednesday</div>
            <div class="weekday">Thursday</div>
            <div class="weekday">Friday</div>
            <div class="weekday weekend">Saturday</div>
            <div class="weekday weekend">Sunday</div>
        </div>

        <div class="calendar-grid" id="calendarGrid">
            <!-- Calendar days will be rendered here -->
        </div>
    </div>

    <!-- Ticket Detail Modal -->
    <div class="modal" id="ticketModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <span id="ticketModalTitle">Ticket Details</span>
            </div>
            <div class="modal-body" id="ticketModalBody">
                <!-- Ticket details will be rendered here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeTicketModal()">Close</button>
                <a id="ticketModalLink" href="#" class="btn btn-primary">Open in Inbox</a>
            </div>
        </div>
    </div>

    <script>window.API_BASE = '../api/tickets/'; window.INBOX_URL = 'index.php';</script>
    <script src="../assets/js/calendar.js"></script>
</body>
</html>
