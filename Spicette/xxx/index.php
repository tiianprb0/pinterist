<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
session_start();
// Memastikan hanya admin yang bisa mengakses halaman ini. Jika tidak login atau bukan admin, redirect ke halaman login.
if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header('Location: ../index.html?login=true'); // Redirect ke index dengan overlay login
    exit();
}
$adminUsername = $_SESSION['username'];

// Path ke file users.json (relatif dari xxx/index.php)
$usersFilePath = __DIR__ . '/../data/users.json';

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
    <!-- Admin Specific Styles -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo" onclick="window.location.href='../index.html'">Spicette</div>
        <div class="header-nav-links" style="display: none;">
            <button class="nav-button" data-nav="home" onclick="window.location.href='../index.html'">Beranda</button>
            <button class="nav-button active">Panel Admin</button>
        </div>
        <div class="search-container" style="display: none;"></div>
        <div class="header-icons">
            <!-- Desktop Profile Icon -->
            <button class="icon-button" aria-label="Profil Admin" onclick="window.location.href='index.php'">
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
                        <img id="categoryImagePreview" class="category-image-preview" src="" alt="Pratinjau Gambar Kategori" style="display: none;">
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

    <!-- Edit Pin Modal -->
    <div id="editPinOverlay" class="edit-pin-overlay">
        <div id="editPinSection" class="edit-pin-section">
            <h2>Edit Pin</h2>
            <form id="editPinForm">
                <input type="hidden" id="editPinId">
                <div class="form-group">
                    <label for="editPinTitle">Judul Pin:</label>
                    <input type="text" id="editPinTitle" required>
                </div>
                <div class="form-group">
                    <label for="editPinPhotoDescription">Penjelasan Foto:</label>
                    <textarea id="editPinPhotoDescription"></textarea>
                </div>
                <div class="form-group">
                    <label for="editPinDescription">Deskripsi Pin:</label>
                    <textarea id="editPinDescription"></textarea>
                </div>
                <div class="form-group">
                    <label>Tipe Tampilan Pin:</label>
                    <div class="radio-group">
                        <input type="radio" id="editDisplayStacked" name="edit_display_type" value="stacked">
                        <label for="editDisplayStacked">Gambar Bertumpuk!</label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" id="editDisplaySlider" name="edit_display_type" value="slider">
                        <label for="editDisplaySlider">Slider Gambar!</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editPinCategory">Kategori:</label>
                    <div id="editPinCategoryCheckboxes" class="category-checkbox-group">
                        <!-- Categories will be loaded here -->
                    </div>
                </div>
                <div class="form-group">
                    <label for="editPinPersonTags">Orang dalam Pin (pisahkan dengan koma):</label>
                    <textarea id="editPinPersonTags"></textarea>
                    <ul id="editPinPersonTagSuggestions" class="person-tag-suggestions"></ul>
                </div>
                <div class="form-group">
                    <label for="editPinLevel">Visibilitas Pin:</label>
                    <select id="editPinLevel">
                        <!-- Options will be dynamically loaded -->
                    </select>
                </div>
                <div class="edit-pin-actions">
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                    <button type="button" class="btn-secondary" id="cancelEditPinBtn">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <div class="overlay-mobile-sidebar" id="overlayMobileSidebar"></div>

    <!-- Admin Specific Scripts -->
    <script src="script.js"></script>
</body>
</html>
