<?php
// ENSURE NO CHARACTERS BEFORE THIS LINE
// ENSURE THIS FILE IS ENCODED AS UTF-8 WITHOUT BOM
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Include utility file

$categoriesFile = '../data/categories.json'; // Path to categories file
$categoryUploadDir = '../uploads/categories/'; // NEW: Directory for category image uploads

// Helper functions using utilities
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
        echo json_encode(['success' => false, 'message' => 'Access denied: Administrator privileges required.']);
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
        echo json_encode(['success' => false, 'message' => 'Invalid GET action.']);
    }
} elseif ($method === 'POST') {
    // Check if the request is multipart/form-data (for file upload)
    if (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        checkAdmin();

        $name = $_POST['name'] ?? '';
        $imageUrl = ''; // Default empty

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Category name cannot be empty.']);
            exit;
        }

        // Handle file upload if present
        if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['imageFile']['tmp_name'];
            $file_name = $_FILES['imageFile']['name'];
            $file_size = $_FILES['imageFile']['size'];
            $file_type = $_FILES['imageFile']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_ext = ['jpeg', 'jpg', 'png', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_ext, $allowed_ext)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF are allowed.']);
                exit;
            }
            if ($file_size > $max_size) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
                exit;
            }

            if (!is_dir($categoryUploadDir)) {
                mkdir($categoryUploadDir, 0777, true); // Create directory if it doesn't exist
            }

            $new_file_name = uniqid('cat_') . '.' . $file_ext;
            $destination = $categoryUploadDir . $new_file_name;
            $web_path = './uploads/categories/' . $new_file_name; // Path relative to Spicette/

            if (move_uploaded_file($file_tmp, $destination)) {
                $imageUrl = $web_path;
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
                exit;
            }
        }

        $categories = readCategories();
        foreach ($categories as $cat) {
            if (strtolower($cat['name']) === strtolower($name)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Category already exists.']);
                exit;
            }
        }

        $categories[] = ['name' => $name, 'imageUrl' => $imageUrl]; // Save with uploaded image URL
        if (saveCategories($categories)) {
            echo json_encode(['success' => true, 'message' => 'Category added successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save category. Check data folder permissions.']);
        }

    } else { // Handle JSON input for other POST actions
        $input = getJsonInput();

        if ($input === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON input format.']);
            exit;
        }

        if ($action === 'delete') {
            checkAdmin();
            $nameToDelete = $input['name'] ?? '';
            if (empty($nameToDelete)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Category name is required for deletion.']);
                exit;
            }

            $categories = readCategories();
            $initialCount = count($categories);
            $categoryToDelete = null;

            foreach ($categories as $key => $cat) {
                if ($cat['name'] === $nameToDelete) {
                    $categoryToDelete = $cat;
                    unset($categories[$key]);
                    break;
                }
            }

            if ($categoryToDelete === null) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Category not found.']);
                exit;
            }

            // Delete associated image file if it exists
            if (!empty($categoryToDelete['imageUrl'])) {
                $filePath = str_replace('./uploads/categories/', '../uploads/categories/', $categoryToDelete['imageUrl']);
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                }
            }

            if (saveCategories(array_values($categories))) {
                echo json_encode(['success' => true, 'message' => 'Category deleted successfully.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete category. Check data folder permissions.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid POST action.']);
        }
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
// NO CHARACTERS AFTER THIS LINE
?>
