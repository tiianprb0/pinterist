<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
// PASTIKAN FILE INI DI-ENCODE SEBAGAI UTF-8 TANPA BOM
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$notificationsFile = '../data/notifications.json';

// Helper fungsi yang menggunakan utilitas
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
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Diperlukan hak akses administrator.']);
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
        echo json_encode(['success' => false, 'message' => 'Aksi GET tidak valid.']);
    }
} elseif ($method === 'POST') {
    $input = getJsonInput(); // Gunakan fungsi helper untuk input JSON

    // Input mungkin null (tidak ada body) atau false (JSON tidak valid)
    // Jika aksi adalah mark_as_read, input null diperbolehkan.
    if ($input === false) { // JSON tidak valid
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input format.']);
        exit;
    }

    if ($action === 'add') {
        checkAdmin();
        $text = $input['text'] ?? '';
        if (empty($text)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Teks notifikasi tidak boleh kosong.']);
            exit;
        }
        $notifications = readNotifications();
        $newId = 'notif_' . uniqid() . '_' . time();
        $notifications[] = ['id' => $newId, 'text' => $text, 'timestamp' => date('Y-m-d H:i:s'), 'read' => false];
        if (saveNotifications($notifications)) {
            echo json_encode(['success' => true, 'message' => 'Notifikasi berhasil ditambahkan.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan notifikasi. Periksa izin folder data.']);
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
            echo json_encode(['success' => false, 'message' => 'Notifikasi tidak ditemukan.']);
            exit;
        }
        if (saveNotifications(array_values($updatedNotifications))) {
            echo json_encode(['success' => true, 'message' => 'Notifikasi berhasil dihapus.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus notifikasi. Periksa izin folder data.']);
        }
    } elseif ($action === 'mark_as_read') {
        // Untuk mark_as_read, input JSON tidak diperlukan, jadi $input bisa null
        $notifications = readNotifications();
        foreach ($notifications as &$notif) {
            $notif['read'] = true;
        }
        if (saveNotifications($notifications)) {
            echo json_encode(['success' => true, 'message' => 'Semua notifikasi ditandai sebagai dibaca.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menandai notifikasi sebagai dibaca.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi POST tidak valid.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
// JANGAN ADA KARAKTER APAPUN SETELAH BARIS INI