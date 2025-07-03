<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$categoriesFile = '../data/categories.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $categories = readJsonFile($categoriesFile);

    if ($categories !== false) {
        echo json_encode(['success' => true, 'categories' => $categories]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memuat kategori.']);
    }
} else {
    http_response_code(405); // Metode Tidak Diizinkan
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
