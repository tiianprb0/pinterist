<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
header('Content-Type: application/json');
session_start();

require_once 'utils.php'; // Sertakan file utilitas

$pinsFile = '../data/pins.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pins = readJsonFile($pinsFile);
    $persons = [];

    if ($pins !== false) {
        foreach ($pins as $pin) {
            // Only add personTags to persons list
            if (isset($pin['personTags']) && is_array($pin['personTags'])) {
                foreach ($pin['personTags'] as $tag) {
                    if (!empty($tag)) {
                        $persons[] = $tag;
                    }
                }
            }
            // Add 'people' field if it exists (from older data structure)
            if (isset($pin['people']) && is_array($pin['people'])) {
                foreach ($pin['people'] as $person) {
                    if (!empty($person)) {
                        $persons[] = $person;
                    }
                }
            }
        }
        // Remove duplicates and re-index
        $persons = array_values(array_unique($persons));
        sort($persons); // Sort alphabetically for consistent display
        
        echo json_encode(['success' => true, 'persons' => $persons]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memuat pin untuk daftar orang.']);
    }
} else {
    http_response_code(405); // Metode Tidak Diizinkan
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
