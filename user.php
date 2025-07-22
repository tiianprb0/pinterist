<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
header('Content-Type: text/html; charset=UTF-8'); // Pastikan header ini ada untuk HTML
session_start();
// Memastikan pengguna login sebelum mengakses halaman ini.
if (!isset($_SESSION['username'])) {
    header('Location: index.html?login=true'); // Redirect ke index dengan overlay login
    exit();
}
$username = $_SESSION['username'];
$isAdmin = $_SESSION['isAdmin'] ?? false;

// Path ke file users.json
$usersFilePath = __DIR__ . '/data/users.json';

// Fungsi untuk mendapatkan data pengguna
function getUserData($username, $usersFilePath) {
    if (file_exists($usersFilePath)) {
        $users = json_decode(file_get_contents($usersFilePath), true);
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                // Pastikan 'liked_pins' ada, bahkan jika kosong
                if (!isset($user['liked_pins'])) {
                    $user['liked_pins'] = [];
                }
                // Pastikan 'level' ada, bahkan jika kosong
                if (!isset($user['level'])) {
                    $user['level'] = 'tempted'; // Default level
                }
                // Jika user adalah admin, paksa level menjadi Sinful
                if (isset($user['isAdmin']) && $user['isAdmin']) {
                    $user['level'] = 'Sinful';
                }
                return $user;
            }
        }
    }
    return null;
}

$userData = getUserData($username, $usersFilePath);
$profileImageUrl = $userData['profile_image_url'] ?? 'https://i.pinimg.com/736x/45/69/16/456916ec52c1c93abed7f12d60749b6f.jpg'; // Default image
$preferredCategories = $userData['preferred_categories'] ?? [];
$preferredPersons = $userData['preferred_persons'] ?? [];
$likedPins = $userData['liked_pins'] ?? []; // NEW: Ambil daftar pin yang disukai
$userLevel = $userData['level'] ?? 'tempted'; // Ambil level pengguna
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - Spicette</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif; /* Menggunakan font Plus Jakarta Sans */
            background-color: #f9f9f9;
        }
        header {
             box-shadow: none !important; /* Dihilangkan box-shadow */
        }
        .user-profile-avatar {
            width: 120px;
            height: 120px;
            background-color: #e60023;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            font-weight: bold;
            color: white;
            margin: 0 0 20px 0; /* Rata kiri */
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            overflow: hidden; /* Ensure image stays within bounds */
        }
        .user-profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .pin-grid {
            column-count: 3;
            column-gap: 15px;
            padding: 0; /* Hapus padding */
            margin-top: 30px;
        }
        @media (max-width: 992px) { .pin-grid { column-count: 2; } }
        @media (max-width: 576px) { .pin-grid { column-count: 2; } /* Column count 2 for mobile */ }
        .pin {
            display: inline-block; width: 100%; margin-bottom: 15px; border-radius: 16px; overflow: hidden; position: relative; cursor: pointer; background-color: #fff; /* box-shadow: 0 1px 3px rgba(0,0,0,0.1); */ /* Dihapus */
        }
        .pin img { width: 100%; height: auto; display: block; border-radius: 16px; }

        /* Gaya untuk tab */
        .tab-navigation {
            display: flex;
            justify-content: flex-start; /* Rata kiri */
            margin-top: 30px;
            margin-bottom: 20px;
            gap: 10px;
        }
        .tab-button {
            padding: 10px 20px;
            border: none;
            background-color: transparent; /* Transparan */
            color: #333;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s ease-in-out;
            position: relative;
        }
        .tab-button.active {
            color: #111;
        }
        .tab-button.active::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -5px; /* Adjust as needed */
            height: 2px;
            background-color: black; /* Garis bawah hitam */
        }
        .tab-button:hover:not(.active) {
            color: #555;
        }
        .tab-content {
            display: none; /* Sembunyikan semua konten tab secara default */
        }
        .tab-content.active {
            display: block; /* Tampilkan konten tab yang aktif */
        }

        /* Gaya untuk bagian edit profil (Popup) */
        .edit-profile-overlay {
            display: none; /* Sembunyikan secara default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .edit-profile-overlay.active {
            display: flex;
        }

        .edit-profile-section {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            width: 90%;
            max-width: 600px;
            box-sizing: border-box;
            position: relative; /* For positioning close button */
            overflow-y: auto; /* Enable scrolling for content */
            max-height: 90vh; /* Limit height for smaller screens */
        }
        .edit-profile-section h2 {
            font-size: 24px;
            color: #111;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left; /* Rata kiri untuk form group */
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="url"] {
            width: calc(100% - 24px);
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-group .profile-image-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 10px;
            border: 2px solid #eee;
        }
        .edit-profile-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .edit-profile-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .edit-profile-actions .save-btn {
            background-color: #e60023;
            color: white;
        }
        .edit-profile-actions .save-btn:hover {
            background-color: #ad081b;
        }
        .edit-profile-actions .cancel-btn {
            background-color: #ccc;
            color: #333;
        }
        .edit-profile-actions .cancel-btn:hover {
            background-color: #bbb;
        }

        /* Styles for choice grids in edit profile */
        .edit-profile-section .choice-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); /* Smaller cards for edit */
            gap: 10px;
            width: 100%;
            margin-bottom: 15px;
        }

        .edit-profile-section .choice-card {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
        }

        .edit-profile-section .choice-card:hover {
            border-color: #e60023;
            background-color: #e0e0e0;
        }

        .edit-profile-section .choice-card.selected {
            background-color: #e60023;
            color: white;
            border-color: #e60023;
        }

        /* Manual input group for edit section */
        .manual-input-group-edit {
            width: 100%;
            margin-top: 10px;
            text-align: left;
        }
        .manual-input-group-edit .custom-toggle-button {
            background-color: #555;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 15px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            transition: background-color 0.2s ease;
            margin-bottom: 10px;
        }
        .manual-input-group-edit .custom-toggle-button:hover {
            background-color: #777;
        }
        .manual-input-group-edit .manual-input-fields {
            display: none;
            margin-top: 10px;
        }
        .manual-input-group-edit .manual-input-fields.active {
            display: block;
        }
        .manual-input-group-edit input {
            width: calc(100% - 24px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .manual-input-group-edit button {
            background-color: #e60023;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 15px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            transition: background-color 0.2s ease;
        }
        .manual-input-group-edit button:hover {
            background-color: #ad081b;
        }
        .manual-list {
            list-style: none;
            padding: 0;
            margin-top: 5px;
        }
        .manual-list li {
            background-color: #f9f9f9;
            padding: 6px 10px;
            border-radius: 6px;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            border: 1px solid #eee;
        }
        .manual-list li button {
            background: none;
            border: none;
            color: #e60023;
            cursor: pointer;
            font-size: 16px;
        }

        /* General layout improvements for user.php */
        .user-page-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 10px; /* Padding 10px */
            background-color: transparent; /* Dihapus background */
            border-radius: 12px;
            box-shadow: none; /* Dihapus shadow */
            text-align: left; /* Rata kiri */
        }

        .user-page-header {
            display: none; /* Dihapus dari tampilan */
        }

        /* Styles for the logout button in user-profile-actions (desktop) */
        .user-profile-actions .user-logout-btn {
            background-color: #e60023;
            color: white;
            border: none;
            padding: 10px 20px; /* Larger padding for desktop */
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex; /* Show by default (desktop) */
            align-items: center;
            gap: 5px;
        }
        .user-profile-actions .user-logout-btn:hover {
            background-color: #ad081b;
        }
        
        .header-icons {
            display: flex;
            align-items: center;
            flex-shrink: 0; /* Prevent shrinking */
        }

        .header-icons .icon-button.logout-icon-button {
            display: none; /* Hide this one now, as we're moving the main logout button */
        }

        /* User profile info and logout button positioning */
        .user-profile-info {
            margin-bottom: 10px;
            text-align: left;
            display: flex; /* Use flexbox */
            flex-wrap: wrap; /* Allow items to wrap */
            align-items: center; /* Align items vertically */
            justify-content: flex-start; /* Default alignment */
        }

        .user-profile-info strong {
            /* Existing styles */
            /* flex-grow: 1; */ /* Removed flex-grow to prevent it from pushing the button too far */
        }

        /* Hide the mobile logout button by default (desktop) */
        .user-profile-info .user-logout-btn-mobile {
            display: none;
        }

        .user-profile-info p {
            margin: 5px 0;
            color: #555;
            width: 100%; /* Ensure status text goes to new line if needed */
        }

        /* Styling for the level icon */
        .level-icon {
            margin-right: 3px;
            margin-left: 6px;
            font-size: 1.2em; /* Slightly larger icon */
            vertical-align: middle; /* Align with text */
        }

        /* Level specific colors - these are for the text color, not the icon itself */
        .level-tempted { color: #ff9800; /* Orange */ }
        .level-naughty { color: #ffeb3b; /* Yellow */ }
        .level-sinful { color: #f44336; /* Red */ }

        /* Delete button on pins */
        .pin .delete-pin-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 10;
        }

        .pin:hover .delete-pin-btn {
            opacity: 1;
        }

        /* NEW: CSS untuk blur dan overlay pin */
        .pin-blurred img {
            filter: blur(3px); /* Efek blur samar */
            transition: filter 0.3s ease;
        }

        .pin-overlay-restricted {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3); /* Overlay gelap transparan */
            display: flex;
            flex-direction: column; /* Untuk menumpuk teks dan link */
            justify-content: center;
            align-items: center;
            opacity: 0; /* Awalnya tersembunyi */
            transition: opacity 0.3s ease;
            pointer-events: none; /* Memungkinkan klik di bawah overlay */
            border-radius: 16px; /* Sesuai dengan pin */
            overflow: hidden; /* Pastikan blur tidak meluber */
            text-align: center;
            padding: 10px;
            box-sizing: border-box;
        }

        .pin-blurred .pin-overlay-restricted {
            opacity: 1; /* Tampilkan overlay saat pin diburamkan */
            pointer-events: auto; /* Aktifkan pointer events saat overlay terlihat */
        }

        .pin-restricted-text {
            color: white;
            font-weight: bold;
            font-size: 0.9em; /* Ukuran font kecil */
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            margin-bottom: 5px; /* Jarak antara teks dan link */
        }

        .pin-restricted-text a {
            color: #ffcc00; /* Warna kuning untuk link */
            text-decoration: underline;
            font-weight: bold;
        }


        .user-profile-actions {
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap; /* Izinkan wrap pada layar kecil */
            justify-content: flex-start; /* Rata kiri */
            gap: 15px; /* Jarak antar tombol */
        }
        .user-profile-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease;
            background-color: #111;
            color: white;
            flex-shrink: 0; /* Pastikan tombol tidak menyusut */
        }
        .user-profile-actions button.secondary {
            background-color: #767676;
        }
        .user-profile-actions button:hover {
            background-color: #555;
        }
        .user-profile-actions button.secondary:hover {
            background-color: #5f5f5f;
        }

        .message-box {
            margin-top: 20px;
            padding: 10px;
            border-radius: 8px;
            display: none;
            font-size: 14px;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }
        .message-box.success {
            background-color: #e6ffe6;
            color: green;
            border: 1px solid green;
        }
        .message-box.error {
            color: red;
            background-color: #ffe6e6;
            border: 1px solid red;
        }

        /* No shadow or border for user-section (tab-content) */
        .user-section, .admin-section { /* Added .admin-section for consistency */
            background-color: transparent;
            box-shadow: none;
            border: none;
            padding: 0; /* Menghapus padding */
        }

        /* Responsive adjustments for mobile */
        @media (max-width: 576px) {
            /* Hide desktop logout button on mobile */
            .user-profile-actions .user-logout-btn {
                display: none;
            }

            /* Show mobile logout button on mobile */
            .user-profile-info .user-logout-btn-mobile {
                display: flex; /* Show on mobile */
                background-color: #e60023;
                color: white;
                border: none;
                padding: 5px 8px; /* Smaller padding for mobile */
                border-radius: 15px;
                font-weight: bold;
                font-size: 13px; /* Smaller font size */
                cursor: pointer;
                transition: background-color 0.2s ease;
                align-items: center;
                gap: 5px;
                margin-left: 10px; /* Space from username */
                flex-shrink: 0; /* Prevent it from shrinking */
            }
            .user-profile-info .user-logout-btn-mobile:hover {
                background-color: #ad081b;
            }

            /* Ensure username and logout button stay on one line if possible */
            .user-profile-info {
                justify-content: space-between; /* Push username left, logout right */
                align-items: flex-start; /* Align items to the top if they wrap */
            }
            .user-profile-info strong {
                flex-grow: 0; /* Don't grow too much, let button have space */
                margin-right: auto; /* Push username to the left, allowing button to move right */
            }
            .user-profile-info p {
                width: 100%; /* Ensure status text takes full width below username/button */
                margin-top: 5px; /* Add some space below username/button line */
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo" onclick="window.location.href='index.html'"></div>
        <div class="header-nav-links">
            <button class="nav-button" data-nav="home" onclick="window.location.href='index.html'">Beranda</button>
            <button class="nav-button active">Profil</button>
        </div>

        <div class="search-container" style="flex-grow: 1;"></div>
        <div class="header-icons">
            <button class="icon-button" aria-label="Profil Pengguna" onclick="window.location.href='user.php'">
                <div class="profile-icon" id="headerProfileIcon">
                    <?php if (!empty($profileImageUrl)): ?>
                        <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profil Pengguna">
                    <?php else: ?>
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    <?php endif; ?>
                </div>
            </button>
            <!-- Removed the logout icon button from header -->
        </div>
    </header>

    <main>
        <div class="user-page-container">
            <div id="message" class="message-box" style="display: none;"></div>

            <div class="user-profile-avatar" id="userProfileAvatar">
                <?php if (!empty($profileImageUrl)): ?>
                    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profil Pengguna">
                <?php else: ?>
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="user-profile-info">
                <strong id="displayUsername"><?php echo htmlspecialchars($username); ?></strong>
                <button class="user-logout-btn-mobile" id="userLogoutBtnMobile">
                    <i class="fas fa-sign-out-alt"></i> Out
                </button>
                <p>Level: 
                    <?php if ($isAdmin): ?>
                        Administrator
                    <?php else: ?>
                        <!-- Changed level icon to fa-check-circle -->
                        <i class="fas fa-check-circle level-icon level-<?php echo strtolower(htmlspecialchars($userLevel)); ?>"></i>
                        <?php echo htmlspecialchars($userLevel); ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="user-profile-actions">
                <button id="editProfileBtn" class="secondary">Edit Profil</button>
                <?php if ($isAdmin): ?>
                    <button onclick="window.location.href='admin.php'">Pergi ke Panel Admin</button>
                <?php endif; ?>
                <button class="user-logout-btn" id="userLogoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Keluar <!-- Added icon -->
                </button>
            </div>

            <div class="tab-navigation">
                <button class="tab-button active" data-tab="created">Dibuat</button>
                <button class="tab-button" data-tab="saved">Disimpan</button>
                <button class="tab-button" data-tab="liked">Disukai</button> <!-- NEW: Tab Disukai -->
            </div>

            <div id="createdPinsContent" class="tab-content active user-section">
                <div class="pin-grid" id="createdPinsGrid">
                    <p style="text-align: left; color: #767676;">Memuat pin Anda...</p>
                </div>
                <div id="loading-indicator-created" style="display: none;">Memuat lebih banyak pin...</div>
            </div>

            <div id="savedPinsContent" class="tab-content user-section">
                <div class="pin-grid" id="savedPinsGrid">
                    <p style="text-align: left; color: #767676;">Memuat pin yang disimpan...</p>
                </div>
                <div id="loading-indicator-saved" style="display: none;">Memuat lebih banyak pin...</div>
            </div>

            <!-- NEW: Konten Tab Disukai -->
            <div id="likedPinsContent" class="tab-content user-section">
                <div class="pin-grid" id="likedPinsGrid">
                    <p style="text-align: left; color: #767676;">Memuat pin yang disukai...</p>
                </div>
                <div id="loading-indicator-liked" style="display: none;">Memuat lebih banyak pin...</div>
            </div>

        </div>
    </main>

    <!-- Edit Profile Popup Overlay -->
    <div id="editProfileOverlay" class="edit-profile-overlay">
        <div id="editProfileSection" class="edit-profile-section">
            <h2>Edit Profil Anda</h2>
            <div class="form-group">
                <label for="editUsername">Nama Pengguna:</label>
                <input type="text" id="editUsername" value="<?php echo htmlspecialchars($username); ?>">
            </div>
            <div class="form-group">
                <label for="editProfileImageUrl">URL Gambar Profil:</label>
                <input type="url" id="editProfileImageUrl" value="<?php echo htmlspecialchars($profileImageUrl); ?>" placeholder="Masukkan URL gambar profil" oninput="updateProfilePreview(this.value)">
                <img id="profileImagePreview" class="profile-image-preview" src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Pratinjau Gambar Profil" onerror="this.style.display='none'">
            </div>
            <div class="form-group">
                <label>Kategori Pilihan:</label>
                <div class="choice-grid" id="editCategoryGrid">
                    <p style="text-align: left; grid-column: 1 / -1; color: #767676;">Memuat kategori...</p>
                </div>
            </div>
            <div class="form-group">
                <label>Orang Pilihan:</label>
                <div class="choice-grid" id="editPersonGrid">
                    <p style="text-align: left; grid-column: 1 / -1; color: #767676;">Memuat orang...</p>
                </div>
                <div class="manual-input-group-edit">
                    <button id="toggleManualPersonInputEdit" class="custom-toggle-button">Tambahkan Orang Manual</button>
                    <div id="manualInputFieldsEdit" class="manual-input-fields">
                        <input type="text" id="manualPersonInputEdit" placeholder="Masukkan nama orang">
                        <button id="addManualPersonBtnEdit">Tambahkan</button>
                    </div>
                    <ul class="manual-list" id="manualPersonListEdit">
                        <!-- Manually added persons will appear here -->
                    </ul>
                </div>
            </div>
            <div class="edit-profile-actions">
                <button id="saveProfileBtn" class="save-btn">Simpan Perubahan</button>
                <button id="cancelEditBtn" class="cancel-btn">Batal</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageDiv = document.getElementById('message');
            const userLogoutBtn = document.getElementById('userLogoutBtn'); // Desktop logout button
            const userLogoutBtnMobile = document.getElementById('userLogoutBtnMobile'); // Mobile logout button
            const editProfileBtn = document.getElementById('editProfileBtn');
            const editProfileOverlay = document.getElementById('editProfileOverlay'); // Overlay for popup
            const editProfileSection = document.getElementById('editProfileSection');

            const editUsernameInput = document.getElementById('editUsername');
            const editProfileImageUrlInput = document.getElementById('editProfileImageUrl');
            const profileImagePreview = document.getElementById('profileImagePreview');
            const editCategoryGrid = document.getElementById('editCategoryGrid');
            const editPersonGrid = document.getElementById('editPersonGrid');
            const toggleManualPersonInputEdit = document.getElementById('toggleManualPersonInputEdit');
            const manualInputFieldsEdit = document.getElementById('manualInputFieldsEdit');
            const manualPersonInputEdit = document.getElementById('manualPersonInputEdit');
            const addManualPersonBtnEdit = document.getElementById('addManualPersonBtnEdit');
            const manualPersonListEdit = document.getElementById('manualPersonListEdit');
            const saveProfileBtn = document.getElementById('saveProfileBtn');
            const cancelEditBtn = document.getElementById('cancelEditBtn');


            const displayUsername = document.getElementById('displayUsername');
            const userProfileAvatar = document.getElementById('userProfileAvatar');
            const headerProfileIcon = document.getElementById('headerProfileIcon');


            const tabButtons = document.querySelectorAll('.tab-button');
            const createdPinsContent = document.getElementById('createdPinsContent');
            const savedPinsContent = document.getElementById('savedPinsContent');
            const likedPinsContent = document.getElementById('likedPinsContent'); // NEW: Konten tab disukai
            const createdPinsGrid = document.getElementById('createdPinsGrid');
            const savedPinsGrid = document.getElementById('savedPinsGrid');
            const likedPinsGrid = document.getElementById('likedPinsGrid'); // NEW: Grid pin disukai
            const loadingIndicatorCreated = document.getElementById('loading-indicator-created');
            const loadingIndicatorSaved = document.getElementById('loading-indicator-saved');
            const loadingIndicatorLiked = document.getElementById('loading-indicator-liked'); // NEW: Indikator loading disukai

            let pinsPerPage = 10;
            let loadedCreatedPinsCount = 0;
            let loadedSavedPinsCount = 0;
            let loadedLikedPinsCount = 0; // NEW: Hitungan pin disukai yang dimuat
            let currentActiveTab = 'created'; // Default active tab

            const currentUsername = '<?php echo $username; ?>';
            let initialProfileImageUrl = '<?php echo $profileImageUrl; ?>'; // Store initial URL
            let initialLikedPins = <?php echo json_encode($likedPins); ?>; // NEW: Ambil pin yang disukai dari PHP
            const currentUserLevel = '<?php echo $userLevel; ?>'; // Ambil level pengguna dari PHP

            // Initialize sets for selected categories and persons in edit mode
            let editSelectedCategories = new Set(<?php echo json_encode($preferredCategories); ?>);
            let editSelectedPersons = new Set(<?php echo json_encode($preferredPersons); ?>);
            let editManualPersons = []; // For persons added manually during edit session

            // --- URL Dasar API ---
            const API_BASE_URL = 'api/';

            // --- Fungsi Pembantu untuk Permintaan API ---
            async function makeApiRequest(endpoint, method = 'GET', data = null) {
                try {
                    const options = { method };
                    if (data !== null && typeof data !== 'undefined') {
                        options.headers = { 'Content-Type': 'application/json' };
                        options.body = JSON.stringify(data);
                    } else if (method === 'POST') {
                        // No need to delete options.headers; just leave it if there's no body data
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
                messageDiv.className = 'message-box ' + (isError ? 'error' : 'success');
                messageDiv.style.display = 'block';
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            }

            // --- Modal Konfirmasi Kustom ---
            function showCustomConfirmation(message, onConfirmCallback) {
                const modal = document.createElement('div');
                modal.id = 'customConfirmationModal';
                modal.style.cssText = `
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center;
                    align-items: center; z-index: 2000; opacity: 0; visibility: hidden;
                    transition: all 0.3s ease;
                `;
                modal.innerHTML = `
                    <div style="background: #fff; padding: 35px; border-radius: 12px; max-width: 420px;
                                text-align: center; transform: scale(0.9); opacity: 0;
                                transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: 1px solid #ddd; color: #333;">
                        <p id="modalMessage" style="font-size: 15px; margin-bottom: 28px; font-weight: 500;"></p>
                        <div style="display: flex; justify-content: center; gap: 15px;">
                            <button id="confirmNo" style="border: none; padding: 11px 22px; border-radius: 8px;
                                        cursor: pointer; font-size: 15px; font-weight: 600;
                                        transition: all 0.3s ease; background: #ccc; color: #333;">Tidak</button>
                            <button id="confirmYes" style="border: none; padding: 11px 22px; border-radius: 8px;
                                        cursor: pointer; font-size: 15px; font-weight: 600;
                                        transition: all 0.3s ease; background: #e60023; color: white;">Ya</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);

                const modalMessageElem = modal.querySelector('#modalMessage');
                const confirmYesButton = modal.querySelector('#confirmYes');
                const confirmNoButton = modal.querySelector('#confirmNo');

                modalMessageElem.textContent = message;
                modal.style.opacity = '1';
                modal.style.visibility = 'visible';
                modal.querySelector('div').style.transform = 'scale(1)';
                modal.querySelector('div').style.opacity = '1';

                const handleConfirm = () => {
                    modal.style.opacity = '0';
                    modal.style.visibility = 'hidden';
                    modal.querySelector('div').style.transform = 'scale(0.9)';
                    onConfirmCallback();
                    modal.remove(); // Remove modal from DOM after action
                };

                const handleCancel = () => {
                    modal.style.opacity = '0';
                    modal.style.visibility = 'hidden';
                    modal.querySelector('div').style.transform = 'scale(0.9)';
                    modal.remove(); // Remove modal from DOM after action
                };

                confirmYesButton.addEventListener('click', handleConfirm);
                confirmNoButton.addEventListener('click', handleCancel);
            }

            // --- Fungsionalitas Logout Pengguna ---
            async function handleUserLogout() {
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
            }
            userLogoutBtn.addEventListener('click', handleUserLogout);
            userLogoutBtnMobile.addEventListener('click', handleUserLogout); // Add listener for mobile button

            // --- Fungsi untuk membuat elemen Pin ---
            function createPinElement(pinData, isOwnerPin = false) { // Added isOwnerPin parameter
                const pinDiv = document.createElement('div');
                pinDiv.classList.add('pin');
                pinDiv.dataset.id = pinData.id;

                // Use display_url provided by backend, which is already determined based on user access
                const imageUrlToDisplay = (Array.isArray(pinData.images) && pinData.images.length > 0) 
                                         ? pinData.images[0].display_url 
                                         : 'https://placehold.co/236x300/e0e0e0/767676?text=Image+Not+Found';

                const img = document.createElement('img');
                img.src = imageUrlToDisplay;
                img.alt = 'Pin Image';
                img.onerror = function() {
                    this.onerror=null;
                    this.src='https://placehold.co/236x300/e0e0e0/767676?text=Image+Not+Found';
                };

                pinDiv.appendChild(img);
                
                // Add delete button if it's an owner's pin
                if (isOwnerPin) {
                    const deleteButton = document.createElement('button');
                    deleteButton.classList.add('delete-pin-btn');
                    deleteButton.innerHTML = '<i class="fas fa-times"></i>';
                    deleteButton.title = 'Hapus Pin Ini';
                    deleteButton.addEventListener('click', (e) => {
                        e.stopPropagation(); // Prevent pin click event from firing
                        deleteUserPin(pinData.id);
                    });
                    pinDiv.appendChild(deleteButton);
                }

                // NEW: Logic for blurring and restricted access overlay
                if (!pinData.can_view_clearly) {
                    pinDiv.classList.add('pin-blurred');
                    const restrictedOverlay = document.createElement('div');
                    restrictedOverlay.classList.add('pin-overlay-restricted');
                    const restrictedText = document.createElement('span');
                    restrictedText.classList.add('pin-restricted-text');
                    restrictedText.innerHTML = 'Level anda tidak dijinkan, silakan <a href="/Spicette/chat">hubungi admin</a>'; // Link ke chat
                    restrictedOverlay.appendChild(restrictedText);
                    pinDiv.appendChild(restrictedOverlay);

                    // Override click behavior for blurred pins to redirect to chat
                    pinDiv.addEventListener('click', (e) => {
                        e.stopPropagation(); // Prevent opening pin detail
                        window.location.href = '/Spicette/chat'; // Redirect to chat page
                    });
                } else {
                    // Tambahkan event listener untuk membuka detail pin saat diklik (hanya jika tidak diburamkan)
                    pinDiv.addEventListener('click', () => {
                        // Simpan pinData ke sessionStorage agar dapat diakses oleh index.html
                        sessionStorage.setItem('tempPinDetail', JSON.stringify(pinData));
                        // Arahkan ke index.html dengan parameter pin
                        window.location.href = `index.html?pin=${pinData.id}`;
                    });
                }

                return pinDiv;
            }

            // --- Fungsi untuk menghapus pin pengguna ---
            async function deleteUserPin(pinId) {
                showCustomConfirmation('Apakah Anda yakin ingin menghapus pin ini?', async () => {
                    try {
                        // Menggunakan action 'delete_user_pin' yang baru ditambahkan di pins.php
                        const response = await makeApiRequest('pins.php?action=delete_user_pin', 'POST', { pinId: pinId });
                        if (response.success) {
                            showMessage('Pin berhasil dihapus!', false);
                            // Muat ulang pin di tab "Dibuat" setelah penghapusan
                            loadPins('created', pinsPerPage, false);
                        } else {
                            showMessage('Gagal menghapus pin: ' + response.message, true);
                        }
                    } catch (error) {
                        showMessage('Kesalahan menghapus pin: ' + error.message, true);
                    }
                });
            }


            // --- Muat Pin Berdasarkan Tipe (Dibuat, Disimpan, atau Disukai) ---
            async function loadPins(type, count, append = true) {
                let targetGrid, loadingIndicator, loadedCountVar, endpoint;
                let allPins = []; // Untuk menyimpan semua pin dari respons API

                if (type === 'created') {
                    targetGrid = createdPinsGrid;
                    loadingIndicator = loadingIndicatorCreated;
                    loadedCountVar = loadedCreatedPinsCount;
                    endpoint = `pins.php?action=fetch_user_pins&username=${currentUsername}`;
                } else if (type === 'saved') {
                    targetGrid = savedPinsGrid;
                    loadingIndicator = loadingIndicatorSaved;
                    loadedCountVar = loadedSavedPinsCount;
                    endpoint = `pins.php?action=fetch_saved&username=${currentUsername}`;
                } else if (type === 'liked') { // NEW: Handle 'liked' type
                    targetGrid = likedPinsGrid;
                    loadingIndicator = loadingIndicatorLiked;
                    loadedCountVar = loadedLikedPinsCount;
                    // Untuk pin yang disukai, kita perlu mengambil semua pin, lalu memfilter berdasarkan initialLikedPins
                    endpoint = `pins.php?action=fetch_all`; 
                } else {
                    console.error('Tipe pin tidak valid:', type);
                    return;
                }

                loadingIndicator.style.display = 'block';
                console.log(`[loadPins] Memuat pin tipe: ${type}, dari endpoint: ${endpoint}`); // Debugging log
                try {
                    const response = await makeApiRequest(endpoint);
                    loadingIndicator.style.display = 'none';
                    console.log(`[loadPins] Respons API untuk ${type} pins:`, response); // Debugging log

                    if (response.success) {
                        allPins = response.pins || []; 
                        
                        // NEW: Filter liked pins if type is 'liked'
                        if (type === 'liked') {
                            const likedPinIds = initialLikedPins; // Gunakan daftar pin yang disukai dari PHP
                            allPins = allPins.filter(pin => likedPinIds.includes(pin.id));
                        }

                        console.log(`[loadPins] Ditemukan ${allPins.length} pin untuk tipe ${type}.`); // Debugging log

                        const startIndex = append ? loadedCountVar : 0;
                        const pinsToDisplay = allPins.slice(startIndex, startIndex + count);

                        if (!append) {
                            targetGrid.innerHTML = '';
                            if (type === 'created') loadedCreatedPinsCount = 0;
                            else if (type === 'saved') loadedSavedPinsCount = 0;
                            else if (type === 'liked') loadedLikedPinsCount = 0; // NEW: Reset liked count
                        }

                        const fragment = document.createDocumentFragment();
                        pinsToDisplay.forEach(pinData => {
                            // Pass true to createPinElement if it's the 'created' tab
                            fragment.appendChild(createPinElement(pinData, type === 'created'));
                            if (type === 'created') loadedCreatedPinsCount++;
                            else if (type === 'saved') loadedSavedPinsCount++;
                            else if (type === 'liked') loadedLikedPinsCount++; // NEW: Increment liked count
                        });
                        targetGrid.appendChild(fragment);

                        if (allPins.length === 0) {
                            targetGrid.innerHTML = `<p style="text-align: left; color: #767676; margin-top: 50px;">Anda belum memiliki pin ${type === 'created' ? 'dibuat' : (type === 'saved' ? 'disimpan' : 'disukai')} apa pun.</p>`;
                        } else if (pinsToDisplay.length === 0 && loadedCountVar > 0) {
                             // Semua pin sudah dimuat, do nothing.
                        }
                    } else {
                        // This block handles response.success === false
                        showMessage(`Gagal memuat pin ${type === 'created' ? 'dibuat' : (type === 'saved' ? 'disimpan' : 'disukai')}: ` + response.message, true);
                        targetGrid.innerHTML = `<p style="text-align: left; color: #e60023; margin-top: 50px;">Kesalahan memuat pin. Silakan coba lagi.</p>`;
                    }
                } catch (error) {
                    // This block handles network errors or JSON parsing errors
                    showMessage(`Kesalahan memuat pin ${type === 'created' ? 'dibuat' : (type === 'saved' ? 'disimpan' : 'disukai')}: ` + error.message, true);
                    targetGrid.innerHTML = `<p style="text-align: left; color: #e60023; margin-top: 50px;">Terjadi kesalahan. Silakan coba lagi nanti.</p>`;
                    console.error(`[loadPins] Kesalahan saat memuat pin ${type}:`, error); // Debugging log
                }
            }

            // --- Penanganan Pergantian Tab ---
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    const tab = this.dataset.tab;
                    currentActiveTab = tab; // Update active tab state

                    // Hide edit profile section when switching tabs
                    editProfileOverlay.classList.remove('active'); // Hide popup
                    document.body.style.overflow = ''; // Enable body scroll

                    // Hide all tab contents first
                    createdPinsContent.classList.remove('active');
                    savedPinsContent.classList.remove('active');
                    likedPinsContent.classList.remove('active'); // NEW: Sembunyikan tab disukai

                    // Show the active tab content
                    if (tab === 'created') {
                        createdPinsContent.classList.add('active');
                        loadPins('created', pinsPerPage, false); // Muat ulang pin dibuat
                    } else if (tab === 'saved') {
                        savedPinsContent.classList.add('active');
                        loadPins('saved', pinsPerPage, false); // Muat ulang pin disimpan
                    } else if (tab === 'liked') { // NEW: Handle 'liked' tab
                        likedPinsContent.classList.add('active');
                        loadPins('liked', pinsPerPage, false); // Muat ulang pin disukai
                    }
                });
            });

            // --- Infinite Scroll ---
            window.addEventListener('scroll', () => {
                let currentLoadingIndicator;
                if (currentActiveTab === 'created') {
                    currentLoadingIndicator = loadingIndicatorCreated;
                } else if (currentActiveTab === 'saved') {
                    currentLoadingIndicator = loadingIndicatorSaved;
                } else if (currentActiveTab === 'liked') { // NEW: Handle liked tab for infinite scroll
                    currentLoadingIndicator = loadingIndicatorLiked;
                }

                // Only load more if edit profile section is not active
                if (!editProfileOverlay.classList.contains('active') && (window.innerHeight + window.scrollY) >= document.body.offsetHeight - 400 && currentLoadingIndicator.style.display === 'none') {
                    loadPins(currentActiveTab, pinsPerPage, true);
                }
            });

            // --- Edit Profile Functionality ---
            editProfileBtn.addEventListener('click', async function() {
                editProfileOverlay.classList.add('active'); // Show popup
                document.body.style.overflow = 'hidden'; // Disable body scroll
                // Hide pin grids when edit profile is active
                createdPinsContent.classList.remove('active');
                savedPinsContent.classList.remove('active');
                likedPinsContent.classList.remove('active'); // NEW: Sembunyikan tab disukai saat edit profil
                tabButtons.forEach(btn => btn.classList.remove('active')); // Deactivate tab buttons

                // Load all categories and persons for selection
                await fetchAndRenderEditCategories();
                await fetchAndRenderEditPersons();
            });

            cancelEditBtn.addEventListener('click', function() {
                editProfileOverlay.classList.remove('active'); // Hide popup
                document.body.style.overflow = ''; // Enable body scroll
                // Restore active tab and load pins
                document.querySelector(`.tab-button[data-tab="${currentActiveTab}"]`).classList.add('active');
                if (currentActiveTab === 'created') {
                    createdPinsContent.classList.add('active');
                } else if (currentActiveTab === 'saved') {
                    savedPinsContent.classList.add('active');
                } else if (currentActiveTab === 'liked') { // NEW: Restore liked tab
                    likedPinsContent.classList.add('active');
                }
                // Reset form fields to initial values if needed (optional, for now just hide)
                // Re-initialize selected sets from PHP values
                editSelectedCategories = new Set(<?php echo json_encode($preferredCategories); ?>);
                editSelectedPersons = new Set(<?php echo json_encode($preferredPersons); ?>);
                editManualPersons = []; // Clear manual persons on cancel
                manualInputFieldsEdit.classList.remove('active'); // Hide manual input fields
                renderEditManualPersons(); // Clear rendered manual persons
            });

            // Function to update profile image preview
            window.updateProfilePreview = function(url) {
                if (url) {
                    profileImagePreview.src = url;
                    profileImagePreview.style.display = 'block';
                } else {
                    profileImagePreview.style.display = 'none';
                }
            };


            // --- Fetch and Render Categories for Edit ---
            async function fetchAndRenderEditCategories() {
                editCategoryGrid.innerHTML = '<p style="text-align: left; grid-column: 1 / -1; color: #767676;">Memuat kategori...</p>';
                const response = await makeApiRequest('categories.php?action=fetch_all');
                if (response.success) {
                    editCategoryGrid.innerHTML = '';
                    response.categories.forEach(category => {
                        const card = document.createElement('div');
                        card.classList.add('choice-card');
                        card.dataset.name = category.name;
                        if (editSelectedCategories.has(category.name)) {
                            card.classList.add('selected');
                        }
                        card.innerHTML = `<span>${category.name}</span>`;
                        card.addEventListener('click', () => toggleEditSelection(card, editSelectedCategories));
                        editCategoryGrid.appendChild(card);
                    });
                } else {
                    showMessage('Gagal memuat kategori untuk edit: ' + response.message, 'error');
                    editCategoryGrid.innerHTML = `<p style="text-align: left; grid-column: 1 / -1; color: red;">Gagal memuat kategori.</p>`;
                }
            }

            // --- Fetch and Render Persons for Edit ---
            async function fetchAndRenderEditPersons() {
                editPersonGrid.innerHTML = '<p style="text-align: left; grid-column: 1 / -1; color: #767676;">Memuat orang...</p>';
                const response = await makeApiRequest('get_persons.php');
                if (response.success) {
                    editPersonGrid.innerHTML = '';
                    response.persons.forEach(person => {
                        const card = document.createElement('div');
                        card.classList.add('choice-card');
                        card.dataset.name = person;
                        if (editSelectedPersons.has(person)) {
                            card.classList.add('selected');
                        }
                        card.innerHTML = `<span>${person}</span>`;
                        card.addEventListener('click', () => toggleEditSelection(card, editSelectedPersons));
                        editPersonGrid.appendChild(card);
                    });
                    renderEditManualPersons(); // Also render any existing manual persons
                } else {
                    showMessage('Gagal memuat orang untuk edit: ' + response.message, 'error');
                    editPersonGrid.innerHTML = `<p style="text-align: left; grid-column: 1 / -1; color: red;">Gagal memuat orang.</p>`;
                }
            }

            // --- Toggle Selection for Edit Cards ---
            function toggleEditSelection(card, selectionSet) {
                const name = card.dataset.name;
                if (selectionSet.has(name)) {
                    selectionSet.delete(name);
                    card.classList.remove('selected');
                } else {
                    selectionSet.add(name);
                    card.classList.add('selected');
                }
            }

            // --- Toggle Manual Person Input (Edit Mode) ---
            toggleManualPersonInputEdit.addEventListener('click', () => {
                manualInputFieldsEdit.classList.toggle('active');
                if (manualInputFieldsEdit.classList.contains('active')) {
                    toggleManualPersonInputEdit.textContent = 'Sembunyikan Input Manual';
                } else {
                    toggleManualPersonInputEdit.textContent = 'Tambahkan Orang Manual';
                }
            });

            // --- Add Manual Person (Edit Mode) ---
            addManualPersonBtnEdit.addEventListener('click', () => {
                const personName = manualPersonInputEdit.value.trim();
                if (personName) {
                    if (editManualPersons.includes(personName) || editSelectedPersons.has(personName)) {
                        showMessage('Orang ini sudah dipilih atau ditambahkan.', 'error');
                    } else {
                        editManualPersons.push(personName);
                        renderEditManualPersons();
                        manualPersonInputEdit.value = '';
                        editSelectedPersons.add(personName); // Add to selected persons set as well
                    }
                } else {
                    showMessage('Nama orang tidak boleh kosong.', 'error');
                }
            });

            function renderEditManualPersons() {
                manualPersonListEdit.innerHTML = '';
                editManualPersons.forEach((person, index) => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <span>${person}</span>
                        <button data-index="${index}"><i class="fas fa-times"></i></button>
                    `;
                    li.querySelector('button').addEventListener('click', (e) => {
                        const idx = parseInt(e.target.closest('button').dataset.index);
                        const removedPerson = editManualPersons.splice(idx, 1)[0];
                        editSelectedPersons.delete(removedPerson); // Remove from selected persons set
                        renderEditManualPersons();
                    });
                    manualPersonListEdit.appendChild(li);
                });
            }


            saveProfileBtn.addEventListener('click', async function() {
                const newUsername = editUsernameInput.value.trim();
                const newProfileImageUrl = editProfileImageUrlInput.value.trim();
                const newCategories = Array.from(editSelectedCategories);
                const newPersons = Array.from(editSelectedPersons);

                if (!newUsername) {
                    showMessage('Nama pengguna tidak boleh kosong.', true);
                    return;
                }

                // Send data to save_preferences.php (which now handles profile updates)
                const data = {
                    username: currentUsername, // Original username to identify user
                    new_username: newUsername,
                    profile_image_url: newProfileImageUrl,
                    preferred_categories: newCategories,
                    preferred_persons: newPersons,
                    manual_persons_requested: editManualPersons // Send manually added persons from edit
                };

                const response = await makeApiRequest('save_preferences.php', 'POST', data);

                if (response.success) {
                    showMessage('Profil berhasil diperbarui!', false);
                    // Update displayed info
                    displayUsername.textContent = newUsername;
                    // Update avatar in header and main profile
                    if (newProfileImageUrl) {
                        userProfileAvatar.innerHTML = `<img src="${newProfileImageUrl}" alt="Profil Pengguna">`;
                        headerProfileIcon.innerHTML = `<img src="${newProfileImageUrl}" alt="Profil Pengguna" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                    } else {
                        userProfileAvatar.innerHTML = `<?php echo strtoupper(substr($username, 0, 1)); ?>`; // Fallback to initial
                        headerProfileIcon.textContent = newUsername.charAt(0).toUpperCase();
                    }
                    // If username changed, reload to update session or handle it via JS
                    if (newUsername !== currentUsername) {
                        console.warn("Username changed. A page reload might be required for full session consistency.");
                    }
                    editProfileOverlay.classList.remove('active'); // Hide popup
                    document.body.style.overflow = ''; // Enable body scroll
                    // Restore active tab and load pins
                    document.querySelector(`.tab-button[data-tab="${currentActiveTab}"]`).classList.add('active');
                    if (currentActiveTab === 'created') {
                        createdPinsContent.classList.add('active');
                    } else if (currentActiveTab === 'saved') {
                        savedPinsContent.classList.add('active');
                    } else if (currentActiveTab === 'liked') { // NEW: Restore liked tab
                        likedPinsContent.classList.add('active');
                    }
                } else {
                    showMessage('Gagal memperbarui profil: ' + response.message, true);
                }
            });

            // Initial load of created pins
            loadPins('created', pinsPerPage, false);

            // Set initial profile image preview
            if (initialProfileImageUrl) {
                profileImagePreview.src = initialProfileImageUrl;
                profileImagePreview.style.display = 'block';
            } else {
                profileImagePreview.style.display = 'none';
            }

            // Update header profile icon on load
            if (initialProfileImageUrl) {
                headerProfileIcon.innerHTML = `<img src="${initialProfileImageUrl}" alt="Profil Pengguna" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
            } else {
                headerProfileIcon.textContent = currentUsername.charAt(0).toUpperCase();
            }
        });
    </script>
</body>
</html>
