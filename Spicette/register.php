<?php
session_start();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Both fields are required';
    } else {
        $file = __DIR__ . '/data/users.json';
        $users = json_decode(file_get_contents($file), true);
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $error = 'Username already exists';
                break;
            }
        }
        if (!$error) {
            $users[] = ['username' => $username, 'password' => $password, 'isAdmin' => false];
            file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = false;
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - Spicette</title>
</head>
<body>
<h2>Register</h2>
<?php if ($error): ?>
<p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<form method="post">
    <label>Username: <input type="text" name="username" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <button type="submit">Register</button>
</form>
<p><a href="login.php">Login</a></p>
</body>
</html>
