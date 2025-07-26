<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
// PASTIKAN FILE INI DI-ENCODE SEBAGAI UTF-8 TANPA BOM
header('Content-Type: application/json');

// Setel durasi sesi cookie menjadi 24 jam (86400 detik)
ini_set('session.cookie_lifetime', 86400);
// Setel durasi maksimum sesi di server menjadi 24 jam (86400 detik)
ini_set('session.gc_maxlifetime', 86400);

session_start();

require_once 'utils.php'; // Sertakan file utilitas

$usersFile = '../data/users.json';

// Fungsi pembantu untuk mendapatkan daftar pengguna
function getUsers() {
    global $usersFile;
    // Pastikan nilai default untuk field-field yang mungkin tidak ada di JSON
    $users = readJsonFile($usersFile);
    foreach ($users as &$user) { // Gunakan referensi untuk memodifikasi array asli
        if (!isset($user['canUpload'])) {
            $user['canUpload'] = false; // Nilai default jika tidak disetel
        }
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
        if (!isset($user['level'])) {
            $user['level'] = 'tempted'; // Default level untuk pengguna baru atau yang tidak memiliki level
        }
        // Jika pengguna adalah admin, set level mereka menjadi 'Sinful'
        if (isset($user['isAdmin']) && $user['isAdmin']) {
            $user['level'] = 'Sinful';
        }
        // Tambahkan firebase_uid jika belum ada (untuk kompatibilitas mundur)
        if (!isset($user['firebase_uid'])) {
            $user['firebase_uid'] = null;
        }
        // Tambahkan flag untuk menandai apakah pengguna perlu melengkapi username
        if (!isset($user['needs_username_completion'])) {
            $user['needs_username_completion'] = false;
        }
    }
    return $users;
}

// Fungsi pembantu untuk menyimpan daftar pengguna
function saveUsers($users) {
    global $usersFile;
    return writeJsonFile($usersFile, $users);
}

// Fungsi pembantu untuk memeriksa otentikasi admin
function checkAdminAuth() {
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Diperlukan hak akses administrator.']);
        exit;
    }
}

// Fungsi pembantu untuk mencari pengguna berdasarkan username
function getUserByUsername($username) {
    $users = getUsers();
    foreach ($users as $user) {
        if (isset($user['username']) && strtolower($user['username']) === strtolower($username)) {
            return $user;
        }
    }
    return null;
}

// Fungsi pembantu untuk mencari pengguna berdasarkan email
function getUserByEmail($email) {
    $users = getUsers();
    foreach ($users as $user) {
        if (isset($user['email']) && strtolower($user['email']) === strtolower($email)) {
            return $user;
        }
    }
    return null;
}

// Fungsi pembantu untuk mencari pengguna berdasarkan Firebase UID
function getUserByFirebaseUid($uid) {
    $users = getUsers();
    foreach ($users as $user) {
        if (isset($user['firebase_uid']) && $user['firebase_uid'] === $uid) {
            return $user;
        }
    }
    return null;
}

// Fungsi untuk mengatur sesi pengguna
function setSession($user) {
    $_SESSION['username'] = $user['username'];
    $_SESSION['isAdmin'] = $user['isAdmin'];
    $_SESSION['email'] = $user['email'] ?? '';
    $_SESSION['canUpload'] = $user['canUpload'] ?? false;
    $_SESSION['user_level'] = $user['level'] ?? 'tempted';
    $_SESSION['firebase_uid'] = $user['firebase_uid'] ?? null;
    $_SESSION['needs_username_completion'] = $user['needs_username_completion'] ?? false;
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

    if ($action === 'login_with_username') {
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama pengguna dan kata sandi wajib diisi.']);
            exit;
        }

        $users = getUsers();
        $loggedInUser = null;

        foreach ($users as $user) {
            if (isset($user['username']) && strtolower($user['username']) === strtolower($username)) {
                // Perhatian: Password disimpan dalam plain text di users.json.
                // Untuk keamanan, disarankan menggunakan hashing password (misal: password_hash dan password_verify).
                if (isset($user['password']) && $user['password'] === $password) {
                    $loggedInUser = $user;
                    break;
                }
            }
        }

        if ($loggedInUser) {
            setSession($loggedInUser);
            echo json_encode([
                'success' => true,
                'message' => 'Login berhasil!',
                'user' => [
                    'username' => $loggedInUser['username'],
                    'isAdmin' => $loggedInUser['isAdmin'],
                    'email' => $loggedInUser['email'] ?? '',
                    'canUpload' => $loggedInUser['canUpload'] ?? false,
                    'level' => $loggedInUser['level'] ?? 'tempted'
                ]
            ]);
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'Nama pengguna atau kata sandi salah.']);
        }

    } elseif ($action === 'firebase_login') { // Aksi untuk login Firebase (dari Google Sign-In)
        if ($input === null || !isset($input['uid']) || empty($input['uid'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data Firebase login (UID) diperlukan.']);
            exit;
        }

        $firebaseUid = $input['uid'];
        $firebaseEmail = $input['email'] ?? '';
        $firebaseDisplayName = $input['displayName'] ?? '';
        $firebasePhotoURL = $input['photoURL'] ?? '';

        $users = getUsers();
        $userFound = false;
        $loggedInUser = null;

        // Cari pengguna berdasarkan Firebase UID
        foreach ($users as &$user) {
            if (isset($user['firebase_uid']) && $user['firebase_uid'] === $firebaseUid) {
                $userFound = true;
                $loggedInUser = &$user;
                break;
            }
        }

        if ($userFound) {
            // Pengguna sudah ada di users.json
            // Perbarui email jika ada perubahan dari Firebase
            if (!empty($firebaseEmail) && $loggedInUser['email'] !== $firebaseEmail) {
                $loggedInUser['email'] = $firebaseEmail;
            }
            // JANGAN PERBARUI profile_image_url jika sudah ada nilai,
            // karena pengguna mungkin sudah memilihnya di select_preferences.html
            // Ini akan diatur ulang hanya jika pengguna secara eksplisit memilihnya di sana.
            // if (empty($loggedInUser['profile_image_url']) && !empty($firebasePhotoURL)) {
            //     $loggedInUser['profile_image_url'] = $firebasePhotoURL;
            // }

            // Perbarui display name jika Firebase menyediakannya dan username internal kosong
            // Ini mungkin tidak relevan jika kita selalu memaksa needs_username_completion = true untuk pengguna baru Google
            if (empty($loggedInUser['username']) && !empty($firebaseDisplayName)) {
                $loggedInUser['username'] = $firebaseDisplayName;
                $loggedInUser['needs_username_completion'] = false; // Username sudah diisi dari display name
            }

            // Pastikan level diatur dengan benar, terutama jika admin
            if ($loggedInUser['isAdmin'] && $loggedInUser['level'] !== 'Sinful') {
                $loggedInUser['level'] = 'Sinful';
            }
            
            saveUsers($users); // Simpan perubahan
            setSession($loggedInUser);

            echo json_encode([
                'success' => true,
                'message' => 'Login Firebase berhasil.',
                'user' => [
                    'username' => $loggedInUser['username'] ?? null,
                    'isAdmin' => $loggedInUser['isAdmin'],
                    'email' => $loggedInUser['email'],
                    'canUpload' => $loggedInUser['canUpload'] ?? false,
                    'level' => $loggedInUser['level'] ?? 'tempted',
                    'firebase_uid' => $loggedInUser['firebase_uid'] ?? null,
                    'needs_username_completion' => $loggedInUser['needs_username_completion'] ?? false
                ]
            ]);
        } else {
            // Pengguna baru dari Firebase (terutama Google), daftarkan mereka ke users.json
            // Selalu set needs_username_completion ke true untuk memastikan mereka mengisi username
            $initialUsername = ''; // Biarkan kosong, akan diisi di select_preferences.html

            $newUser = [
                'firebase_uid' => $firebaseUid,
                'username' => $initialUsername, // Akan diisi di select_preferences.html
                'email' => $firebaseEmail,
                'password' => 'FIREBASE_AUTH_USER', // Placeholder, otentikasi ditangani Firebase
                'isAdmin' => false,
                'canUpload' => true, // Default: pengguna Firebase bisa mengunggah
                'preferred_categories' => [],
                'preferred_persons' => [],
                'manual_persons_requested' => [],
                'profile_image_url' => '', // Set to empty string for new Google users to force selection in select_preferences.html
                'level' => 'tempted',
                'needs_username_completion' => true // Selalu true untuk pengguna Firebase baru agar mengisi username
            ];
            $users[] = $newUser;

            if (saveUsers($users)) {
                setSession($newUser);
                echo json_encode([
                    'success' => true,
                    'message' => 'Pendaftaran Firebase berhasil.',
                    'user' => [
                        'username' => $newUser['username'],
                        'email' => $newUser['email'],
                        'level' => $newUser['level'],
                        'needs_username_completion' => $newUser['needs_username_completion']
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pengguna Firebase baru. Periksa izin folder data.']);
            }
        }

    } elseif ($action === 'firebase_register') { // Aksi untuk pendaftaran Email/Password Firebase
        if ($input === null || !isset($input['uid']) || empty($input['uid']) || !isset($input['email']) || empty($input['email']) || !isset($input['displayName']) || empty($input['displayName'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data pendaftaran Firebase (UID, email, displayName/username) diperlukan.']);
            exit;
        }

        $firebaseUid = $input['uid'];
        $firebaseEmail = $input['email'];
        $usernameFromRegisterForm = $input['displayName']; // Ini adalah username yang dimasukkan pengguna

        $users = getUsers();

        // Cek apakah username sudah ada
        if (getUserByUsername($usernameFromRegisterForm)) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'Nama pengguna ini sudah digunakan. Silakan pilih nama pengguna lain.']);
            exit;
        }

        // Cek apakah email sudah ada (walaupun Firebase sudah cek, ini untuk konsistensi di users.json)
        if (getUserByEmail($firebaseEmail)) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'Email ini sudah terdaftar. Silakan masuk.']);
            exit;
        }

        $newUser = [
            'firebase_uid' => $firebaseUid,
            'username' => $usernameFromRegisterForm,
            'email' => $firebaseEmail,
            'password' => 'FIREBASE_AUTH_USER', // Placeholder
            'isAdmin' => false,
            'canUpload' => true,
            'preferred_categories' => [],
            'preferred_persons' => [],
            'manual_persons_requested' => [],
            'profile_image_url' => $input['photoURL'] ?? '', // For email/password registration, use photoURL if provided, else empty
            'level' => 'tempted',
            'needs_username_completion' => false // Username sudah diisi saat register
        ];
        $users[] = $newUser;

        if (saveUsers($users)) {
            setSession($newUser);
            echo json_encode(['success' => true, 'message' => 'Pendaftaran berhasil.', 'user' => ['username' => $newUser['username'], 'email' => $newUser['email'], 'level' => $newUser['level']]]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pengguna baru. Periksa izin folder data.']);
        }

    } elseif ($action === 'check_username') { // Aksi untuk memeriksa apakah pengguna memiliki username
        if ($input === null || !isset($input['uid']) || empty($input['uid'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'UID diperlukan untuk memeriksa username.']);
            exit;
        }
        $firebaseUid = $input['uid'];
        $user = getUserByFirebaseUid($firebaseUid);

        // Pengguna memiliki username jika field 'username' tidak kosong DAN 'needs_username_completion' adalah false
        if ($user && !empty($user['username']) && !($user['needs_username_completion'] ?? false)) {
            echo json_encode(['success' => true, 'has_username' => true]);
        } else {
            echo json_encode(['success' => true, 'has_username' => false]);
        }

    } elseif ($action === 'update_username') { // Aksi untuk memperbarui username pengguna
        if ($input === null || !isset($input['uid']) || empty($input['uid']) || !isset($input['new_username']) || empty($input['new_username'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'UID dan username baru diperlukan.']);
            exit;
        }

        $firebaseUid = $input['uid'];
        $newUsername = $input['new_username'];

        $users = getUsers();
        $userUpdated = false;

        // Cek apakah username baru sudah digunakan oleh pengguna lain
        foreach ($users as $user) {
            if (isset($user['username']) && strtolower($user['username']) === strtolower($newUsername) && (!isset($user['firebase_uid']) || $user['firebase_uid'] !== $firebaseUid)) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'Nama pengguna ini sudah digunakan. Silakan pilih nama pengguna lain.']);
                exit;
            }
        }

        foreach ($users as &$user) {
            if (isset($user['firebase_uid']) && $user['firebase_uid'] === $firebaseUid) {
                $user['username'] = $newUsername;
                $user['needs_username_completion'] = false; // Username sudah diisi
                $userUpdated = true;
                setSession($user); // Perbarui sesi setelah username diubah
                break;
            }
        }

        if ($userUpdated) {
            if (saveUsers($users)) {
                echo json_encode(['success' => true, 'message' => 'Nama pengguna berhasil diperbarui.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan nama pengguna. Periksa izin folder data.']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
        }

    } elseif ($action === 'logout') {
        session_unset();
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logout berhasil.']);

    } elseif ($action === 'update_user_permission') { // Aksi untuk memperbarui izin pengguna
        checkAdminAuth(); // Hanya admin yang bisa melakukan ini
        $targetUsername = $input['username'] ?? null;
        $newCanUploadStatus = $input['canUpload'] ?? null; // True/False
        $newLevel = $input['level'] ?? null;

        if (empty($targetUsername) || (!isset($newCanUploadStatus) && !isset($newLevel))) {
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
                if (isset($newLevel)) {
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

} elseif ($method === 'GET') {
    if ($action === 'check_session') {
        if (isset($_SESSION['username'])) {
            // Kembalikan status canUpload dan level dari sesi
            echo json_encode([
                'success' => true,
                'user' => [
                    'username' => $_SESSION['username'],
                    'isAdmin' => $_SESSION['isAdmin'],
                    'email' => $_SESSION['email'] ?? '',
                    'canUpload' => $_SESSION['canUpload'] ?? false,
                    'level' => $_SESSION['user_level'] ?? 'tempted',
                    'firebase_uid' => $_SESSION['firebase_uid'] ?? null,
                    'needs_username_completion' => $_SESSION['needs_username_completion'] ?? false
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Tidak ada sesi aktif.']);
        }
    } elseif ($action === 'get_user_preferences') {
        $targetUsername = $_GET['username'] ?? null;
        if (empty($targetUsername)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama pengguna diperlukan.']);
            exit;
        }
        $users = getUsers();
        $foundUser = null;
        foreach ($users as $user) {
            if (isset($user['username']) && $user['username'] === $targetUsername) {
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
                    'profile_image_url' => $foundUser['profile_image_url'] ?? '',
                    'liked_pins' => $foundUser['liked_pins'] ?? [] // Include liked pins
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
        }
    } elseif ($action === 'get_user_count') { // Action to get total user count
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
        http_response_code(400); // Permintaan Buruk
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
    }
} else {
    http_response_code(405); // Metode Tidak Diizinkan
    echo json_encode(['success' => false, 'message' => 'Metode permintaan atau aksi tidak valid.']);
}
// JANGAN ADA KARAKTER APAPUN SETELAH BARIS INI
?>
