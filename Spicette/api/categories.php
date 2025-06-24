<?php
// ENSURE NO CHARACTERS BEFORE THIS LINE
// ENSURE THIS FILE IS ENCODED AS UTF-8 WITHOUT BOM
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Include utility file

$categoriesFile = '../data/categories.json'; // Path to categories file

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
    $input = getJsonInput();

    if ($input === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input format.']);
        exit;
    }

    if ($action === 'add') {
        checkAdmin();
        $name = $input['name'] ?? '';
        $imageUrl = $input['imageUrl'] ?? ''; // Get new imageUrl
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Category name cannot be empty.']);
            exit;
        }

        $categories = readCategories();
        foreach ($categories as $cat) {
            if (strtolower($cat['name']) === strtolower($name)) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Category already exists.']);
                exit;
            }
        }

        $categories[] = ['name' => $name, 'imageUrl' => $imageUrl]; // Save as object
        if (saveCategories($categories)) {
            echo json_encode(['success' => true, 'message' => 'Category added successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save category. Check data folder permissions.']);
        }
    } elseif ($action === 'delete') {
        checkAdmin();
        $nameToDelete = $input['name'] ?? '';
        if (empty($nameToDelete)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Category name is required for deletion.']);
            exit;
        }

        $categories = readCategories();
        $initialCount = count($categories);
        $updatedCategories = array_filter($categories, function($cat) use ($nameToDelete) {
            return $cat['name'] !== $nameToDelete; // Compare with 'name' property
        });

        if (count($updatedCategories) === $initialCount) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Category not found.']);
            exit;
        }

        if (saveCategories(array_values($updatedCategories))) {
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete category. Check data folder permissions.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid POST action.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
// NO CHARACTERS AFTER THIS LINE
?>