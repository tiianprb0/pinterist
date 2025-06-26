<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
// PASTIKAN FILE INI DI-ENCODE SEBAGAI UTF-8 TANPA BOM
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$pinsFile = '../data/pins.json';
$usersFile = '../data/users.json'; // Path ke users.json (untuk delete_user action)
$savedPinsDir = '../data/saved_pins/';
$uploadDir = '../uploads/pins/'; // Direktori untuk menyimpan gambar yang diunggah

// Fungsi pembantu yang menggunakan utilitas
function getAllPins() {
    global $pinsFile;
    return readJsonFile($pinsFile);
}

function saveAllPins($pins) {
    global $pinsFile;
    return writeJsonFile($pinsFile, $pins);
}

// BARU: Fungsi untuk mendapatkan pengguna dengan canUpload. Ini akan memanggil getUsers dari auth.php secara efektif
function getUsersForPinsApi() { 
    global $usersFile;
    $users = readJsonFile($usersFile);
    foreach ($users as &$user) { // Gunakan referensi untuk memodifikasi array asli
        if (!isset($user['canUpload'])) {
            $user['canUpload'] = false; // Nilai default jika tidak disetel
        }
    }
    return $users;
}

function saveUsersForPinsApi($users) { // Digunakan di delete_user
    global $usersFile;
    return writeJsonFile($usersFile, $users);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

function checkAdmin() {
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Diperlukan hak akses administrator.']);
        exit;
    }
}

function checkLoggedIn() {
    if (!isset($_SESSION['username'])) {
        http_response_code(401); // Tidak Sah
        echo json_encode(['success' => false, 'message' => 'Login diperlukan.']);
        exit;
    }
}

if ($method === 'GET') {
    if ($action === 'fetch_all') {
        echo json_encode(['success' => true, 'pins' => getAllPins()]);
    } elseif ($action === 'fetch_saved') {
        checkLoggedIn();
        $username = $_SESSION['username'];
        $allPins = getAllPins();
        // Pastikan file saved_pins/[username].json ada, jika tidak, inisialisasi dengan array kosong
        $userSavedPinsFile = $savedPinsDir . $username . '.json';
        if (!file_exists($userSavedPinsFile)) {
            // Buat file jika belum ada dengan array kosong
            writeJsonFile($userSavedPinsFile, []);
        }
        $savedPinIds = readJsonFile($userSavedPinsFile);
        
        $savedPins = array_filter($allPins, function($pin) use ($savedPinIds) {
            return in_array($pin['id'], $savedPinIds);
        });
        echo json_encode(['success' => true, 'pins' => array_values($savedPins)]);
    } elseif ($action === 'fetch_all_users') {
        checkAdmin();
        // BARU: Panggil getUsersForPinsApi untuk menyertakan status canUpload
        echo json_encode(['success' => true, 'users' => getUsersForPinsApi()]); 
    } elseif ($action === 'fetch_user_pins') {
        $username = $_GET['username'] ?? ($_SESSION['username'] ?? null);
        if (empty($username)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama pengguna diperlukan untuk mengambil pin pengguna.']);
            exit;
        }
        $allPins = getAllPins();
        $userPins = array_filter($allPins, function($pin) use ($username) {
            return ($pin['uploadedBy'] ?? 'system') === $username;
        });
        echo json_encode(['success' => true, 'pins' => array_values($userPins)]);
    } elseif ($action === 'search') {
        $query = $_GET['query'] ?? '';
        $allPins = getAllPins();
        $filteredPins = array_filter($allPins, function($pin) use ($query) {
            $queryLower = strtolower($query);
            
            // Cari dalam kategori (sekarang array)
            $inCategories = false;
            if (isset($pin['categories']) && is_array($pin['categories'])) { 
                foreach ($pin['categories'] as $categoryName) {
                    if (stripos($categoryName, $queryLower) !== false) {
                        $inCategories = true;
                        break;
                    }
                }
            }
            
            // Periksa dalam judul, deskripsi, dan deskripsi gambar
            $inImageDescriptions = false;
            if (isset($pin['images']) && is_array($pin['images'])) {
                foreach ($pin['images'] as $image) {
                    if (isset($image['description']) && stripos($image['description'], $queryLower) !== false) {
                        $inImageDescriptions = true;
                        break;
                    }
                }
            }

            return (stripos($pin['title'] ?? '', $queryLower) !== false ||
                    stripos($pin['description'] ?? '', $queryLower) !== false || 
                    $inCategories || // Gunakan variabel $inCategories yang sudah dihitung
                    $inImageDescriptions); 
        });
        echo json_encode(['success' => true, 'pins' => array_values($filteredPins)]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi GET tidak valid.']);
    }
} elseif ($method === 'POST') {
    // Memeriksa tipe konten untuk menentukan apakah itu unggahan file atau JSON
    // Ini adalah permintaan POST dari form multipart/form-data
    if (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        checkLoggedIn(); // Pastikan pengguna sudah login

        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        // BARU: Dapatkan kategori sebagai JSON stringified array dan decode
        $categories_json = $_POST['categories'] ?? '[]'; 
        $categories = json_decode($categories_json, true);
        if (!is_array($categories)) {
            $categories = []; // Pastikan ini array
        }

        // Dapatkan uploadedBy langsung dari sesi, ini lebih aman dan akurat
        $uploadedBy = $_SESSION['username'] ?? 'unknown'; 
        $display_type = $_POST['display_type'] ?? 'stacked'; // Default ke 'stacked'

        // Dapatkan deskripsi individual gambar jika ada
        $image_descriptions = [];
        if (isset($_POST['image_descriptions'])) {
            $image_descriptions = json_decode($_POST['image_descriptions'], true);
            if (!is_array($image_descriptions)) {
                $image_descriptions = []; // Pastikan ini array
            }
        }

        $uploaded_image_data = []; // Akan berisi [{url: 'path', description: 'desc'}, ...]

        if (!empty($_FILES['images']['name'][0])) {
            // Pastikan direktori unggah ada
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // Pastikan izin aman di lingkungan produksi!
            }

            $total_files = count($_FILES['images']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                $file_name = $_FILES['images']['name'][$i];
                $file_tmp = $_FILES['images']['tmp_name'][$i];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $new_file_name = uniqid('pin_') . '.' . $file_ext;
                $destination = $uploadDir . $new_file_name;
                $web_path = './uploads/pins/' . $new_file_name; // Path relatif dari Spicette/

                if (move_uploaded_file($file_tmp, $destination)) {
                    $img_desc = $image_descriptions[$i] ?? ''; // Dapatkan deskripsi individual
                    $uploaded_image_data[] = ['url' => $web_path, 'description' => $img_desc];
                } else {
                    // Tangani kesalahan unggah file individual
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Gagal mengunggah beberapa gambar.', 'error_file' => $file_name]);
                    exit();
                }
            }
        } else {
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => 'Tidak ada gambar yang diunggah.']);
             exit();
        }

        // Validasi data
        if (empty($title) || empty($uploaded_image_data) || empty($categories) || empty($uploadedBy)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data pin tidak lengkap (judul, gambar, kategori, atau ID pengguna hilang).']);
            exit();
        }

        $pins = getAllPins();
        $newPinId = 'pin_' . uniqid() . '_' . time();
        $newPin = [
            'id' => $newPinId,
            'title' => $title,
            'description' => $description, // Deskripsi umum pin
            'images' => $uploaded_image_data, // Simpan array objek gambar
            'display_type' => $display_type, // Simpan tipe tampilan
            'categories' => $categories, // BARU: Kategori sekarang array
            'uploadedBy' => $uploadedBy, // Menggunakan variabel $uploadedBy yang sudah diverifikasi
            'created_at' => date('Y-m-d H:i:s')
        ];

        $pins[] = $newPin;
        if (saveAllPins($pins)) {
            echo json_encode(['success' => true, 'message' => 'Pin berhasil ditambahkan.', 'pin_id' => $newPinId, 'pin' => $newPin]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pin. Periksa izin folder data.']);
        }

    } else { // Ini adalah permintaan JSON biasa
        $input = getJsonInput(); // Gunakan fungsi helper untuk input JSON

        if ($input === false) { // JSON tidak valid
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Format input JSON tidak valid.']);
            exit;
        }

        // Logika untuk aksi POST yang bukan unggah file (save, unsave, delete_pin, delete_user)
        if ($action === 'add') { // Aksi 'add' untuk URL eksternal (mungkin tidak lagi digunakan sepenuhnya)
            checkLoggedIn();
            $imageUrl = $input['imageUrl'] ?? '';
            $source = $input['source'] ?? '';
            $title = $input['title'] ?? ''; 
            $content = $input['content'] ?? ''; // Ini adalah deskripsi pin umum dalam struktur lama
            // BARU: Categories sekarang array. Jika dari input lama, mungkin single string atau array kosong.
            $categories_input = $input['categories'] ?? []; 
            // Pastikan $categories_input adalah array. Jika string, ubah menjadi array dengan satu elemen.
            $categories = is_array($categories_input) ? $categories_input : [$categories_input];


            // Jika Anda ingin mempertahankan kemampuan menambahkan pin dengan URL eksternal (single image)
            // Maka logika ini akan tetap digunakan, jika tidak, bisa dihapus atau disesuaikan.
            // Untuk kompatibilitas, kita akan pertahankan ini tapi sesuaikan struktur data
            if (empty($imageUrl) || empty($title)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'URL gambar dan judul diperlukan.']);
                exit;
            }
            
            $pins = getAllPins();
            $newId = 'pin_' . uniqid() . '_' . time();

            $newPin = [
                'id' => $newId,
                'title' => $title,
                'description' => $content, // Konten lama menjadi deskripsi
                'images' => [['url' => $imageUrl, 'description' => '']], // Satu gambar, tanpa deskripsi individual
                'display_type' => 'stacked', // Default stacked untuk single image
                'categories' => $categories, // BARU: Simpan sebagai array
                'uploadedBy' => $_SESSION['username'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $pins[] = $newPin;
            if (saveAllPins($pins)) {
                echo json_encode(['success' => true, 'message' => 'Pin berhasil ditambahkan.', 'pin' => $newPin]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pin. Periksa izin folder data.']);
            }

        } elseif ($action === 'delete_pin') {
            checkAdmin();
            $pinId = $input['pinId'] ?? null;

            if (empty($pinId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk penghapusan.']);
                exit;
            }

            $pins = getAllPins();
            $initialCount = count($pins);
            $pinToDelete = null;

            // Cari pin dan hapus gambar terkait
            foreach ($pins as $key => $pin) {
                if ($pin['id'] === $pinId) {
                    $pinToDelete = $pin;
                    unset($pins[$key]); // Hapus pin dari array
                    break;
                }
            }

            if ($pinToDelete === null) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Pin tidak ditemukan.']);
                exit;
            }

            // Hapus file gambar terkait dari server
            if (isset($pinToDelete['images']) && is_array($pinToDelete['images'])) {
                foreach ($pinToDelete['images'] as $image) {
                    // Dapatkan path fisik file
                    // Hati-hati dengan path: './uploads/pins/' => '../uploads/pins/' dari pins.php
                    $filePath = str_replace('./uploads/pins/', '../uploads/pins/', $image['url']);
                    if (file_exists($filePath) && is_file($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            if (!saveAllPins(array_values($pins))) { // Re-index array setelah filter
                 http_response_code(500);
                 echo json_encode(['success' => false, 'message' => 'Gagal menghapus pin. Periksa izin folder data.']);
                 exit;
            }

            $users = getUsersForPinsApi(); // Menggunakan fungsi yang dimodifikasi
            foreach ($users as $user) {
                $userSavedPinsFile = $savedPinsDir . $user['username'] . '.json';
                if (file_exists($userSavedPinsFile)) { // Periksa keberadaan file sebelum membaca
                    $savedPins = readJsonFile($userSavedPinsFile);
                    $updatedSavedPins = array_filter($savedPins, function($id) use ($pinId) {
                        return $id !== $pinId;
                    });
                    // Penting: Gunakan array_values untuk mengindeks ulang array setelah filter
                    writeJsonFile($userSavedPinsFile, array_values($updatedSavedPins));
                }
            }

            echo json_encode(['success' => true, 'message' => 'Pin berhasil dihapus.']);

        } elseif ($action === 'delete_user') {
            checkAdmin();
            $usernameToDelete = $input['username'] ?? null;

            if (empty($usernameToDelete)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama pengguna diperlukan untuk penghapusan.']);
                exit;
            }

            $users = getUsersForPinsApi(); // Menggunakan fungsi yang dimodifikasi
            $initialUserCount = count($users);
            $updatedUsers = array_filter($users, function($user) use ($usernameToDelete) {
                return $user['username'] !== $usernameToDelete;
            });

            if (count($updatedUsers) === $initialUserCount) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
                exit;
            }
            
            if (isset($_SESSION['username']) && $usernameToDelete === $_SESSION['username']) {
                 http_response_code(403);
                 echo json_encode(['success' => false, 'message' => 'Anda tidak bisa menghapus akun Anda sendiri.']);
                 exit;
            }

            if (!saveUsersForPinsApi(array_values($updatedUsers))) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus pengguna. Periksa izin folder data.']);
                exit;
            }

            $userSavedPinsFile = $savedPinsDir . $usernameToDelete . '.json';
            if (file_exists($userSavedPinsFile)) {
                unlink($userSavedPinsFile);
            }

            // Hapus juga pin yang diunggah oleh pengguna ini
            $pins = getAllPins();
            $updatedPins = array_filter($pins, function($pin) use ($usernameToDelete) {
                // Hapus juga gambar fisik yang diunggah oleh pengguna yang dihapus
                if (($pin['uploadedBy'] ?? 'unknown') === $usernameToDelete) {
                    if (isset($pin['images']) && is_array($pin['images'])) {
                        foreach ($pin['images'] as $image) {
                             $filePath = str_replace('./uploads/pins/', '../uploads/pins/', $image['url']);
                            if (file_exists($filePath) && is_file($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                    return false; // Hapus pin ini
                }
                return true; // Pertahankan pin ini
            });
            saveAllPins(array_values($updatedPins));


            echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dihapus.']);

        } elseif ($action === 'save') {
            checkLoggedIn();
            $username = $_SESSION['username'];
            $pinId = $input['pinId'] ?? null;

            if (empty($pinId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk menyimpan.']);
                exit;
            }

            $userSavedPinsFile = $savedPinsDir . $username . '.json';
            // Pastikan file ada, jika tidak, buat dengan array kosong
            if (!file_exists($userSavedPinsFile)) {
                writeJsonFile($userSavedPinsFile, []);
            }

            $savedPins = readJsonFile($userSavedPinsFile);
            if (!in_array($pinId, $savedPins)) {
                $savedPins[] = $pinId;
                if (writeJsonFile($userSavedPinsFile, $savedPins)) {
                    echo json_encode(['success' => true, 'message' => 'Pin berhasil disimpan.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pin. Periksa izin folder data.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Pin sudah disimpan.']);
            }
        } elseif ($action === 'unsave') { // BARU: Aksi Unsave
            checkLoggedIn();
            $username = $_SESSION['username'];
            $pinId = $input['pinId'] ?? null;

            if (empty($pinId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk menghapus dari daftar simpan.']);
                exit;
            }

            $userSavedPinsFile = $savedPinsDir . $username . '.json';
            if (!file_exists($userSavedPinsFile)) {
                http_response_code(404); // Jika file saved_pins tidak ada, berarti tidak ada pin yang disimpan
                echo json_encode(['success' => false, 'message' => 'Tidak ada pin yang disimpan untuk pengguna ini.']);
                exit;
            }

            $savedPins = readJsonFile($userSavedPinsFile);
            $initialCount = count($savedPins);
            $updatedSavedPins = array_filter($savedPins, function($id) use ($pinId) {
                return $id !== $pinId;
            });

            // Jika jumlah elemen tidak berubah, berarti pin tidak ditemukan
            if (count($updatedSavedPins) === $initialCount) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Pin tidak ditemukan di daftar simpan Anda.']);
                exit;
            }
            
            // Penting: Gunakan array_values untuk mengindeks ulang array setelah filter
            if (writeJsonFile($userSavedPinsFile, array_values($updatedSavedPins))) {
                echo json_encode(['success' => true, 'message' => 'Pin berhasil dihapus dari daftar simpan.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus pin dari daftar simpan. Periksa izin folder data.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Aksi POST tidak valid.']);
        }
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
// JANGAN ADA KARAKTER APAPUN SETELAH BARIS INI
?>
