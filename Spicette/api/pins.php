<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
// PASTIKAN FILE INI DI-ENCODE SEBAGAI UTF-8 TANPA BOM
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$pinsFile = '../data/pins.json';
$usersFile = '../data/users.json'; // Path to users.json (for delete_user action)
$savedPinsDir = '../data/saved_pins/';

// Helper fungsi yang menggunakan utilitas
function getAllPins() {
    global $pinsFile;
    return readJsonFile($pinsFile);
}

function saveAllPins($pins) {
    global $pinsFile;
    return writeJsonFile($pinsFile, $pins);
}

function getUsersForPinsApi() { // Used in delete_user
    global $usersFile;
    return readJsonFile($usersFile);
}
function saveUsersForPinsApi($users) { // Used in delete_user
    global $usersFile;
    return writeJsonFile($usersFile, $users);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

function checkAdmin() {
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Diperlukan hak akses administrator.']);
        exit;
    }
}

function checkLoggedIn() {
    if (!isset($_SESSION['username'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Login diperlukan.']);
        exit;
    }
}

if ($method === 'GET') {
    if ($action === 'fetch_all') {
        echo json_encode(['success' => true, 'pins' => getAllPins()]);
    } elseif ($action === 'fetch_saved') {
        checkLoggedIn();
        $username = $_SESSION['username'];
        $allPins = getAllPins();
        // Pastikan file saved_pins/[username].json ada, jika tidak, inisialisasi dengan array kosong
        $userSavedPinsFile = $savedPinsDir . $username . '.json';
        if (!file_exists($userSavedPinsFile)) {
            // Buat file jika belum ada dengan array kosong
            writeJsonFile($userSavedPinsFile, []);
        }
        $savedPinIds = readJsonFile($userSavedPinsFile);
        
        $savedPins = array_filter($allPins, function($pin) use ($savedPinIds) {
            return in_array($pin['id'], $savedPinIds);
        });
        echo json_encode(['success' => true, 'pins' => array_values($savedPins)]);
    } elseif ($action === 'fetch_all_users') {
        checkAdmin();
        echo json_encode(['success' => true, 'users' => getUsersForPinsApi()]);
    } elseif ($action === 'fetch_user_pins') {
        $username = $_GET['username'] ?? ($_SESSION['username'] ?? null);
        if (empty($username)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username diperlukan untuk mengambil pin pengguna.']);
            exit;
        }
        $allPins = getAllPins();
        $userPins = array_filter($allPins, function($pin) use ($username) {
            return ($pin['uploadedBy'] ?? 'system') === $username;
        });
        echo json_encode(['success' => true, 'pins' => array_values($userPins)]);
    } elseif ($action === 'search') {
        $query = $_GET['query'] ?? '';
        $allPins = getAllPins();
        $filteredPins = array_filter($allPins, function($pin) use ($query) {
            $queryLower = strtolower($query);
            // Search in title, source, content, and categories
            $inCategories = false;
            if (isset($pin['categories']) && is_array($pin['categories'])) {
                foreach ($pin['categories'] as $category) {
                    if (stripos($category, $queryLower) !== false) {
                        $inCategories = true;
                        break;
                    }
                }
            }
            return (stripos($pin['title'] ?? '', $queryLower) !== false ||
                    stripos($pin['source'] ?? '', $queryLower) !== false ||
                    stripos($pin['content'] ?? '', $queryLower) !== false ||
                    $inCategories);
        });
        echo json_encode(['success' => true, 'pins' => array_values($filteredPins)]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi GET tidak valid.']);
    }
} elseif ($method === 'POST') {
    $input = getJsonInput(); // Gunakan fungsi helper untuk input JSON

    if ($input === false) { // JSON tidak valid
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input format.']);
        exit;
    }

    if ($action === 'add') {
        checkLoggedIn();
        $imageUrl = $input['imageUrl'] ?? '';
        $source = $input['source'] ?? '';
        $title = $input['title'] ?? ''; 
        $content = $input['content'] ?? ''; 
        $categories = $input['categories'] ?? []; // New: Get categories

        if (empty($imageUrl) || empty($title)) { // Source and Content are now optional
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'URL gambar dan judul diperlukan.']);
            exit;
        }

        // Validate categories
        if (!is_array($categories)) {
            // Jika kategori datang sebagai string (misal dari form input dipisahkan koma), ubah menjadi array
            if (is_string($categories)) {
                $categories = array_map('trim', explode(',', $categories));
            } else {
                $categories = []; // Default to empty array if not array or string
            }
        }
        $categories = array_slice(array_filter(array_map('trim', $categories)), 0, 3); // Max 3 categories, trimmed, filter empty strings

        $pins = getAllPins();
        $newId = 'pin_' . uniqid() . '_' . time();

        $newPin = [
            'id' => $newId,
            'img' => $imageUrl,
            'source' => $source, // Source is optional
            'title' => $title, 
            'content' => $content, // Content is optional
            'categories' => $categories, // New: Save categories
            'uploadedBy' => $_SESSION['username'] ?? 'unknown'
        ];
        $pins[] = $newPin;
        if (saveAllPins($pins)) {
            echo json_encode(['success' => true, 'message' => 'Pin berhasil ditambahkan.', 'pin' => $newPin]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pin. Periksa izin folder data.']);
        }

    } elseif ($action === 'delete_pin') {
        checkAdmin();
        $pinId = $input['pinId'] ?? null;

        if (empty($pinId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk penghapusan.']);
            exit;
        }

        $pins = getAllPins();
        $initialCount = count($pins);
        $updatedPins = array_filter($pins, function($pin) use ($pinId) {
            return $pin['id'] !== $pinId;
        });

        if (count($updatedPins) === $initialCount) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pin tidak ditemukan.']);
            exit;
        }

        if (!saveAllPins(array_values($updatedPins))) {
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Gagal menghapus pin. Periksa izin folder data.']);
             exit;
        }

        $users = getUsersForPinsApi();
        foreach ($users as $user) {
            $userSavedPinsFile = $savedPinsDir . $user['username'] . '.json';
            if (file_exists($userSavedPinsFile)) { // Periksa keberadaan file sebelum membaca
                $savedPins = readJsonFile($userSavedPinsFile);
                $updatedSavedPins = array_filter($savedPins, function($id) use ($pinId) {
                    return $id !== $pinId;
                });
                // Penting: Gunakan array_values untuk mengindeks ulang array setelah filter
                writeJsonFile($userSavedPinsFile, array_values($updatedSavedPins));
            }
        }

        echo json_encode(['success' => true, 'message' => 'Pin berhasil dihapus.']);

    } elseif ($action === 'delete_user') {
        checkAdmin();
        $usernameToDelete = $input['username'] ?? null;

        if (empty($usernameToDelete)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username diperlukan untuk penghapusan.']);
            exit;
        }

        $users = getUsersForPinsApi();
        $initialUserCount = count($users);
        $updatedUsers = array_filter($users, function($user) use ($usernameToDelete) {
            return $user['username'] !== $usernameToDelete;
        });

        if (count($updatedUsers) === $initialUserCount) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
            exit;
        }
        
        if (isset($_SESSION['username']) && $usernameToDelete === $_SESSION['username']) {
             http_response_code(403);
             echo json_encode(['success' => false, 'message' => 'Anda tidak bisa menghapus akun Anda sendiri.']);
             exit;
        }

        if (!saveUsersForPinsApi(array_values($updatedUsers))) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus pengguna. Periksa izin folder data.']);
            exit;
        }

        $userSavedPinsFile = $savedPinsDir . $usernameToDelete . '.json';
        if (file_exists($userSavedPinsFile)) {
            unlink($userSavedPinsFile);
        }

        echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dihapus.']);

    } elseif ($action === 'save') {
        checkLoggedIn();
        $username = $_SESSION['username'];
        $pinId = $input['pinId'] ?? null;

        if (empty($pinId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk menyimpan.']);
            exit;
        }

        $userSavedPinsFile = $savedPinsDir . $username . '.json';
        // Pastikan file ada, jika tidak, buat dengan array kosong
        if (!file_exists($userSavedPinsFile)) {
            writeJsonFile($userSavedPinsFile, []);
        }

        $savedPins = readJsonFile($userSavedPinsFile);
        if (!in_array($pinId, $savedPins)) {
            $savedPins[] = $pinId;
            if (writeJsonFile($userSavedPinsFile, $savedPins)) {
                echo json_encode(['success' => true, 'message' => 'Pin berhasil disimpan.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pin. Periksa izin folder data.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Pin sudah disimpan.']);
        }
    } elseif ($action === 'unsave') { // NEW: Unsave action
        checkLoggedIn();
        $username = $_SESSION['username'];
        $pinId = $input['pinId'] ?? null;

        if (empty($pinId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk menghapus dari daftar simpan.']);
            exit;
        }

        $userSavedPinsFile = $savedPinsDir . $username . '.json';
        if (!file_exists($userSavedPinsFile)) {
            http_response_code(404); // Jika file saved_pins tidak ada, berarti tidak ada pin yang disimpan
            echo json_encode(['success' => false, 'message' => 'Tidak ada pin yang disimpan untuk pengguna ini.']);
            exit;
        }

        $savedPins = readJsonFile($userSavedPinsFile);
        $initialCount = count($savedPins);
        $updatedSavedPins = array_filter($savedPins, function($id) use ($pinId) {
            return $id !== $pinId;
        });

        // Jika jumlah elemen tidak berubah, berarti pin tidak ditemukan
        if (count($updatedSavedPins) === $initialCount) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pin tidak ditemukan di daftar simpan Anda.']);
            exit;
        }
        
        // Penting: Gunakan array_values untuk mengindeks ulang array setelah filter
        if (writeJsonFile($userSavedPinsFile, array_values($updatedSavedPins))) {
            echo json_encode(['success' => true, 'message' => 'Pin berhasil dihapus dari daftar simpan.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus pin dari daftar simpan. Periksa izin folder data.']);
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
?>