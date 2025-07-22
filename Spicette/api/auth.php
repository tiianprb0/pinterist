<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
// PASTIKAN FILE INI DI-ENCODE SEBAGAI UTF-8 TANPA BOM
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$usersFile = '../data/users.json';

// Fungsi pembantu menggunakan utilitas
function getUsers() {
    global $usersFile;
    // Pastikan nilai default untuk 'canUpload' jika tidak ada di JSON
    $users = readJsonFile($usersFile);
    foreach ($users as &$user) { // Gunakan referensi untuk memodifikasi array asli
        if (!isset($user['canUpload'])) {
            $user['canUpload'] = false; // Nilai default jika tidak disetel
        }
        // Pastikan field preferensi ada, inisialisasi jika tidak
        if (!isset($user['preferred_categories'])) {
            $user['preferred_categories'] = [];
        }
        if (!isset($user['preferred_persons'])) {
            $user['preferred_persons'] = [];
        }
        if (!isset($user['manual_persons_requested'])) {
            $user['manual_persons_requested'] = [];
        }
        if (!isset($user['profile_image_url'])) {
            $user['profile_image_url'] = '';
        }
        // Pastikan 'level' ada
        if (!isset($user['level'])) {
            $user['level'] = 'tempted'; // Default level untuk pengguna baru atau yang tidak memiliki level
        }
        // Jika pengguna adalah admin, set level mereka menjadi 'Sinful'
        if (isset($user['isAdmin']) && $user['isAdmin']) {
            $user['level'] = 'Sinful';
        }
    }
    return $users;
}

function saveUsers($users) {
    global $usersFile;
    return writeJsonFile($usersFile, $users);
}

function checkAdminAuth() {
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Diperlukan hak akses administrator.']);
        exit;
    }
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = getJsonInput(); // Gunakan fungsi pembantu untuk input JSON

    // Input bisa berupa null (tanpa body) atau false (JSON tidak valid)
    // Jika aksi adalah logout, input null diperbolehkan.
    if ($input === false) { // JSON tidak valid
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Format input JSON tidak valid.']);
        exit;
    }

    // Aksi 'register' dan 'login' manual dihapus karena sekarang ditangani Firebase
    // $username = $input['username'] ?? '';
    // $password = $input['password'] ?? '';
    // $email = $input['email'] ?? ''; 

    if ($action === 'firebase_login') { // Aksi untuk login/registrasi Firebase (termasuk Google dan Email/Password)
        if ($input === null || !isset($input['email']) || empty($input['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data Firebase login (email) diperlukan.']);
            exit;
        }

        $firebaseEmail = $input['email'];
        $firebaseDisplayName = $input['displayName'] ?? ''; // Bisa null dari Firebase Email/Password
        $firebaseUid = $input['uid'];
        $firebasePhotoURL = $input['photoURL'] ?? ''; // Bisa null dari Firebase Email/Password

        $users = getUsers();
        $userFound = false;
        $loggedInUser = null;

        // Cari pengguna berdasarkan email Firebase
        foreach ($users as &$user) { // Gunakan referensi untuk memodifikasi
            if (isset($user['email']) && strtolower($user['email']) === strtolower($firebaseEmail)) {
                $userFound = true;
                $loggedInUser = &$user; // Simpan referensi ke pengguna yang ditemukan
                break;
            }
        }

        if ($userFound) {
            // Pengguna sudah ada di users.json
            // Perbarui photoURL jika kosong dan Firebase menyediakannya
            if (isset($loggedInUser['profile_image_url']) && empty($loggedInUser['profile_image_url']) && !empty($firebasePhotoURL)) {
                $loggedInUser['profile_image_url'] = $firebasePhotoURL;
                saveUsers($users); // Simpan perubahan
            }
            // Pastikan level diatur dengan benar, terutama jika admin
            if ($loggedInUser['isAdmin'] && $loggedInUser['level'] !== 'Sinful') {
                $loggedInUser['level'] = 'Sinful';
                saveUsers($users); // Simpan perubahan
            }
            // Set session untuk pengguna yang sudah ada
            $_SESSION['username'] = $loggedInUser['username'];
            $_SESSION['isAdmin'] = $loggedInUser['isAdmin'];
            $_SESSION['email'] = $loggedInUser['email'];
            $_SESSION['canUpload'] = $loggedInUser['canUpload'] ?? false;
            $_SESSION['user_level'] = $loggedInUser['level'] ?? 'tempted';

            echo json_encode([
                'success' => true, 
                'message' => 'Login Firebase berhasil.', 
                'user' => [
                    'username' => $loggedInUser['username'], 
                    'isAdmin' => $loggedInUser['isAdmin'], 
                    'email' => $loggedInUser['email'],
                    'canUpload' => $loggedInUser['canUpload'] ?? false,
                    'level' => $loggedInUser['level'] ?? 'tempted'
                ]
            ]);
        } else {
            // Pengguna baru dari Firebase, daftarkan mereka ke users.json
            // Gunakan display name dari Firebase atau username yang diberikan oleh user (untuk email/password)
            // Jika tidak ada display name, gunakan bagian email sebelum '@'
            $newUsername = $firebaseDisplayName; // Coba gunakan display name dari Firebase
            
            // Jika display name kosong atau tidak disediakan (misal: dari email/password),
            // coba ambil dari input username di form register, atau dari email.
            if (empty($newUsername)) {
                $newUsername = $input['username'] ?? explode('@', $firebaseEmail)[0];
            }
            
            // Pastikan username unik
            $originalUsername = $newUsername;
            $counter = 1;
            while (true) {
                $usernameExists = false;
                foreach ($users as $user) {
                    if (strtolower($user['username']) === strtolower($newUsername)) {
                        $usernameExists = true;
                        break;
                    }
                }
                if (!$usernameExists) {
                    break;
                }
                $newUsername = $originalUsername . $counter++;
            }

            $newUser = [
                'username' => $newUsername, 
                'email' => $firebaseEmail, 
                'password' => 'FIREBASE_AUTH_USER', // Placeholder, otentikasi ditangani Firebase
                'isAdmin' => false,
                'canUpload' => true, // Default: pengguna Firebase bisa mengunggah
                'preferred_categories' => [],
                'preferred_persons' => [],
                'manual_persons_requested' => [],
                'profile_image_url' => $firebasePhotoURL,
                'level' => 'tempted'
            ];
            $users[] = $newUser;

            if (saveUsers($users)) {
                $_SESSION['username'] = $newUser['username'];
                $_SESSION['isAdmin'] = $newUser['isAdmin'];
                $_SESSION['email'] = $newUser['email'];
                $_SESSION['canUpload'] = $newUser['canUpload'];
                $_SESSION['user_level'] = $newUser['level'];
                echo json_encode(['success' => true, 'message' => 'Pendaftaran Firebase berhasil.', 'user' => ['username' => $newUser['username'], 'email' => $newUser['email'], 'level' => $newUser['level']]]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pengguna Firebase baru. Periksa izin folder data.']);
            }
        }
    } elseif ($action === 'logout') {
        // Untuk logout, input JSON tidak diperlukan, jadi $input bisa berupa null
        session_unset();
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logout berhasil.']);
    } elseif ($action === 'update_user_permission') { // Aksi untuk memperbarui izin pengguna
        checkAdminAuth(); // Hanya admin yang bisa melakukan ini
        $targetUsername = $input['username'] ?? null;
        $newCanUploadStatus = $input['canUpload'] ?? null; // True/False
        $newLevel = $input['level'] ?? null; // BARU: Dapatkan level baru

        if (empty($targetUsername) || (!isset($newCanUploadStatus) && !isset($newLevel))) { // Perbarui kondisi
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama pengguna dan status canUpload baru atau level baru diperlukan.']);
            exit;
        }

        $users = getUsers();
        $userUpdated = false;
        foreach ($users as &$user) {
            if ($user['username'] === $targetUsername) {
                // Periksa apakah admin mencoba mengubah izin admin lain atau dirinya sendiri
                if ($user['isAdmin'] && $user['username'] !== $_SESSION['username']) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Tidak dapat mengubah izin administrator lain.']);
                    exit;
                }
                
                if (isset($newCanUploadStatus)) {
                    $user['canUpload'] = (bool)$newCanUploadStatus;
                }
                if (isset($newLevel)) { // BARU: Perbarui level jika disediakan
                    // Admin tidak bisa mengubah level mereka sendiri dari Sinful
                    if ($user['username'] === $_SESSION['username'] && $user['isAdmin'] && $newLevel !== 'Sinful') {
                         http_response_code(403);
                         echo json_encode(['success' => false, 'message' => 'Admin tidak dapat menurunkan level mereka sendiri dari Sinful.']);
                         exit;
                    }
                    $user['level'] = $newLevel;
                    // Jika level diubah menjadi tempted, pastikan canUpload false
                    if ($newLevel === 'tempted') {
                        $user['canUpload'] = false;
                    }
                }
                
                $userUpdated = true;
                break;
            }
        }

        if (!$userUpdated) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
            exit;
        }

        if (saveUsers($users)) {
            // Jika admin mengubah izin mereka sendiri, perbarui sesi mereka
            if ($targetUsername === $_SESSION['username']) {
                if (isset($newCanUploadStatus)) {
                    $_SESSION['canUpload'] = (bool)$newCanUploadStatus;
                }
                if (isset($newLevel)) {
                    $_SESSION['user_level'] = $newLevel;
                }
            }
            echo json_encode(['success' => true, 'message' => 'Izin pengguna berhasil diperbarui.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui izin pengguna. Periksa izin folder data.']);
        }
    } else {
        http_response_code(400); // Permintaan Buruk
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
    }

} elseif ($method === 'GET' && $action === 'check_session') {
    if (isset($_SESSION['username'])) {
        // Kembalikan status canUpload dan level dari sesi
        echo json_encode([
            'success' => true, 
            'user' => [
                'username' => $_SESSION['username'], 
                'isAdmin' => $_SESSION['isAdmin'], 
                'email' => $_SESSION['email'] ?? '',
                'canUpload' => $_SESSION['canUpload'] ?? false, // Ambil canUpload dari sesi
                'level' => $_SESSION['user_level'] ?? 'tempted' // Ambil level dari sesi
            ]
        ]); 
    } else {
        echo json_encode(['success' => false, 'message' => 'Tidak ada sesi aktif.']);
    }
} elseif ($method === 'GET' && $action === 'get_user_preferences') {
    $targetUsername = $_GET['username'] ?? null;
    if (empty($targetUsername)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nama pengguna diperlukan.']);
        exit;
    }
    $users = getUsers();
    $foundUser = null;
    foreach ($users as $user) {
        if ($user['username'] === $targetUsername) {
            $foundUser = $user;
            break;
        }
    }
    if ($foundUser) {
        echo json_encode([
            'success' => true,
            'preferences' => [
                'preferred_categories' => $foundUser['preferred_categories'] ?? [],
                'preferred_persons' => $foundUser['preferred_persons'] ?? [],
                'profile_image_url' => $foundUser['profile_image_url'] ?? ''
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
    }
} elseif ($method === 'GET' && $action === 'get_user_count') { // Action to get total user count
    try {
        checkAdminAuth(); // Pastikan hanya admin yang bisa mengakses
        $users = getUsers();
        echo json_encode(['success' => true, 'count' => count($users)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Kesalahan internal server saat menghitung pengguna: ' . $e->getMessage()]);
        error_log("ERROR: Exception in get_user_count: " . $e->getMessage());
    }
} else {
    http_response_code(405); // Metode Tidak Diizinkan
    echo json_encode(['success' => false, 'message' => 'Metode permintaan atau aksi tidak valid.']);
}
// JANGAN ADA KARAKTER APAPUN SETELAH BARIS INI
?>
