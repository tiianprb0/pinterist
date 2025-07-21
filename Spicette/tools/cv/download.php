<?php
// Set header untuk mencegah caching
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Pastikan Content-Type untuk respons non-file tidak mengganggu
header('Content-Type: text/plain'); 

// --- Logika untuk menerima file yang di-upload (POST request) dan langsung stream ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gifFile'])) {
    $uploadedFile = $_FILES['gifFile'];

    // Basic validation
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        http_response_code(500);
        error_log("Upload error: " . $uploadedFile['error']); // Log error ke server
        die("Upload error: " . $uploadedFile['error']);
    }

    // Generate a random 3-digit number for the filename
    $random_digits = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
    // Template nama file: spicette.cv-666[random 3 angka].gif
    $filename = "spicette.cv-666{$random_digits}.gif";

    // Set headers for direct download
    header('Content-Type: application/octet-stream'); // MIME type generik untuk download biner
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . $uploadedFile['size']); // Ukuran file yang di-upload

    // Read the temporary uploaded file and stream it directly to output
    // This avoids saving the file permanently on the server
    if (is_uploaded_file($uploadedFile['tmp_name'])) {
        readfile($uploadedFile['tmp_name']);
        // PHP secara otomatis menghapus file sementara setelah eksekusi skrip untuk $_FILES['tmp_name'].
        // Tidak perlu unlink() eksplisit di sini.
    } else {
        http_response_code(500);
        error_log("Failed to read uploaded temporary file."); // Log error ke server
        die("Failed to read uploaded file.");
    }
    exit; // Terminate script after sending file
}

// Jika diakses tanpa POST request yang valid
http_response_code(400);
echo "Permintaan tidak valid. Endpoint ini mengharapkan POST request dengan file.";
?>
