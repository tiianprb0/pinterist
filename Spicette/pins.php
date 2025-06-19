<?php
header('Content-Type: application/json');
$pinsFile = __DIR__ . '/data/pins.json';
if (!file_exists($pinsFile)) {
    echo json_encode([]);
    exit;
}
$pins = file_get_contents($pinsFile);
if ($pins === false) {
    echo json_encode([]);
    exit;
}
// simply output
echo $pins;
