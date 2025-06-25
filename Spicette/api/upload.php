<?php
// Spicette/api/upload.php

// Pastikan hanya permintaan POST yang diterima
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak diizinkan.']);
    exit();
}

// Direktori tempat gambar akan disimpan
// Pastikan direktori ini ada dan dapat ditulis oleh server web!
// Path ini relatif dari api/upload.php ke Spicette/uploads/pins/
$upload_dir = '../uploads/pins/'; 

// Buat direktori jika belum ada
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Buat direktori secara rekursif dengan izin penuh (pastikan izin aman di lingkungan produksi)
}

$uploaded_files = [];
$errors = [];

// Periksa apakah ada file yang diunggah
if (!empty($_FILES['images']['name'][0])) {
    $total_files = count($_FILES['images']['name']);

    for ($i = 0; $i < $total_files; $i++) {
        $file_name = $_FILES['images']['name'][$i];
        $file_tmp = $_FILES['images']['tmp_name'][$i];
        $file_size = $_FILES['images']['size'][$i];
        $file_error = $_FILES['images']['error'][$i];
        $file_type = $_FILES['images']['type'][$i];

        // Dapatkan ekstensi file
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Ekstensi yang diizinkan
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            if ($file_error === 0) {
                // Buat nama file unik untuk mencegah timpa file
                $new_file_name = uniqid('pin_') . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;

                // Pindahkan file dari direktori sementara ke direktori tujuan
                if (move_uploaded_file($file_tmp, $destination)) {
                    // Simpan jalur relatif yang dapat diakses oleh browser
                    // Path ini harus relatif dari root web server Anda
                    // Asumsikan Spicette berada di root web server atau sub-direktori yang konsisten
                    $web_path = './uploads/pins/' . $new_file_name; // Path relatif dari Spicette/
                    $uploaded_files[] = $web_path;
                } else {
                    $errors[] = "Gagal memindahkan file {$file_name}.";
                }
            } else {
                $errors[] = "Kesalahan unggah untuk {$file_name}: " . $file_error;
            }
        } else {
            $errors[] = "Tipe file tidak diizinkan untuk {$file_name}. Hanya JPG, JPEG, PNG, GIF yang diizinkan.";
        }
    }
} else {
    $errors[] = 'Tidak ada file yang diunggah.';
}

if (!empty($uploaded_files) && empty($errors)) {
    echo json_encode(['success' => true, 'files' => $uploaded_files]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat mengunggah file.', 'errors' => $errors]);
}
?>
