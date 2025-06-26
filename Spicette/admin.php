<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
session_start();
// Memastikan hanya admin yang bisa mengakses halaman ini. Jika tidak login atau bukan admin, redirect ke halaman login.
if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header('Location: index.html?login=true'); // Redirect ke index dengan overlay login
    exit();
}
$adminUsername = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Spicette</title>
    <!-- Font Playfair Display untuk Judul -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Plus Jakarta Sans untuk Isi dan Umum -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome untuk Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css"> <!-- Pertahankan jika ada gaya global yang masih relevan -->
    <style>
        /* Apple-inspired Minimalist Design - Tanpa Box Shadow */
        :root {
            --primary: #007aff; /* Biru yang lebih cerah, mirip iOS */
            --primary-hover: #005bb5;
            --danger: #ff3b30; /* Merah cerah seperti iOS */
            --danger-hover: #cc0000;
            --text: #1a1a1a; /* Teks sangat gelap untuk kontras tinggi */
            --text-secondary: #6a6a6a; /* Abu-abu sekunder */
            --background: #f0f2f5; /* Latar belakang terang, sedikit lebih hangat */
            --card: #ffffff; /* Warna kartu putih bersih */
            --border: #ebebeb; /* Garis border yang sangat terang */
            --success: #34c759; /* Hijau cerah seperti iOS */
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased; /* Untuk teks yang lebih halus */
            -moz-osx-font-smoothing: grayscale; /* Untuk teks yang lebih halus */
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif; /* Font utama untuk body dan teks umum */
            background-color: var(--background);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            font-size: 15px; /* Ukuran font umum: 15px */
            font-weight: 400;
        }

        /* Gaya Header */
        header {
            background-color: var(--card);
            padding: 16px 30px;
            /* box-shadow: none; */ /* Hapus bayangan */
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-family: 'Playfair Display', serif; /* Font untuk logo */
            color: var(--primary);
            font-weight: 700; /* Font weight untuk logo tetap bold */
            font-size: 26px; /* Ukuran font logo */
            cursor: pointer;
            letter-spacing: -0.03em;
            transition: opacity 0.2s ease-out;
        }
        .logo:hover {
            opacity: 0.9;
        }

        .header-nav-links {
            display: flex;
            margin-left: 30px;
            gap: 15px;
        }

        .nav-button {
            background: none;
            border: none;
            padding: 10px 18px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .nav-button:hover {
            background-color: rgba(0, 0, 0, 0.05); /* Sedikit latar belakang saat hover */
            color: var(--text);
        }
        .nav-button.active {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }

        .search-container {
            flex-grow: 1;
            display: flex;
            justify-content: flex-end;
            margin-right: 25px;
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .icon-button {
            background: none; /* Tanpa latar belakang */
            border: none;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 20px;
        }
        .icon-button:hover {
            background-color: rgba(0, 0, 0, 0.05);
            color: var(--text);
        }

        .profile-icon {
            width: 36px;
            height: 36px;
            background-color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        /* Tata Letak Dashboard Utama */
        main.admin-dashboard-layout {
            display: flex;
            flex: 1;
            padding: 25px;
            gap: 25px;
        }

        /* Navigasi Sidebar */
        .admin-sidebar {
            width: 250px;
            background-color: var(--card);
            padding: 25px;
            border-radius: 12px;
            /* box-shadow: none; */ /* Hapus bayangan */
            flex-shrink: 0;
            transition: transform 0.3s ease-in-out, width 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border);
        }
        .admin-sidebar h2 {
            font-family: 'Playfair Display', serif; /* Font untuk judul sidebar */
            font-size: 24px; /* Ukuran font 24px */
            color: var(--text);
            margin-bottom: 35px;
            text-align: center;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .admin-sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }
        .admin-sidebar-nav li {
            margin-bottom: 10px;
        }
        .admin-sidebar-nav button {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 14px 20px;
            border: none;
            background-color: transparent;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px; /* Ukuran font 15px */
            font-weight: 500;
            color: var(--text-secondary);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .admin-sidebar-nav button:hover {
            background-color: rgba(0, 0, 0, 0.05); /* Sedikit latar belakang saat hover */
            color: var(--text);
            transform: translateX(5px);
        }
        .admin-sidebar-nav button.active {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            transform: translateX(0);
        }
        .admin-sidebar-nav button.active .fa-solid {
            color: white;
        }
        .admin-sidebar-nav button .fa-solid {
            margin-right: 15px;
            width: 20px;
            height: 20px;
            color: var(--text-secondary);
            transition: color 0.2s ease;
        }
        .admin-sidebar-nav button:hover .fa-solid {
            color: var(--text);
        }

        /* Area Konten */
        .admin-content-area {
            flex-grow: 1;
            padding: 0;
            overflow-y: auto;
            background-color: transparent;
        }

        .admin-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .admin-page-header h1 {
            font-family: 'Playfair Display', serif; /* Font untuk judul halaman */
            font-size: 24px; /* Ukuran font 24px */
            font-weight: 700;
            color: var(--text);
            margin: 0;
            letter-spacing: -0.03em;
        }

        .admin-logout-btn {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px; /* Ukuran font 15px */
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-logout-btn:hover {
            background-color: var(--danger-hover);
            transform: translateY(-1px);
        }

        /* Tampilan Pesan */
        #message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: none;
            font-weight: 500;
            text-align: center;
            animation: fadeInOut 4s forwards;
            border: 1px solid transparent;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px; /* Ukuran font 15px */
        }
        #message.error {
            background-color: rgba(255, 59, 48, 0.1); /* Merah muda lembut */
            color: var(--danger); /* Merah cerah */
            border-color: var(--danger);
        }
        #message:not(.error) {
            background-color: rgba(48, 209, 88, 0.1); /* Hijau muda lembut */
            color: var(--success); /* Hijau cerah seperti iOS */
            border-color: var(--success);
        }
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-10px); }
            10% { opacity: 1; transform: translateY(0); }
            90% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); }
        }


        /* Bagian Konten Tab */
        .admin-tab-content {
            display: none;
            animation: fadeIn 0.6s ease-out;
        }
        .admin-tab-content.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .admin-section {
            background-color: var(--card);
            padding: 30px;
            border-radius: 12px;
            /* box-shadow: none; */ /* Hapus bayangan */
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }

        .admin-section h2 {
            font-family: 'Playfair Display', serif; /* Font untuk judul bagian */
            font-size: 24px; /* Ukuran font 24px */
            color: var(--text);
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
            letter-spacing: -0.02em;
        }
        .admin-section h3 {
            font-family: 'Playfair Display', serif; /* Font untuk sub-judul bagian */
            font-size: 20px; /* Ukuran font 20px (lebih kecil dari 24px) */
            color: var(--text);
            margin-top: 35px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* Formulir */
        .admin-form label {
            display: block;
            margin-bottom: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 15px; /* Ukuran font 15px */
        }

        .admin-form input[type="text"],
        .admin-form input[type="url"],
        .admin-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px; /* Ukuran font 15px */
            color: var(--text);
            transition: all 0.2s ease;
            background-color: var(--card);
            /* box-shadow: none; */ /* Hapus bayangan */
        }
        .admin-form input[type="text"]:focus,
        .admin-form input[type="url"]:focus,
        .admin-form textarea:focus {
            border-color: var(--primary);
            /* box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.2); */ /* Hapus bayangan fokus */
            outline: none;
        }

        .admin-form textarea {
            resize: vertical;
            min-height: 100px;
        }

        .admin-form button[type="submit"] {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px; /* Ukuran font 15px */
            font-weight: 600;
            transition: all 0.2s ease;
            /* box-shadow: none; */ /* Hapus bayangan */
        }
        .admin-form button[type="submit"]:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            /* box-shadow: none; */ /* Hapus bayangan */
        }

        /* Daftar Item */
        .admin-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .list-item {
            display: flex;
            flex-direction: column;
            background-color: var(--card);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            /* box-shadow: none; */ /* Hapus bayangan */
            transition: transform 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .list-item:hover {
            transform: translateY(-5px);
            /* box-shadow: none; */ /* Hapus bayangan */
        }

        .list-item-img {
            width: 100%;
            height: 160px;
            border-radius: 8px;
            object-fit: cover;
            margin-bottom: 15px;
            border: 1px solid var(--border);
        }

        .list-item-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            padding-bottom: 10px;
        }

        .list-item-content strong {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16px; /* Ukuran font 15px, disesuaikan agar tidak terlalu kecil */
            color: var(--text);
            margin-bottom: 8px;
            font-weight: 600;
            line-height: 1.3;
        }

        .list-item-content span {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px; /* Ukuran font 15px, disesuaikan agar tidak terlalu kecil */
            color: var(--text-secondary);
            margin-bottom: 5px;
            display: block;
        }
        .list-item-content span:last-of-type {
            margin-bottom: 0;
        }

        .list-item-actions {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .list-item:hover .list-item-actions {
            opacity: 1;
        }

        .list-item-actions .delete-btn {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px; /* Ukuran font 14px */
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .list-item-actions .delete-btn:hover:not(:disabled) {
            background-color: var(--danger-hover);
            transform: translateY(-1px);
        }
        .list-item-actions .delete-btn:disabled {
            background-color: var(--border);
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* Switch Toggle untuk canUpload */
        .can-upload-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .can-upload-toggle label {
            margin: 0; /* Override default label margin */
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
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
            border-radius: 24px; /* Rounded slider */
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 50%; /* Rounded circle */
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary);
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(20px);
            -ms-transform: translateX(20px);
            transform: translateX(20px);
        }

        /* Hamburger Menu Icon (untuk mobile) */
        .hamburger-menu-icon {
            display: none; /* Default tersembunyi */
            background: none;
            border: none;
            cursor: pointer;
            position: relative;
            width: 28px;
            height: 20px;
            transition: all 0.3s ease;
            z-index: 1100;
        }
        .hamburger-menu-icon span {
            display: block;
            position: absolute;
            height: 2px;
            width: 100%;
            background: var(--text-secondary);
            border-radius: 2px;
            opacity: 1;
            left: 0;
            transform: rotate(0deg);
            transition: all 0.3s ease-in-out;
        }
        .hamburger-menu-icon span:nth-child(1) { top: 0px; }
        .hamburger-menu-icon span:nth-child(2) { top: 9px; }
        .hamburger-menu-icon span:nth-child(3) { top: 18px; }

        .hamburger-menu-icon.active span:nth-child(1) { top: 9px; transform: rotate(135deg); background: var(--primary); }
        .hamburger-menu-icon.active span:nth-child(2) { opacity: 0; left: -60px; }
        .hamburger-menu-icon.active span:nth-child(3) { top: 9px; transform: rotate(-135deg); background: var(--primary); }


        /* Modal Konfirmasi Kustom */
        #customConfirmationModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.45);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            font-family: 'Plus Jakarta Sans', sans-serif;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
        }
        #customConfirmationModal.show {
            opacity: 1;
            visibility: visible;
        }
        #customConfirmationModal > div {
            background: var(--card);
            padding: 35px;
            border-radius: 12px;
            /* box-shadow: none; */ /* Hapus bayangan */
            max-width: 420px;
            text-align: center;
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid var(--border);
        }
        #customConfirmationModal.show > div {
            transform: scale(1);
            opacity: 1;
        }
        #customConfirmationModal p {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px; /* Ukuran font 15px */
            margin-bottom: 28px;
            color: var(--text);
            font-weight: 500;
            line-height: 1.5;
        }
        #customConfirmationModal button {
            border: none;
            padding: 11px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px; /* Ukuran font 15px */
            font-weight: 600;
            transition: all 0.2s ease;
        }
        #customConfirmationModal #confirmYes {
            background-color: var(--primary);
            color: white;
        }
        #customConfirmationModal #confirmYes:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        #customConfirmationModal #confirmNo {
            background-color: rgba(0, 0, 0, 0.05); /* Latar belakang tombol sekunder */
            color: var(--text-secondary);
            margin-left: 15px;
        }
        #customConfirmationModal #confirmNo:hover {
            background-color: rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        /* Gaya khusus seluler (disesuaikan untuk tata letak baru) */
        @media (max-width: 992px) { /* Tablet dan seluler besar */
            header {
                padding: 14px 20px;
            }
            .header-nav-links, .search-container {
                display: none;
            }
            /* Ikon hamburger hanya muncul di seluler */
            .hamburger-menu-icon {
                display: block;
                margin-left: auto;
            }
            main.admin-dashboard-layout {
                flex-direction: column;
                padding: 20px;
                gap: 20px;
            }
            .admin-sidebar {
                position: fixed;
                top: 60px; /* Sesuaikan dengan tinggi header */
                left: 0;
                height: calc(100vh - 60px);
                transform: translateX(-100%); /* Sembunyikan secara default */
                z-index: 1050;
                /* box-shadow: none; */ /* Hapus bayangan */
                width: 75%;
                padding-top: 20px;
                overflow-y: auto;
                border-radius: 0 12px 12px 0;
                border-right: 1px solid var(--border); /* Tetap ada batas kanan saat terbuka */
            }
            .admin-sidebar.active {
                transform: translateX(0%); /* Tampilkan sidebar saat aktif */
            }
            .admin-sidebar h2 {
                font-size: 20px; /* Ukuran font judul sidebar */
                margin-bottom: 25px;
            }
            .admin-sidebar-nav button {
                font-size: 15px;
                padding: 10px 15px;
            }
            .admin-sidebar-nav button .fa-solid {
                margin-right: 10px;
                width: 16px;
                height: 16px;
            }

            .admin-page-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 25px;
                padding-bottom: 12px;
            }
            .admin-page-header h1 {
                font-size: 24px; /* Ukuran font judul halaman seluler */
            }
            .admin-page-header .admin-logout-btn {
                margin-top: 15px;
                width: 100%;
                justify-content: center;
                padding: 8px 16px;
                font-size: 14px;
            }
            .admin-section {
                padding: 20px;
                margin-bottom: 20px;
            }
            .admin-section h2 {
                font-size: 20px; /* Ukuran font judul bagian seluler */
                margin-bottom: 15px;
                padding-bottom: 10px;
            }
            .admin-section h3 {
                font-size: 18px; /* Ukuran font sub-judul bagian seluler */
                margin-top: 25px;
                margin-bottom: 15px;
            }
            .admin-form input, .admin-form textarea {
                padding: 10px;
                font-size: 14px;
                margin-bottom: 15px;
            }
            .admin-form label {
                font-size: 14px;
                margin-bottom: 6px;
            }
            .admin-form button[type="submit"] {
                padding: 10px 20px;
                font-size: 15px;
            }
            .admin-list {
                grid-template-columns: 1fr; /* Satu kolom di seluler */
            }
            .list-item {
                padding: 18px;
            }
            .list-item-img {
                height: 140px;
            }
            .list-item-content strong {
                font-size: 15px;
            }
            .list-item-content span {
                font-size: 13px;
            }
            .list-item-actions {
                opacity: 1; /* Selalu tampilkan aksi di seluler */
                justify-content: center;
                padding-top: 10px;
            }
            .list-item-actions .delete-btn {
                width: 100%;
                justify-content: center;
                padding: 7px 14px;
                font-size: 13px;
            }

            #customConfirmationModal > div {
                max-width: 90%;
                padding: 25px;
            }
            #customConfirmationModal p {
                font-size: 15px;
            }
            #customConfirmationModal button {
                padding: 8px 16px;
                font-size: 14px;
            }
        }

        @media (max-width: 576px) { /* Seluler yang lebih kecil */
            header {
                padding: 10px 15px;
            }
            .logo {
                font-size: 22px; /* Ukuran logo lebih kecil */
            }
            .icon-button {
                padding: 8px;
                font-size: 18px;
            }
            .profile-icon {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
            .hamburger-menu-icon {
                width: 24px;
                height: 18px;
            }
            .hamburger-menu-icon span:nth-child(2) { top: 8px; }
            .hamburger-menu-icon span:nth-child(3) { top: 16px; }
            .hamburger-menu-icon.active span:nth-child(1),
            .hamburger-menu-icon.active span:nth-child(3) { top: 8px; }

            main.admin-dashboard-layout {
                padding: 15px;
                gap: 15px;
            }
            .admin-sidebar {
                top: 50px;
                height: calc(100vh - 50px);
                width: 90%;
            }
            .admin-sidebar h2 {
                font-size: 18px;
                margin-bottom: 20px;
            }
            .admin-sidebar-nav button {
                font-size: 14px;
                padding: 8px 12px;
            }
            .admin-page-header h1 {
                font-size: 22px;
            }
            .admin-section {
                padding: 15px;
                margin-bottom: 15px;
            }
            .admin-section h2 {
                font-size: 18px;
                margin-bottom: 12px;
                padding-bottom: 8px;
            }
            .admin-section h3 {
                font-size: 16px;
                margin-top: 20px;
                margin-bottom: 12px;
            }
            .admin-form input, .admin-form textarea {
                padding: 8px;
                font-size: 14px;
                margin-bottom: 12px;
            }
            .admin-form label {
                font-size: 13px;
                margin-bottom: 5px;
            }
            .admin-form button[type="submit"] {
                padding: 8px 16px;
                font-size: 14px;
            }
            .list-item {
                padding: 15px;
            }
            .list-item-img {
                height: 100px;
            }
            .list-item-content strong {
                font-size: 14px;
            }
            .list-item-content span {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo" onclick="window.location.href='index.html'">Spicette</div>
        <!-- Navigasi Header: Disembunyikan di sini untuk admin, bisa diaktifkan jika perlu -->
        <div class="header-nav-links" style="display: none;">
            <button class="nav-button" data-nav="home" onclick="window.location.href='index.html'">Beranda</button>
            <button class="nav-button active">Panel Admin</button>
        </div>

        <!-- Search Container: Dihapus atau disembunyikan untuk desain minimalis di header ini -->
        <div class="search-container" style="display: none;"></div> 

        <div class="header-icons">
            <button class="icon-button" aria-label="Profil Admin" onclick="window.location.href='admin.php'"><div class="profile-icon">A</div></button>
            <button class="icon-button" aria-label="Keluar Admin" id="adminLogoutBtnHeader">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
            <!-- Ikon Hamburger Menu hanya untuk seluler -->
            <button class="hamburger-menu-icon" aria-label="Menu" id="hamburgerMenuButton">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <main class="admin-dashboard-layout">
        <aside class="admin-sidebar" id="adminSidebar">
            <h2>Dashboard</h2>
            <nav class="admin-sidebar-nav">
                <ul>
                    <li>
                        <button class="admin-tab-button active" data-tab="notifications">
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
                </ul>
            </nav>
        </aside>

        <section class="admin-content-area" id="adminContentArea">
            <div class="admin-page-header">
                <h1>Dashboard Admin</h1>
                <button class="admin-logout-btn" id="adminLogoutBtnContent">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Keluar
                </button>
            </div>

            <div id="message"></div>

            <div id="notificationsTabContent" class="admin-tab-content active">
                <div class="admin-section">
                    <h2>Pengaturan Notifikasi</h2>
                    <form class="admin-form" id="addNotificationForm">
                        <label for="newNotificationText">Teks Notifikasi Baru:</label>
                        <textarea id="newNotificationText" placeholder="Masukkan teks notifikasi baru" required></textarea>
                        <button type="submit">Tambah Notifikasi</button>
                    </form>
                    <h3>Notifikasi Aktif</h3>
                    <div class="admin-list" id="notificationList">
                        <p style="text-align: center; color: var(--text-secondary);">Memuat notifikasi...</p>
                    </div>
                </div>
            </div>

            <div id="categoriesTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Manajemen Kategori</h2>
                    <form class="admin-form" id="addCategoryForm">
                        <label for="newCategoryName">Nama Kategori Baru:</label>
                        <input type="text" id="newCategoryName" placeholder="Contoh: Desain Grafis" required>
                        <label for="newCategoryImageUrl">URL Gambar Kategori (opsional):</label>
                        <input type="url" id="newCategoryImageUrl" placeholder="Contoh: https://picsum.photos/200/200">
                        <button type="submit">Tambah Kategori</button>
                    </form>
                    <h3>Kategori Aktif</h3>
                    <div class="admin-list" id="categoryList">
                        <p style="text-align: center; color: var(--text-secondary);">Memuat daftar kategori...</p>
                    </div>
                </div>
            </div>

            <div id="usersTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Manajemen Pengguna</h2>
                    <div class="admin-list" id="userList">
                        <p style="text-align: center; color: var(--text-secondary);">Memuat daftar pengguna...</p>
                    </div>
                </div>
            </div>

            <div id="pinsTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Manajemen Pin</h2>
                    <div class="admin-list" id="pinList">
                        <p style="text-align: center; color: var(--text-secondary);">Memuat daftar pin...</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal Konfirmasi Kustom -->
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
            const adminSidebar = document.getElementById('adminSidebar');
            const messageDiv = document.getElementById('message');
            const adminLogoutBtnHeader = document.getElementById('adminLogoutBtnHeader');
            const adminLogoutBtnContent = document.getElementById('adminLogoutBtnContent');

            // Elemen formulir
            const addNotificationForm = document.getElementById('addNotificationForm');
            const newNotificationText = document.getElementById('newNotificationText');
            const notificationList = document.getElementById('notificationList');

            const addCategoryForm = document.getElementById('addCategoryForm');
            const newCategoryName = document.getElementById('newCategoryName');
            const newCategoryImageUrl = document.getElementById('newCategoryImageUrl');
            const categoryList = document.getElementById('categoryList');

            const userList = document.getElementById('userList');
            const pinList = document.getElementById('pinList');

            // Elemen modal
            const customConfirmationModal = document.getElementById('customConfirmationModal');
            const modalMessage = document.getElementById('modalMessage');
            const confirmYesButton = document.getElementById('confirmYes');
            const confirmNoButton = document.getElementById('confirmNo');

            const adminUsername = '<?php echo $adminUsername; ?>';

            // --- URL Dasar API ---
            const API_BASE_URL = 'api/';

            // --- Fungsi Pembantu untuk Permintaan API ---
            async function makeApiRequest(endpoint, method = 'GET', data = null) {
                try {
                    const options = { method };
                    if (data !== null && typeof data !== 'undefined' && method !== 'GET') {
                        options.headers = { 'Content-Type': 'application/json' };
                        options.body = JSON.stringify(data);
                    } else if (method === 'POST' && (data === null || typeof data === 'undefined')) {
                        // Tidak perlu menghapus options.headers; biarkan saja jika tidak ada body.
                    }

                    const response = await fetch(API_BASE_URL + endpoint, options);
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`Kesalahan HTTP! Status: ${response.status} - ${errorText}`);
                    }
                    const textResponse = await response.text();
                    if (!textResponse) {
                        return { success: true, message: 'Tidak ada konten' };
                    }
                    try {
                        const jsonResponse = JSON.parse(textResponse);
                        return jsonResponse;
                    } catch (e) {
                        console.error('Gagal mengurai respons JSON:', textResponse);
                        throw new Error(`Respons JSON tidak valid: ${textResponse}`);
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
                messageDiv.className = isError ? 'error' : ''; // class 'error' atau kosong
                messageDiv.style.display = 'block';
                // Reset dan tambahkan kembali kelas untuk memulai ulang animasi jika pesan datang dengan cepat
                messageDiv.classList.remove('fadeInOut');
                void messageDiv.offsetWidth; // Memicu reflow
                messageDiv.classList.add('fadeInOut');

                // Animasi menangani penyembunyian, jadi tidak perlu setTimeout di sini kecuali untuk fallback non-animasi
            }

            // --- Modal Konfirmasi Kustom ---
            function showCustomConfirmation(message, onConfirmCallback) {
                modalMessage.textContent = message;
                customConfirmationModal.classList.add('show');

                const handleConfirm = () => {
                    customConfirmationModal.classList.remove('show');
                    onConfirmCallback();
                    // Hapus event listener untuk mencegah panggilan ganda
                    confirmYesButton.removeEventListener('click', handleConfirm);
                    confirmNoButton.removeEventListener('click', handleCancel);
                };

                const handleCancel = () => {
                    customConfirmationModal.classList.remove('show');
                    // Hapus event listener
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
                    } catch (error) {
                        showMessage('Kesalahan selama proses logout: ' + error.message, true);
                    }
                });
            }
            adminLogoutBtnHeader.addEventListener('click', handleAdminLogout);
            adminLogoutBtnContent.addEventListener('click', handleAdminLogout);

            // --- Fungsionalitas Tab Admin ---
            adminTabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;

                    // Hapus kelas aktif dari semua tombol dan konten
                    adminTabButtons.forEach(btn => btn.classList.remove('active'));
                    adminTabContents.forEach(content => content.classList.remove('active'));

                    // Tambahkan kelas aktif ke tombol yang diklik dan konten yang sesuai
                    this.classList.add('active');
                    document.getElementById(targetTab + 'TabContent').classList.add('active');

                    // Sembunyikan sidebar di seluler setelah seleksi
                    if (window.innerWidth <= 992) {
                        adminSidebar.classList.remove('active');
                        hamburgerMenuButton.classList.remove('active');
                    }

                    // Muat ulang data untuk tab yang dipilih
                    switch(targetTab) {
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
                    }
                });
            });

            // Toggle menu hamburger untuk seluler
            hamburgerMenuButton.addEventListener('click', () => {
                adminSidebar.classList.toggle('active');
                hamburgerMenuButton.classList.toggle('active');
            });

            // Tutup sidebar jika diklik di luar (di seluler)
            document.addEventListener('click', (event) => {
                const isClickInsideSidebar = adminSidebar.contains(event.target);
                const isClickOnHamburger = hamburgerMenuButton.contains(event.target);

                if (window.innerWidth <= 992 && adminSidebar.classList.contains('active') && !isClickInsideSidebar && !isClickOnHamburger) {
                    adminSidebar.classList.remove('active');
                    hamburgerMenuButton.classList.remove('active');
                }
            });

            // --- Manajemen Notifikasi ---
            async function fetchNotifications() {
                notificationList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Memuat notifikasi...</p>';
                try {
                    const response = await makeApiRequest('notifications.php?action=fetch_all');
                    if (response.success) {
                        renderNotificationList(response.notifications);
                    } else {
                        showMessage('Gagal memuat notifikasi: ' + response.message, true);
                        notificationList.innerHTML = `<p style="text-align: center; color: var(--danger);">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Kesalahan mengambil daftar notifikasi: ' + error.message, true);
                    notificationList.innerHTML = `<p style="text-align: center; color: var(--danger);">${error.message}</p>`;
                }
            }

            function renderNotificationList(notifications) {
                notificationList.innerHTML = '';
                if (notifications.length === 0) {
                    notificationList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Tidak ada notifikasi aktif.</p>';
                    return;
                }

                notifications.forEach(notif => {
                    const notifItem = document.createElement('div');
                    notifItem.classList.add('list-item');
                    notifItem.innerHTML = `
                        <div class="list-item-content">
                            <strong class="list-item-title">${notif.text}</strong>
                            <span class="list-item-detail">ID: ${notif.id}</span>
                            <span class="list-item-detail">Ditambahkan: ${notif.timestamp || 'N/A'}</span>
                            <span class="list-item-detail">Status: ${notif.read ? 'Sudah Dibaca' : 'Belum Dibaca'}</span>
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

            addNotificationForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const text = newNotificationText.value.trim();
                if (!text) {
                    showMessage('Teks notifikasi tidak boleh kosong.', true);
                    return;
                }

                try {
                    const response = await makeApiRequest('notifications.php?action=add', 'POST', { text });
                    if (response.success) {
                        showMessage('Notifikasi berhasil ditambahkan!', false);
                        newNotificationText.value = '';
                        fetchNotifications();
                    } else {
                        showMessage('Gagal menambahkan notifikasi: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Kesalahan menambahkan notifikasi: ' + error.message, true);
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
                categoryList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Memuat daftar kategori...</p>';
                try {
                    const response = await makeApiRequest('categories.php?action=fetch_all');
                    if (response.success) {
                        renderCategoryList(response.categories);
                    } else {
                        showMessage('Gagal memuat kategori: ' + response.message, true);
                        categoryList.innerHTML = `<p style="text-align: center; color: var(--danger);">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Kesalahan mengambil daftar kategori: ' + error.message, true);
                    categoryList.innerHTML = `<p style="text-align: center; color: var(--danger);">${error.message}</p>`;
                }
            }

            function renderCategoryList(categories) {
                categoryList.innerHTML = '';
                if (categories.length === 0) {
                    categoryList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Tidak ada kategori.</p>';
                    return;
                }
                categories.forEach(category => {
                    const categoryItem = document.createElement('div');
                    categoryItem.classList.add('list-item');
                    categoryItem.innerHTML = `
                        <img src="${category.imageUrl || 'https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Kategori'}" alt="Gambar Kategori" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Kategori';">
                        <div class="list-item-content">
                            <strong class="list-item-title">${category.name}</strong>
                            <span class="list-item-detail">URL Gambar: ${category.imageUrl || 'N/A'}</span>
                        </div>
                        <div class="list-item-actions">
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

            addCategoryForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const categoryName = newCategoryName.value.trim();
                const categoryImageUrl = newCategoryImageUrl.value.trim();
                if (!categoryName) {
                    showMessage('Nama kategori tidak boleh kosong.', true);
                    return;
                }
                try {
                    const response = await makeApiRequest('categories.php?action=add', 'POST', { name: categoryName, imageUrl: categoryImageUrl });
                    if (response.success) {
                        showMessage('Kategori berhasil ditambahkan!', false);
                        newCategoryName.value = '';
                        newCategoryImageUrl.value = '';
                        fetchCategoriesAdmin();
                    } else {
                        showMessage('Gagal menambahkan kategori: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Kesalahan menambahkan kategori: ' + error.message, true);
                }
            });

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
                userList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Memuat daftar pengguna...</p>';
                try {
                    // Menggunakan endpoint pins.php karena memiliki akses ke users.json
                    const response = await makeApiRequest('pins.php?action=fetch_all_users'); 
                    if (response.success) {
                        renderUserList(response.users);
                    } else {
                        showMessage('Gagal memuat pengguna: ' + response.message, true);
                        userList.innerHTML = `<p style="text-align: center; color: var(--danger);">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Kesalahan mengambil daftar pengguna: ' + error.message, true);
                    userList.innerHTML = `<p style="text-align: center; color: var(--danger);">${error.message}</p>`;
                }
            }

            function renderUserList(users) {
                userList.innerHTML = '';
                if (users.length === 0) {
                    userList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Tidak ada pengguna terdaftar.</p>';
                    return;
                }

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

                    // Tambahkan toggle canUpload
                    const canUploadChecked = user.canUpload ? 'checked' : '';
                    const canUploadDisabled = user.isAdmin || user.username === adminUsername ? 'disabled' : ''; // Admin tidak dapat mengubah izin unggah dirinya sendiri atau admin lain
                    
                    userItem.innerHTML = `
                        <div class="list-item-content">
                            <strong class="list-item-title">${user.username}</strong>
                            <span class="list-item-detail">Status: ${user.isAdmin ? 'Administrator' : 'Pengguna Biasa'}</span>
                            <div class="can-upload-toggle">
                                <label for="canUpload_${user.username}">Dapat Mengunggah:</label>
                                <label class="switch">
                                    <input type="checkbox" id="canUpload_${user.username}" data-username="${user.username}" ${canUploadChecked} ${canUploadDisabled}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="list-item-actions">
                            ${deleteButtonHtml}
                        </div>
                    `;
                    const deleteBtn = userItem.querySelector('.delete-btn:not(:disabled)'); // Pilih hanya jika tidak dinonaktifkan
                    if (deleteBtn) {
                         deleteBtn.onclick = () => deleteUser(user.username);
                    }

                    // Tambahkan event listener untuk toggle canUpload
                    const canUploadToggle = userItem.querySelector(`#canUpload_${user.username}`);
                    if (canUploadToggle && !canUploadToggle.disabled) {
                        canUploadToggle.addEventListener('change', (e) => {
                            toggleUserUploadPermission(user.username, e.target.checked);
                        });
                    }

                    userList.appendChild(userItem);
                });
            }

            async function toggleUserUploadPermission(username, canUpload) {
                showCustomConfirmation(`Apakah Anda yakin ingin ${canUpload ? 'memberikan' : 'mencabut'} izin unggah untuk pengguna ${username}?`, async () => {
                    try {
                        const response = await makeApiRequest('auth.php?action=update_user_permission', 'POST', { username: username, canUpload: canUpload });
                        if (response.success) {
                            showMessage(`Izin unggah untuk ${username} berhasil diperbarui!`, false);
                            fetchUsers(); // Muat ulang daftar pengguna
                        } else {
                            showMessage('Gagal memperbarui izin unggah: ' + response.message, true);
                            fetchUsers(); // Muat ulang daftar pengguna untuk menyinkronkan status
                        }
                    } catch (error) {
                        showMessage('Kesalahan memperbarui izin unggah: ' + error.message, true);
                        fetchUsers(); // Muat ulang daftar pengguna untuk menyinkronkan status
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
                        } else {
                            showMessage('Gagal menghapus pengguna: ' + response.message, true);
                        }
                    } catch (error) {
                        showMessage('Kesalahan menghapus pengguna: ' + error.message, true);
                    }
                });
            }

            // --- Manajemen Pin (Hanya hapus untuk admin di sini) ---
            async function fetchPinsForAdmin() {
                pinList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Memuat daftar pin...</p>';
                try {
                    const response = await makeApiRequest('pins.php?action=fetch_all');
                    if (response.success) {
                        renderAdminPinList(response.pins);
                    } else {
                        showMessage('Gagal memuat pin: ' + response.message, true);
                        pinList.innerHTML = `<p style="text-align: center; color: var(--danger);">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Kesalahan mengambil daftar pin: ' + error.message, true);
                    pinList.innerHTML = `<p style="text-align: center; color: var(--danger);">${error.message}</p>`;
                }
            }

            function renderAdminPinList(pins) {
                pinList.innerHTML = '';
                if (pins.length === 0) {
                    pinList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Tidak ada pin tersedia.</p>';
                    return;
                }

                pins.forEach(pin => {
                    // --- Ambil URL Gambar ---
                    let imageUrl = 'https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Pin'; // Placeholder default
                    if (Array.isArray(pin.images) && pin.images.length > 0) { // Struktur baru: 'images' array
                        imageUrl = pin.images[0].url;
                    } else if (typeof pin.img === 'string' && pin.img) { // Struktur lama: 'img' string
                        imageUrl = pin.img;
                    }

                    // --- Ambil Deskripsi/Konten ---
                    const pinDescription = pin.description || pin.content || 'N/A'; // Baru: 'description', Lama: 'content'

                    // --- Ambil Kategori (sekarang array) ---
                    let categoriesText = 'N/A';
                    if (Array.isArray(pin.categories) && pin.categories.length > 0) { // Struktur baru: 'categories' array
                        categoriesText = pin.categories.join(', ');
                    } else if (typeof pin.category === 'string' && pin.category) { // Struktur lama: 'category' string tunggal
                        categoriesText = pin.category;
                    }

                    // --- Ambil Tipe Tampilan (opsional) ---
                    const displayTypeHtml = pin.display_type ? `<span class="list-item-detail">Tipe Tampilan: ${pin.display_type}</span>` : '';

                    const pinItem = document.createElement('div');
                    pinItem.classList.add('list-item');
                    pinItem.innerHTML = `
                        <img src="${imageUrl}" alt="Gambar Pin" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Pin';">
                        <div class="list-item-content">
                            <strong class="list-item-title">ID: ${pin.id}</strong>
                            <span class="list-item-detail">Sumber: ${pin.source || 'N/A'}</span>
                            <span class="list-item-detail">Diunggah oleh: ${pin.uploadedBy || 'N/A'}</span>
                            <span class="list-item-detail">Judul: ${pin.title || 'N/A'}</span>
                            <span class="list-item-detail">Deskripsi: ${pinDescription}</span>
                            <span class="list-item-detail">Kategori: ${categoriesText}</span>
                            ${displayTypeHtml}
                        </div>
                        <div class="list-item-actions">
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
                        } else {
                            showMessage('Gagal menghapus pin: ' + response.message, true);
                        }
                    } catch (error) {
                        showMessage('Kesalahan menghapus pin: ' + error.message, true);
                    }
                });
            }

            // Muatan awal untuk panel admin (muat tab default)
            fetchNotifications();
        });
    </script>
</body>
</html>
