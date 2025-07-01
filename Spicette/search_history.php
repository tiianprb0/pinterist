<?php
session_start();
require_once 'utils.php';

header('Content-Type: application/json');

$searchHistoryDir = '../data/search_history/';
$maxHistory = 5;

// Determine user ID or use a guest identifier
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
$userHistoryFile = $searchHistoryDir . $userId . '.json';

// Create the directory if it doesn't exist
if (!is_dir($searchHistoryDir)) {
    mkdir($searchHistoryDir, 0775, true);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'fetch':
        $history = readJsonFile($userHistoryFile);
        sendJsonResponse(['success' => true, 'history' => $history]);
        break;

    case 'add':
        $input = getJsonInput();
        $query = $input['query'] ?? '';

        if (empty($query)) {
            sendJsonResponse(['success' => false, 'message' => 'Query pencarian tidak boleh kosong.'], 400);
            break;
        }

        $history = readJsonFile($userHistoryFile);

        // Remove existing entry if it matches the new query (case-insensitive)
        $history = array_values(array_filter($history, function($item) use ($query) {
            return strtolower($item) !== strtolower($query);
        }));

        // Add new query to the beginning
        array_unshift($history, $query);

        // Keep only the latest N entries
        if (count($history) > $maxHistory) {
            $history = array_slice($history, 0, $maxHistory);
        }

        if (writeJsonFile($userHistoryFile, $history)) {
            sendJsonResponse(['success' => true, 'message' => 'Riwayat pencarian berhasil ditambahkan.']);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Gagal menyimpan riwayat pencarian.'], 500);
        }
        break;

    case 'clear':
        // Check if the file exists and delete it
        if (file_exists($userHistoryFile)) {
            if (unlink($userHistoryFile)) {
                sendJsonResponse(['success' => true, 'message' => 'Riwayat pencarian berhasil dihapus.']);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Gagal menghapus riwayat pencarian.'], 500);
            }
        } else {
            sendJsonResponse(['success' => true, 'message' => 'Tidak ada riwayat pencarian untuk dihapus.']);
        }
        break;

    default:
        sendJsonResponse(['success' => false, 'message' => 'Aksi tidak valid.'], 400);
        break;
}

// sendJsonResponse function is assumed to be in utils.php, but included here for self-containment if not.
if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
?>
