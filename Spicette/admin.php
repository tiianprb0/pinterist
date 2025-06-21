<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header('Location: index.html');
    exit();
}
$adminUsername = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spicette Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif; /* Menggunakan font Plus Jakarta Sans */
        }
        header {
             box-shadow: none !important; /* Dihilangkan box-shadow */
        }
    </style>
</head>
<body>
    <header>
        <div class="logo" onclick="window.location.href='index.html'"></div>
        <div class="header-nav-links">
            <button class="nav-button" data-nav="home" onclick="window.location.href='index.html'">Beranda</button>
            <button class="nav-button active">Panel Admin</button>
        </div>

        <div class="search-container" style="flex-grow: 1;"></div>
        <div class="header-icons">
            <button class="icon-button" aria-label="Profil Admin" onclick="window.location.href='admin.php'"><div class="profile-icon">A</div></button>
            <button class="icon-button" aria-label="Logout Admin" id="adminLogoutBtnHeader">
                <svg viewBox="0 0 24 24" fill="#5f5f5f" width="24px" height="24px"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"></path></svg>
            </button>
        </div>
    </header>

    <main>
        <div class="admin-page-container">
            <div class="admin-page-header">
                <h1>Spicette Admin Panel</h1>
                <button class="admin-logout-btn" id="adminLogoutBtn">Logout</button>
            </div>

            <div id="message"></div>

            <div class="admin-section">
                <h2>Pengaturan Notifikasi</h2>
                <form class="admin-form" id="addNotificationForm">
                    <label for="newNotificationText">Teks Notifikasi Baru:</label>
                    <textarea id="newNotificationText" placeholder="Masukkan teks notifikasi baru"></textarea>
                    <button type="submit">Tambah Notifikasi</button>
                </form>
                <h3>Daftar Notifikasi Aktif</h3>
                <div class="admin-list" id="notificationList">
                    <p style="text-align: center; color: #767676;">Memuat notifikasi...</p>
                </div>
            </div>

            <div class="admin-section">
                <h2>Manajemen Kategori</h2>
                <form class="admin-form" id="addCategoryForm">
                    <label for="newCategoryName">Nama Kategori Baru:</label>
                    <input type="text" id="newCategoryName" placeholder="Contoh: Desain Grafis" required>
                    <label for="newCategoryImageUrl">URL Gambar Kategori (opsional):</label>
                    <input type="url" id="newCategoryImageUrl" placeholder="Contoh: https://picsum.photos/200/200">
                    <button type="submit">Tambah Kategori</button>
                </form>
                <h3>Daftar Kategori Aktif</h3>
                <div class="admin-list" id="categoryList">
                    <p style="text-align: center; color: #767676;">Memuat daftar kategori...</p>
                </div>
            </div>

            <div class="admin-section">
                <h2>Manajemen Pengguna</h2>
                <div class="admin-list" id="userList">
                    <p style="text-align: center; color: #767676;">Memuat daftar pengguna...</p>
                </div>
            </div>

            <div class="admin-section">
                <h2>Manajemen Pin</h2>
                <div class="admin-list" id="pinList">
                    <p style="text-align: center; color: #767676;">Memuat daftar pin...</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userList = document.getElementById('userList');
            const pinList = document.getElementById('pinList');
            const notificationList = document.getElementById('notificationList');
            const messageDiv = document.getElementById('message');
            const adminLogoutBtn = document.getElementById('adminLogoutBtn');
            const adminLogoutBtnHeader = document.getElementById('adminLogoutBtnHeader');

            const addNotificationForm = document.getElementById('addNotificationForm');
            const newNotificationText = document.getElementById('newNotificationText');

            const addCategoryForm = document.getElementById('addCategoryForm');
            const newCategoryName = document.getElementById('newCategoryName');
            const newCategoryImageUrl = document.getElementById('newCategoryImageUrl');
            const categoryList = document.getElementById('categoryList');

            const adminUsername = '<?php echo $adminUsername; ?>';

            // --- API Base URL ---
            const API_BASE_URL = 'api/';

            // --- Helper Function for API Requests ---
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
                        throw new Error(`HTTP error! Status: ${response.status} - ${errorText}`);
                    }
                    const textResponse = await response.text();
                    if (!textResponse) {
                        return { success: true, message: 'No content' };
                    }
                    try {
                        const jsonResponse = JSON.parse(textResponse);
                        return jsonResponse;
                    } catch (e) {
                        console.error('Failed to parse JSON response:', textResponse);
                        throw new Error(`Invalid JSON response: ${textResponse}`);
                    }
                } catch (error) {
                    console.error('API Request Gagal:', error);
                    showMessage(`Error jaringan atau server: ${error.message}`, true);
                    return { success: false, message: 'Error jaringan atau server.' };
                }
            }

            // --- Function to Display Messages ---
            function showMessage(msg, isError = false) {
                messageDiv.textContent = msg;
                messageDiv.className = isError ? 'error' : '';
                messageDiv.style.display = 'block';
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            }

            // --- Admin Logout Functionality ---
            async function handleAdminLogout() {
                try {
                    const response = await makeApiRequest('auth.php?action=logout', 'POST', null); 
                    if (response.success) {
                        window.location.href = 'index.html';
                    } else {
                        showMessage('Logout gagal: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error selama proses logout: ' + error.message, true);
                }
            }
            adminLogoutBtn.addEventListener('click', handleAdminLogout);
            adminLogoutBtnHeader.addEventListener('click', handleAdminLogout);


            // --- Notification Management ---
            async function fetchNotifications() {
                notificationList.innerHTML = '<p style="text-align: center; color: #767676;">Memuat notifikasi...</p>';
                try {
                    const response = await makeApiRequest('notifications.php?action=fetch_all');
                    if (response.success) {
                        renderNotificationList(response.notifications);
                    } else {
                        showMessage('Gagal memuat notifikasi: ' + response.message, true);
                        notificationList.innerHTML = `<p style="text-align: center; color: red;">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Error saat mengambil daftar notifikasi: ' + error.message, true);
                    notificationList.innerHTML = `<p style="text-align: center; color: red;">${error.message}</p>`;
                }
            }

            function renderNotificationList(notifications) {
                notificationList.innerHTML = '';
                if (notifications.length === 0) {
                    notificationList.innerHTML = '<p style="text-align: center; color: #767676;">Tidak ada notifikasi aktif.</p>';
                    return;
                }

                notifications.forEach(notif => {
                    const notifItem = document.createElement('div');
                    notifItem.classList.add('list-item');
                    notifItem.innerHTML = `
                        <div class="list-item-content">
                            <strong>${notif.text}</strong>
                            <span>ID: ${notif.id}</span>
                            <span>Ditambahkan: ${notif.timestamp || 'N/A'}</span>
                            <span>Status: ${notif.read ? 'Dibaca' : 'Belum Dibaca'}</span>
                        </div>
                        <div class="list-item-actions">
                            <button class="delete-btn" data-id="${notif.id}">Hapus</button>
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
                    showMessage('Error saat menambahkan notifikasi: ' + error.message, true);
                }
            });

            async function deleteNotification(id) {
                if (!confirm(`Apakah Anda yakin ingin menghapus notifikasi ini?`)) {
                    return;
                }
                try {
                    const response = await makeApiRequest('notifications.php?action=delete', 'POST', { id });
                    if (response.success) {
                        showMessage('Notifikasi berhasil dihapus!', false);
                        fetchNotifications();
                    } else {
                        showMessage('Gagal menghapus notifikasi: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error saat menghapus notifikasi: ' + error.message, true);
                }
            }

            // --- Category Management ---
            async function fetchCategoriesAdmin() {
                categoryList.innerHTML = '<p style="text-align: center; color: #767676;">Memuat daftar kategori...</p>';
                try {
                    const response = await makeApiRequest('categories.php?action=fetch_all');
                    if (response.success) {
                        renderCategoryList(response.categories);
                    } else {
                        showMessage('Gagal memuat kategori: ' + response.message, true);
                        categoryList.innerHTML = `<p style="text-align: center; color: red;">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Error saat mengambil daftar kategori: ' + error.message, true);
                    categoryList.innerHTML = `<p style="text-align: center; color: red;">${error.message}</p>`;
                }
            }

            function renderCategoryList(categories) {
                categoryList.innerHTML = '';
                if (categories.length === 0) {
                    categoryList.innerHTML = '<p style="text-align: center; color: #767676;">Tidak ada kategori.</p>';
                    return;
                }
                categories.forEach(category => {
                    const categoryItem = document.createElement('div');
                    categoryItem.classList.add('list-item');
                    categoryItem.innerHTML = `
                        <img src="${category.imageUrl || 'https://placehold.co/60x60/e0e0e0/767676?text=No+Image'}" alt="Gambar Kategori" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/60x60/e0e0e0/767676?text=No+Image';">
                        <div class="list-item-content">
                            <strong>${category.name}</strong>
                            <span>URL Gambar: ${category.imageUrl || 'N/A'}</span>
                        </div>
                        <div class="list-item-actions">
                            <button class="delete-btn" data-name="${category.name}">Hapus</button>
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
                    showMessage('Error saat menambahkan kategori: ' + error.message, true);
                }
            });

            async function deleteCategory(name) {
                if (!confirm(`Apakah Anda yakin ingin menghapus kategori "${name}"?`)) {
                    return;
                }
                try {
                    const response = await makeApiRequest('categories.php?action=delete', 'POST', { name });
                    if (response.success) {
                        showMessage('Kategori berhasil dihapus!', false);
                        fetchCategoriesAdmin();
                    } else {
                        showMessage('Gagal menghapus kategori: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error saat menghapus kategori: ' + error.message, true);
                }
            }


            // --- User Management ---
            async function fetchUsers() {
                userList.innerHTML = '<p style="text-align: center; color: #767676;">Memuat daftar pengguna...</p>';
                try {
                    const response = await makeApiRequest('pins.php?action=fetch_all_users');
                    if (response.success) {
                        renderUserList(response.users);
                    } else {
                        showMessage('Gagal memuat pengguna: ' + response.message, true);
                        userList.innerHTML = `<p style="text-align: center; color: red;">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Error saat mengambil daftar pengguna: ' + error.message, true);
                    userList.innerHTML = `<p style="text-align: center; color: red;">${error.message}</p>`;
                }
            }

            function renderUserList(users) {
                userList.innerHTML = '';
                if (users.length === 0) {
                    userList.innerHTML = '<p style="text-align: center; color: #767676;">Tidak ada pengguna terdaftar.</p>';
                    return;
                }

                users.forEach(user => {
                    const userItem = document.createElement('div');
                    userItem.classList.add('list-item');
                    
                    let deleteButtonHtml = '';
                    if (user.username === adminUsername) {
                        deleteButtonHtml = `<button class="delete-btn" disabled title="Anda tidak bisa menghapus akun Anda sendiri">Anda</button>`;
                    } else if (user.isAdmin) {
                         deleteButtonHtml = `<button class="delete-btn" disabled title="Tidak bisa menghapus admin lain">Admin</button>`;
                    } else {
                        deleteButtonHtml = `<button class="delete-btn" data-username="${user.username}">Hapus</button>`;
                    }

                    userItem.innerHTML = `
                        <div class="list-item-content">
                            <strong>${user.username}</strong>
                            <span>Status: ${user.isAdmin ? 'Admin' : 'Pengguna Biasa'}</span>
                        </div>
                        <div class="list-item-actions">
                            ${deleteButtonHtml}
                        </div>
                    `;
                    const deleteBtn = userItem.querySelector('.delete-btn');
                    if (deleteBtn) {
                         deleteBtn.onclick = () => deleteUser(user.username);
                    }
                    userList.appendChild(userItem);
                });
            }

            async function deleteUser(username) {
                if (!confirm(`Apakah Anda yakin ingin menghapus pengguna ${username} dan semua pin yang disimpannya?`)) {
                    return;
                }
                try {
                    const response = await makeApiRequest('pins.php?action=delete_user', 'POST', { username });
                    if (response.success) {
                        showMessage(`Pengguna ${username} berhasil dihapus!`, false);
                        fetchUsers();
                    } else {
                        showMessage('Gagal menghapus pengguna: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error saat menghapus pengguna: ' + error.message, true);
                }
            }

            // --- Pin Management (Delete only for admin here) ---
            async function fetchPinsForAdmin() {
                pinList.innerHTML = '<p style="text-align: center; color: #767676;">Memuat daftar pin...</p>';
                try {
                    const response = await makeApiRequest('pins.php?action=fetch_all');
                    if (response.success) {
                        renderAdminPinList(response.pins);
                    } else {
                        showMessage('Gagal memuat pin: ' + response.message, true);
                        pinList.innerHTML = `<p style="text-align: center; color: red;">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Error saat mengambil daftar pin: ' + error.message, true);
                    pinList.innerHTML = `<p style="text-align: center; color: red;">${error.message}</p>`;
                }
            }

            function renderAdminPinList(pins) {
                pinList.innerHTML = '';
                if (pins.length === 0) {
                    pinList.innerHTML = '<p style="text-align: center; color: #767676;">Tidak ada pin tersedia.</p>';
                    return;
                }

                pins.forEach(pin => {
                    const pinItem = document.createElement('div');
                    pinItem.classList.add('list-item');
                    pinItem.innerHTML = `
                        <img src="${pin.img}" alt="Gambar Pin" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/60x60/e0e0e0/767676?text=No+Image';">
                        <div class="list-item-content">
                            <strong>ID: ${pin.id}</strong>
                            <span>Sumber: ${pin.source || 'N/A'}</span>
                            <span>Diunggah oleh: ${pin.uploadedBy || 'N/A'}</span>
                            <span>Judul: ${pin.title || 'N/A'}</span>
                        </div>
                        <div class="list-item-actions">
                            <button class="delete-btn" data-id="${pin.id}">Hapus</button>
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
                if (!confirm(`Apakah Anda yakin ingin menghapus pin ${pinId}?`)) {
                    return;
                }
                try {
                    const response = await makeApiRequest('pins.php?action=delete_pin', 'POST', { pinId });
                    if (response.success) {
                        showMessage('Pin berhasil dihapus!', false);
                        fetchPinsForAdmin();
                    } else {
                        showMessage('Gagal menghapus pin: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error saat menghapus pin: ' + error.message, true);
                }
            }

            // Initial loads for admin panel
            fetchNotifications();
            fetchCategoriesAdmin();
            fetchUsers();
            fetchPinsForAdmin();
        });
    </script>
</body>
</html>