<?php
// ENSURE NO CHARACTERS BEFORE THIS LINE
// ENSURE THIS FILE IS ENCODED AS UTF-8 WITHOUT BOM
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Include utility file

$usersFile = '../data/users.json';

// Helper functions using utilities
function getUsers() {
    global $usersFile;
    return readJsonFile($usersFile);
}

function saveUsers($users) {
    global $usersFile;
    return writeJsonFile($usersFile, $users);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = getJsonInput(); // Use helper function for JSON input

    // Input may be null (no body) or false (invalid JSON)
    // If action is logout, null input is allowed.
    if ($input === false) { // Invalid JSON
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input format.']);
        exit;
    }

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $email = $input['email'] ?? ''; // New: Get email from input

    if ($action === 'register') {
        if ($input === null) { // Ensure input exists for registration
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Registration data is required.']);
            exit;
        }

        $users = getUsers();
        if (empty($username) || empty($email) || empty($password)) { // New: Check for email
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username, email, and password are required.']);
            exit;
        }
        
        foreach ($users as $user) {
            if (strtolower($user['username']) === strtolower($username)) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'Username already exists. Please choose another.']);
                exit;
            }
            // New: Check for duplicate email
            if (isset($user['email']) && strtolower($user['email']) === strtolower($email)) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'Email address already registered.']);
                exit;
            }
        }

        // New: Include email in the user data
        $users[] = ['username' => $username, 'email' => $email, 'password' => $password, 'isAdmin' => false];
        if (saveUsers($users)) {
            echo json_encode(['success' => true, 'message' => 'Registration successful.']);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Failed to save user data. Check data folder permissions.']);
        }

    } elseif ($action === 'login') {
        if ($input === null) { // Ensure input exists for login
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Login data is required.']);
            exit;
        }

        $users = getUsers();
        $userFound = false;
        $isAdmin = false;
        $loggedInUsername = '';
        $loggedInEmail = ''; // New: Variable for logged-in email

        foreach ($users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                $userFound = true;
                $isAdmin = $user['isAdmin'] ?? false;
                $loggedInUsername = $user['username'];
                $loggedInEmail = $user['email'] ?? ''; // New: Get email if exists
                break;
            }
        }

        if ($userFound) {
            $_SESSION['username'] = $loggedInUsername;
            $_SESSION['isAdmin'] = $isAdmin;
            $_SESSION['email'] = $loggedInEmail; // New: Store email in session
            echo json_encode(['success' => true, 'message' => 'Login successful.', 'user' => ['username' => $loggedInUsername, 'isAdmin' => $isAdmin, 'email' => $loggedInEmail]]); // New: Return email in user data
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'Incorrect username or password.']);
        }
    } elseif ($action === 'logout') {
        // For logout, JSON input is not required, so $input can be null
        session_unset();
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logout successful.']);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }

} elseif ($method === 'GET' && $action === 'check_session') {
    if (isset($_SESSION['username'])) {
        echo json_encode(['success' => true, 'user' => ['username' => $_SESSION['username'], 'isAdmin' => $_SESSION['isAdmin'], 'email' => $_SESSION['email'] ?? '']]); // New: Return email
    } else {
        echo json_encode(['success' => false, 'message' => 'No active session.']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method or action.']);
}
// NO CHARACTERS AFTER THIS LINE
?>
