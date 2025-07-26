<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$manualPersonRequestsFile = '../data/manual_person_requests.json';

// Memastikan hanya admin yang bisa mengakses API ini
function checkAdminAuth() {
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Diperlukan hak akses administrator.']);
        exit;
    }
}

checkAdminAuth(); // Panggil fungsi cek admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();

    if ($input === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format input JSON tidak valid.']);
        exit;
    }

    $indexToDelete = $input['index'] ?? null;

    if (!isset($indexToDelete) || !is_numeric($indexToDelete)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Indeks permintaan tidak valid.']);
        exit;
    }

    $requests = readJsonFile($manualPersonRequestsFile);

    if (!is_array($requests)) {
        $requests = []; // Inisialisasi jika file kosong atau tidak valid
    }

    if ($indexToDelete >= 0 && $indexToDelete < count($requests)) {
        array_splice($requests, $indexToDelete, 1); // Hapus elemen pada indeks yang ditentukan
        if (writeJsonFile($manualPersonRequestsFile, $requests)) {
            echo json_encode(['success' => true, 'message' => 'Permintaan orang manual berhasil dihapus.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data permintaan orang manual. Periksa izin folder data.']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Permintaan orang manual tidak ditemukan.']);
    }

} else {
    http_response_code(405); // Metode Tidak Diizinkan
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
