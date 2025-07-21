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

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $email = $input['email'] ?? ''; // Dapatkan email dari input

    if ($action === 'register') {
        if ($input === null) { // Pastikan input ada untuk pendaftaran
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data pendaftaran diperlukan.']);
            exit;
        }

        $users = getUsers();
        if (empty($username) || empty($email) || empty($password)) { // Periksa email
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama pengguna, email, dan kata sandi diperlukan.']);
            exit;
        }
        
        foreach ($users as $user) {
            if (strtolower($user['username']) === strtolower($username)) {
                http_response_code(409); // Konflik
                echo json_encode(['success' => false, 'message' => 'Nama pengguna sudah ada. Harap pilih yang lain.']);
                exit;
            }
            // Periksa email duplikat
            if (isset($user['email']) && strtolower($user['email']) === strtolower($email)) {
                http_response_code(409); // Konflik
                echo json_encode(['success' => false, 'message' => 'Alamat email sudah terdaftar.']);
                exit;
            }
        }

        // Sertakan email dan canUpload default dalam data pengguna baru
        $newUser = [
            'username' => $username, 
            'email' => $email, 
            'password' => $password, 
            'isAdmin' => false,
            'canUpload' => true, // Default: pengguna baru bisa mengunggah
            'preferred_categories' => [], // Inisialisasi array kosong
            'preferred_persons' => [],     // Inisialisasi array kosong
            'manual_persons_requested' => [], // Inisialisasi array kosong
            'profile_image_url' => '',      // Inisialisasi string kosong
            'level' => 'tempted' // Default level untuk pengguna baru
        ];
        $users[] = $newUser;

        if (saveUsers($users)) {
            // Set session for the newly registered user immediately
            $_SESSION['username'] = $newUser['username'];
            $_SESSION['isAdmin'] = $newUser['isAdmin'];
            $_SESSION['email'] = $newUser['email'];
            $_SESSION['canUpload'] = $newUser['canUpload'];
            $_SESSION['user_level'] = $newUser['level']; // Simpan level di sesi
            echo json_encode(['success' => true, 'message' => 'Pendaftaran berhasil.', 'user' => ['username' => $newUser['username'], 'email' => $newUser['email'], 'level' => $newUser['level']]]);
        } else {
            http_response_code(500); // Kesalahan Server Internal
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pengguna. Periksa izin folder data.']);
        }

    } elseif ($action === 'login') {
        if ($input === null) { // Pastikan input ada untuk login
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data login diperlukan.']);
            exit;
        }

        $users = getUsers();
        $userFound = false;
        $isAdmin = false;
        $loggedInUsername = '';
        $loggedInEmail = ''; 
        $canUpload = false; // Inisialisasi canUpload
        $userLevel = 'tempted'; // Inisialisasi level

        foreach ($users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                $userFound = true;
                $isAdmin = $user['isAdmin'] ?? false;
                $loggedInUsername = $user['username'];
                $loggedInEmail = $user['email'] ?? ''; 
                $canUpload = $user['canUpload'] ?? false; // Dapatkan status canUpload
                $userLevel = $user['level'] ?? 'tempted'; // Dapatkan level

                // Jika pengguna adalah admin, paksa level menjadi 'Sinful'
                if ($isAdmin) {
                    $userLevel = 'Sinful';
                }
                break;
            }
        }

        if ($userFound) {
            $_SESSION['username'] = $loggedInUsername;
            $_SESSION['isAdmin'] = $isAdmin;
            $_SESSION['email'] = $loggedInEmail; 
            $_SESSION['canUpload'] = $canUpload; // Simpan canUpload di sesi
            $_SESSION['user_level'] = $userLevel; // Simpan level di sesi
            echo json_encode([
                'success' => true, 
                'message' => 'Login berhasil.', 
                'user' => [
                    'username' => $loggedInUsername, 
                    'isAdmin' => $isAdmin, 
                    'email' => $loggedInEmail,
                    'canUpload' => $canUpload, // Kembalikan canUpload dalam data pengguna
                    'level' => $userLevel // Kembalikan level
                ]
            ]); 
        } else {
            http_response_code(401); // Tidak Sah
            echo json_encode(['success' => false, 'message' => 'Nama pengguna atau kata sandi salah.']);
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
