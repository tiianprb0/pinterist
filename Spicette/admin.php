<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
session_start();
// Memastikan hanya admin yang bisa mengakses halaman ini. Jika tidak login atau bukan admin, redirect ke halaman login.
if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header('Location: index.html?login=true'); // Redirect ke index dengan overlay login
    exit();
}
$adminUsername = $_SESSION['username'];

// Path ke file users.json
$usersFilePath = __DIR__ . '/data/users.json';

// Fungsi untuk mendapatkan data pengguna (diperlukan untuk mendapatkan profile_image_url admin)
function getAdminData($username, $usersFilePath) {
    if (file_exists($usersFilePath)) {
        $users = json_decode(file_get_contents($usersFilePath), true);
        foreach ($users as $user) {
            if ($user['username'] === $username && ($user['isAdmin'] ?? false)) {
                return $user;
            }
        }
    }
    return null;
}

$adminData = getAdminData($adminUsername, $usersFilePath);
$adminProfileImageUrl = $adminData['profile_image_url'] ?? 'https://i.pinimg.com/736x/45/69/16/456916ec52c1c93abed7f12d60749b6f.jpg'; // Default image
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Admin | Spicette</title>
    <!-- Font Playfair Display untuk Judul -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Plus Jakarta Sans untuk Isi dan Umum -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome untuk Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Variabel CSS Global (dari login.html dan create.html) */
        :root {
            --color-primary: #e60023; /* Pinterest Red */
            --color-dark: #111; /* Dark text */
            --color-text-main: #333; /* Main text color */
            --color-text-secondary: #5f5f5f; /* Secondary text */
            --color-text-light: #767676; /* Info message, divider */
            --color-background-light: #f9f9f9; /* Body background */
            --color-white: #ffffff; /* White */
            --color-border-light: #eee; /* Divider border */
            --color-border-medium: #ddd; /* Google button border, disabled button */
            --color-hover-light: #f0f0f0; /* Google button hover */
            --color-success: #28a745; /* Green for success alerts */
            --color-error: #dc3545; /* Red for error alerts */
            --color-accent-hover: #ad081b; /* Darker red for hover states of primary elements */
            --color-dashed-line: #ccc; /* Specific color for dashed line */

            /* Font Families */
            --font-family-body: 'Plus Jakarta Sans', sans-serif;
            --font-family-heading: 'Playfair Display', serif;

            /* Base Font Sizes */
            --font-size-base: 16px;
            --font-size-small: 14px;
            --font-size-medium: 1.2rem; /* Ukuran font menengah untuk sub-judul */
            --font-size-large: 2.2rem; /* Untuk judul utama */
        }

        /* Simple CSS Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Gaya dasar untuk body */
        body {
            font-family: var(--font-family-body);
            font-size: var(--font-size-base);
            line-height: 1.5;
            background-color: var(--color-background-light);
            color: var(--color-text-main);
            display: block; /* Menggunakan block, bukan flex untuk main layout */
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Header (konsisten dengan user.php) */
        header {
            background-color: var(--color-white);
            padding: 10px 20px;
            display: flex; /* Tetap pakai flex untuk header */
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--color-border-light);
            box-shadow: none !important; /* Dihilangkan box-shadow */
        }
        header .logo {
            font-family: var(--font-family-heading);
            font-size: 24px;
            font-weight: bold;
            color: var(--color-primary);
            cursor: pointer;
            flex-shrink: 0;
        }
        header .header-icons {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }
        header .icon-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.2s ease;
            color: var(--color-text-main);
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        header .icon-button:hover {
            background-color: var(--color-hover-light);
        }
        header .profile-icon {
            width: 36px;
            height: 36px;
            background-color: var(--color-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            color: var(--color-white);
            overflow: hidden;
        }
        header .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Main layout untuk admin dashboard */
        .admin-dashboard-layout {
            display: block; /* Menggunakan block */
            width: 100%;
            max-width: 1200px; /* Lebar maksimum yang lebih besar */
            margin: 20px auto; /* Pusatkan */
            padding: 0 20px; /* Padding horizontal */
            box-sizing: border-box;
            overflow: hidden; /* Untuk clear float dari sidebar */
        }

        /* Sidebar Admin */
        .admin-sidebar {
            width: 250px;
            background-color: var(--color-white);
            padding: 20px;
            border-radius: 16px;
            float: left; /* Menggunakan float untuk layout satu baris */
            margin-right: 20px; /* Jarak antar sidebar dan content */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); /* Sedikit shadow untuk sidebar */
            box-sizing: border-box;
        }
        .admin-sidebar h2 {
            font-family: var(--font-family-heading);
            font-size: var(--font-size-large); /* Menggunakan variabel */
            color: var(--color-dark);
            margin-bottom: 20px;
            text-align: left;
        }
        .admin-sidebar-nav ul {
            list-style: none;
            padding: 0;
        }
        .admin-sidebar-nav li {
            margin-bottom: 10px;
        }
        .admin-tab-button {
            width: 100%;
            padding: 12px 15px;
            border: none;
            background-color: transparent;
            color: var(--color-text-main);
            font-size: var(--font-size-base);
            font-weight: 500;
            text-align: left;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.2s ease, color 0.2s ease;
            display: block; /* Menggunakan block agar memenuhi lebar */
        }
        .admin-tab-button i {
            margin-right: 10px;
            font-size: 18px;
            vertical-align: middle;
        }
        .admin-tab-button.active {
            background-color: var(--color-primary);
            color: var(--color-white);
        }
        .admin-tab-button:hover:not(.active) {
            background-color: var(--color-hover-light);
        }

        /* Area Konten Admin */
        .admin-content-area {
            overflow: hidden; /* Clear float dari sidebar */
            background-color: var(--color-white);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); /* Sedikit shadow untuk content area */
            box-sizing: border-box;
        }
        .admin-tab-content {
            display: none;
        }
        .admin-tab-content.active {
            display: block;
        }
        .admin-section {
            margin-bottom: 30px;
            background-color: transparent;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
        }
        .admin-section h2 {
            font-family: var(--font-family-heading);
            font-size: var(--font-size-large); /* Menggunakan variabel */
            color: var(--color-dark);
            margin-bottom: 20px;
            text-align: left;
        }
        .admin-section h3 { /* Untuk sub-judul seperti "Statistik Tag Orang" */
            font-family: var(--font-family-body);
            font-size: var(--font-size-medium); /* Ukuran menengah */
            color: var(--color-dark);
            margin-top: 30px; /* Jarak dari bagian atas */
            margin-bottom: 15px; /* Jarak dari konten di bawahnya */
            text-align: left;
            font-weight: 700;
        }

        /* Overview Stats */
        .overview-stats {
            margin-bottom: 30px;
            overflow: hidden; /* Clear floats of stat-cards */
        }
        .stat-card {
            background-color: var(--color-background-light);
            border: 1px solid var(--color-border-medium);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
            display: inline-block; /* Menggunakan inline-block */
            width: calc(50% - 10px); /* Lebar untuk 2 kolom dengan gap */
            margin-right: 20px; /* Jarak antar card */
            vertical-align: top;
            box-sizing: border-box;
        }
        .stat-card:nth-child(2n) { /* Hapus margin kanan untuk setiap card kedua */
            margin-right: 0;
        }
        .stat-card h4 {
            font-size: var(--font-size-base);
            color: var(--color-text-secondary);
            margin-bottom: 10px;
        }
        .stat-card p {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--color-primary);
        }

        /* Admin Forms (konsisten dengan create.html) */
        .admin-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--color-text-main);
            font-size: var(--font-size-small);
        }
        .admin-form input[type="text"],
        .admin-form input[type="url"],
        .admin-form textarea {
            width: 100%;
            padding: 12px 0; /* Padding disesuaikan */
            border: none;
            border-bottom: 1px dashed var(--color-dashed-line); /* Border bawah putus-putus */
            background-color: transparent;
            border-radius: 0;
            font-size: var(--font-size-base);
            color: var(--color-text-main);
            box-sizing: border-box;
            transition: border-color 0.2s ease;
            outline: none;
            margin-bottom: 15px;
            touch-action: manipulation; /* Mencegah zoom pada iOS */
        }
        .admin-form input[type="text"]:focus,
        .admin-form input[type="url"]:focus,
        .admin-form textarea:focus {
            border-bottom-color: var(--color-primary);
        }
        .admin-form textarea {
            min-height: 80px;
            resize: vertical;
        }
        .admin-form button[type="submit"] {
            background-color: var(--color-primary);
            color: var(--color-white);
            border: none;
            padding: 12px 20px;
            border-radius: 24px;
            font-weight: bold;
            font-size: var(--font-size-base);
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: block; /* Menggunakan block */
            margin-top: 10px; /* Jarak dari input */
        }
        .admin-form button[type="submit"]:hover {
            background-color: var(--color-accent-hover);
        }

        /* Admin Lists (umum untuk notifikasi, pengguna) */
        .admin-list {
            margin-top: 20px;
        }
        .admin-list .list-item {
            background-color: var(--color-white);
            border: 1px solid var(--color-border-medium);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            overflow: hidden; /* Clear floats */
            box-sizing: border-box;
        }
        .admin-list .list-item-img { /* Untuk gambar kecil di list (notifikasi, user) */
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
            float: left; /* Menggunakan float */
        }
        .admin-list .list-item-content {
            overflow: hidden; /* Clear float */
            padding-right: 10px; /* Ruang untuk tombol aksi */
        }
        .admin-list .list-item-title {
            font-size: var(--font-size-base);
            font-weight: bold;
            color: var(--color-dark);
            display: block;
            margin-bottom: 5px;
        }
        .admin-list .list-item-detail {
            font-size: var(--font-size-small);
            color: var(--color-text-secondary);
            display: block;
            margin-bottom: 3px;
        }
        .admin-list .list-item-actions {
            margin-top: 10px;
            text-align: right;
        }
        .admin-list .list-item-actions button {
            background-color: var(--color-error);
            color: var(--color-white);
            border: none;
            padding: 8px 12px;
            border-radius: 15px;
            font-size: var(--font-size-small);
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-left: 10px;
            display: inline-block; /* Menggunakan inline-block */
            vertical-align: middle;
        }
        .admin-list .list-item-actions button:hover {
            background-color: #c02c3a;
        }
        .admin-list .list-item-actions button.accept-btn {
            background-color: var(--color-success);
        }
        .admin-list .list-item-actions button.accept-btn:hover {
            background-color: #218838;
        }
        .admin-list .list-item-actions button.reject-btn {
            background-color: var(--color-text-light);
        }
        .admin-list .list-item-actions button.reject-btn:hover {
            background-color: var(--color-text-secondary);
        }

        /* Image Grid for Categories and Pins */
        .image-grid {
            margin-top: 20px;
            column-count: 5; /* Default 5 kolom untuk desktop */
            column-gap: 15px;
            padding: 0;
        }
        .image-grid-item {
            display: inline-block; /* Penting untuk column-count */
            width: 100%; /* Agar memenuhi lebar kolom */
            margin-bottom: 15px; /* Jarak antar baris */
            background-color: var(--color-white);
            border: 1px solid var(--color-border-medium);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none; /* Menghapus shadow */
            box-sizing: border-box;
            cursor: pointer;
            position: relative;
        }
        .image-grid-item img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 12px 12px 0 0; /* Hanya bagian atas yang rounded */
        }
        .image-grid-item-content {
            padding: 10px;
            text-align: left;
            border-top: 1px dashed var(--color-dashed-line); /* Pembatas antar gambar dan detail */
            margin-top: 5px; /* Jarak dari gambar */
        }
        .image-grid-item-detail {
            font-size: var(--font-size-small);
            color: var(--color-text-secondary);
            display: block;
            margin-bottom: 3px;
            white-space: nowrap; /* Mencegah wrap */
            overflow: hidden; /* Sembunyikan overflow */
            text-overflow: ellipsis; /* Tambahkan elipsis */
        }
        .image-grid-item-actions {
            padding: 0 10px 10px;
            text-align: right;
        }
        .image-grid-item-actions button {
            background-color: var(--color-error);
            color: var(--color-white);
            border: none;
            padding: 6px 10px;
            border-radius: 12px;
            font-size: var(--font-size-small);
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .image-grid-item-actions button:hover {
            background-color: #c02c3a;
        }


        /* Person Tag Stats List */
        .person-tag-stats-list {
            list-style: none;
            padding: 0;
        }
        .person-tag-stats-list .person-tag-stats-item {
            background-color: var(--color-background-light);
            border: 1px solid var(--color-border-light);
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 8px;
            display: block; /* Menggunakan block */
            overflow: hidden; /* Clear float */
        }
        .person-tag-stats-list .person-tag-stats-item span:first-child {
            font-weight: bold;
            color: var(--color-text-main);
            float: left; /* Menggunakan float */
        }
        .person-tag-stats-list .person-tag-stats-item span:last-child {
            color: var(--color-text-secondary);
            float: right; /* Menggunakan float */
        }

        /* User Management Specifics */
        .user-level-control {
            margin-top: 10px;
            display: block; /* Menggunakan block */
        }
        .user-level-control label {
            font-size: var(--font-size-small);
            color: var(--color-text-secondary);
            margin-right: 5px;
            display: inline-block; /* Menggunakan inline-block */
            vertical-align: middle;
        }
        .user-level-control select {
            padding: 5px 8px;
            border: 1px solid var(--color-border-medium);
            border-radius: 5px;
            font-size: var(--font-size-small);
            background-color: var(--color-background-light);
            color: var(--color-text-main);
            display: inline-block; /* Menggunakan inline-block */
            vertical-align: middle;
        }

        .can-upload-toggle {
            margin-top: 10px;
            display: block; /* Menggunakan block */
        }
        .can-upload-toggle label {
            font-size: var(--font-size-small);
            color: var(--color-text-secondary);
            margin-right: 10px;
            display: inline-block; /* Menggunakan inline-block */
            vertical-align: middle;
        }
        /* Toggle Switch (dari user.php) */
        .switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
            vertical-align: middle;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 20px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: var(--color-primary);
        }
        input:focus + .slider {
            box-shadow: 0 0 1px var(--color-primary);
        }
        input:checked + .slider:before {
            -webkit-transform: translateX(20px);
            -ms-transform: translateX(20px);
            transform: translateX(20px);
        }

        .expand-user-btn {
            background-color: var(--color-text-light);
            color: var(--color-white);
            border: none;
            padding: 8px 12px;
            border-radius: 15px;
            font-size: var(--font-size-small);
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 10px;
            display: inline-block; /* Menggunakan inline-block */
        }
        .expand-user-btn:hover {
            background-color: var(--color-text-secondary);
        }

        /* Message Box (konsisten dengan login.html dan create.html) */
        #message {
            margin-bottom: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: var(--font-size-base);
            text-align: center;
            display: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
            white-space: nowrap;
            max-width: 90%;
            box-sizing: border-box;
            background-color: var(--color-text-main); /* Default background */
            color: var(--color-white); /* Default text color */
        }
        #message.show {
            opacity: 1;
            visibility: visible;
        }
        #message.error {
            background-color: var(--color-error);
            color: var(--color-white);
        }
        #message.success {
            background-color: var(--color-success);
            color: var(--color-white);
        }

        /* Modal Konfirmasi Kustom (konsisten dengan user.php) */
        #customConfirmationModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none; /* Default hidden */
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        #customConfirmationModal.show {
            display: flex;
            opacity: 1;
            visibility: visible;
        }
        #customConfirmationModal > div {
            background: var(--color-white);
            padding: 35px;
            border-radius: 12px;
            max-width: 420px;
            text-align: center;
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid var(--color-border-medium);
            color: var(--color-text-main);
        }
        #customConfirmationModal.show > div {
            transform: scale(1);
            opacity: 1;
        }
        #customConfirmationModal #modalMessage {
            font-size: var(--font-size-base);
            margin-bottom: 28px;
            font-weight: 500;
        }
        #customConfirmationModal button {
            border: none;
            padding: 11px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-size: var(--font-size-base);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        #customConfirmationModal #confirmNo {
            background: var(--color-border-medium);
            color: var(--color-text-main);
        }
        #customConfirmationModal #confirmNo:hover {
            background: var(--color-text-light);
        }
        #customConfirmationModal #confirmYes {
            background: var(--color-primary);
            color: var(--color-white);
        }
        #customConfirmationModal #confirmYes:hover {
            background: var(--color-accent-hover);
        }

        /* Hamburger Menu (dari user.php) */
        .hamburger-menu-container {
            display: none; /* Default hidden for desktop */
            margin-left: 15px;
        }
        .hamburger-menu-icon {
            width: 30px;
            height: 20px;
            position: relative;
            transform: rotate(0deg);
            transition: .5s ease-in-out;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }
        .hamburger-menu-icon span {
            display: block;
            position: absolute;
            height: 3px;
            width: 100%;
            background: var(--color-text-main);
            border-radius: 9px;
            opacity: 1;
            left: 0;
            transform: rotate(0deg);
            transition: .25s ease-in-out;
        }
        .hamburger-menu-icon span:nth-child(1) {
            top: 0px;
        }
        .hamburger-menu-icon span:nth-child(2) {
            top: 8px;
        }
        .hamburger-menu-icon span:nth-child(3) {
            top: 16px;
        }
        .hamburger-menu-icon.active span:nth-child(1) {
            top: 8px;
            transform: rotate(135deg);
        }
        .hamburger-menu-icon.active span:nth-child(2) {
            opacity: 0;
            left: -60px;
        }
        .hamburger-menu-icon.active span:nth-child(3) {
            top: 8px;
            transform: rotate(-135deg);
        }

        /* Drag and Drop Area (untuk upload gambar kategori) */
        .drag-area {
            border: 2px dashed var(--color-dashed-line);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
            margin-bottom: 15px;
            color: var(--color-text-secondary);
            font-size: var(--font-size-base);
            display: block; /* Menggunakan block */
            min-height: 100px;
        }

        .drag-area.highlight {
            background-color: var(--color-hover-light);
            border-color: var(--color-primary);
        }

        .drag-area i {
            font-size: 30px;
            margin-bottom: 5px;
            color: var(--color-text-light);
        }

        .drag-area p {
            margin: 0;
            font-size: var(--font-size-base);
            color: var(--color-text-secondary);
        }

        .drag-area span {
            font-weight: bold;
            color: var(--color-primary);
        }

        .drag-area input[type="file"] {
            display: none; /* Sembunyikan input file asli */
        }

        /* Indikator file yang dipilih untuk kategori */
        #selectedCategoryImageIndicator {
            margin-top: 10px;
            font-size: var(--font-size-small);
            color: var(--color-text-main);
            text-align: center;
            display: none; /* Sembunyikan secara default */
            font-weight: 500;
        }

        /* Admin Profile Summary in Sidebar */
        .admin-profile-summary {
            text-align: left;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--color-border-light);
            margin-bottom: 20px;
            overflow: hidden; /* Clear floats */
        }
        .admin-profile-summary .profile-icon {
            float: left; /* Menggunakan float */
            margin-right: 10px;
            width: 48px; /* Lebih besar dari header */
            height: 48px;
            font-size: 24px;
        }
        .admin-profile-summary .username-text {
            font-size: var(--font-size-base);
            font-weight: bold;
            color: var(--color-dark);
            display: block; /* Menggunakan block */
            margin-top: 5px;
        }
        .admin-profile-summary .logout-button-sidebar {
            background-color: var(--color-primary);
            color: var(--color-white);
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: var(--font-size-small);
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 10px;
            display: inline-block; /* Menggunakan inline-block */
            clear: both; /* Clear float di bawah profil */
        }
        .admin-profile-summary .logout-button-sidebar:hover {
            background-color: var(--color-accent-hover);
        }

        /* Desktop Logout Button in Header */
        .admin-logout-btn-desktop {
            background-color: var(--color-primary);
            color: var(--color-white);
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: var(--font-size-small);
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-left: 15px;
            display: none; /* Default hidden, shown on desktop */
        }
        .admin-logout-btn-desktop:hover {
            background-color: var(--color-accent-hover);
        }


        /* Responsive adjustments */
        @media (max-width: 992px) {
            .admin-dashboard-layout {
                padding: 0;
                margin: 0;
                width: 100%;
            }
            .admin-sidebar {
                width: 250px;
                position: fixed;
                top: 0;
                left: -250px;
                height: 100%;
                z-index: 100;
                transition: left 0.3s ease-in-out;
                float: none;
                margin-right: 0;
                border-radius: 0;
                box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            }
            .admin-sidebar.active {
                left: 0;
            }
            .admin-content-area {
                width: 100%;
                padding: 20px;
                border-radius: 0;
                box-shadow: none;
            }
            header .hamburger-menu-container {
                display: block; /* Show hamburger on mobile */
            }
            .admin-logout-btn-desktop {
                display: none; /* Hide desktop logout on mobile */
            }
            .overview-stats .stat-card {
                width: 100%;
                margin-right: 0;
            }
            .image-grid {
                column-count: 3; /* 3 kolom untuk tablet */
            }
        }

        @media (min-width: 993px) { /* Desktop styles */
            header .hamburger-menu-container {
                display: none; /* Hide hamburger on desktop */
            }
            .admin-logout-btn-desktop {
                display: inline-block; /* Show desktop logout on desktop */
            }
            .admin-profile-summary .logout-button-sidebar {
                display: none; /* Hide sidebar logout on desktop */
            }
        }

        @media (max-width: 768px) {
            .image-grid {
                column-count: 2; /* 2 kolom untuk mobile */
            }
        }

        @media (max-width: 576px) {
            header .logo {
                font-size: 20px;
            }
            header .icon-button {
                font-size: 16px;
                padding: 5px;
            }
            header .profile-icon {
                width: 30px;
                height: 30px;
                font-size: 16px;
            }
            .admin-sidebar {
                width: 200px;
                left: -200px;
            }
            .admin-sidebar.active {
                left: 0;
            }
            .admin-sidebar h2 {
                font-size: 1.5rem;
            }
            .admin-tab-button {
                font-size: var(--font-size-small);
                padding: 10px 12px;
            }
            .admin-tab-button i {
                font-size: 16px;
            }
            .admin-content-area {
                padding: 15px;
            }
            .admin-section h2 {
                font-size: 1.5rem;
            }
            .stat-card h4 {
                font-size: var(--font-size-small);
            }
            .stat-card p {
                font-size: 2rem;
            }
            .admin-form input, .admin-form textarea, .admin-form button {
                font-size: var(--font-size-small);
                padding: 10px;
            }
            .admin-list .list-item-img {
                width: 50px;
                height: 50px;
            }
            .admin-list .list-item-title {
                font-size: var(--font-size-small);
            }
            .admin-list .list-item-detail {
                font-size: 12px;
            }
            .admin-list .list-item-actions button {
                font-size: 12px;
                padding: 6px 10px;
            }
            .image-grid {
                column-count: 2; /* Tetap 2 kolom untuk mobile */
            }
            .image-grid-item-title {
                font-size: var(--font-size-small);
            }
            .image-grid-item-detail {
                font-size: 12px;
            }
            #message {
                font-size: var(--font-size-small);
                padding: 10px 15px;
            }
            #customConfirmationModal #modalMessage {
                font-size: var(--font-size-small);
            }
            #customConfirmationModal button {
                font-size: var(--font-size-small);
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo" onclick="window.location.href='index.html'">Spicette</div>
        <div class="header-nav-links" style="display: none;">
            <button class="nav-button" data-nav="home" onclick="window.location.href='index.html'">Beranda</button>
            <button class="nav-button active">Panel Admin</button>
        </div>
        <div class="search-container" style="display: none;"></div>
        <div class="header-icons">
            <!-- Desktop Profile Icon -->
            <button class="icon-button" aria-label="Profil Admin" onclick="window.location.href='admin.php'">
                <div class="profile-icon">
                    <?php if (!empty($adminProfileImageUrl)): ?>
                        <img src="<?php echo htmlspecialchars($adminProfileImageUrl); ?>" alt="Profil Admin">
                    <?php else: ?>
                        <?php echo strtoupper(substr($adminUsername, 0, 1)); ?>
                    <?php endif; ?>
                </div>
            </button>
            <!-- Desktop Logout Button -->
            <button class="admin-logout-btn-desktop" id="adminLogoutBtnDesktop">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar
            </button>
            <!-- Hamburger Menu for Mobile -->
            <div class="hamburger-menu-container" id="hamburgerMenuContainer">
                <button class="hamburger-menu-icon" aria-label="Menu" id="hamburgerMenuButton">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <main class="admin-dashboard-layout">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-profile-summary">
                <div class="profile-icon">
                    <?php if (!empty($adminProfileImageUrl)): ?>
                        <img src="<?php echo htmlspecialchars($adminProfileImageUrl); ?>" alt="Profil Admin">
                    <?php else: ?>
                        <?php echo strtoupper(substr($adminUsername, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <span class="username-text">Login sebagai <?php echo htmlspecialchars($adminUsername); ?></span>
                <button class="logout-button-sidebar" id="adminLogoutBtnSidebar">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar
                </button>
            </div>
            <h2>Dashboard</h2>
            <nav class="admin-sidebar-nav">
                <ul>
                    <li>
                        <button class="admin-tab-button active" data-tab="overview">
                            <i class="fa-solid fa-chart-line"></i>
                            Overview
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="notifications">
                            <i class="fa-solid fa-bell"></i>
                            Notifikasi
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="categories">
                            <i class="fa-solid fa-layer-group"></i>
                            Kategori
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="users">
                            <i class="fa-solid fa-users"></i>
                            Pengguna
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="pins">
                            <i class="fa-solid fa-thumbtack"></i>
                            Pin
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="manual-person-requests">
                            <i class="fa-solid fa-user-plus"></i>
                            Permintaan Orang Manual
                        </button>
                    </li>
                </ul>
            </nav>
        </aside>

        <section class="admin-content-area" id="adminContentArea">
            <div id="message"></div>

            <div id="overviewTabContent" class="admin-tab-content active">
                <div class="admin-section">
                    <h2>Overview Statistik</h2>
                    <div class="overview-stats">
                        <div class="stat-card">
                            <h4>Total Pengguna</h4>
                            <p id="totalUsersCount">...</p>
                        </div>
                        <div class="stat-card">
                            <h4>Total Pin</h4>
                            <p id="totalPinsCount">...</p>
                        </div>
                    </div>
                    <h3>Statistik Tag Orang</h3>
                    <div class="admin-list">
                        <ul class="person-tag-stats-list" id="personTagStatsList">
                            <p style="text-align: center; color: var(--color-text-secondary);">Memuat statistik tag orang...</p>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="notificationsTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Pengaturan Notifikasi</h2>
                    <form class="admin-form" id="addNotificationForm">
                        <label for="newNotificationText">Teks Notifikasi Baru:</label>
                        <textarea id="newNotificationText" placeholder="Masukkan teks notifikasi baru" required></textarea>
                        <label for="newNotificationLink">Tautan Notifikasi (opsional):</label>
                        <input type="url" id="newNotificationLink" placeholder="Contoh: https://example.com/promo">
                        <button type="submit">Tambah Notifikasi</button>
                    </form>
                    <h3>Notifikasi Aktif</h3>
                    <div class="admin-list" id="notificationList">
                        <p style="text-align: center; color: var(--color-text-secondary);">Memuat notifikasi...</p>
                    </div>
                </div>
            </div>

            <div id="categoriesTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Manajemen Kategori</h2>
                    <form class="admin-form" id="addCategoryForm">
                        <label for="newCategoryName">Nama Kategori Baru:</label>
                        <input type="text" id="newCategoryName" placeholder="Contoh: Desain Grafis" required>
                        <label for="newCategoryImageFile">Gambar Kategori (opsional):</label>
                        <div id="dragAreaCategory" class="drag-area">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Seret & lepas gambar di sini atau <span>klik untuk memilih</span></p>
                            <input type="file" id="newCategoryImageFile" accept="image/*">
                        </div>
                        <div id="selectedCategoryImageIndicator"></div>
                        <button type="submit">Tambah Kategori</button>
                    </form>
                    <h3>Kategori Aktif</h3>
                    <div class="image-grid" id="categoryList">
                        <p style="text-align: center; color: var(--color-text-secondary); column-span: all;">Memuat daftar kategori...</p>
                    </div>
                </div>
            </div>

            <div id="usersTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Manajemen Pengguna</h2>
                    <div class="admin-list" id="userList">
                        <p style="text-align: center; color: var(--color-text-secondary);">Memuat daftar pengguna...</p>
                    </div>
                </div>
            </div>

            <div id="pinsTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Manajemen Pin</h2>
                    <div class="image-grid" id="pinList">
                        <p style="text-align: center; color: var(--color-text-secondary); column-span: all;">Memuat daftar pin...</p>
                    </div>
                </div>
            </div>

            <div id="manual-person-requestsTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Permintaan Orang Manual</h2>
                    <div class="admin-list" id="manualPersonRequestList">
                        <p style="text-align: center; color: var(--color-text-secondary);">Memuat permintaan orang manual...</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div id="customConfirmationModal">
        <div>
            <p id="modalMessage"></p>
            <div style="display: flex; justify-content: center; gap: 15px;">
                <button id="confirmNo">Tidak</button>
                <button id="confirmYes">Ya</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elemen
            const adminTabButtons = document.querySelectorAll('.admin-tab-button');
            const adminTabContents = document.querySelectorAll('.admin-tab-content');
            const hamburgerMenuButton = document.getElementById('hamburgerMenuButton');
            const hamburgerMenuContainer = document.getElementById('hamburgerMenuContainer');
            const adminSidebar = document.getElementById('adminSidebar');
            const messageDiv = document.getElementById('message');
            const adminLogoutBtnDesktop = document.getElementById('adminLogoutBtnDesktop'); // Desktop logout button
            const adminLogoutBtnSidebar = document.getElementById('adminLogoutBtnSidebar'); // Sidebar logout button

            // Elemen formulir
            const addNotificationForm = document.getElementById('addNotificationForm');
            const newNotificationText = document.getElementById('newNotificationText');
            const newNotificationLink = document.getElementById('newNotificationLink');
            const notificationList = document.getElementById('notificationList');

            const addCategoryForm = document.getElementById('addCategoryForm');
            const newCategoryName = document.getElementById('newCategoryName');
            const newCategoryImageFile = document.getElementById('newCategoryImageFile');
            const categoryList = document.getElementById('categoryList');
            const dragAreaCategory = document.getElementById('dragAreaCategory'); // Drag area untuk kategori
            const selectedCategoryImageIndicator = document.getElementById('selectedCategoryImageIndicator'); // Indikator file kategori

            const userList = document.getElementById('userList');
            const pinList = document.getElementById('pinList');
            const manualPersonRequestList = document.getElementById('manualPersonRequestList');

            // Overview elements
            const totalUsersCountElem = document.getElementById('totalUsersCount');
            const totalPinsCountElem = document.getElementById('totalPinsCount');
            const personTagStatsList = document.getElementById('personTagStatsList');


            // Elemen modal
            const customConfirmationModal = document.getElementById('customConfirmationModal');
            const modalMessage = document.getElementById('modalMessage');
            const confirmYesButton = document.getElementById('confirmYes');
            const confirmNoButton = document.getElementById('confirmNo');

            const adminUsername = '<?php echo $adminUsername; ?>';
            const adminProfileImageUrl = '<?php echo $adminProfileImageUrl; ?>';


            // --- URL Dasar API ---
            const API_BASE_URL = 'api/';

            // --- Fungsi Pembantu untuk Permintaan API ---
            async function makeApiRequest(endpoint, method = 'GET', data = null, isFormData = false) {
                try {
                    const options = { method };
                    if (data !== null && typeof data !== 'undefined') {
                        if (isFormData) {
                            options.body = data;
                        } else if (method !== 'GET') {
                            options.headers = { 'Content-Type': 'application/json' };
                            options.body = JSON.stringify(data);
                        }
                    }

                    const response = await fetch(API_BASE_URL + endpoint, options);
                    // Selalu baca respons sebagai teks terlebih dahulu untuk menangkap pesan kesalahan non-JSON
                    const textResponse = await response.text(); 
                    
                    if (!response.ok) {
                        // Jika respons bukan OK, coba parse sebagai JSON, jika gagal, gunakan teks mentah
                        let errorMessage = `Kesalahan HTTP! Status: ${response.status}`;
                        try {
                            const errorJson = JSON.parse(textResponse);
                            errorMessage += ` - ${errorJson.message || 'Pesan tidak tersedia'}`;
                        } catch (e) {
                            errorMessage += ` - Respons: ${textResponse.substring(0, 200)}... (bukan JSON atau terlalu panjang)`; // Batasi panjang untuk logging
                        }
                        throw new Error(errorMessage);
                    }
                    
                    // Jika respons OK tapi kosong
                    if (!textResponse) {
                        return { success: true, message: 'Tidak ada konten' };
                    }
                    
                    // Coba parse respons sebagai JSON
                    try {
                        const jsonResponse = JSON.parse(textResponse);
                        return jsonResponse;
                    } catch (e) {
                        console.error('Gagal mengurai respons JSON:', textResponse);
                        throw new Error(`Respons JSON tidak valid: ${textResponse.substring(0, 200)}...`);
                    }
                } catch (error) {
                    console.error('Permintaan API Gagal:', error);
                    showMessage(`Kesalahan jaringan atau server: ${error.message}`, true);
                    return { success: false, message: 'Kesalahan jaringan atau server.' };
                }
            }

            // --- Fungsi untuk Menampilkan Pesan ---
            function showMessage(msg, isError = false) {
                messageDiv.textContent = msg;
                messageDiv.className = `message-box ${isError ? 'error' : 'success'}`;
                messageDiv.style.display = 'block';
                // Reset animation by re-adding class after reflow
                messageDiv.classList.remove('show');
                void messageDiv.offsetWidth; // Trigger reflow
                messageDiv.classList.add('show');
                setTimeout(() => {
                    messageDiv.classList.remove('show');
                }, 3000);
            }

            // --- Modal Konfirmasi Kustom ---
            function showCustomConfirmation(message, onConfirmCallback) {
                modalMessage.textContent = message;
                customConfirmationModal.classList.add('show');

                const handleConfirm = () => {
                    customConfirmationModal.classList.remove('show');
                    onConfirmCallback();
                    confirmYesButton.removeEventListener('click', handleConfirm);
                    confirmNoButton.removeEventListener('click', handleCancel);
                };

                const handleCancel = () => {
                    customConfirmationModal.classList.remove('show');
                    confirmYesButton.removeEventListener('click', handleConfirm);
                    confirmNoButton.removeEventListener('click', handleCancel);
                };

                confirmYesButton.addEventListener('click', handleConfirm);
                confirmNoButton.addEventListener('click', handleCancel);
            }

            // --- Fungsionalitas Logout Admin ---
            async function handleAdminLogout() {
                showCustomConfirmation('Apakah Anda yakin ingin keluar?', async () => {
                    try {
                        const response = await makeApiRequest('auth.php?action=logout', 'POST', null);
                        if (response.success) {
                            window.location.href = 'index.html';
                        } else {
                            showMessage('Logout gagal: ' + response.message, true);
                        }
                    }
                    catch (error) {
                        showMessage('Kesalahan selama proses logout: ' + error.message, true);
                    }
                });
            }
            adminLogoutBtnDesktop.addEventListener('click', handleAdminLogout);
            adminLogoutBtnSidebar.addEventListener('click', handleAdminLogout);


            // --- Fungsionalitas Tab Admin ---
            adminTabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;
                    adminTabButtons.forEach(btn => btn.classList.remove('active'));
                    adminTabContents.forEach(content => content.classList.remove('active'));
                    this.classList.add('active');
                    document.getElementById(targetTab + 'TabContent').classList.add('active');

                    if (window.innerWidth <= 992) {
                        adminSidebar.classList.remove('active');
                        hamburgerMenuButton.classList.remove('active');
                        hamburgerMenuContainer.classList.remove('active');
                    }

                    switch(targetTab) {
                        case 'overview':
                            fetchOverviewStats();
                            break;
                        case 'notifications':
                            fetchNotifications();
                            break;
                        case 'categories':
                            fetchCategoriesAdmin();
                            break;
                        case 'users':
                            fetchUsers();
                            break;
                        case 'pins':
                            fetchPinsForAdmin();
                            break;
                        case 'manual-person-requests':
                            fetchManualPersonRequests();
                            break;
                    }
                });
            });

            // Toggle menu hamburger untuk seluler
            hamburgerMenuButton.addEventListener('click', () => {
                adminSidebar.classList.toggle('active');
                hamburgerMenuButton.classList.toggle('active');
                hamburgerMenuContainer.classList.toggle('active');
            });

            // Tutup sidebar jika diklik di luar
            document.addEventListener('click', (event) => {
                const isClickInsideSidebar = adminSidebar.contains(event.target);
                const isClickOnHamburger = hamburgerMenuButton.contains(event.target) || hamburgerMenuContainer.contains(event.target);

                if (window.innerWidth <= 992 && adminSidebar.classList.contains('active') && !isClickInsideSidebar && !isClickOnHamburger) {
                    adminSidebar.classList.remove('active');
                    hamburgerMenuButton.classList.remove('active');
                    hamburgerMenuContainer.classList.remove('active');
                }
            });

            // --- Overview Statistik ---
            async function fetchOverviewStats() {
                totalUsersCountElem.textContent = 'Memuat...';
                totalPinsCountElem.textContent = 'Memuat...';
                personTagStatsList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary);">Memuat statistik tag orang...</p>';

                try {
                    const userCountResponse = await makeApiRequest('auth.php?action=get_user_count');
                    if (userCountResponse.success) {
                        totalUsersCountElem.textContent = userCountResponse.count;
                    } else {
                        totalUsersCountElem.textContent = 'Error';
                        console.error('Gagal memuat jumlah pengguna:', userCountResponse.message);
                    }

                    const pinStatsResponse = await makeApiRequest('pins.php?action=get_stats');
                    if (pinStatsResponse.success) {
                        totalPinsCountElem.textContent = pinStatsResponse.totalPins;
                        renderPersonTagStats(pinStatsResponse.personTagCounts);
                    } else {
                        totalPinsCountElem.textContent = 'Error';
                        personTagStatsList.innerHTML = `<p style="text-align: center; color: var(--color-error);">Gagal memuat statistik pin: ${pinStatsResponse.message}</p>`;
                        console.error('Gagal memuat statistik pin:', pinStatsResponse.message);
                    }
                } catch (error) {
                    totalUsersCountElem.textContent = 'Error';
                    totalPinsCountElem.textContent = 'Error';
                    personTagStatsList.innerHTML = `<p style="text-align: center; color: var(--color-error);">Kesalahan memuat overview: ${error.message}</p>`;
                    console.error('Kesalahan mengambil overview statistik:', error);
                }
            }

            function renderPersonTagStats(personTagCounts) {
                personTagStatsList.innerHTML = '';
                if (Object.keys(personTagCounts).length === 0) {
                    personTagStatsList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary);">Tidak ada tag orang yang ditemukan.</p>';
                    return;
                }

                // Sort person tags alphabetically
                const sortedTags = Object.keys(personTagCounts).sort((a, b) => a.localeCompare(b));

                sortedTags.forEach(tag => {
                    const listItem = document.createElement('li');
                    listItem.classList.add('person-tag-stats-item');
                    listItem.innerHTML = `
                        <span>${tag}</span>
                        <span>${personTagCounts[tag]} pin</span>
                    `;
                    personTagStatsList.appendChild(listItem);
                });
            }

            // --- Manajemen Notifikasi ---
            async function fetchNotifications() {
                notificationList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary);">Memuat notifikasi...</p>';
                try {
                    const response = await makeApiRequest('notifications.php?action=fetch_all');
                    if (response.success) {
                        renderNotificationList(response.notifications);
                    } else {
                        showMessage('Gagal memuat notifikasi: ' + response.message, true);
                        notificationList.innerHTML = `<p style="text-align: center; color: var(--color-error);">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Kesalahan mengambil daftar notifikasi: ' + error.message, true);
                    notificationList.innerHTML = `<p style="text-align: center; color: var(--color-error);">${error.message}</p>`;
                }
            }

            function renderNotificationList(notifications) {
                notificationList.innerHTML = '';
                if (notifications.length === 0) {
                    notificationList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary);">Tidak ada notifikasi aktif.</p>';
                    return;
                }

                notifications.forEach(notif => {
                    const notifItem = document.createElement('div');
                    notifItem.classList.add('list-item');
                    
                    let linkHtml = '';
                    if (notif.link && isValidUrl(notif.link)) {
                        linkHtml = `<a href="${notif.link}" target="_blank" class="list-item-detail" style="color: var(--color-primary);">Lihat Tautan</a>`;
                    }

                    notifItem.innerHTML = `
                        <div class="list-item-content">
                            <strong class="list-item-title">${notif.text}</strong>
                            <span class="list-item-detail">ID: ${notif.id}</span>
                            <span class="list-item-detail">Ditambahkan: ${notif.timestamp || 'N/A'}</span>
                            <span class="list-item-detail">Status: ${notif.read ? 'Sudah Dibaca' : 'Belum Dibaca'}</span>
                            ${linkHtml}
                        </div>
                        <div class="list-item-actions">
                            <button class="delete-btn" data-id="${notif.id}"><i class="fa-solid fa-trash-can"></i> Hapus</button>
                        </div>
                    `;
                    const deleteBtn = notifItem.querySelector('.delete-btn');
                    if (deleteBtn) {
                         deleteBtn.onclick = () => deleteNotification(notif.id);
                    }
                    notificationList.appendChild(notifItem);
                });
            }

            function isValidUrl(string) {
                try {
                    new URL(string);
                    return true;
                } catch (e) {
                    return false;
                }
            }

            addCategoryForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const categoryName = newCategoryName.value.trim();
                const categoryImageFile = newCategoryImageFile.files[0];

                if (!categoryName) {
                    showMessage('Nama kategori tidak boleh kosong.', true);
                    return;
                }

                const formData = new FormData();
                formData.append('name', categoryName);
                if (categoryImageFile) {
                    formData.append('imageFile', categoryImageFile);
                }

                try {
                    const response = await makeApiRequest('categories.php?action=add', 'POST', formData, true);
                    if (response.success) {
                        showMessage('Kategori berhasil ditambahkan!', false);
                        newCategoryName.value = '';
                        newCategoryImageFile.value = '';
                        selectedCategoryImageIndicator.style.display = 'none'; // Sembunyikan indikator setelah upload
                        fetchCategoriesAdmin();
                    } else {
                        showMessage('Gagal menambahkan kategori: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Kesalahan menambahkan kategori: ' + error.message, true);
                }
            });

            async function deleteNotification(id) {
                showCustomConfirmation('Apakah Anda yakin ingin menghapus notifikasi ini?', async () => {
                    try {
                        const response = await makeApiRequest('notifications.php?action=delete', 'POST', { id });
                        if (response.success) {
                            showMessage('Notifikasi berhasil dihapus!', false);
                            fetchNotifications();
                        } else {
                            showMessage('Gagal menghapus notifikasi: ' + response.message, true);
                        }
                    } catch (error) {
                        showMessage('Kesalahan menghapus notifikasi: ' + error.message, true);
                    }
                });
            }

            // --- Manajemen Kategori ---
            async function fetchCategoriesAdmin() {
                categoryList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary); column-span: all;">Memuat daftar kategori...</p>';
                try {
                    const response = await makeApiRequest('categories.php?action=fetch_all');
                    if (response.success) {
                        renderCategoryList(response.categories);
                    } else {
                        showMessage('Gagal memuat kategori: ' + response.message, true);
                        categoryList.innerHTML = `<p style="text-align: center; color: var(--color-error); column-span: all;">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Kesalahan mengambil daftar kategori: ' + error.message, true);
                    categoryList.innerHTML = `<p style="text-align: center; color: var(--color-error); column-span: all;">${error.message}</p>`;
                }
            }

            function getCorrectedCategoryImagePath(originalPath) {
                if (originalPath.startsWith('./uploads/categories/')) {
                    return originalPath;
                }
                if (originalPath.startsWith('uploads/categories/')) {
                    return './' + originalPath;
                }
                return originalPath;
            }

            function renderCategoryList(categories) {
                categoryList.innerHTML = '';
                if (categories.length === 0) {
                    categoryList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary); column-span: all;">Tidak ada kategori.</p>';
                    return;
                }
                categories.forEach(category => {
                    const categoryItem = document.createElement('div');
                    categoryItem.classList.add('image-grid-item'); // Menggunakan kelas baru
                    const imageUrl = getCorrectedCategoryImagePath(category.imageUrl || 'https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Kategori');
                    categoryItem.innerHTML = `
                        <img src="${imageUrl}" alt="Gambar Kategori" onerror="this.onerror=null;this.src='https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Kategori';">
                        <div class="image-grid-item-content">
                            <strong class="image-grid-item-title">${category.name}</strong>
                            <span class="image-grid-item-detail">URL Gambar: ${category.imageUrl || 'N/A'}</span>
                        </div>
                        <div class="image-grid-item-actions">
                            <button class="delete-btn" data-name="${category.name}"><i class="fa-solid fa-trash-can"></i> Hapus</button>
                        </div>
                    `;
                    const deleteBtn = categoryItem.querySelector('.delete-btn');
                    if (deleteBtn) {
                         deleteBtn.onclick = () => deleteCategory(category.name);
                    }
                    categoryList.appendChild(categoryItem);
                });
            }

            async function deleteCategory(name) {
                showCustomConfirmation(`Apakah Anda yakin ingin menghapus kategori "${name}"?`, async () => {
                    try {
                        const response = await makeApiRequest('categories.php?action=delete', 'POST', { name });
                        if (response.success) {
                            showMessage('Kategori berhasil dihapus!', false);
                            fetchCategoriesAdmin();
                        } else {
                            showMessage('Gagal menghapus kategori: ' + response.message, true);
                        }
                    } catch (error) {
                        showMessage('Kesalahan menghapus kategori: ' + error.message, true);
                    }
                });
            }

            // --- Manajemen Pengguna ---
            async function fetchUsers() {
                userList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary);">Memuat daftar pengguna...</p>';
                try {
                    const response = await makeApiRequest('pins.php?action=fetch_all_users'); 
                    if (response.success) {
                        renderUserList(response.users);
                    } else {
                        showMessage('Gagal memuat pengguna: ' + response.message, true);
                        userList.innerHTML = `<p style="text-align: center; color: var(--color-error);">${response.message}</p>`;
                    }
                }
                catch (error) {
                    showMessage('Kesalahan mengambil daftar pengguna: ' + error.message, true);
                    userList.innerHTML = `<p style="text-align: center; color: var(--color-error);">${error.message}</p>`;
                }
            }

            function renderUserList(users) {
                userList.innerHTML = '';
                if (users.length === 0) {
                    userList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary);">Tidak ada pengguna terdaftar.</p>';
                    return;
                }

                const userLevels = ['tempted', 'Naughty', 'Sinful']; // Simplified levels for dropdown, excluding 'Pengguna Biasa' if it's not a true level

                users.forEach(user => {
                    const userItem = document.createElement('div');
                    userItem.classList.add('list-item');
                    
                    let deleteButtonHtml = '';
                    if (user.username === adminUsername) {
                        deleteButtonHtml = `<button class="delete-btn" disabled title="Anda tidak dapat menghapus akun Anda sendiri"><i class="fa-solid fa-trash-can"></i> Anda</button>`;
                    } else if (user.isAdmin) {
                         deleteButtonHtml = `<button class="delete-btn" disabled title="Tidak dapat menghapus admin lain"><i class="fa-solid fa-trash-can"></i> Admin</button>`;
                    } else {
                        deleteButtonHtml = `<button class="delete-btn" data-username="${user.username}"><i class="fa-solid fa-trash-can"></i> Hapus</button>`;
                    }

                    const canUploadChecked = user.canUpload ? 'checked' : '';
                    const canUploadDisabled = user.isAdmin || user.username === adminUsername ? 'disabled' : '';

                    const profileImageUrl = user.profile_image_url || 'https://placehold.co/60x60/e0e0e0/767676?text=Profil';
                    
                    // User Level Dropdown
                    let levelSelectHtml = '';
                    if (!user.isAdmin) { // Only show level control for non-admin users
                        levelSelectHtml = `
                            <div class="user-level-control">
                                <label for="userLevel_${user.username}">Level:</label>
                                <select id="userLevel_${user.username}" data-username="${user.username}" data-initial-level="${user.level || 'tempted'}">
                                    ${userLevels.map(level => `
                                        <option value="${level}" ${user.level === level ? 'selected' : ''}>
                                            ${level.charAt(0).toUpperCase() + level.slice(1)}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                        `;
                    } else {
                        levelSelectHtml = `<span class="list-item-detail">Level: Administrator</span>`;
                    }

                    userItem.innerHTML = `
                        <img src="${profileImageUrl}" alt="Profil Pengguna" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/60x60/e0e0e0/767676?text=Profil';">
                        <div class="list-item-content">
                            <strong class="list-item-title">${user.username}</strong>
                            <span class="list-item-detail">Email: ${user.email || 'N/A'}</span>
                            <span class="list-item-detail">Status: ${user.isAdmin ? 'Administrator' : 'Pengguna Biasa'}</span>
                            ${levelSelectHtml}
                            <div class="can-upload-toggle">
                                <label for="canUpload_${user.username}">Dapat Mengunggah:</label>
                                <label class="switch">
                                    <input type="checkbox" id="canUpload_${user.username}" data-username="${user.username}" ${canUploadChecked} ${canUploadDisabled}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="user-details-collapsed" id="userDetailsCollapsed_${user.username}">
                                <!-- Initial summary, hidden by default -->
                            </div>
                            <div class="user-details-expanded" id="userDetailsExpanded_${user.username}">
                                <span class="list-item-detail">Kategori Favorit: ${user.preferred_categories && user.preferred_categories.length > 0 ? user.preferred_categories.join(', ') : 'Belum diatur'}</span>
                                <span class="list-item-detail">Orang Favorit: ${user.preferred_persons && user.preferred_persons.length > 0 ? user.preferred_persons.join(', ') : 'Belum diatur'}</span>
                                <span class="list-item-detail">Orang Manual Diminta: ${user.manual_persons_requested && user.manual_persons_requested.length > 0 ? user.manual_persons_requested.join(', ') : 'Tidak ada'}</span>
                            </div>
                            <button class="expand-user-btn" data-username="${user.username}">Lihat Detail</button>
                        </div>
                        <div class="list-item-actions">
                            ${deleteButtonHtml}
                        </div>
                    `;
                    const deleteBtn = userItem.querySelector('.delete-btn:not(:disabled)');
                    if (deleteBtn) {
                         deleteBtn.onclick = () => deleteUser(user.username);
                    }

                    const canUploadToggle = userItem.querySelector(`#canUpload_${user.username}`);
                    if (canUploadToggle && !canUploadToggle.disabled) {
                        canUploadToggle.addEventListener('change', (e) => {
                            toggleUserUploadPermission(user.username, e.target.checked);
                        });
                    }

                    const userLevelSelect = userItem.querySelector(`#userLevel_${user.username}`);
                    if (userLevelSelect) {
                        userLevelSelect.addEventListener('change', function() {
                            const selectedLevel = this.value;
                            updateUserLevel(user.username, selectedLevel);
                        });
                    }

                    const expandButton = userItem.querySelector(`.expand-user-btn`);
                    const userDetailsExpanded = userItem.querySelector(`#userDetailsExpanded_${user.username}`);
                    
                    // Initially hide expanded details
                    userDetailsExpanded.style.display = 'none';

                    expandButton.addEventListener('click', () => {
                        if (userDetailsExpanded.style.display === 'none') {
                            userDetailsExpanded.style.display = 'block';
                            expandButton.textContent = 'Sembunyikan Detail';
                        } else {
                            userDetailsExpanded.style.display = 'none';
                            expandButton.textContent = 'Lihat Detail';
                        }
                    });

                    userList.appendChild(userItem);
                });
            }

            async function toggleUserUploadPermission(username, canUpload) {
                showCustomConfirmation(`Apakah Anda yakin ingin ${canUpload ? 'memberikan' : 'mencabut'} izin unggah untuk pengguna ${username}?`, async () => {
                    try {
                        const response = await makeApiRequest('auth.php?action=update_user_permission', 'POST', { username: username, canUpload: canUpload });
                        if (response.success) {
                            showMessage(`Izin unggah untuk ${username} berhasil diperbarui!`, false);
                            fetchUsers();
                        } else {
                            showMessage('Gagal memperbarui izin unggah: ' + response.message, true);
                            fetchUsers(); // Muat ulang untuk memastikan status yang benar jika gagal
                        }
                    } catch (error) {
                        showMessage('Kesalahan memperbarui izin unggah: ' + error.message, true);
                        fetchUsers(); // Muat ulang untuk memastikan status yang benar jika gagal
                    }
                });
            }

            async function updateUserLevel(username, level) {
                showCustomConfirmation(`Apakah Anda yakin ingin mengubah level pengguna ${username} menjadi "${level}"?`, async () => {
                    try {
                        // Menggunakan auth.php untuk update_user_permission yang sekarang juga menangani level
                        const response = await makeApiRequest('auth.php?action=update_user_permission', 'POST', { username: username, level: level });
                        if (response.success) {
                            showMessage(`Level pengguna ${username} berhasil diperbarui menjadi "${level}"!`, false);
                            fetchUsers(); // Muat ulang daftar pengguna untuk menampilkan perubahan
                        } else {
                            showMessage('Gagal memperbarui level pengguna: ' + response.message, true);
                            fetchUsers(); // Muat ulang untuk memastikan status yang benar jika gagal
                        }
                    } catch (error) {
                        showMessage('Kesalahan memperbarui level pengguna: ' + error.message, true);
                        fetchUsers(); // Muat ulang untuk memastikan status yang benar jika gagal
                    }
                });
            }

            async function deleteUser(username) {
                showCustomConfirmation(`Apakah Anda yakin ingin menghapus pengguna ${username} dan semua pin yang disimpan?`, async () => {
                    try {
                        const response = await makeApiRequest('pins.php?action=delete_user', 'POST', { username });
                        if (response.success) {
                            showMessage(`Pengguna ${username} berhasil dihapus!`, false);
                            fetchUsers();
                            fetchOverviewStats(); // Update overview after user deletion
                        } else {
                            showMessage('Gagal menghapus pengguna: ' + response.message, true);
                        }
                    } catch (error) {
                        showMessage('Kesalahan menghapus pengguna: ' + error.message, true);
                    }
                });
            }

            // --- Manajemen Pin ---
            async function fetchPinsForAdmin() {
                pinList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary); column-span: all;">Memuat daftar pin...</p>';
                try {
                    const response = await makeApiRequest('pins.php?action=fetch_all');
                    if (response.success) {
                        renderAdminPinList(response.pins);
                    } else {
                        showMessage('Gagal memuat pin: ' + response.message, true);
                        pinList.innerHTML = `<p style="text-align: center; color: var(--color-error); column-span: all;">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Kesalahan mengambil daftar pin: ' + error.message, true);
                    pinList.innerHTML = `<p style="text-align: center; color: var(--color-error); column-span: all;">${error.message}</p>`;
                }
            }

            function renderAdminPinList(pins) {
                pinList.innerHTML = '';
                if (pins.length === 0) {
                    pinList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary); column-span: all;">Tidak ada pin tersedia.</p>';
                    return;
                }

                pins.forEach(pin => {
                    // Admin should always see the original image
                    let imageUrl = 'https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Pin';
                    if (Array.isArray(pin.images) && pin.images.length > 0) {
                        imageUrl = pin.images[0].url_original || pin.images[0].url; // Use url_original first, fallback to old 'url'
                    } else if (typeof pin.img === 'string' && pin.img) { // Fallback for very old pins if 'images' array is not present
                        imageUrl = pin.img;
                    }

                    const pinDescription = pin.description || pin.content || 'N/A';
                    let categoriesText = 'N/A';
                    if (Array.isArray(pin.categories) && pin.categories.length > 0) {
                        categoriesText = pin.categories.join(', ');
                    } else if (typeof pin.category === 'string' && pin.category) {
                        categoriesText = pin.category;
                    }
                    let personTagsText = 'N/A';
                    if (Array.isArray(pin.personTags) && pin.personTags.length > 0) {
                        personTagsText = pin.personTags.join(', ');
                    }

                    const displayTypeHtml = pin.display_type ? `<span class="image-grid-item-detail">Tipe Tampilan: ${pin.display_type}</span>` : '';
                    const pinLevelHtml = pin.level ? `<span class="image-grid-item-detail">Level: ${pin.level}</span>` : ''; // Display pin level

                    const pinItem = document.createElement('div');
                    pinItem.classList.add('image-grid-item'); // Menggunakan kelas baru
                    pinItem.innerHTML = `
                        <img src="${imageUrl}" alt="Gambar Pin" onerror="this.onerror=null;this.src='https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Pin';">
                        <div class="image-grid-item-content">
                            <strong class="image-grid-item-title">ID: ${pin.id}</strong>
                            <span class="image-grid-item-detail">Sumber: ${pin.source || 'N/A'}</span>
                            <span class="image-grid-item-detail">Diunggah oleh: ${pin.uploadedBy || 'N/A'}</span>
                            <span class="image-grid-item-detail">Judul: ${pin.title || 'N/A'}</span>
                            <span class="image-grid-item-detail">Deskripsi: ${pinDescription}</span>
                            <span class="image-grid-item-detail">Kategori: ${categoriesText}</span>
                            <span class="image-grid-item-detail">Tag Orang: ${personTagsText}</span>
                            ${displayTypeHtml}
                            ${pinLevelHtml}
                        </div>
                        <div class="image-grid-item-actions">
                            <button class="delete-btn" data-id="${pin.id}"><i class="fa-solid fa-trash-can"></i> Hapus</button>
                        </div>
                    `;
                    const deleteBtn = pinItem.querySelector('.delete-btn');
                    if (deleteBtn) {
                         deleteBtn.onclick = () => deletePin(pin.id);
                    }
                    pinList.appendChild(pinItem);
                });
            }

            async function deletePin(pinId) {
                showCustomConfirmation(`Apakah Anda yakin ingin menghapus pin ${pinId}?`, async () => {
                    try {
                        const response = await makeApiRequest('pins.php?action=delete_pin', 'POST', { pinId });
                        if (response.success) {
                            showMessage('Pin berhasil dihapus!', false);
                            fetchPinsForAdmin();
                            fetchOverviewStats(); // Update overview after pin deletion
                        } else {
                            showMessage('Gagal menghapus pin: ' + response.message, true);
                        }
                    } catch (error) {
                        showMessage('Kesalahan menghapus pin: ' + error.message, true);
                    }
                });
            }

            // --- Manajemen Permintaan Orang Manual ---
            async function fetchManualPersonRequests() {
                manualPersonRequestList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary);">Memuat permintaan orang manual...</p>';
                try {
                    const response = await makeApiRequest('manual_person_requests.php?action=fetch_all');
                    if (response.success) {
                        renderManualPersonRequests(response.requests);
                    } else {
                        showMessage('Gagal memuat permintaan orang manual: ' + response.message, true);
                        manualPersonRequestList.innerHTML = `<p style="text-align: center; color: var(--color-error);">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Kesalahan mengambil daftar permintaan orang manual: ' + error.message, true);
                    manualPersonRequestList.innerHTML = `<p style="text-align: center; color: var(--color-error);">${error.message}</p>`;
                }
            }

            function renderManualPersonRequests(requests) {
                manualPersonRequestList.innerHTML = '';
                if (requests.length === 0) {
                    manualPersonRequestList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary);">Tidak ada permintaan orang manual.</p>';
                    return;
                }

                requests.forEach((request, index) => {
                    const requestItem = document.createElement('div');
                    requestItem.classList.add('list-item');
                    requestItem.innerHTML = `
                        <div class="list-item-content">
                            <strong class="list-item-title">${request.person_name}</strong>
                            <span class="list-item-detail">Diminta oleh: ${request.username || 'N/A'}</span>
                            <span class="list-item-detail">Waktu: ${request.timestamp || 'N/A'}</span>
                        </div>
                        <div class="list-item-actions">
                            <button class="accept-btn" data-index="${index}" data-person-name="${request.person_name}"><i class="fa-solid fa-check"></i> Terima</button>
                            <button class="reject-btn" data-index="${index}"><i class="fa-solid fa-times"></i> Tolak</button>
                        </div>
                    `;
                    const acceptBtn = requestItem.querySelector('.accept-btn');
                    const rejectBtn = requestItem.querySelector('.reject-btn');

                    if (acceptBtn) {
                         acceptBtn.onclick = () => acceptManualPersonRequest(index, request.person_name);
                    }
                    if (rejectBtn) {
                         rejectBtn.onclick = () => rejectManualPersonRequest(index);
                    }
                    manualPersonRequestList.appendChild(requestItem);
                });
            }

            async function acceptManualPersonRequest(index, personName) {
                showCustomConfirmation(`Apakah Anda yakin ingin MENERIMA permintaan "${personName}"? Ini akan menambahkannya ke daftar orang.`, async () => {
                    try {
                        const response = await makeApiRequest('manual_person_requests.php?action=accept', 'POST', { index: index, person_name: personName });
                        if (response.success) {
                            showMessage(`Permintaan "${personName}" berhasil diterima!`, false);
                            fetchManualPersonRequests();
                            fetchOverviewStats(); // Update overview after accepting person
                        } else {
                            showMessage('Gagal menerima permintaan: ' + response.message, true);
                        }
                    } catch (error) {
                        showMessage('Kesalahan menerima permintaan: ' + error.message, true);
                    }
                });
            }

            async function rejectManualPersonRequest(index) {
                showCustomConfirmation('Apakah Anda yakin ingin MENOLAK permintaan ini? Ini akan menghapusnya.', async () => {
                    try {
                        const response = await makeApiRequest('manual_person_requests.php?action=reject', 'POST', { index: index });
                        if (response.success) {
                            showMessage('Permintaan orang manual berhasil ditolak!', false);
                            fetchManualPersonRequests();
                        } else {
                            showMessage('Gagal menolak permintaan: ' + response.message, true);
                        }
                    } catch (error) {
                        showMessage('Kesalahan menolak permintaan: ' + error.message, true);
                    }
                });
            }

            // Drag and Drop for Category Image Upload
            dragAreaCategory.addEventListener('click', () => newCategoryImageFile.click());

            dragAreaCategory.addEventListener('dragover', (e) => {
                e.preventDefault();
                dragAreaCategory.classList.add('highlight');
            });

            dragAreaCategory.addEventListener('dragleave', () => {
                dragAreaCategory.classList.remove('highlight');
            });

            dragAreaCategory.addEventListener('drop', (e) => {
                e.preventDefault();
                dragAreaCategory.classList.remove('highlight');
                newCategoryImageFile.files = e.dataTransfer.files;
                updateSelectedCategoryImageIndicator();
            });

            newCategoryImageFile.addEventListener('change', updateSelectedCategoryImageIndicator);

            function updateSelectedCategoryImageIndicator() {
                if (newCategoryImageFile.files.length > 0) {
                    selectedCategoryImageIndicator.textContent = `${newCategoryImageFile.files.length} file dipilih: ${newCategoryImageFile.files[0].name}`;
                    selectedCategoryImageIndicator.style.display = 'block';
                } else {
                    selectedCategoryImageIndicator.textContent = '';
                    selectedCategoryImageIndicator.style.display = 'none';
                }
            }


            // Muatan awal untuk panel admin
            fetchOverviewStats(); // Load overview first
        });
    </script>
</body>
</html>
