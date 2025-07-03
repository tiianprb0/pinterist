<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$usersFile = '../data/users.json';
$manualPersonRequestsFile = '../data/manual_person_requests.json';

// Fungsi untuk membaca dan menulis file JSON
function getUsers() {
    global $usersFile;
    return readJsonFile($usersFile);
}

function saveUsers($users) {
    global $usersFile;
    return writeJsonFile($usersFile, $users);
}

function getManualPersonRequests() {
    global $manualPersonRequestsFile;
    return readJsonFile($manualPersonRequestsFile);
}

function saveManualPersonRequests($requests) {
    global $manualPersonRequestsFile;
    return writeJsonFile($manualPersonRequestsFile, $requests);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();

    if ($input === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format input JSON tidak valid.']);
        exit;
    }

    $username = $input['username'] ?? null;
    $preferredCategories = $input['preferred_categories'] ?? [];
    $preferredPersons = $input['preferred_persons'] ?? [];
    $manualPersonsRequested = $input['manual_persons_requested'] ?? [];
    $profileImageUrl = $input['profile_image_url'] ?? '';

    // Validasi input dasar
    if (empty($username)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nama pengguna tidak ditemukan.']);
        exit;
    }
    if (count($preferredCategories) < 3) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Minimal 3 kategori favorit diperlukan.']);
        exit;
    }
    if (count($preferredPersons) < 3) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Minimal 3 orang yang dikagumi diperlukan.']);
        exit;
    }
    if (empty($profileImageUrl)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'URL gambar profil diperlukan.']);
        exit;
    }

    $users = getUsers();
    $userFound = false;

    foreach ($users as &$user) {
        if ($user['username'] === $username) {
            $user['preferred_categories'] = $preferredCategories;
            $user['preferred_persons'] = $preferredPersons;
            $user['manual_persons_requested'] = array_unique(array_merge($user['manual_persons_requested'], $manualPersonsRequested)); // Gabungkan dan hapus duplikat
            $user['profile_image_url'] = $profileImageUrl;
            $userFound = true;
            break;
        }
    }

    if (!$userFound) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
        exit;
    }

    if (saveUsers($users)) {
        // Log manual person requests for admin review
        if (!empty($manualPersonsRequested)) {
            $existingRequests = getManualPersonRequests();
            if (!is_array($existingRequests)) {
                $existingRequests = [];
            }
            foreach ($manualPersonsRequested as $personName) {
                $existingRequests[] = [
                    'username' => $username,
                    'person_name' => $personName,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            saveManualPersonRequests($existingRequests);
        }
        echo json_encode(['success' => true, 'message' => 'Preferensi berhasil disimpan.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan preferensi pengguna. Periksa izin folder data.']);
    }

} else {
    http_response_code(405); // Metode Tidak Diizinkan
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
