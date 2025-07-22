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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requests = readJsonFile($manualPersonRequestsFile);

    if ($requests === false) {
        $requests = []; // Inisialisasi jika file kosong atau tidak valid
    }

    echo json_encode(['success' => true, 'requests' => $requests]);
} else {
    http_response_code(405); // Metode Tidak Diizinkan
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
