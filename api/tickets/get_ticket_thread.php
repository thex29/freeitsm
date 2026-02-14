<?php
/**
 * API Endpoint: Get all emails for a ticket (for building reply thread)
 * Returns emails ordered by received_datetime ASC
 */
session_start();
require_once '../../config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['analyst_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$ticketId = $_GET['ticket_id'] ?? null;

if (!$ticketId) {
    echo json_encode(['success' => false, 'error' => 'Ticket ID is required']);
    exit;
}

try {
    $conn = connectToDatabase();

    $sql = "SELECT id, from_address, from_name, to_recipients, received_datetime,
                   body_content, direction
            FROM emails
            WHERE ticket_id = ?
            ORDER BY received_datetime ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticketId]);
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($emails as &$email) {
        if ($email['body_content']) {
            $email['body_content'] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $email['body_content']);
            $email['body_content'] = str_replace("\xEF\xBF\xBD", '', $email['body_content']);
            // Strip quoted thread content so each email only shows its own content
            $email['body_content'] = stripQuotedThread($email['body_content']);
        }
        if ($email['received_datetime']) {
            $email['received_datetime'] = date('Y-m-d\TH:i:s', strtotime($email['received_datetime']));
        }
    }

    echo json_encode(['success' => true, 'emails' => $emails]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Strip quoted/nested thread content from an email body
 * so each email in the thread only shows its own new content
 */
function stripQuotedThread($body) {
    // 1. Our reply marker div: <div data-reply-marker="true">...</div>
    $divPattern = '/<div[^>]*data-reply-marker="true"[^>]*>.*?<\/div>/is';
    if (preg_match($divPattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
        $stripped = trim(substr($body, 0, $matches[0][1]));
        if (!empty($stripped)) return $stripped;
    }

    // 2. Our raw marker text: [*** SDREF:XXX-XXX-XXXXX REPLY ABOVE THIS LINE ***]
    $markerPattern = '/\[\*{3}\s*SDREF:[A-Z]{3}-\d{3}-\d{5}\s*REPLY ABOVE THIS LINE\s*\*{3}\]/i';
    if (preg_match($markerPattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
        $stripped = trim(substr($body, 0, $matches[0][1]));
        if (!empty($stripped)) return $stripped;
    }

    // 3. Gmail: <div class="gmail_quote"> or <div class="gmail_attr">
    if (preg_match('/<div[^>]+class="[^"]*gmail_(quote|attr)[^"]*"[^>]*>/i', $body, $matches, PREG_OFFSET_CAPTURE)) {
        $stripped = trim(substr($body, 0, $matches[0][1]));
        if (!empty($stripped)) return $stripped;
    }

    // 4. Outlook-style separator: <div id="appendonsend">
    if (preg_match('/<div[^>]*id="appendonsend"[^>]*>/i', $body, $matches, PREG_OFFSET_CAPTURE)) {
        $stripped = trim(substr($body, 0, $matches[0][1]));
        if (!empty($stripped)) return $stripped;
    }

    // 5. Outlook "From:" / "Sent:" header block after <hr>
    if (preg_match('/<hr[^>]*>\s*(<(div|p|span)[^>]*>)?\s*<b>\s*From:\s*<\/b>/is', $body, $matches, PREG_OFFSET_CAPTURE)) {
        $stripped = trim(substr($body, 0, $matches[0][1]));
        if (!empty($stripped)) return $stripped;
    }

    // 6. Generic "On ... wrote:" pattern (allows HTML tags like <a> inside, and <br> before close)
    if (preg_match('/(<div[^>]*>)\s*On\s+[\s\S]{10,300}?\s+wrote:\s*(<br\s*\/?>)?\s*<\/div>/i', $body, $matches, PREG_OFFSET_CAPTURE)) {
        $stripped = trim(substr($body, 0, $matches[0][1]));
        if (!empty($stripped)) return $stripped;
    }

    // 7. Blockquote elements (quoted replies, only if there's content before it)
    if (preg_match('/<blockquote[^>]*>/i', $body, $matches, PREG_OFFSET_CAPTURE)) {
        $before = trim(substr($body, 0, $matches[0][1]));
        if (!empty($before)) return $before;
    }

    return $body;
}
