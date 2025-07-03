<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
// PASTIKAN FILE INI DI-ENCODE SEBAGAI UTF-8 TANPA BOM

function readJsonFile($filePath) {
    if (!file_exists($filePath)) {
        $dir = dirname($filePath);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents($filePath, json_encode([]));
        return [];
    }
    $content = file_get_contents($filePath);
    if ($content === false) {
        error_log("ERROR: Failed to read file: " . $filePath . " - Check permissions.");
        return [];
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("ERROR: JSON Decode Error (" . json_last_error_msg() . ") in file: " . $filePath . " - File might be corrupted. Attempting to reset.");
        file_put_contents($filePath, json_encode([]));
        return [];
    }
    return $data;
}

function writeJsonFile($filePath, $data) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true)) {
            error_log("ERROR: Failed to create directory: " . $dir . " - Check permissions.");
            return false;
        }
    }
    if (file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        error_log("ERROR: Failed to write to file: " . $filePath . " - Check permissions.");
        return false;
    }
    return true;
}

// Fungsi tambahan untuk memproses input JSON dari POST requests
function getJsonInput() {
    $rawInput = file_get_contents('php://input');
    // Jika tidak ada raw input, atau raw input string "null", kembalikan null
    if (empty($rawInput) || $rawInput === 'null') {
        return null;
    }
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("ERROR: Invalid JSON input received: " . json_last_error_msg() . " Raw input: " . $rawInput);
        return false; // Mengembalikan false jika ada error parsing
    }
    return $input;
}

// JANGAN ADA KARAKTER APAPUN SETELAH BARIS INI