<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
// PASTIKAN FILE INI DI-ENCODE SEBAGAI UTF-8 TANPA BOM
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$usersFile = '../data/users.json';

// Helper fungsi yang menggunakan utilitas
function getUsers() {
    global $usersFile;
    return readJsonFile($usersFile);
}

function saveUsers($users) {
    global $usersFile;
    return writeJsonFile($usersFile, $users);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = getJsonInput(); // Gunakan fungsi helper untuk input JSON

    // Input mungkin null (tidak ada body) atau false (JSON tidak valid)
    // Jika aksi adalah logout, input null diperbolehkan.
    if ($input === false) { // JSON tidak valid
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input format.']);
        exit;
    }

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    if ($action === 'register') {
        if ($input === null) { // Memastikan input ada untuk register
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data pendaftaran diperlukan.']);
            exit;
        }

        $users = getUsers();
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username dan password diperlukan.']);
            exit;
        }
        
        foreach ($users as $user) {
            if (strtolower($user['username']) === strtolower($username)) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'Username sudah ada. Silakan pilih yang lain.']);
                exit;
            }
        }

        $users[] = ['username' => $username, 'password' => $password, 'isAdmin' => false];
        if (saveUsers($users)) {
            echo json_encode(['success' => true, 'message' => 'Registrasi berhasil.']);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pengguna. Periksa izin folder data.']);
        }

    } elseif ($action === 'login') {
        if ($input === null) { // Memastikan input ada untuk login
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data login diperlukan.']);
            exit;
        }

        $users = getUsers();
        $userFound = false;
        $isAdmin = false;
        $loggedInUsername = '';

        foreach ($users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                $userFound = true;
                $isAdmin = $user['isAdmin'] ?? false;
                $loggedInUsername = $user['username'];
                break;
            }
        }

        if ($userFound) {
            $_SESSION['username'] = $loggedInUsername;
            $_SESSION['isAdmin'] = $isAdmin;
            echo json_encode(['success' => true, 'message' => 'Login berhasil.', 'user' => ['username' => $loggedInUsername, 'isAdmin' => $isAdmin]]);
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'Username atau password salah.']);
        }
    } elseif ($action === 'logout') {
        // Untuk logout, input JSON tidak diperlukan, jadi $input bisa null
        session_unset();
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logout berhasil.']);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
    }

} elseif ($method === 'GET' && $action === 'check_session') {
    if (isset($_SESSION['username'])) {
        echo json_encode(['success' => true, 'user' => ['username' => $_SESSION['username'], 'isAdmin' => $_SESSION['isAdmin']]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tidak ada sesi aktif.']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Metode atau aksi permintaan tidak valid.']);
}
// JANGAN ADA KARAKTER APAPUN SETELAH BARIS INI