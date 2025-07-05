<?php
// Pastikan tidak ada karakter apapun sebelum baris ini
header('Content-Type: application/json; charset=UTF-8');
session_start();

// Memastikan hanya admin yang bisa mengakses API ini
if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya administrator yang dapat melakukan tindakan ini.']);
    exit();
}

$usersFilePath = __DIR__ . '/../data/users.json';

// Memastikan metode request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $usernameToUpdate = $input['username'] ?? '';
    $newLevel = $input['level'] ?? '';

    if (empty($usernameToUpdate) || empty($newLevel)) {
        echo json_encode(['success' => false, 'message' => 'Nama pengguna dan level baru harus disediakan.']);
        exit();
    }

    // Validasi level yang diizinkan
    $allowedLevels = ['tempted', 'Naughty', 'sinful'];
    if (!in_array($newLevel, $allowedLevels)) {
        echo json_encode(['success' => false, 'message' => 'Level tidak valid. Level yang diizinkan adalah: ' . implode(', ', $allowedLevels)]);
        exit();
    }

    if (!file_exists($usersFilePath)) {
        echo json_encode(['success' => false, 'message' => 'File pengguna tidak ditemukan.']);
        exit();
    }

    $users = json_decode(file_get_contents($usersFilePath), true);
    $userFound = false;

    foreach ($users as &$user) {
        if ($user['username'] === $usernameToUpdate) {
            // Perbarui level pengguna
            $user['level'] = $newLevel;
            $userFound = true;
            break;
        }
    }
    unset($user); // Putuskan referensi terakhir ke $user

    if (!$userFound) {
        echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
        exit();
    }

    // Simpan kembali data pengguna yang diperbarui ke file JSON
    if (file_put_contents($usersFilePath, json_encode($users, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'message' => 'Level pengguna berhasil diperbarui.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pengguna.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
}
?>
