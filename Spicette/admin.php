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
    <!-- Admin Specific Styles -->
    <link rel="stylesheet" href="admin-style.css">
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
            <!-- Removed admin-page-header as requested -->
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
                            <p style="text-align: center; color: var(--text-secondary);">Memuat statistik tag orang...</p>
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
            // const adminLogoutBtnContent = document.getElementById('adminLogoutBtnContent'); // Removed as requested

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
                    }
                    catch (error) {
                        showMessage('Kesalahan selama proses logout: ' + error.message, true);
                    }
                });
            }
            adminLogoutBtnHeader.addEventListener('click', handleAdminLogout);
            // adminLogoutBtnContent.addEventListener('click', handleAdminLogout); // Removed as requested

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
                personTagStatsList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Memuat statistik tag orang...</p>';

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
                        personTagStatsList.innerHTML = `<p style="text-align: center; color: var(--danger);">Gagal memuat statistik pin: ${pinStatsResponse.message}</p>`;
                        console.error('Gagal memuat statistik pin:', pinStatsResponse.message);
                    }
                } catch (error) {
                    totalUsersCountElem.textContent = 'Error';
                    totalPinsCountElem.textContent = 'Error';
                    personTagStatsList.innerHTML = `<p style="text-align: center; color: var(--danger);">Kesalahan memuat overview: ${error.message}</p>`;
                    console.error('Kesalahan mengambil overview statistik:', error);
                }
            }

            function renderPersonTagStats(personTagCounts) {
                personTagStatsList.innerHTML = '';
                if (Object.keys(personTagCounts).length === 0) {
                    personTagStatsList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Tidak ada tag orang yang ditemukan.</p>';
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
                    const response = await makeApiRequest('pins.php?action=fetch_all_users'); 
                    if (response.success) {
                        renderUserList(response.users);
                    } else {
                        showMessage('Gagal memuat pengguna: ' + response.message, true);
                        userList.innerHTML = `<p style="text-align: center; color: var(--danger);">${response.message}</p>`;
                    }
                }
                catch (error) {
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

                    const displayTypeHtml = pin.display_type ? `<span class="list-item-detail">Tipe Tampilan: ${pin.display_type}</span>` : '';
                    const pinLevelHtml = pin.level ? `<span class="list-item-detail">Level: ${pin.level}</span>` : ''; // Display pin level

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
                            <span class="list-item-detail">Tag Orang: ${personTagsText}</span>
                            ${displayTypeHtml}
                            ${pinLevelHtml}
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
                manualPersonRequestList.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Memuat permintaan orang manual...</p>';
                try {
                    const response = await makeApiRequest('manual_person_requests.php?action=fetch_all');
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


            // Muatan awal untuk panel admin
            fetchOverviewStats(); // Load overview first
        });
    </script>
</body>
</html>
