<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
// PASTIKAN FILE INI DI-ENCODE SEBAGAI UTF-8 TANPA BOM
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$categoriesFile = '../data/categories.json'; // Path ke file kategori

// Helper fungsi yang menggunakan utilitas
function readCategories() {
    global $categoriesFile;
    return readJsonFile($categoriesFile);
}

function saveCategories($categories) {
    global $categoriesFile;
    return writeJsonFile($categoriesFile, $categories);
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
        echo json_encode(['success' => true, 'categories' => readCategories()]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi GET tidak valid.']);
    }
} elseif ($method === 'POST') {
    $input = getJsonInput();

    if ($input === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input format.']);
        exit;
    }

    if ($action === 'add') {
        checkAdmin();
        $name = $input['name'] ?? '';
        $imageUrl = $input['imageUrl'] ?? ''; // Ambil imageUrl baru
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama kategori tidak boleh kosong.']);
            exit;
        }

        $categories = readCategories();
        foreach ($categories as $cat) {
            if (strtolower($cat['name']) === strtolower($name)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Kategori sudah ada.']);
                exit;
            }
        }

        $categories[] = ['name' => $name, 'imageUrl' => $imageUrl]; // Simpan sebagai objek
        if (saveCategories($categories)) {
            echo json_encode(['success' => true, 'message' => 'Kategori berhasil ditambahkan.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan kategori. Periksa izin folder data.']);
        }
    } elseif ($action === 'delete') {
        checkAdmin();
        $nameToDelete = $input['name'] ?? '';
        if (empty($nameToDelete)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama kategori diperlukan untuk penghapusan.']);
            exit;
        }

        $categories = readCategories();
        $initialCount = count($categories);
        $updatedCategories = array_filter($categories, function($cat) use ($nameToDelete) {
            return $cat['name'] !== $nameToDelete; // Bandingkan dengan properti 'name'
        });

        if (count($updatedCategories) === $initialCount) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan.']);
            exit;
        }

        if (saveCategories(array_values($updatedCategories))) {
            echo json_encode(['success' => true, 'message' => 'Kategori berhasil dihapus.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus kategori. Periksa izin folder data.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi POST tidak valid.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
// JANGAN ADA KARAKTER APAPUN SETELAH BARIS INI
?>