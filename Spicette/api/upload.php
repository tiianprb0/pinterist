<?php
// Spicette/api/upload.php

// Pastikan hanya permintaan POST yang diterima
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak diizinkan.']);
    exit();
}

// --- DEBUGGING START ---
error_log("[DEBUG UPLOAD] Request received. Method: " . $_SERVER['REQUEST_METHOD']);
error_log("[DEBUG UPLOAD] Content-Length: " . (isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : 'N/A'));
error_log("[DEBUG UPLOAD] upload_max_filesize: " . ini_get('upload_max_filesize'));
error_log("[DEBUG UPLOAD] post_max_size: " . ini_get('post_max_size'));
error_log("[DEBUG UPLOAD] Current $_FILES content: " . print_r($_FILES, true));
error_log("[DEBUG UPLOAD] Current $_POST content: " . print_r($_POST, true)); // FormData might also put non-file fields here
// --- DEBUGGING END ---


// Periksa apakah ekstensi GD dimuat
if (!extension_loaded('gd')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'GD Library PHP TIDAK DITEMUKAN atau tidak dimuat. Kompresi gambar tidak dapat dilakukan. Pastikan gd.so atau php_gd2.dll diaktifkan di php.ini Anda.']);
    exit();
}

// Direktori tempat gambar akan disimpan
// Pastikan direktori ini ada dan dapat ditulis oleh server web!
// Path ini relatif dari api/upload.php ke Spicette/uploads/pins/
$upload_dir = '../uploads/pins/'; 

// Buat direktori jika belum ada
if (!is_dir($upload_dir)) {
    // Coba buat direktori secara rekursif dengan izin penuh.
    // Di lingkungan produksi, pastikan izin ini aman (misalnya 0755).
    if (!mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
        error_log("[ERROR UPLOAD] Gagal membuat direktori unggahan: {$upload_dir}. Periksa izin server.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal membuat direktori unggahan. Periksa izin server.']);
        exit();
    }
}

$uploaded_files = [];
$errors = [];

/**
 * Fungsi untuk mengkompresi gambar dan menyimpannya.
 * Mendukung format JPEG dan PNG.
 *
 * @param string $source_path Jalur file gambar asli (sementara).
 * @param string $destination_path Jalur tujuan untuk menyimpan gambar yang dikompresi.
 * @param string $file_type Tipe MIME file (misalnya 'image/jpeg', 'image/png').
 * @param int $quality Kualitas kompresi (0-100 untuk JPEG, 0-9 untuk PNG).
 * @return bool True jika kompresi berhasil, false jika gagal.
 */
function compressImage($source_path, $destination_path, $file_type, $quality) {
    // Pastikan file sementara ada sebelum mencoba memprosesnya
    if (!file_exists($source_path) || !is_readable($source_path)) {
        error_log("[ERROR UPLOAD] compressImage: File sumber sementara tidak ditemukan atau tidak dapat dibaca: {$source_path}");
        return false;
    }

    $info = getimagesize($source_path);
    $image = null;
    $result = false;

    if ($info === false) {
        error_log("[ERROR UPLOAD] compressImage: Gagal mendapatkan info gambar dari {$source_path}. File mungkin rusak atau bukan gambar.");
        return false;
    }

    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source_path);
            if ($image === false) {
                error_log("[ERROR UPLOAD] compressImage: Gagal membuat gambar dari JPEG {$source_path}. Memori mungkin habis atau file rusak.");
                return false;
            }
            $result = imagejpeg($image, $destination_path, $quality);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source_path);
            if ($image === false) {
                error_log("[ERROR UPLOAD] compressImage: Gagal membuat gambar dari PNG {$source_path}. Memori mungkin habis atau file rusak.");
                return false;
            }
            // Konversi kualitas 0-100 ke 0-9 untuk PNG (terbalik)
            // 0 = tanpa kompresi (ukuran besar), 9 = kompresi maksimal (ukuran kecil)
            $png_quality = 9 - (int)round(($quality / 100) * 9);
            imagesavealpha($image, true); // Pertahankan transparansi PNG
            $result = imagepng($image, $destination_path, $png_quality);
            break;
        case 'image/gif':
            // GIF tidak dikompresi dengan cara yang sama, cukup salin
            $result = copy($source_path, $destination_path);
            break;
        default:
            error_log("[ERROR UPLOAD] compressImage: Tipe MIME tidak didukung untuk kompresi GD: {$info['mime']}. Hanya JPEG, PNG, GIF yang didukung.");
            return false;
    }

    if ($image !== null) {
        imagedestroy($image); // Bebaskan memori
    }
    
    // Setel izin file setelah disimpan untuk memastikan dapat dibaca oleh web server
    if ($result && file_exists($destination_path)) {
        chmod($destination_path, 0644); // Izin baca/tulis untuk pemilik, baca saja untuk grup/lainnya
    }

    return $result;
}


// Periksa apakah ada file yang diunggah
if (!empty($_FILES['images']['name'][0])) {
    $total_files = count($_FILES['images']['name']);

    for ($i = 0; $i < $total_files; $i++) {
        try {
            $file_name = $_FILES['images']['name'][$i];
            $file_tmp = $_FILES['images']['tmp_name'][$i]; // Jalur file sementara di server
            $file_size = $_FILES['images']['size'][$i];
            $file_error = $_FILES['images']['error'][$i];
            $file_type = $_FILES['images']['type'][$i];

            // Dapatkan ekstensi file
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Ekstensi yang diizinkan
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_ext)) {
                if ($file_error === 0) {
                    // Buat nama file unik dengan awalan 'raw_'
                    $new_file_name = uniqid('raw_') . '.' . $file_ext;
                    $destination = $upload_dir . $new_file_name;

                    // Tentukan kualitas kompresi (misalnya 65 untuk JPEG, atau 6 untuk PNG)
                    // Nilai yang lebih rendah akan menghasilkan kompresi yang lebih agresif (ukuran file lebih kecil)
                    $compression_quality = 75; 

                    // Kompresi dan pindahkan file
                    if (compressImage($file_tmp, $destination, $file_type, $compression_quality)) {
                        // Simpan jalur relatif yang dapat diakses oleh browser
                        // Path ini harus relatif dari root web server Anda
                        // Asumsikan Spicette berada di root web server atau sub-direktori yang konsisten
                        $web_path = './uploads/pins/' . $new_file_name; // Path relatif dari Spicette/
                        
                        // Dapatkan ukuran file setelah kompresi
                        $compressed_file_size = filesize($destination); // Ukuran dalam byte
                        
                        $uploaded_files[] = [
                            'url' => $web_path,
                            'original_size_kb' => round($file_size / 1024, 2), // Ukuran asli dalam KB
                            'compressed_size_kb' => round($compressed_file_size / 1024, 2) // Ukuran terkompresi dalam KB
                        ];
                    } else {
                        $errors[] = "Gagal mengkompresi atau memindahkan file {$file_name}. Ini mungkin terjadi jika tipe gambar tidak didukung, masalah izin direktori, atau ada masalah dengan GD library. Lihat log server untuk detail.";
                        error_log("[ERROR UPLOAD] Gagal kompresi/pindah file: {$file_name}. Detail: " . (isset($php_errormsg) ? $php_errormsg : 'Tidak ada pesan error PHP spesifik.'));
                    }
                } else {
                    $error_message = "Kesalahan unggah untuk {$file_name}: " . $file_error . " (Kode Error PHP: " . $file_error . ").";
                    if ($file_error == UPLOAD_ERR_INI_SIZE || $file_error == UPLOAD_ERR_FORM_SIZE) {
                        $error_message .= " Pastikan ukuran file tidak melebihi batas upload_max_filesize dan post_max_size di php.ini.";
                    }
                    $errors[] = $error_message;
                    error_log("[ERROR UPLOAD] " . $error_message);
                }
            } else {
                $errors[] = "Tipe file tidak diizinkan untuk {$file_name}. Hanya JPG, JPEG, PNG, GIF yang diizinkan.";
                error_log("[ERROR UPLOAD] Tipe file tidak diizinkan: {$file_name} ({$file_type})");
            }
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan tak terduga saat memproses {$file_name}: " . $e->getMessage();
            error_log("[ERROR UPLOAD] Unhandled exception during file upload for {$file_name}: " . $e->getMessage() . " on line " . $e->getLine());
        }
    }
} else {
    $errors[] = 'Tidak ada file yang diunggah.';
    error_log("[ERROR UPLOAD] Tidak ada file yang diunggah dalam permintaan.");
}

if (!empty($uploaded_files) && empty($errors)) {
    echo json_encode(['success' => true, 'files' => $uploaded_files]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat mengunggah file.', 'errors' => $errors]);
}
?>
