<?php
session_start();
$pins = json_decode(file_get_contents(__DIR__ . '/data/pins.json'), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Spicette</title>
<style>
body {font-family: Arial, sans-serif;background:#f0f0f0;margin:0;padding:0;}
header {background:#ef5350;color:#fff;padding:1rem;text-align:center;}
.masonry {column-count:4;column-gap:1em;padding:1em;}
.item {background:white;margin:0 0 1em;display:inline-block;width:100%;border-radius:8px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.3);}
.item img {width:100%;display:block;}
@media (max-width:1000px){.masonry{column-count:3;}}
@media (max-width:700px){.masonry{column-count:2;}}
@media (max-width:400px){.masonry{column-count:1;}}
</style>
</head>
<body>
<header>
<h1>Welcome to Spicette</h1>
<?php if(isset($_SESSION['username'])): ?>
<p>Logged in as <?= htmlspecialchars($_SESSION['username']) ?> | <a href="logout.php" style="color:white;">Logout</a> <?php if(!empty($_SESSION['is_admin'])): ?>| <a href="admin.php" style="color:white;">Admin</a><?php endif; ?></p>
<?php else: ?>
<p><a href="login.php" style="color:white;">Login</a> or <a href="register.php" style="color:white;">Register</a></p>
<?php endif; ?>
</header>
<div class="masonry">
<?php foreach($pins as $p): ?>
<div class="item"><img src="<?= htmlspecialchars($p['img']) ?>" alt="Image"></div>
<?php endforeach; ?>
</div>
</body>
</html>
