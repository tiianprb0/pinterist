<?php
header('Content-Type: application/json');
session_start();

require_once 'utils.php';

$manualPersonRequestsFile = '../data/manual_person_requests.json';
$manualPersonFile = '../data/manual_person.json'; // File untuk menyimpan daftar orang manual yang disetujui

function getManualPersonRequests() {
    global $manualPersonRequestsFile;
    return readJsonFile($manualPersonRequestsFile);
}

function saveManualPersonRequests($requests) {
    global $manualPersonRequestsFile;
    return writeJsonFile($manualPersonRequestsFile, $requests);
}

function getManualPersons() {
    global $manualPersonFile;
    if (!file_exists($manualPersonFile)) {
        writeJsonFile($manualPersonFile, []); // Buat file jika belum ada
    }
    return readJsonFile($manualPersonFile);
}

function saveManualPersons($persons) {
    global $manualPersonFile;
    return writeJsonFile($manualPersonFile, $persons);
}

function checkAdmin() {
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Diperlukan hak akses administrator.']);
        exit;
    }
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if ($action === 'fetch_all') {
        checkAdmin();
        echo json_encode(['success' => true, 'requests' => getManualPersonRequests()]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi GET tidak valid.']);
    }
} elseif ($method === 'POST') {
    $input = getJsonInput();

    if ($input === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format input JSON tidak valid.']);
        exit;
    }

    if ($action === 'add') {
        // Aksi 'add' ini biasanya dipanggil dari sisi pengguna (user.php atau create.html)
        // Jadi, tidak perlu checkAdmin(), hanya perlu checkLoggedIn() jika ingin membatasi.
        // Untuk saat ini, asumsikan bisa ditambahkan oleh siapa saja yang bisa mengirim request.
        $personName = $input['person_name'] ?? null;
        $username = $_SESSION['username'] ?? 'guest'; // Ambil username dari sesi

        if (empty($personName)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama orang diperlukan.']);
            exit;
        }

        $requests = getManualPersonRequests();
        // Cek duplikasi sebelum menambahkan
        foreach ($requests as $req) {
            if (strtolower($req['person_name']) === strtolower($personName)) {
                echo json_encode(['success' => false, 'message' => 'Permintaan untuk orang ini sudah ada.']);
                exit;
            }
        }

        $newRequest = [
            'person_name' => $personName,
            'username' => $username,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $requests[] = $newRequest;

        if (saveManualPersonRequests($requests)) {
            echo json_encode(['success' => true, 'message' => 'Permintaan orang manual berhasil ditambahkan.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan permintaan orang manual.']);
        }
    } elseif ($action === 'accept') {
        checkAdmin(); // Hanya admin yang bisa menerima permintaan
        $index = $input['index'] ?? null;
        $personName = $input['person_name'] ?? null; // Nama orang juga dikirim untuk verifikasi

        $requests = getManualPersonRequests();
        if ($index === null || !isset($requests[$index]) || $requests[$index]['person_name'] !== $personName) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid atau tidak ditemukan.']);
            exit;
        }

        $acceptedPerson = $requests[$index]['person_name'];
        $manualPersons = getManualPersons();

        // Tambahkan ke daftar orang manual jika belum ada
        if (!in_array($acceptedPerson, $manualPersons)) {
            $manualPersons[] = $acceptedPerson;
            sort($manualPersons); // Jaga agar tetap terurut
            if (!saveManualPersons($manualPersons)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan daftar orang manual yang disetujui.']);
                exit;
            }
        }

        // Hapus permintaan dari daftar
        array_splice($requests, $index, 1);
        if (saveManualPersonRequests($requests)) {
            echo json_encode(['success' => true, 'message' => 'Permintaan berhasil diterima dan dihapus.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus permintaan setelah diterima.']);
        }
    } elseif ($action === 'reject') {
        checkAdmin(); // Hanya admin yang bisa menolak permintaan
        $index = $input['index'] ?? null;

        $requests = getManualPersonRequests();
        if ($index === null || !isset($requests[$index])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid atau tidak ditemukan.']);
            exit;
        }

        // Hapus permintaan dari daftar
        array_splice($requests, $index, 1);
        if (saveManualPersonRequests($requests)) {
            echo json_encode(['success' => true, 'message' => 'Permintaan orang manual berhasil ditolak dan dihapus.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus permintaan yang ditolak.']);
        }
    } elseif ($action === 'delete') { // Aksi 'delete' yang lama, mungkin tidak lagi digunakan secara langsung
        checkAdmin();
        $index = $input['index'] ?? null;

        $requests = getManualPersonRequests();
        if ($index === null || !isset($requests[$index])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Indeks permintaan tidak valid.']);
            exit;
        }

        array_splice($requests, $index, 1);
        if (saveManualPersonRequests($requests)) {
            echo json_encode(['success' => true, 'message' => 'Permintaan orang manual berhasil dihapus.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus permintaan orang manual.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi POST tidak valid.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
