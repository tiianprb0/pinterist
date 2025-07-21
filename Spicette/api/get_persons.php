<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$pinsFile = '../data/pins.json';
$manualPersonFile = '../data/manual_person.json'; // Path ke file manual_person.json

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $allPersons = [];

    // Ambil orang dari pins.json
    $pins = readJsonFile($pinsFile);
    if ($pins !== false) {
        foreach ($pins as $pin) {
            if (isset($pin['personTags']) && is_array($pin['personTags'])) {
                foreach ($pin['personTags'] as $tag) {
                    if (!empty($tag)) {
                        $allPersons[] = $tag;
                    }
                }
            }
            if (isset($pin['people']) && is_array($pin['people'])) { // Untuk kompatibilitas mundur
                foreach ($pin['people'] as $person) {
                    if (!empty($person)) {
                        $allPersons[] = $person;
                    }
                }
            }
        }
    }

    // Ambil orang dari manual_person.json
    $manualPersons = readJsonFile($manualPersonFile);
    if ($manualPersons !== false) {
        foreach ($manualPersons as $person) {
            if (!empty($person)) {
                $allPersons[] = $person;
            }
        }
    }

    // Hapus duplikat dan urutkan
    $allPersons = array_values(array_unique($allPersons));
    sort($allPersons); // Urutkan secara alfabetis untuk tampilan yang konsisten
    
    echo json_encode(['success' => true, 'persons' => $allPersons]);
} else {
    http_response_code(405); // Metode Tidak Diizinkan
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
