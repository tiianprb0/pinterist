<?php
session_start();
if (!isset($_SESSION['username']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}
$pinsFile = __DIR__ . '/data/pins.json';
$pins = json_decode(file_get_contents($pinsFile), true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete'];
        $pins = array_filter($pins, function($p) use ($id) { return $p['id'] !== $id; });
        file_put_contents($pinsFile, json_encode(array_values($pins), JSON_PRETTY_PRINT));
    } elseif (isset($_POST['img'], $_POST['source'])) {
        $new = ['id' => count($pins) ? max(array_column($pins, 'id')) + 1 : 1,
                'img' => $_POST['img'], 'source' => $_POST['source']];
        $pins[] = $new;
        file_put_contents($pinsFile, json_encode($pins, JSON_PRETTY_PRINT));
    }
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Spicette</title>
</head>
<body>
<h2>Admin Panel</h2>
<p><a href="index.php">Home</a> | <a href="logout.php">Logout</a></p>
<h3>Add Pin</h3>
<form method="post">
    <input type="text" name="img" placeholder="Image URL" required>
    <input type="text" name="source" placeholder="Source" required>
    <button type="submit">Add</button>
</form>
<h3>Existing Pins</h3>
<table border="1" cellpadding="5">
<tr><th>ID</th><th>Image</th><th>Source</th><th>Action</th></tr>
<?php foreach ($pins as $p): ?>
<tr>
<td><?= $p['id'] ?></td>
<td><img src="<?= htmlspecialchars($p['img']) ?>" width="100"></td>
<td><?= htmlspecialchars($p['source']) ?></td>
<td>
    <form method="post" style="display:inline">
        <button type="submit" name="delete" value="<?= $p['id'] ?>">Delete</button>
    </form>
</td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
