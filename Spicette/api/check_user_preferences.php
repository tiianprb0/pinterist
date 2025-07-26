<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$usersFile = '../data/users.json';

function getUsers() {
    global $usersFile;
    return readJsonFile($usersFile);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['success' => true, 'isLoggedIn' => false, 'preferencesSet' => false]);
        exit;
    }

    $username = $_SESSION['username'];
    $users = getUsers();
    $preferencesSet = false;
    $profileImageUrl = '';

    foreach ($users as $user) {
        if ($user['username'] === $username) {
            // Check if preferences are set (at least 3 categories, 3 persons, and a profile image)
            if (isset($user['preferred_categories']) && count($user['preferred_categories']) >= 3 &&
                isset($user['preferred_persons']) && count($user['preferred_persons']) >= 3 &&
                isset($user['profile_image_url']) && !empty($user['profile_image_url'])) {
                $preferencesSet = true;
            }
            $profileImageUrl = $user['profile_image_url'] ?? '';
            break;
        }
    }

    echo json_encode([
        'success' => true,
        'isLoggedIn' => true,
        'preferencesSet' => $preferencesSet,
        'username' => $username,
        'profile_image_url' => $profileImageUrl // Return profile image URL for potential use
    ]);

} else {
    http_response_code(405); // Metode Tidak Diizinkan
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
