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
    <style>
        /* Glassmorphism-inspired Design */
        :root {
            --primary: #007aff; /* Biru iOS */
            --primary-hover: #005bb5;
            --danger: #ff3b30; /* Merah iOS */
            --danger-hover: #cc0000;
            --success: #34c759; /* Hijau iOS */
            --text: #ffffff; /* Teks putih untuk kontras pada glass */
            --text-secondary: #d1d1d1; /* Abu-abu terang untuk teks sekunder */
            --background: #1a1a1a; /* Latar belakang gelap untuk glassmorphism */
            --card: rgba(255, 255, 255, 0.1); /* Glass effect untuk kartu */
            --card-hover: rgba(255, 255, 255, 0.15);
            --border: rgba(255, 255, 255, 0.2); /* Border transparan */
            --blur: blur(12px); /* Efek blur untuk glassmorphism */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            background: linear-gradient(135deg, #1a1a1a, #2c2c2c); /* Gradient gelap untuk depth */
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            font-size: 15px;
            font-weight: 400;
        }

        /* Header */
        header {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: var(--blur);
            padding: 16px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-family: 'Playfair Display', serif;
            color: var(--text);
            font-weight: 700;
            font-size: 28px;
            cursor: pointer;
            letter-spacing: -0.04em;
            transition: all 0.3s ease;
        }
        .logo:hover {
            color: var(--primary);
            transform: translateY(-1px);
        }

        .header-nav-links, .search-container {
            display: none; /* Disembunyikan untuk admin */
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .icon-button {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: var(--blur);
            border: 1px solid var(--border);
            cursor: pointer;
            padding: 10px;
            border-radius: 12px;
            transition: all 0.3s ease;
            color: var(--text-secondary);
            font-size: 22px;
        }
        .icon-button:hover {
            background: var(--card-hover);
            color: var(--text);
            transform: scale(1.05);
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            border: 1px solid var(--border);
        }
        .profile-icon:hover {
            background: var(--primary-hover);
        }

        /* Hamburger Menu */
        .hamburger-menu-container {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--card);
            backdrop-filter: var(--blur);
            cursor: pointer;
            transition: all 0.3s ease;
            align-items: center;
            justify-content: center;
        }
        .hamburger-menu-container:hover {
            background: var(--card-hover);
            transform: scale(1.05);
        }

        .hamburger-menu-icon {
            background: none;
            border: none;
            cursor: pointer;
            width: 24px;
            height: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .hamburger-menu-icon span {
            height: 2px;
            width: 100%;
            background: var(--text-secondary);
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .hamburger-menu-icon.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); background: var(--primary); }
        .hamburger-menu-icon.active span:nth-child(2) { opacity: 0; }
        .hamburger-menu-icon.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); background: var(--primary); }

        /* Dashboard Layout */
        main.admin-dashboard-layout {
            display: flex;
            flex: 1;
            padding: 25px;
            gap: 25px;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 250px;
            background: var(--card);
            backdrop-filter: var(--blur);
            padding: 25px;
            border-radius: 12px;
            flex-shrink: 0;
            transition: transform 0.3s ease-in-out, width 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border);
        }
        .admin-sidebar h2 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: var(--text);
            margin-bottom: 35px;
            text-align: center;
            font-weight: 700;
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
            background: transparent;
            font-size: 15px;
            font-weight: 500;
            color: var(--text-secondary);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .admin-sidebar-nav button:hover {
            background: var(--card-hover);
            color: var(--text);
            transform: translateX(5px);
        }
        .admin-sidebar-nav button.active {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }
        .admin-sidebar-nav button.active .fa-solid {
            color: white;
        }
        .admin-sidebar-nav button .fa-solid {
            margin-right: 15px;
            width: 20px;
            height: 20px;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }
        .admin-sidebar-nav button:hover .fa-solid {
            color: var(--text);
        }

        /* Content Area */
        .admin-content-area {
            flex-grow: 1;
            padding: 0;
            overflow-y: auto;
            background: transparent;
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
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
        }
        .admin-logout-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-logout-btn:hover {
            background: var(--danger-hover);
            transform: translateY(-2px);
        }

        /* Message */
        #message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: none;
            font-weight: 500;
            text-align: center;
            animation: fadeInOut 4s forwards;
            background: var(--card);
            backdrop-filter: var(--blur);
            border: 1px solid var(--border);
        }
        #message.error {
            background: rgba(255, 59, 48, 0.2);
            color: var(--danger);
            border-color: var(--danger);
        }
        #message:not(.error) {
            background: rgba(48, 209, 88, 0.2);
            color: var(--success);
            border-color: var(--success);
        }
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-10px); }
            10% { opacity: 1; transform: translateY(0); }
            90% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); }
        }

        /* Tab Content */
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
            background: var(--card);
            backdrop-filter: var(--blur);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }
        .admin-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: var(--text);
            margin-bottom: 20px;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }
        .admin-section h3 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: var(--text);
            margin-top: 35px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* Form */
        .admin-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 15px;
        }
        .admin-form input[type="text"],
        .admin-form input[type="url"],
        .admin-form input[type="file"],
        .admin-form textarea,
        .admin-form select { /* Added select for user levels */
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            color: var(--text);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: var(--blur);
            transition: all 0.3s ease;
        }
        .admin-form input[type="file"] {
            padding: 8px 12px;
        }
        .admin-form input[type="text"]:focus,
        .admin-form input[type="url"]:focus,
        .admin-form input[type="file"]:focus,
        .admin-form textarea:focus,
        .admin-form select:focus { /* Added select for user levels */
            border-color: var(--primary);
            background: var(--card-hover);
            outline: none;
        }
        .admin-form textarea {
            resize: vertical;
            min-height: 100px;
        }
        .admin-form button[type="submit"] {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .admin-form button[type="submit"]:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        /* List Items */
        .admin-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .list-item {
            background: var(--card);
            backdrop-filter: var(--blur);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .list-item:hover {
            background: var(--card-hover);
            transform: translateY(-5px);
        }
        .list-item-img {
            width: 100%;
            height: 160px;
            border-radius: 8px;
            object-fit: cover;
            margin-bottom: 15px;
            border: 1px solid var(--border);
        }
        .list-item-content strong {
            font-size: 16px;
            color: var(--text);
            margin-bottom: 8px;
            font-weight: 600;
        }
        .list-item-content span {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 5px;
            display: block;
        }
        .notification-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-top: 5px;
            display: inline-block;
        }
        .notification-link:hover {
            text-decoration: underline;
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
            background: var(--danger);
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .list-item-actions .delete-btn:hover:not(:disabled) {
            background: var(--danger-hover);
            transform: translateY(-2px);
        }
        .list-item-actions .delete-btn:disabled {
            background: var(--border);
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* User Level Specific Styles */
        .user-level-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap; /* Allow wrapping on small screens */
        }
        .user-level-control select {
            flex-grow: 1;
            min-width: 120px; /* Ensure select box is not too small */
            color: #333; /* Darker text for select options */
        }
        .user-level-control button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            flex-shrink: 0; /* Prevent button from shrinking */
        }
        .user-level-control button:hover:not(:disabled) {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        .user-level-control button:disabled {
            background: var(--border);
            cursor: not-allowed;
            opacity: 0.7;
        }


        /* Toggle Switch */
        .can-upload-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .can-upload-toggle label {
            margin: 0;
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
            background: var(--border);
            transition: 0.4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background: var(--primary);
        }
        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary);
        }
        input:checked + .slider:before {
            transform: translateX(20px);
        }

        /* Modal */
        #customConfirmationModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        #customConfirmationModal.show {
            opacity: 1;
            visibility: visible;
        }
        #customConfirmationModal > div {
            background: var(--card);
            backdrop-filter: var(--blur);
            padding: 35px;
            border-radius: 12px;
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
            font-size: 15px;
            margin-bottom: 28px;
            color: var(--text);
            font-weight: 500;
        }
        #customConfirmationModal button {
            border: none;
            padding: 11px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        #customConfirmationModal #confirmYes {
            background: var(--primary);
            color: white;
        }
        #customConfirmationModal #confirmYes:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        #customConfirmationModal #confirmNo {
            background: var(--card-hover);
            color: var(--text-secondary);
            margin-left: 15px;
        }
        #customConfirmationModal #confirmNo:hover {
            background: var(--card);
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            header {
                padding: 14px 20px;
            }
            .hamburger-menu-container {
                display: flex;
            }
            .profile-icon {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }
            .icon-button {
                font-size: 20px;
            }
            main.admin-dashboard-layout {
                flex-direction: column;
                padding: 20px;
                gap: 20px;
            }
            .admin-sidebar {
                position: fixed;
                top: 68px;
                left: 0;
                height: calc(100vh - 68px);
                transform: translateX(-100%);
                z-index: 1050;
                width: 75%;
                padding-top: 20px;
                border-radius: 0 12px 12px 0;
            }
            .admin-sidebar.active {
                transform: translateX(0);
            }
            .admin-sidebar h2 {
                font-size: 20px;
                margin-bottom: 25px;
            }
            .admin-sidebar-nav button {
                font-size: 15px;
                padding: 10px 15px;
            }
            .admin-page-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 25px;
                padding-bottom: 12px;
            }
            .admin-page-header h1 {
                font-size: 24px;
            }
            .admin-logout-btn {
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
                font-size: 20px;
                margin-bottom: 15px;
                padding-bottom: 10px;
            }
            .admin-section h3 {
                font-size: 18px;
                margin-top: 25px;
                margin-bottom: 15px;
            }
            .admin-form input, .admin-form textarea, .admin-form select { /* Added select */
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
                grid-template-columns: 1fr;
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
                opacity: 1;
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

        @media (max-width: 576px) {
            header {
                padding: 10px 15px;
            }
            .logo {
                font-size: 24px;
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
            .hamburger-menu-container {
                width: 36px;
                height: 36px;
            }
            .hamburger-menu-icon {
                width: 20px;
                height: 16px;
            }
            .hamburger-menu-icon.active span:nth-child(1) { transform: rotate(45deg) translate(4px, 4px); }
            .hamburger-menu-icon.active span:nth-child(2) { opacity: 0; }
            .hamburger-menu-icon.active span:nth-child(3) { transform: rotate(-45deg) translate(4px, -4px); }
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
            .admin-form input, .admin-form textarea, .admin-form select { /* Added select */
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
        <div class="header-nav-links" style="display: none;">
            <button class="nav-button" data-nav="home" onclick="window.location.href='index.html'">Beranda</button>
            <button class="nav-button active">Panel Admin</button>
        </div>
        <div class="search-container" style="display: none;"></div>
        <div class="header-icons">
            <button class="icon-button" aria-label="Profil Admin" onclick="window.location.href='admin.php'"><div class="profile-icon">A</div></button>
            <button class="icon-button" aria-label="Keluar Admin" id="adminLogoutBtnHeader">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
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
                        <label for="newNotificationLink">Tautan Notifikasi (opsional):</label>
                        <input type="url" id="newNotificationLink" placeholder="Contoh: https://example.com/promo">
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
                        <label for="newCategoryImageFile">Gambar Kategori (opsional):</label>
                        <input type="file" id="newCategoryImageFile" accept="image/*">
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

            <div id="manual-person-requestsTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Permintaan Orang Manual</h2>
                    <div class="admin-list" id="manualPersonRequestList">
                        <p style="text-align: center; color: var(--text-secondary);">Memuat permintaan orang manual...</p>
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
            const adminLogoutBtnHeader = document.getElementById('adminLogoutBtnHeader');
            const adminLogoutBtnContent = document.getElementById('adminLogoutBtnContent');

            // Elemen formulir
            const addNotificationForm = document.getElementById('addNotificationForm');
            const newNotificationText = document.getElementById('newNotificationText');
            const newNotificationLink = document.getElementById('newNotificationLink');
            const notificationList = document.getElementById('notificationList');

            const addCategoryForm = document.getElementById('addCategoryForm');
            const newCategoryName = document.getElementById('newCategoryName');
            const newCategoryImageFile = document.getElementById('newCategoryImageFile');
            const categoryList = document.getElementById('categoryList');

            const userList = document.getElementById('userList');
            const pinList = document.getElementById('pinList');
            const manualPersonRequestList = document.getElementById('manualPersonRequestList');

            // Elemen modal
            const customConfirmationModal = document.getElementById('customConfirmationModal');
            const modalMessage = document.getElementById('modalMessage');
            const confirmYesButton = document.getElementById('confirmYes');
            const confirmNoButton = document.getElementById('confirmNo');

            const adminUsername = '<?php echo $adminUsername; ?>';

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
                messageDiv.className = isError ? 'error' : '';
                messageDiv.style.display = 'block';
                messageDiv.classList.remove('fadeInOut');
                void messageDiv.offsetWidth; // Memaksa reflow untuk memulai animasi ulang
                messageDiv.classList.add('fadeInOut');
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
                    
                    let linkHtml = '';
                    if (notif.link && isValidUrl(notif.link)) {
                        linkHtml = `<a href="${notif.link}" target="_blank" class="notification-link">Lihat Tautan</a>`;
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

            addNotificationForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const text = newNotificationText.value.trim();
                const link = newNotificationLink.value.trim();
                
                if (!text) {
                    showMessage('Teks notifikasi tidak boleh kosong.', true);
                    return;
                }

                if (link && !isValidUrl(link)) {
                    showMessage('Tautan notifikasi tidak valid. Harap masukkan URL yang benar.', true);
                    return;
                }

                try {
                    const response = await makeApiRequest('notifications.php?action=add', 'POST', { text, link });
                    if (response.success) {
                        showMessage('Notifikasi berhasil ditambahkan!', false);
                        newNotificationText.value = '';
                        newNotificationLink.value = '';
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
                    categoryList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Tidak ada kategori.</p>';
                    return;
                }
                categories.forEach(category => {
                    const categoryItem = document.createElement('div');
                    categoryItem.classList.add('list-item');
                    const imageUrl = getCorrectedCategoryImagePath(category.imageUrl || 'https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Kategori');
                    categoryItem.innerHTML = `
                        <img src="${imageUrl}" alt="Gambar Kategori" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Kategori';">
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
                    // Fetch all users directly from auth.php or a new endpoint if available
                    // Assuming pins.php?action=fetch_all_users now fetches all user data including preferences
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

                const userLevels = ['tempted', 'Naughty', 'sinful'];

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

                    const preferredCategoriesHtml = user.preferred_categories && user.preferred_categories.length > 0
                        ? `<span>Kategori Favorit: ${user.preferred_categories.join(', ')}</span>`
                        : `<span>Kategori Favorit: Belum diatur</span>`;
                    
                    const preferredPersonsHtml = user.preferred_persons && user.preferred_persons.length > 0
                        ? `<span>Orang Favorit: ${user.preferred_persons.join(', ')}</span>`
                        : `<span>Orang Favorit: Belum diatur</span>`;

                    const manualPersonsHtml = user.manual_persons_requested && user.manual_persons_requested.length > 0
                        ? `<span>Orang Manual Diminta: ${user.manual_persons_requested.join(', ')}</span>`
                        : `<span>Orang Manual Diminta: Tidak ada</span>`;

                    const profileImageUrlHtml = user.profile_image_url
                        ? `<img src="${user.profile_image_url}" alt="Profil" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/60x60/e0e0e0/767676?text=Profil';">`
                        : `<img src="https://placehold.co/60x60/e0e0e0/767676?text=Profil" alt="Profil" class="list-item-img">`;
                    
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
                                <button class="save-level-btn" id="saveLevelBtn_${user.username}" data-username="${user.username}" disabled>Simpan Level</button>
                            </div>
                        `;
                    } else {
                        levelSelectHtml = `<span class="list-item-detail">Level: Administrator</span>`;
                    }

                    userItem.innerHTML = `
                        ${profileImageUrlHtml}
                        <div class="list-item-content">
                            <strong class="list-item-title">${user.username}</strong>
                            <span class="list-item-detail">Email: ${user.email || 'N/A'}</span>
                            <span class="list-item-detail">Status: ${user.isAdmin ? 'Administrator' : 'Pengguna Biasa'}</span>
                            ${levelSelectHtml}
                            ${preferredCategoriesHtml}
                            ${preferredPersonsHtml}
                            ${manualPersonsHtml}
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
                    const saveLevelBtn = userItem.querySelector(`#saveLevelBtn_${user.username}`);

                    if (userLevelSelect && saveLevelBtn) {
                        userLevelSelect.addEventListener('change', function() {
                            if (this.value !== this.dataset.initialLevel) {
                                saveLevelBtn.disabled = false;
                            } else {
                                saveLevelBtn.disabled = true;
                            }
                        });

                        saveLevelBtn.addEventListener('click', () => {
                            const selectedLevel = userLevelSelect.value;
                            updateUserLevel(user.username, selectedLevel);
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
                        const response = await makeApiRequest('update_user_level.php', 'POST', { username: username, level: level });
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
                    let imageUrl = 'https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Pin';
                    if (Array.isArray(pin.images) && pin.images.length > 0) {
                        imageUrl = pin.images[0].url;
                    } else if (typeof pin.img === 'string' && pin.img) {
                        imageUrl = pin.img;
                    }

                    const pinDescription = pin.description || pin.content || 'N/A';
                    let categoriesText = 'N/A';
                    if (Array.isArray(pin.categories) && pin.categories.length > 0) {
                        categoriesText = pin.categories.join(', ');
                    } else if (typeof pin.category === 'string' && pin.category) {
                        categoriesText = pin.category;
                    }

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

            // --- Manajemen Permintaan Orang Manual ---
            async function fetchManualPersonRequests() {
                manualPersonRequestList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Memuat permintaan orang manual...</p>';
                try {
                    const response = await makeApiRequest('get_manual_person_requests.php'); // New endpoint
                    if (response.success) {
                        renderManualPersonRequests(response.requests);
                    } else {
                        showMessage('Gagal memuat permintaan orang manual: ' + response.message, true);
                        manualPersonRequestList.innerHTML = `<p style="text-align: center; color: var(--danger);">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Kesalahan mengambil daftar permintaan orang manual: ' + error.message, true);
                    manualPersonRequestList.innerHTML = `<p style="text-align: center; color: var(--danger);">${error.message}</p>`;
                }
            }

            function renderManualPersonRequests(requests) {
                manualPersonRequestList.innerHTML = '';
                if (requests.length === 0) {
                    manualPersonRequestList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Tidak ada permintaan orang manual.</p>';
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
                            <button class="delete-btn" data-index="${index}"><i class="fa-solid fa-check"></i> Tandai Selesai</button>
                        </div>
                    `;
                    const deleteBtn = requestItem.querySelector('.delete-btn');
                    if (deleteBtn) {
                         deleteBtn.onclick = () => deleteManualPersonRequest(index);
                    }
                    manualPersonRequestList.appendChild(requestItem);
                });
            }

            async function deleteManualPersonRequest(index) {
                showCustomConfirmation('Apakah Anda yakin ingin menandai permintaan ini sebagai selesai?', async () => {
                    try {
                        const response = await makeApiRequest('delete_manual_person_request.php', 'POST', { index: index });
                        if (response.success) {
                            showMessage('Permintaan orang manual berhasil ditandai selesai!', false);
                            fetchManualPersonRequests();
                        } else {
                            showMessage('Gagal menandai permintaan selesai: ' + response.message, true);
                        }
                    } catch (error) {
                        showMessage('Kesalahan menandai permintaan selesai: ' + error.message, true);
                    }
                });
            }


            // Muatan awal untuk panel admin
            fetchNotifications();
        });
    </script>
</body>
</html>
