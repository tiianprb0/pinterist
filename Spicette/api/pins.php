<?php
header('Content-Type: application/json');
session_start();

require_once 'utils.php';

$pinsFile = '../data/pins.json';
$usersFile = '../data/users.json';
$savedPinsDir = '../data/saved_pins/';
$uploadDir = '../uploads/pins/'; // Direktori ini hanya relevan untuk upload.php sekarang, tapi tetap didefinisikan.
$blurUploadDir = '../uploads/blur-pins/'; // Direktori untuk gambar blur

// Define level hierarchy
$levelHierarchy = [
    'tempted' => 1,
    'Naughty' => 2,
    'Sinful' => 3
];

/**
 * Helper function to determine if user can see pin clearly.
 * @param string $userLevel The current user's level.
 * @param string $pinLevel The pin's required level.
 * @return bool True if user can see clearly, false otherwise.
 */
function canUserSeePinClearly($userLevel, $pinLevel) {
    global $levelHierarchy;
    $userLevelValue = $levelHierarchy[$userLevel] ?? 0;
    $pinLevelValue = $levelHierarchy[$pinLevel] ?? 0;
    return $userLevelValue >= $pinLevelValue;
}

function getAllPins() {
    global $pinsFile;
    $pins = readJsonFile($pinsFile);
    foreach ($pins as &$pin) { // Use reference to modify the original array
        if (!isset($pin['level'])) {
            $pin['level'] = 'tempted'; // Default level for pin if not set
        }
        // Ensure images array has url_original and url_blur
        if (isset($pin['images']) && is_array($pin['images'])) {
            foreach ($pin['images'] as &$image) {
                if (!isset($image['url_original'])) {
                    $image['url_original'] = $image['url'] ?? ''; // Fallback to 'url' if 'url_original' is missing
                }
                if (!isset($image['url_blur'])) {
                    // Create a placeholder blur URL or handle old pins without blur versions
                    $originalFileName = basename($image['url_original']);
                    $image['url_blur'] = './uploads/blur-pins/blur_' . $originalFileName;
                    // Note: This assumes a blur_ prefix. For older pins, the blur file might not exist.
                    // Frontend should handle image loading errors gracefully.
                }
                // Remove the old 'url' key if it exists to standardize
                if (isset($image['url'])) {
                    unset($image['url']);
                }
            }
        }
    }
    return $pins;
}

function saveAllPins($pins) {
    global $pinsFile;
    return writeJsonFile($pinsFile, $pins);
}

function getUsersForPinsApi() { 
    global $usersFile;
    $users = readJsonFile($usersFile);
    foreach ($users as &$user) {
        // Pastikan 'canUpload' ada
        if (!isset($user['canUpload'])) {
            $user['canUpload'] = false;
        }
        // Pastikan 'liked_pins' ada untuk setiap pengguna
        if (!isset($user['liked_pins'])) {
            $user['liked_pins'] = [];
        }
        // Pastikan 'level' ada
        if (!isset($user['level'])) {
            $user['level'] = 'tempted'; // Default level for new users or those without level
        }
        // If user is admin, force their level to 'Sinful'
        if (isset($user['isAdmin']) && $user['isAdmin']) {
            $user['level'] = 'Sinful';
        }
    }
    return $users;
}

function saveUsersForPinsApi($users) {
    global $usersFile;
    return writeJsonFile($usersFile, $users);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

function checkAdmin() {
    if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Diperlukan hak akses administrator.']);
        exit;
    }
}

function checkLoggedIn() {
    if (!isset($_SESSION['username'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login diperlukan.']);
        exit;
    }
}

if ($method === 'GET') {
    $currentUserLevel = $_SESSION['user_level'] ?? 'tempted'; // Get user's level from session, default to 'tempted'

    if ($action === 'fetch_all') {
        $allPins = getAllPins();
        $filteredPins = [];
        foreach ($allPins as $pin) {
            $pin['can_view_clearly'] = canUserSeePinClearly($currentUserLevel, $pin['level']);
            // Adjust image URLs based on permission
            if (isset($pin['images']) && is_array($pin['images'])) {
                foreach ($pin['images'] as &$image) {
                    $image['display_url'] = $pin['can_view_clearly'] ? ($image['url_original'] ?? '') : ($image['url_blur'] ?? '');
                }
            }
            $filteredPins[] = $pin;
        }
        echo json_encode(['success' => true, 'pins' => $filteredPins]);

    } elseif ($action === 'fetch_saved') {
        checkLoggedIn();
        $username = $_SESSION['username'];
        $allPins = getAllPins();
        $userSavedPinsFile = $savedPinsDir . $username . '.json';
        if (!file_exists($userSavedPinsFile)) {
            writeJsonFile($userSavedPinsFile, []);
        }
        $savedPinIds = readJsonFile($userSavedPinsFile);
        
        $savedPins = array_filter($allPins, function($pin) use ($savedPinIds) {
            return in_array($pin['id'], $savedPinIds);
        });

        $filteredSavedPins = [];
        foreach ($savedPins as $pin) {
            $pin['can_view_clearly'] = canUserSeePinClearly($currentUserLevel, $pin['level']);
            if (isset($pin['images']) && is_array($pin['images'])) {
                foreach ($pin['images'] as &$image) {
                    $image['display_url'] = $pin['can_view_clearly'] ? ($image['url_original'] ?? '') : ($image['url_blur'] ?? '');
                }
            }
            $filteredSavedPins[] = $pin;
        }
        echo json_encode(['success' => true, 'pins' => array_values($filteredSavedPins)]);

    } elseif ($action === 'fetch_all_users') {
        checkAdmin();
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

        $filteredUserPins = [];
        foreach ($userPins as $pin) {
            $pin['can_view_clearly'] = canUserSeePinClearly($currentUserLevel, $pin['level']);
            if (isset($pin['images']) && is_array($pin['images'])) {
                foreach ($pin['images'] as &$image) {
                    $image['display_url'] = $pin['can_view_clearly'] ? ($image['url_original'] ?? '') : ($image['url_blur'] ?? '');
                }
            }
            $filteredUserPins[] = $pin;
        }
        echo json_encode(['success' => true, 'pins' => array_values($filteredUserPins)]);

    } elseif ($action === 'search') {
        $query = $_GET['query'] ?? '';
        $allPins = getAllPins();
        $filteredPins = array_filter($allPins, function($pin) use ($query) {
            $queryLower = strtolower($query);
            
            $inCategories = false;
            if (isset($pin['categories']) && is_array($pin['categories'])) { 
                foreach ($pin['categories'] as $categoryName) {
                    if (stripos($categoryName, $queryLower) !== false) {
                        $inCategories = true;
                        break;
                    }
                }
            }
            
            $inImageDescriptions = false;
            if (isset($pin['images']) && is_array($pin['images'])) {
                foreach ($pin['images'] as $image) {
                    if (isset($image['description']) && stripos($image['description'], $queryLower) !== false) {
                        $inImageDescriptions = true;
                        break;
                    }
                }
            }
            // Search in personTags
            $inPersonTags = false;
            if (isset($pin['personTags']) && is_array($pin['personTags'])) {
                foreach ($pin['personTags'] as $personName) {
                    if (stripos($personName, $queryLower) !== false) {
                        $inPersonTags = true;
                        break;
                    }
                }
            }

            return (stripos($pin['title'] ?? '', $queryLower) !== false ||
                    stripos($pin['description'] ?? '', $queryLower) !== false || 
                    $inCategories ||
                    $inImageDescriptions ||
                    $inPersonTags); // Include personTags in search
        });

        $finalFilteredPins = [];
        foreach ($filteredPins as $pin) {
            $pin['can_view_clearly'] = canUserSeePinClearly($currentUserLevel, $pin['level']);
            if (isset($pin['images']) && is_array($pin['images'])) {
                foreach ($pin['images'] as &$image) {
                    $image['display_url'] = $pin['can_view_clearly'] ? ($image['url_original'] ?? '') : ($image['url_blur'] ?? '');
                }
            }
            $finalFilteredPins[] = $pin;
        }
        echo json_encode(['success' => true, 'pins' => array_values($finalFilteredPins)]);

    } elseif ($action === 'getPinsByPersonTag') { // NEW ACTION
        $personName = $_GET['name'] ?? '';
        if (empty($personName)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama orang diperlukan untuk mengambil pin.']);
            exit;
        }
        $allPins = getAllPins();
        $filteredPins = array_filter($allPins, function($pin) use ($personName) {
            if (isset($pin['personTags']) && is_array($pin['personTags'])) {
                foreach ($pin['personTags'] as $tag) {
                    if (strtolower($tag) === strtolower($personName)) {
                        return true;
                    }
                }
            }
            return false;
        });

        $finalPersonPins = [];
        foreach ($filteredPins as $pin) {
            $pin['can_view_clearly'] = canUserSeePinClearly($currentUserLevel, $pin['level']);
            if (isset($pin['images']) && is_array($pin['images'])) {
                foreach ($pin['images'] as &$image) {
                    $image['display_url'] = $pin['can_view_clearly'] ? ($image['url_original'] ?? '') : ($image['url_blur'] ?? '');
                }
            }
            $finalPersonPins[] = $pin;
        }
        echo json_encode(['success' => true, 'pins' => array_values($finalPersonPins)]);

    } elseif ($action === 'get_pin_like_count') { // NEW: Action to get global like count for a pin
        $pinId = $_GET['pinId'] ?? null;
        if (empty($pinId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk mendapatkan jumlah like.']);
            exit;
        }

        $users = getUsersForPinsApi(); // Get all users
        $likeCount = 0;
        foreach ($users as $user) {
            if (isset($user['liked_pins']) && is_array($user['liked_pins'])) {
                if (in_array($pinId, $user['liked_pins'])) {
                    $likeCount++;
                }
            }
        }
        echo json_encode(['success' => true, 'count' => $likeCount]);
    } elseif ($action === 'get_stats') { // NEW: Action to get overall pin statistics
        try {
            checkAdmin();
            $allPins = getAllPins();
            $totalPins = count($allPins);
            $personTagCounts = [];

            foreach ($allPins as $pin) {
                if (isset($pin['personTags']) && is_array($pin['personTags'])) {
                    foreach ($pin['personTags'] as $tag) {
                        $tag = trim($tag);
                        if (!empty($tag)) {
                            if (!isset($personTagCounts[$tag])) {
                                $personTagCounts[$tag] = 0;
                            }
                            $personTagCounts[$tag]++;
                        }
                    }
                }
            }
            echo json_encode(['success' => true, 'totalPins' => $totalPins, 'personTagCounts' => $personTagCounts]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Kesalahan internal server saat mengambil statistik pin: ' . $e->getMessage()]);
            error_log("ERROR: Exception in get_stats: " . $e->getMessage());
        }
    }
    else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi GET tidak valid.']);
    }
} elseif ($method === 'POST') {
    // Periksa apakah ini permintaan untuk membuat pin baru dengan gambar yang sudah diunggah
    // create.html sekarang mengirimkan 'images' sebagai JSON string dalam FormData
    if ($action === 'create_pin_from_upload') { // Mengubah nama aksi untuk kejelasan
        checkLoggedIn();

        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $photoDescription = $_POST['photo_description'] ?? ''; // Ambil photo_description
        $categories_json = $_POST['categories'] ?? '[]'; 
        $categories = json_decode($categories_json, true);
        if (!is_array($categories)) {
            $categories = [];
        }

        $personTags_json = $_POST['personTags'] ?? '[]';
        $personTags = json_decode($personTags_json, true);
        if (!is_array($personTags)) {
            $personTags = [];
        }

        $uploadedBy = $_SESSION['username'] ?? 'unknown'; 
        $display_type = $_POST['display_type'] ?? 'stacked';
        $pin_level = $_POST['pin_level'] ?? 'tempted'; // Ambil level pin dari input

        // Ambil data gambar yang sudah diunggah dari POST (ini adalah JSON string)
        // Ini sekarang akan berisi url_original dan url_blur
        $images_json = $_POST['images'] ?? '[]';
        $uploaded_image_data = json_decode($images_json, true);
        if (!is_array($uploaded_image_data)) {
            $uploaded_image_data = [];
        }

        // Jika ada deskripsi gambar individual, gabungkan
        $image_descriptions = [];
        if (isset($_POST['image_descriptions'])) {
            $image_descriptions = json_decode($_POST['image_descriptions'], true);
            if (!is_array($image_descriptions)) {
                $image_descriptions = [];
            }
        }
        
        // Gabungkan deskripsi ke dalam data gambar yang diunggah
        // Asumsi urutan deskripsi sesuai dengan urutan gambar yang diunggah
        foreach ($uploaded_image_data as $key => $imageData) {
            if (isset($image_descriptions[$key])) {
                $uploaded_image_data[$key]['description'] = $image_descriptions[$key];
            }
        }


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
            'description' => $description,
            'photo_description' => $photoDescription, // Simpan photo_description
            'images' => $uploaded_image_data, // Ini sudah berisi url_original dan url_blur
            'display_type' => $display_type,
            'categories' => $categories,
            'personTags' => $personTags,
            'uploadedBy' => $uploadedBy,
            'created_at' => date('Y-m-d H:i:s'),
            'level' => $pin_level // Simpan level pin
        ];

        $pins[] = $newPin;
        if (saveAllPins($pins)) {
            echo json_encode(['success' => true, 'message' => 'Pin berhasil ditambahkan.', 'pin_id' => $newPinId, 'pin' => $newPin]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pin. Periksa izin folder data.']);
        }

    } elseif ($action === 'add') { // Ini adalah aksi 'add' yang lama, mungkin dari sumber lain
        $input = getJsonInput();

        if ($input === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Format input JSON tidak valid.']);
            exit;
        }

        checkLoggedIn();
        $imageUrl = $input['imageUrl'] ?? '';
        $source = $input['source'] ?? '';
        $title = $input['title'] ?? ''; 
        $content = $input['content'] ?? '';
        $categories_input = $input['categories'] ?? []; 
        $categories = is_array($categories_input) ? $categories_input : [$categories_input];
        $personTags_input = $input['personTags'] ?? []; 
        $personTags = is_array($personTags_input) ? $personTags_input : [$personTags_input];
        $pin_level = $input['level'] ?? 'tempted'; // Ambil level pin dari input

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
            'description' => $content,
            'images' => [['url_original' => $imageUrl, 'url_blur' => $imageUrl, 'description' => '']], // For old 'add' action, use same URL for both
            'display_type' => 'stacked',
            'categories' => $categories,
            'personTags' => $personTags,
            'uploadedBy' => $_SESSION['username'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s'),
            'level' => $pin_level // Simpan level pin
        ];
        $pins[] = $newPin;
        if (saveAllPins($pins)) {
            echo json_encode(['success' => true, 'message' => 'Pin berhasil ditambahkan.', 'pin' => $newPin]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pin. Periksa izin folder data.']);
        }

    } elseif ($action === 'delete_pin') { // Aksi untuk admin menghapus pin
        checkAdmin();
        $input = getJsonInput();
        error_log("DEBUG: Input for delete_pin (admin): " . json_encode($input)); // Debug log
        $pinId = $input['pinId'] ?? null;

        if (empty($pinId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk penghapusan.']);
            exit;
        }

        $pins = getAllPins();
        $pinToDelete = null;

        foreach ($pins as $key => $pin) {
            if ($pin['id'] === $pinId) {
                $pinToDelete = $pin;
                unset($pins[$key]);
                break;
            }
        }

        if ($pinToDelete === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pin tidak ditemukan.']);
            exit;
        }

        // Hapus file gambar terkait (asli dan blur)
        if (isset($pinToDelete['images']) && is_array($pinToDelete['images'])) {
            foreach ($pinToDelete['images'] as $image) {
                if (isset($image['url_original'])) {
                    $filePathOriginal = str_replace('./uploads/pins/', '../uploads/pins/', $image['url_original']);
                    if (file_exists($filePathOriginal) && is_file($filePathOriginal)) {
                        unlink($filePathOriginal);
                    }
                }
                if (isset($image['url_blur'])) {
                    $filePathBlur = str_replace('./uploads/blur-pins/', '../uploads/blur-pins/', $image['url_blur']);
                    if (file_exists($filePathBlur) && is_file($filePathBlur)) {
                        unlink($filePathBlur);
                    }
                }
            }
        }

        if (!saveAllPins(array_values($pins))) {
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Gagal menghapus pin. Periksa izin folder data.']);
             exit;
        }

        // Hapus pin dari daftar tersimpan pengguna
        $users = getUsersForPinsApi();
        foreach ($users as $userKey => $user) {
            $userSavedPinsFile = $savedPinsDir . $user['username'] . '.json';
            if (file_exists($userSavedPinsFile)) {
                $savedPins = readJsonFile($userSavedPinsFile);
                $updatedSavedPins = array_filter($savedPins, function($id) use ($pinId) {
                    return $id !== $pinId;
                });
                writeJsonFile($userSavedPinsFile, array_values($updatedSavedPins));
            }
            // Hapus pin dari daftar liked_pins pengguna
            if (isset($user['liked_pins']) && is_array($user['liked_pins'])) {
                $updatedLikedPins = array_filter($user['liked_pins'], function($id) use ($pinId) {
                    return $id !== $pinId;
                });
                $users[$userKey]['liked_pins'] = array_values($updatedLikedPins);
            }
        }
        saveUsersForPinsApi($users); // Simpan perubahan pada users.json

        echo json_encode(['success' => true, 'message' => 'Pin berhasil dihapus.']);

    } elseif ($action === 'delete_user_pin') { // Aksi untuk pengguna menghapus pin mereka sendiri
        checkLoggedIn(); // Pastikan pengguna login
        $input = getJsonInput();
        error_log("DEBUG: Input for delete_user_pin: " . json_encode($input)); // Debug log
        $pinId = $input['pinId'] ?? null;
        $loggedInUsername = $_SESSION['username'];

        if (empty($pinId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk penghapusan.']);
            exit;
        }

        $pins = getAllPins();
        $pinToDelete = null;
        $pinKey = -1;

        foreach ($pins as $key => $pin) {
            if ($pin['id'] === $pinId) {
                $pinToDelete = $pin;
                $pinKey = $key;
                break;
            }
        }

        if ($pinToDelete === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pin tidak ditemukan.']);
            exit;
        }

        // VERIFIKASI KEPEMILIKAN PIN
        if (($pinToDelete['uploadedBy'] ?? 'system') !== $loggedInUsername) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin untuk menghapus pin ini.']);
            exit;
        }

        // Hapus pin dari array
        unset($pins[$pinKey]);

        // Hapus file gambar terkait (asli dan blur)
        if (isset($pinToDelete['images']) && is_array($pinToDelete['images'])) {
            foreach ($pinToDelete['images'] as $image) {
                if (isset($image['url_original'])) {
                    $filePathOriginal = str_replace('./uploads/pins/', '../uploads/pins/', $image['url_original']);
                    if (file_exists($filePathOriginal) && is_file($filePathOriginal)) {
                        unlink($filePathOriginal);
                    }
                }
                if (isset($image['url_blur'])) {
                    $filePathBlur = str_replace('./uploads/blur-pins/', '../uploads/blur-pins/', $image['url_blur']);
                    if (file_exists($filePathBlur) && is_file($filePathBlur)) {
                        unlink($filePathBlur);
                    }
                }
            }
        }

        if (!saveAllPins(array_values($pins))) {
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Gagal menghapus pin. Periksa izin folder data.']);
             exit;
        }

        // Hapus pin dari daftar tersimpan pengguna (untuk semua pengguna)
        $users = getUsersForPinsApi();
        foreach ($users as $userKey => $user) {
            $userSavedPinsFile = $savedPinsDir . $user['username'] . '.json';
            if (file_exists($userSavedPinsFile)) {
                $savedPins = readJsonFile($userSavedPinsFile);
                $updatedSavedPins = array_filter($savedPins, function($id) use ($pinId) {
                    return $id !== $pinId;
                });
                writeJsonFile($userSavedPinsFile, array_values($updatedSavedPins));
            }
            // Hapus pin dari daftar liked_pins pengguna (untuk semua pengguna)
            if (isset($user['liked_pins']) && is_array($user['liked_pins'])) {
                $updatedLikedPins = array_filter($user['liked_pins'], function($id) use ($pinId) {
                    return $id !== $pinId;
                });
                $users[$userKey]['liked_pins'] = array_values($updatedLikedPins);
            }
        }
        saveUsersForPinsApi($users); // Simpan perubahan pada users.json

        echo json_encode(['success' => true, 'message' => 'Pin berhasil dihapus.']);

    } elseif ($action === 'delete_user') {
        checkAdmin();
        $input = getJsonInput();
        error_log("DEBUG: Input for delete_user: " . json_encode($input)); // Debug log
        $usernameToDelete = $input['username'] ?? null;

        if (empty($usernameToDelete)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama pengguna diperlukan untuk penghapusan.']);
            exit;
        }

        $users = getUsersForPinsApi();
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

        $pins = getAllPins();
        $updatedPins = array_filter($pins, function($pin) use ($usernameToDelete) {
            // Hapus pin yang diunggah oleh pengguna yang dihapus
            if (($pin['uploadedBy'] ?? 'unknown') === $usernameToDelete) {
                if (isset($pin['images']) && is_array($pin['images'])) {
                    foreach ($pin['images'] as $image) {
                        if (isset($image['url_original'])) {
                            $filePathOriginal = str_replace('./uploads/pins/', '../uploads/pins/', $image['url_original']);
                            if (file_exists($filePathOriginal) && is_file($filePathOriginal)) {
                                unlink($filePathOriginal);
                            }
                        }
                        if (isset($image['url_blur'])) {
                            $filePathBlur = str_replace('./uploads/blur-pins/', '../uploads/blur-pins/', $image['url_blur']);
                            if (file_exists($filePathBlur) && is_file($filePathBlur)) {
                                unlink($filePathBlur);
                            }
                        }
                    }
                }
                return false;
            }
            return true;
        });
        saveAllPins(array_values($updatedPins));


        echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dihapus.']);

    } elseif ($action === 'save') {
        checkLoggedIn();
        $input = getJsonInput();
        error_log("DEBUG: Input for save: " . json_encode($input));
        $username = $_SESSION['username'];
        $pinId = $input['pinId'] ?? null;

        if (empty($pinId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk menyimpan.']);
            exit;
        }

        $userSavedPinsFile = $savedPinsDir . $username . '.json';
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
    } elseif ($action === 'unsave') {
        checkLoggedIn();
        $input = getJsonInput();
        error_log("DEBUG: Input for unsave: " . json_encode($input));
        $username = $_SESSION['username'];
        $pinId = $input['pinId'] ?? null;

        if (empty($pinId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk menghapus dari daftar simpan.']);
            exit;
        }

        $userSavedPinsFile = $savedPinsDir . $username . '.json';
        if (!file_exists($userSavedPinsFile)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Tidak ada pin yang disimpan untuk pengguna ini.']);
            exit;
        }

        $savedPins = readJsonFile($userSavedPinsFile);
        $initialCount = count($savedPins);
        $updatedSavedPins = array_filter($savedPins, function($id) use ($pinId) {
            return $id !== $pinId;
        });

        if (count($updatedSavedPins) === $initialCount) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pin tidak ditemukan di daftar simpan Anda.']);
            exit;
        }
        
        if (writeJsonFile($userSavedPinsFile, array_values($updatedSavedPins))) {
            echo json_encode(['success' => true, 'message' => 'Pin berhasil dihapus dari daftar simpan.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus pin dari daftar simpan. Periksa izin folder data.']);
        }
    } elseif ($action === 'toggle_like_pin') { // NEW: Action to toggle like status
        checkLoggedIn();
        $input = getJsonInput();
        error_log("DEBUG: Input for toggle_like_pin: " . json_encode($input)); // Debug log
        $username = $_SESSION['username'];
        $pinId = $input['pinId'] ?? null;

        if (empty($pinId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Pin diperlukan untuk menyukai/tidak menyukai.']);
            exit;
        }

        $users = getUsersForPinsApi();
        $userFound = false;
        $likedStatus = false;

        foreach ($users as $key => $user) {
            if ($user['username'] === $username) {
                $userFound = true;
                if (!isset($users[$key]['liked_pins'])) {
                    $users[$key]['liked_pins'] = [];
                }

                $index = array_search($pinId, $users[$key]['liked_pins']);
                if ($index !== false) {
                    // Pin sudah disukai, hapus
                    array_splice($users[$key]['liked_pins'], $index, 1);
                    $likedStatus = false;
                } else {
                    // Pin belum disukai, tambahkan
                    $users[$key]['liked_pins'][] = $pinId;
                    $likedStatus = true;
                }
                break;
            }
        }

        if (!$userFound) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
            exit;
        }

        if (saveUsersForPinsApi($users)) {
            echo json_encode(['success' => true, 'message' => 'Status like pin berhasil diperbarui.', 'liked_status' => $likedStatus]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status like pin. Periksa izin folder data.']);
        }
    } else {
        // Ini adalah blok catch-all untuk aksi POST yang tidak dikenal.
        // Jika ada aksi POST yang valid, harus ditangani di atas.
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi POST tidak valid.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
