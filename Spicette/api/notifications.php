<?php
// ENSURE NO CHARACTERS BEFORE THIS LINE
// ENSURE THIS FILE IS ENCODED AS UTF-8 WITHOUT BOM
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Include utility file

$notificationsFile = '../data/notifications.json';

// Helper functions using utilities
function readNotifications() {
    global $notificationsFile;
    return readJsonFile($notificationsFile);
}

function saveNotifications($notifications) {
    global $notificationsFile;
    return writeJsonFile($notificationsFile, $notifications);
}

function checkAdmin() {
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied: Administrator privileges required.']);
        exit;
    }
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if ($action === 'fetch_all') {
        echo json_encode(['success' => true, 'notifications' => readNotifications()]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid GET action.']);
    }
} elseif ($method === 'POST') {
    $input = getJsonInput(); // Use helper function for JSON input

    // Input may be null (no body) or false (invalid JSON)
    // If action is mark_as_read, null input is allowed.
    if ($input === false) { // Invalid JSON
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input format.']);
        exit;
    }

    if ($action === 'add') {
        checkAdmin();
        $text = $input['text'] ?? '';
        $link = $input['link'] ?? ''; // NEW: Get link for notification
        if (empty($text)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Notification text cannot be empty.']);
            exit;
        }
        $notifications = readNotifications();
        $newId = 'notif_' . uniqid() . '_' . time();
        $notifications[] = ['id' => $newId, 'text' => $text, 'link' => $link, 'timestamp' => date('Y-m-d H:i:s'), 'read' => false]; // NEW: Save link
        if (saveNotifications($notifications)) {
            echo json_encode(['success' => true, 'message' => 'Notification added successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save notification. Check data folder permissions.']);
        }
    } elseif ($action === 'delete') {
        checkAdmin();
        $idToDelete = $input['id'] ?? '';
        $notifications = readNotifications();
        $initialCount = count($notifications);
        $updatedNotifications = array_filter($notifications, function($notif) use ($idToDelete) {
            return $notif['id'] !== $idToDelete;
        });

        if (count($updatedNotifications) === $initialCount) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Notification not found.']);
            exit;
        }
        if (saveNotifications(array_values($updatedNotifications))) {
            echo json_encode(['success' => true, 'message' => 'Notification deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete notification. Check data folder permissions.']);
        }
    } elseif ($action === 'mark_as_read') {
        // For mark_as_read, JSON input is not required, so $input can be null
        $notifications = readNotifications();
        foreach ($notifications as &$notif) {
            $notif['read'] = true;
        }
        if (saveNotifications($notifications)) {
            echo json_encode(['success' => true, 'message' => 'All notifications marked as read.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid POST action.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
// NO CHARACTERS AFTER THIS LINE
?>
