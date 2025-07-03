<?php
header('Content-Type: application/json');
session_start();

require_once 'utils.php';

$pinsFile = '../data/pins.json';
$usersFile = '../data/users.json';
$savedPinsDir = '../data/saved_pins/';
$uploadDir = '../uploads/pins/';

function getAllPins() {
    global $pinsFile;
    return readJsonFile($pinsFile);
}

function saveAllPins($pins) {
    global $pinsFile;
    return writeJsonFile($pinsFile, $pins);
}

function getUsersForPinsApi() { 
    global $usersFile;
    $users = readJsonFile($usersFile);
    foreach ($users as &$user) {
        if (!isset($user['canUpload'])) {
            $user['canUpload'] = false;
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
    if ($action === 'fetch_all') {
        echo json_encode(['success' => true, 'pins' => getAllPins()]);
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
        echo json_encode(['success' => true, 'pins' => array_values($savedPins)]);
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
        echo json_encode(['success' => true, 'pins' => array_values($userPins)]);
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
            // NEW: Search in personTags
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
        echo json_encode(['success' => true, 'pins' => array_values($filteredPins)]);
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
        echo json_encode(['success' => true, 'pins' => array_values($filteredPins)]);
    }
    else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi GET tidak valid.']);
    }
} elseif ($method === 'POST') {
    if (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        checkLoggedIn();

        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $categories_json = $_POST['categories'] ?? '[]'; 
        $categories = json_decode($categories_json, true);
        if (!is_array($categories)) {
            $categories = [];
        }

        // NEW: Get personTags as JSON stringified array and decode
        $personTags_json = $_POST['personTags'] ?? '[]';
        $personTags = json_decode($personTags_json, true);
        if (!is_array($personTags)) {
            $personTags = [];
        }

        $uploadedBy = $_SESSION['username'] ?? 'unknown'; 
        $display_type = $_POST['display_type'] ?? 'stacked';

        $image_descriptions = [];
        if (isset($_POST['image_descriptions'])) {
            $image_descriptions = json_decode($_POST['image_descriptions'], true);
            if (!is_array($image_descriptions)) {
                $image_descriptions = [];
            }
        }

        $uploaded_image_data = [];

        if (!empty($_FILES['images']['name'][0])) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $total_files = count($_FILES['images']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                $file_name = $_FILES['images']['name'][$i];
                $file_tmp = $_FILES['images']['tmp_name'][$i];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $new_file_name = uniqid('pin_') . '.' . $file_ext;
                $destination = $uploadDir . $new_file_name;
                $web_path = './uploads/pins/' . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    $img_desc = $image_descriptions[$i] ?? '';
                    $uploaded_image_data[] = ['url' => $web_path, 'description' => $img_desc];
                } else {
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
            'images' => $uploaded_image_data,
            'display_type' => $display_type,
            'categories' => $categories,
            'personTags' => $personTags, // NEW: Save personTags
            'uploadedBy' => $uploadedBy,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $pins[] = $newPin;
        if (saveAllPins($pins)) {
            echo json_encode(['success' => true, 'message' => 'Pin berhasil ditambahkan.', 'pin_id' => $newPinId, 'pin' => $newPin]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pin. Periksa izin folder data.']);
        }

    } else {
        $input = getJsonInput();

        if ($input === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Format input JSON tidak valid.']);
            exit;
        }

        if ($action === 'add') {
            checkLoggedIn();
            $imageUrl = $input['imageUrl'] ?? '';
            $source = $input['source'] ?? '';
            $title = $input['title'] ?? ''; 
            $content = $input['content'] ?? '';
            $categories_input = $input['categories'] ?? []; 
            $categories = is_array($categories_input) ? $categories_input : [$categories_input];
            $personTags_input = $input['personTags'] ?? []; // NEW: Get personTags for JSON input
            $personTags = is_array($personTags_input) ? $personTags_input : [$personTags_input]; // Ensure array

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
                'images' => [['url' => $imageUrl, 'description' => '']],
                'display_type' => 'stacked',
                'categories' => $categories,
                'personTags' => $personTags, // NEW: Save personTags
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

            if (isset($pinToDelete['images']) && is_array($pinToDelete['images'])) {
                foreach ($pinToDelete['images'] as $image) {
                    $filePath = str_replace('./uploads/pins/', '../uploads/pins/', $image['url']);
                    if (file_exists($filePath) && is_file($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            if (!saveAllPins(array_values($pins))) {
                 http_response_code(500);
                 echo json_encode(['success' => false, 'message' => 'Gagal menghapus pin. Periksa izin folder data.']);
                 exit;
            }

            $users = getUsersForPinsApi();
            foreach ($users as $user) {
                $userSavedPinsFile = $savedPinsDir . $user['username'] . '.json';
                if (file_exists($userSavedPinsFile)) {
                    $savedPins = readJsonFile($userSavedPinsFile);
                    $updatedSavedPins = array_filter($savedPins, function($id) use ($pinId) {
                        return $id !== $pinId;
                    });
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
                if (($pin['uploadedBy'] ?? 'unknown') === $usernameToDelete) {
                    if (isset($pin['images']) && is_array($pin['images'])) {
                        foreach ($pin['images'] as $image) {
                             $filePath = str_replace('./uploads/pins/', '../uploads/pins/', $image['url']);
                            if (file_exists($filePath) && is_file($filePath)) {
                                unlink($filePath);
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
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Aksi POST tidak valid.']);
        }
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
