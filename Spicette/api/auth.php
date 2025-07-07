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
            $user['level'] = 'Pengguna Biasa'; // Default level
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
    $email = $input['email'] ?? ''; // BARU: Dapatkan email dari input

    if ($action === 'register') {
        if ($input === null) { // Pastikan input ada untuk pendaftaran
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data pendaftaran diperlukan.']);
            exit;
        }

        $users = getUsers();
        if (empty($username) || empty($email) || empty($password)) { // BARU: Periksa email
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
            // BARU: Periksa email duplikat
            if (isset($user['email']) && strtolower($user['email']) === strtolower($email)) {
                http_response_code(409); // Konflik
                echo json_encode(['success' => false, 'message' => 'Alamat email sudah terdaftar.']);
                exit;
            }
        }

        // BARU: Sertakan email dan canUpload default dalam data pengguna baru
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
            'level' => 'Pengguna Biasa' // Default level untuk pengguna baru
        ];
        $users[] = $newUser;

        if (saveUsers($users)) {
            // Set session for the newly registered user immediately
            $_SESSION['username'] = $newUser['username'];
            $_SESSION['isAdmin'] = $newUser['isAdmin'];
            $_SESSION['email'] = $newUser['email'];
            $_SESSION['canUpload'] = $newUser['canUpload'];
            echo json_encode(['success' => true, 'message' => 'Pendaftaran berhasil.', 'user' => ['username' => $newUser['username'], 'email' => $newUser['email']]]);
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
        $canUpload = false; // BARU: Inisialisasi canUpload
        $userLevel = 'Pengguna Biasa'; // Inisialisasi level

        foreach ($users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                $userFound = true;
                $isAdmin = $user['isAdmin'] ?? false;
                $loggedInUsername = $user['username'];
                $loggedInEmail = $user['email'] ?? ''; 
                $canUpload = $user['canUpload'] ?? false; // BARU: Dapatkan status canUpload
                $userLevel = $user['level'] ?? 'Pengguna Biasa'; // Dapatkan level
                break;
            }
        }

        if ($userFound) {
            $_SESSION['username'] = $loggedInUsername;
            $_SESSION['isAdmin'] = $isAdmin;
            $_SESSION['email'] = $loggedInEmail; 
            $_SESSION['canUpload'] = $canUpload; // BARU: Simpan canUpload di sesi
            $_SESSION['level'] = $userLevel; // Simpan level di sesi
            echo json_encode([
                'success' => true, 
                'message' => 'Login berhasil.', 
                'user' => [
                    'username' => $loggedInUsername, 
                    'isAdmin' => $isAdmin, 
                    'email' => $loggedInEmail,
                    'canUpload' => $canUpload, // BARU: Kembalikan canUpload dalam data pengguna
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
    } elseif ($action === 'update_user_permission') { // BARU: Aksi untuk memperbarui izin pengguna
        checkAdminAuth(); // Hanya admin yang bisa melakukan ini
        $targetUsername = $input['username'] ?? null;
        $newCanUploadStatus = $input['canUpload'] ?? null; // True/False

        if (empty($targetUsername) || !isset($newCanUploadStatus)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama pengguna dan status canUpload baru diperlukan.']);
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
                if ($user['username'] === $_SESSION['username'] && $newCanUploadStatus === false && $user['isAdmin']) {
                    // Admin tidak bisa mencabut izin upload mereka sendiri jika mereka adalah satu-satunya admin
                    // Ini adalah aturan yang lebih kompleks, untuk saat ini, admin tidak bisa mencabut izin upload mereka sendiri
                    // kecuali mereka melepaskan status admin terlebih dahulu atau ada admin lain.
                    // Untuk kesederhanaan, biarkan admin bisa mengelola izin upload mereka sendiri.
                }

                $user['canUpload'] = (bool)$newCanUploadStatus;
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
                $_SESSION['canUpload'] = (bool)$newCanUploadStatus;
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
        // BARU: Kembalikan status canUpload dari sesi
        echo json_encode([
            'success' => true, 
            'user' => [
                'username' => $_SESSION['username'], 
                'isAdmin' => $_SESSION['isAdmin'], 
                'email' => $_SESSION['email'] ?? '',
                'canUpload' => $_SESSION['canUpload'] ?? false, // Ambil canUpload dari sesi
                'level' => $_SESSION['level'] ?? 'Pengguna Biasa' // Ambil level dari sesi
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
} elseif ($method === 'GET' && $action === 'get_user_count') { // NEW: Action to get total user count
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
