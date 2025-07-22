<?php
// Spicette/api/upload.php

// Pastikan hanya permintaan POST yang diterima
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak diizinkan.']);
    exit();
}

// Periksa apakah ekstensi GD dimuat
if (!extension_loaded('gd')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'GD Library PHP TIDAK DITEMUKAN atau tidak dimuat. Kompresi gambar tidak dapat dilakukan. Pastikan gd.so atau php_gd2.dll diaktifkan di php.ini Anda.']);
    exit();
}

// Direktori tempat gambar asli akan disimpan
$upload_dir = '../uploads/pins/'; 
// Direktori tempat gambar blur akan disimpan
$blur_upload_dir = '../uploads/blur-pins/';

// Buat direktori jika belum ada
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
        error_log("[ERROR UPLOAD] Gagal membuat direktori unggahan: {$upload_dir}. Periksa izin server.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal membuat direktori unggahan. Periksa izin server.']);
        exit();
    }
}
if (!is_dir($blur_upload_dir)) {
    if (!mkdir($blur_upload_dir, 0777, true) && !is_dir($blur_upload_dir)) {
        error_log("[ERROR UPLOAD] Gagal membuat direktori unggahan blur: {$blur_upload_dir}. Periksa izin server.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal membuat direktori unggahan blur. Periksa izin server.']);
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

/**
 * Fungsi untuk membuat versi blur dari gambar.
 *
 * @param string $source_path Jalur file gambar asli.
 * @param string $destination_path Jalur tujuan untuk menyimpan gambar blur.
 * @param string $file_type Tipe MIME file.
 * @param int $blur_strength Kekuatan blur (misalnya 3).
 * @param int $quality Kualitas kompresi untuk gambar blur (lebih rendah untuk ukuran file lebih kecil).
 * @return bool True jika berhasil, false jika gagal.
 */
function createBlurredImage($source_path, $destination_path, $file_type, $blur_strength = 3, $quality = 30) {
    if (!file_exists($source_path) || !is_readable($source_path)) {
        error_log("[ERROR UPLOAD] createBlurredImage: File sumber tidak ditemukan atau tidak dapat dibaca: {$source_path}");
        return false;
    }

    $info = getimagesize($source_path);
    $image = null;
    $result = false;

    if ($info === false) {
        error_log("[ERROR UPLOAD] createBlurredImage: Gagal mendapatkan info gambar dari {$source_path}.");
        return false;
    }

    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source_path);
            imagesavealpha($image, true); // Pertahankan transparansi PNG
            break;
        case 'image/gif':
            // Untuk GIF, kita bisa mengonversi ke PNG/JPEG untuk blur, atau hanya menyalin jika tidak ingin blur
            // Untuk tujuan blur, lebih baik konversi ke format yang didukung GD blur filter
            $image = imagecreatefromgif($source_path);
            break;
        default:
            error_log("[ERROR UPLOAD] createBlurredImage: Tipe MIME tidak didukung untuk blur: {$info['mime']}.");
            return false;
    }

    if ($image === false) {
        error_log("[ERROR UPLOAD] createBlurredImage: Gagal membuat gambar dari {$source_path}.");
        return false;
    }

    // Terapkan efek blur
    for ($x = 1; $x <= $blur_strength; $x++) {
        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
    }

    // Simpan gambar blur dengan kualitas kompresi yang lebih kuat
    switch ($info['mime']) {
        case 'image/jpeg':
            $result = imagejpeg($image, $destination_path, $quality);
            break;
        case 'image/png':
            $png_quality = 9 - (int)round(($quality / 100) * 9); // Kualitas PNG terbalik
            $result = imagepng($image, $destination_path, $png_quality);
            break;
        case 'image/gif':
            // Jika sumbernya GIF, simpan sebagai JPEG atau PNG setelah blur
            // Pilih JPEG karena umumnya lebih kecil untuk foto
            $result = imagejpeg($image, $destination_path, $quality); 
            // Ubah ekstensi tujuan jika aslinya GIF tapi disimpan sebagai JPEG
            if (pathinfo($destination_path, PATHINFO_EXTENSION) == 'gif') {
                $destination_path = str_replace('.gif', '.jpeg', $destination_path);
            }
            break;
        default:
            // Ini seharusnya tidak tercapai karena sudah ditangani di switch pertama
            return false;
    }

    imagedestroy($image); // Bebaskan memori

    if ($result && file_exists($destination_path)) {
        chmod($destination_path, 0644);
    }

    return $result;
}


// Periksa apakah ada file yang diunggah
if (!empty($_FILES['images']['name'][0])) {
    $total_files = count($_FILES['images']['name']);
    $is_first_image = true; // Flag untuk melacak gambar pertama

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
                    // Buat nama file unik dengan awalan 'raw_' untuk gambar asli
                    $new_file_name_original = uniqid('raw_') . '.' . $file_ext;
                    $destination_original = $upload_dir . $new_file_name_original;

                    // Tentukan kualitas kompresi untuk gambar asli (misalnya 75 untuk JPEG, atau 7 untuk PNG)
                    $compression_quality_original = 75; 
                    
                    // Kompresi dan pindahkan file asli
                    if (compressImage($file_tmp, $destination_original, $file_type, $compression_quality_original)) {
                        $web_path_original = './uploads/pins/' . $new_file_name_original;
                        $compressed_file_size_original = filesize($destination_original);

                        $web_path_blur = ''; // Inisialisasi path blur
                        $compressed_file_size_blur = 0;

                        if ($is_first_image) {
                            // HANYA untuk gambar pertama: Buat versi blur
                            $new_file_name_blur = uniqid('blur_') . '.' . $file_ext;
                            if ($file_ext === 'gif') {
                                $new_file_name_blur = uniqid('blur_') . '.jpeg'; // Simpan GIF blur sebagai JPEG
                            }
                            $destination_blur = $blur_upload_dir . $new_file_name_blur;
                            $compression_quality_blur = 30; // Kualitas lebih rendah untuk blur

                            if (createBlurredImage($destination_original, $destination_blur, $file_type, 3, $compression_quality_blur)) {
                                $web_path_blur = './uploads/blur-pins/' . $new_file_name_blur;
                                $compressed_file_size_blur = filesize($destination_blur);
                            } else {
                                $errors[] = "Gagal membuat versi blur untuk {$file_name}.";
                                error_log("[ERROR UPLOAD] Gagal membuat gambar blur: {$file_name}.");
                                // Jika blur gagal, hapus gambar asli yang sudah diunggah
                                if (file_exists($destination_original)) {
                                    unlink($destination_original);
                                }
                                $is_first_image = false; // Pastikan tidak ada blur lagi jika gagal
                                continue; // Lanjut ke file berikutnya
                            }
                        } else {
                            // Untuk gambar kedua dan seterusnya: url_blur sama dengan url_original
                            $web_path_blur = $web_path_original;
                            $compressed_file_size_blur = $compressed_file_size_original; // Ukuran blur sama dengan original
                        }
                        
                        $uploaded_files[] = [
                            'url_original' => $web_path_original,
                            'url_blur' => $web_path_blur,
                            'original_size_kb' => round($file_size / 1024, 2),
                            'compressed_size_original_kb' => round($compressed_file_size_original / 1024, 2),
                            'compressed_size_blur_kb' => round($compressed_file_size_blur / 1024, 2)
                        ];
                        $is_first_image = false; // Setelah gambar pertama diproses, set flag ke false

                    } else {
                        $errors[] = "Gagal mengkompresi atau memindahkan file asli {$file_name}. Ini mungkin terjadi jika tipe gambar tidak didukung, masalah izin direktori, atau ada masalah dengan GD library. Lihat log server untuk detail.";
                        error_log("[ERROR UPLOAD] Gagal kompresi/pindah file asli: {$file_name}.");
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
