<?php
session_start();
// Pastikan jalur ini benar relatif terhadap lokasi search_history.php
// Jika search_history.php ada di direktori root Spicette/, dan utils.php ada di Spicette/api/,
// maka jalur ini sudah benar.
require_once __DIR__ . '/api/utils.php';

// Pastikan tidak ada output apapun sebelum baris ini
header('Content-Type: application/json');

$searchHistoryDir = __DIR__ . '/data/search_history/';
$maxHistory = 5;

// Tambahkan penanganan kesalahan umum untuk mencegah error 500 yang kosong
try {
    // Tentukan ID pengguna atau gunakan pengenal tamu
    // Jika pengguna belum login, gunakan 'guest' sebagai ID
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
    $userHistoryFile = $searchHistoryDir . $userId . '.json';

    // Buat direktori jika belum ada
    if (!is_dir($searchHistoryDir)) {
        // Gunakan mode 0775 untuk keamanan yang lebih baik
        // true untuk rekursif (membuat direktori induk jika tidak ada)
        if (!mkdir($searchHistoryDir, 0775, true)) {
            // Jika gagal membuat direktori, lempar Exception
            throw new Exception("Gagal membuat direktori riwayat pencarian. Periksa izin direktori: " . $searchHistoryDir);
        }
    }

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'fetch':
            // Baca riwayat dari file JSON
            $history = readJsonFile($userHistoryFile);
            sendJsonResponse(['success' => true, 'history' => $history]);
            break;

        case 'add':
            // Ambil input JSON dari body request POST
            $input = getJsonInput();
            $query = $input['query'] ?? '';

            if (empty($query)) {
                sendJsonResponse(['success' => false, 'message' => 'Query pencarian tidak boleh kosong.'], 400);
                break;
            }

            $history = readJsonFile($userHistoryFile);

            // Hapus entri yang sudah ada jika cocok dengan kueri baru (case-insensitive)
            $history = array_values(array_filter($history, function($item) use ($query) {
                return strtolower($item) !== strtolower($query);
            }));

            // Tambahkan kueri baru ke awal array
            array_unshift($history, $query);

            // Pertahankan hanya entri terbaru (sesuai $maxHistory)
            if (count($history) > $maxHistory) {
                $history = array_slice($history, 0, $maxHistory);
            }

            // Tulis riwayat yang diperbarui ke file JSON
            if (writeJsonFile($userHistoryFile, $history)) {
                sendJsonResponse(['success' => true, 'message' => 'Riwayat pencarian berhasil ditambahkan.']);
            } else {
                // Jika gagal menulis file, lempar Exception
                throw new Exception('Gagal menyimpan riwayat pencarian ke file.');
            }
            break;

        case 'clear':
            // Periksa apakah file riwayat ada dan hapus
            if (file_exists($userHistoryFile)) {
                if (unlink($userHistoryFile)) {
                    sendJsonResponse(['success' => true, 'message' => 'Riwayat pencarian berhasil dihapus.']);
                } else {
                    // Jika gagal menghapus file, lempar Exception
                    throw new Exception('Gagal menghapus riwayat pencarian dari file.');
                }
            } else {
                sendJsonResponse(['success' => true, 'message' => 'Tidak ada riwayat pencarian untuk dihapus.']);
            }
            break;

        default:
            // Aksi tidak valid
            sendJsonResponse(['success' => false, 'message' => 'Aksi tidak valid.'], 400);
            break;
    }
} catch (Exception $e) {
    // Tangkap setiap exception (termasuk dari mkdir, file_put_contents, dll.)
    // dan kirim respons JSON error dengan pesan yang jelas
    error_log("Search History API Error: " . $e->getMessage()); // Catat error ke log server
    sendJsonResponse(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
}

// Tidak ada karakter apapun setelah baris ini
// Sangat disarankan untuk tidak menggunakan tag penutup PHP (?>) di file yang hanya berisi kode PHP
