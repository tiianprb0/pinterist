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
    const overlayMobileSidebar = document.getElementById('overlayMobileSidebar'); // Overlay baru

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
    const categoryImagePreview = document.getElementById('categoryImagePreview'); // Image preview untuk kategori

    const userList = document.getElementById('userList');
    const pinList = document.getElementById('pinList');
    const manualPersonRequestList = document.getElementById('manualPersonRequestList');

    // Overview elements
    const totalUsersCountElem = document.getElementById('totalUsersCount');
    const totalPinsCountElem = document.getElementById('totalPinsCount');
    const personTagStatsList = document.getElementById('personTagStatsList');

    // Elemen modal konfirmasi
    const customConfirmationModal = document.getElementById('customConfirmationModal');
    const modalMessage = document.getElementById('modalMessage');
    const confirmYesButton = document.getElementById('confirmYes');
    const confirmNoButton = document.getElementById('confirmNo');

    // Elemen Edit Pin Modal
    const editPinOverlay = document.getElementById('editPinOverlay');
    const editPinForm = document.getElementById('editPinForm');
    const editPinId = document.getElementById('editPinId');
    const editPinTitle = document.getElementById('editPinTitle');
    const editPinPhotoDescription = document.getElementById('editPinPhotoDescription');
    const editPinDescription = document.getElementById('editPinDescription');
    const editDisplayStacked = document.getElementById('editDisplayStacked');
    const editDisplaySlider = document.getElementById('editDisplaySlider');
    const editPinCategoryCheckboxes = document.getElementById('editPinCategoryCheckboxes');
    const editPinPersonTags = document.getElementById('editPinPersonTags');
    const editPinPersonTagSuggestions = document.getElementById('editPinPersonTagSuggestions');
    const editPinLevel = document.getElementById('editPinLevel');
    const cancelEditPinBtn = document.getElementById('cancelEditPinBtn');

    const adminUsername = '<?php echo $adminUsername; ?>';
    const adminProfileImageUrl = '<?php echo $adminProfileImageUrl; ?>';

    let allCategories = []; // Untuk menyimpan semua kategori
    let allPersons = []; // Untuk menyimpan semua orang untuk saran tag
    let currentEditPinCategories = new Set();
    let currentEditPinPersonTags = new Set();
    let editPinSelectedSuggestionIndex = -1;

    // --- URL Dasar API (relatif dari xxx/script.js) ---
    const API_BASE_URL = '../api/';

    /**
     * Mengirim permintaan API ke endpoint yang ditentukan.
     * @param {string} endpoint - Endpoint API.
     * @param {string} method - Metode HTTP (GET, POST).
     * @param {object} data - Data yang akan dikirim dengan permintaan (untuk POST).
     * @param {boolean} isFormData - Apakah data adalah FormData.
     * @returns {Promise<object>} - Respons JSON dari API.
     */
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
            const textResponse = await response.text(); 
            
            if (!response.ok) {
                let errorMessage = `Kesalahan HTTP! Status: ${response.status}`;
                try {
                    const errorJson = JSON.parse(textResponse);
                    errorMessage += ` - ${errorJson.message || 'Pesan tidak tersedia'}`;
                } catch (e) {
                    errorMessage += ` - Respons: ${textResponse.substring(0, 200)}... (bukan JSON atau terlalu panjang)`;
                }
                throw new Error(errorMessage);
            }
            
            if (!textResponse) {
                return { success: true, message: 'Tidak ada konten' };
            }
            
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

    /**
     * Menampilkan pesan alert kustom.
     * @param {string} msg - Pesan yang akan ditampilkan.
     * @param {boolean} isError - True jika pesan error, false jika sukses.
     */
    function showMessage(msg, isError = false) {
        messageDiv.textContent = msg;
        messageDiv.className = `message-box ${isError ? 'error' : 'success'}`;
        messageDiv.style.display = 'block';
        messageDiv.classList.remove('show');
        void messageDiv.offsetWidth; // Trigger reflow
        messageDiv.classList.add('show');
        setTimeout(() => {
            messageDiv.classList.remove('show');
        }, 3000);
    }

    /**
     * Modal Konfirmasi Kustom.
     * @param {string} message - Pesan konfirmasi.
     * @param {Function} onConfirmCallback - Callback jika dikonfirmasi.
     */
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
                    window.location.href = '../index.html'; // Kembali ke root index.html
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
                overlayMobileSidebar.classList.remove('active'); // Sembunyikan overlay
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
        overlayMobileSidebar.classList.toggle('active'); // Toggle overlay
    });

    // Tutup sidebar jika diklik di luar overlay (mobile)
    overlayMobileSidebar.addEventListener('click', () => {
        if (adminSidebar.classList.contains('active')) {
            adminSidebar.classList.remove('active');
            hamburgerMenuButton.classList.remove('active');
            hamburgerMenuContainer.classList.remove('active');
            overlayMobileSidebar.classList.remove('active');
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
        categoryList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary); column-span: all;">Memuat daftar kategori...</p>';
        try {
            const response = await makeApiRequest('categories.php?action=fetch_all');
            if (response.success) {
                allCategories = response.categories; // Simpan kategori yang dimuat
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

    // Fungsi universal untuk mengoreksi path gambar
    function getCorrectedImagePath(originalPath) {
        if (!originalPath || originalPath.startsWith('http://') || originalPath.startsWith('https://')) {
            return originalPath; // Biarkan URL absolut atau kosong
        }

        let path = originalPath;

        // Hapus semua kemunculan '/Spicette/xxx/' atau 'xxx/' dari awal path
        // Ini untuk mengatasi kasus di mana backend mungkin sudah menambahkan 'xxx/' atau bahkan 'Spicette/xxx/'
        path = path.replace(/^\/?Spicette\/xxx\//, ''); // Menghapus /Spicette/xxx/ atau Spicette/xxx/
        path = path.replace(/^xxx\//, ''); // Menghapus xxx/

        // Hapus juga '/Spicette/' jika ada di awal, karena kita akan menambahkannya kembali
        path = path.replace(/^\/?Spicette\//, '');

        // Pastikan path selalu dimulai dengan /Spicette/uploads/ jika itu adalah path upload
        if (path.startsWith('Spicette/uploads/pins/') || path.startsWith('uploads/blur-pins/') || path.startsWith('uploads/categories/')) {
            return '/Spicette/' + path;
        }
        
        // Fallback untuk path lain yang tidak perlu koreksi atau tidak terduga
        // Jika path tidak dimulai dengan '/', tambahkan agar menjadi path absolut dari root domain
        if (!path.startsWith('/')) {
            return '/' + path;
        }

        return path;
    }

    function renderCategoryList(categories) {
        categoryList.innerHTML = '';
        if (categories.length === 0) {
            categoryList.innerHTML = '<p style="text-align: center; color: var(--color-text-secondary); column-span: all;">Tidak ada kategori.</p>';
            return;
        }
        categories.forEach(category => {
            const categoryItem = document.createElement('div');
            categoryItem.classList.add('image-grid-item');
            // Gunakan fungsi getCorrectedImagePath untuk URL kategori
            const imageUrl = getCorrectedImagePath(category.imageUrl || 'https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Kategori');
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
                categoryImagePreview.style.display = 'none'; // Sembunyikan preview gambar
                categoryImagePreview.src = ''; // Hapus sumber gambar
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

        const userLevels = ['tempted', 'Naughty', 'Sinful'];

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
            
            let levelSelectHtml = '';
            if (!user.isAdmin) {
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
                // Menggunakan auth.php untuk update_user_permission yang sekarang juga menangani level
                const response = await makeApiRequest('auth.php?action=update_user_permission', 'POST', { username: username, canUpload: canUpload });
                if (response.success) {
                    showMessage(`Izin unggah untuk ${username} berhasil diperbarui!`, false);
                    fetchUsers();
                } else {
                    showMessage('Gagal memperbarui izin unggah: ' + response.message, true);
                    fetchUsers();
                }
            } catch (error) {
                showMessage('Kesalahan memperbarui izin unggah: ' + error.message, true);
                fetchUsers();
            }
        });
    }

    async function updateUserLevel(username, level) {
        showCustomConfirmation(`Apakah Anda yakin ingin mengubah level pengguna ${username} menjadi "${level}"?`, async () => {
            try {
                const response = await makeApiRequest('auth.php?action=update_user_permission', 'POST', { username: username, level: level });
                if (response.success) {
                    showMessage(`Level pengguna ${username} berhasil diperbarui menjadi "${level}"!`, false);
                    fetchUsers();
                } else {
                    showMessage('Gagal memperbarui level pengguna: ' + response.message, true);
                    fetchUsers();
                }
            } catch (error) {
                showMessage('Kesalahan memperbarui level pengguna: ' + error.message, true);
                fetchUsers();
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
                    fetchOverviewStats();
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
            // Gunakan fungsi getCorrectedImagePath untuk URL pin
            let imageUrl = 'https://placehold.co/700x400/e0e0e0/767676?text=Tidak+Ada+Gambar+Pin';
            if (Array.isArray(pin.images) && pin.images.length > 0) {
                imageUrl = getCorrectedImagePath(pin.images[0].url_original || pin.images[0].url);
            } else if (typeof pin.img === 'string' && pin.img) { // Fallback for very old pins if 'images' array is not present
                imageUrl = getCorrectedImagePath(pin.img);
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
            const pinLevelHtml = pin.level ? `<span class="image-grid-item-detail">Level: ${pin.level}</span>` : '';

            const pinItem = document.createElement('div');
            pinItem.classList.add('image-grid-item');
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
                    <button class="edit-btn" data-id="${pin.id}"><i class="fa-solid fa-pen"></i> Edit</button>
                    <button class="delete-btn" data-id="${pin.id}"><i class="fa-solid fa-trash-can"></i> Hapus</button>
                </div>
            `;
            const deleteBtn = pinItem.querySelector('.delete-btn');
            if (deleteBtn) {
                 deleteBtn.onclick = () => deletePin(pin.id);
            }
            const editBtn = pinItem.querySelector('.edit-btn');
            if (editBtn) {
                editBtn.onclick = () => openEditPinModal(pin.id);
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
                    fetchOverviewStats();
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
                    fetchOverviewStats();
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
        updateCategoryImagePreview(); // Update preview on drop
    });

    newCategoryImageFile.addEventListener('change', () => {
        updateSelectedCategoryImageIndicator();
        updateCategoryImagePreview(); // Update preview on change
    });

    function updateSelectedCategoryImageIndicator() {
        if (newCategoryImageFile.files.length > 0) {
            selectedCategoryImageIndicator.textContent = `${newCategoryImageFile.files.length} file dipilih: ${newCategoryImageFile.files[0].name}`;
            selectedCategoryImageIndicator.style.display = 'block';
        } else {
            selectedCategoryImageIndicator.textContent = '';
            selectedCategoryImageIndicator.style.display = 'none';
        }
    }

    function updateCategoryImagePreview() {
        if (newCategoryImageFile.files && newCategoryImageFile.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                categoryImagePreview.src = e.target.result;
                categoryImagePreview.style.display = 'block';
            };
            reader.readAsDataURL(newCategoryImageFile.files[0]);
        } else {
            categoryImagePreview.src = '';
            categoryImagePreview.style.display = 'none';
        }
    }

    // --- Edit Pin Functionality ---
    async function openEditPinModal(pinId) {
        try {
            const response = await makeApiRequest(`pins.php?action=fetch_single&id=${pinId}`);
            if (response.success && response.pin) {
                const pin = response.pin;
                editPinId.value = pin.id;
                editPinTitle.value = pin.title || '';
                editPinPhotoDescription.value = pin.photo_description || '';
                editPinDescription.value = pin.description || pin.content || '';

                // Set display type
                if (pin.display_type === 'slider') {
                    editDisplaySlider.checked = true;
                } else {
                    editDisplayStacked.checked = true;
                }

                // Load and set categories
                currentEditPinCategories.clear();
                if (Array.isArray(pin.categories)) {
                    pin.categories.forEach(cat => currentEditPinCategories.add(cat));
                } else if (pin.category) { // Fallback for old structure
                    currentEditPinCategories.add(pin.category);
                }
                renderEditPinCategories();

                // Set person tags
                editPinPersonTags.value = Array.isArray(pin.personTags) ? pin.personTags.join(', ') : '';
                
                // Load and set pin level
                const pinLevels = ['tempted', 'Naughty', 'Sinful']; // Define levels
                editPinLevel.innerHTML = '';
                pinLevels.forEach(level => {
                    const option = document.createElement('option');
                    option.value = level;
                    option.textContent = level.charAt(0).toUpperCase() + level.slice(1);
                    editPinLevel.appendChild(option);
                });
                editPinLevel.value = pin.level || 'tempted'; // Default to tempted

                editPinOverlay.classList.add('active');
            } else {
                showMessage('Gagal memuat detail pin: ' + response.message, true);
            }
        } catch (error) {
            showMessage('Kesalahan memuat detail pin: ' + error.message, true);
        }
    }

    async function renderEditPinCategories() {
        editPinCategoryCheckboxes.innerHTML = '';
        if (allCategories.length === 0) {
            const response = await makeApiRequest('categories.php?action=fetch_all');
            if (response.success) {
                allCategories = response.categories;
            } else {
                editPinCategoryCheckboxes.innerHTML = `<p style="color: var(--color-error);">Gagal memuat kategori.</p>`;
                return;
            }
        }

        allCategories.forEach(cat => {
            const checkboxItem = document.createElement('div');
            checkboxItem.classList.add('category-checkbox-item');
            checkboxItem.innerHTML = `
                <input type="checkbox" id="edit_category_${cat.name.replace(/\s+/g, '_')}" value="${cat.name}" ${currentEditPinCategories.has(cat.name) ? 'checked' : ''}>
                <label for="edit_category_${cat.name.replace(/\s+/g, '_')}">${cat.name}</label>
            `;
            checkboxItem.querySelector('input').addEventListener('change', (e) => {
                if (e.target.checked) {
                    currentEditPinCategories.add(e.target.value);
                } else {
                    currentEditPinCategories.delete(e.target.value);
                }
            });
            editPinCategoryCheckboxes.appendChild(checkboxItem);
        });
    }

    // Person Tag Suggestions for Edit Pin Modal
    editPinPersonTags.addEventListener('input', () => {
        const inputValue = editPinPersonTags.value;
        const lastCommaIndex = inputValue.lastIndexOf(',');
        const currentInput = (lastCommaIndex !== -1 ? inputValue.substring(lastCommaIndex + 1) : inputValue).trim().toLowerCase();

        editPinPersonTagSuggestions.innerHTML = '';
        editPinPersonTagSuggestions.style.display = 'none';
        editPinSelectedSuggestionIndex = -1;

        if (currentInput.length === 0) {
            return;
        }

        // Fetch all persons if not already loaded
        if (allPersons.length === 0) {
             makeApiRequest('get_persons.php').then(response => {
                if (response.success) {
                    allPersons = response.persons;
                    filterAndRenderEditPinPersonSuggestions(currentInput);
                }
            });
        } else {
            filterAndRenderEditPinPersonSuggestions(currentInput);
        }
    });

    function filterAndRenderEditPinPersonSuggestions(currentInput) {
        const filteredSuggestions = allPersons.filter(tag =>
            tag.toLowerCase().includes(currentInput)
        );

        if (filteredSuggestions.length > 0) {
            filteredSuggestions.forEach((tag, index) => {
                const li = document.createElement('li');
                li.textContent = tag;
                li.dataset.index = index;
                li.addEventListener('click', () => {
                    addEditPinPersonTagFromSuggestion(tag);
                });
                editPinPersonTagSuggestions.appendChild(li);
            });
            editPinPersonTagSuggestions.style.display = 'block';
        }
    }

    function addEditPinPersonTagFromSuggestion(tag) {
        let inputValue = editPinPersonTags.value;
        const lastCommaIndex = inputValue.lastIndexOf(',');
        
        let newInputValue;
        if (lastCommaIndex !== -1) {
            const existingPart = inputValue.substring(0, lastCommaIndex + 1).trim();
            const existingTags = existingPart.split(',').map(t => t.trim().toLowerCase());
            if (existingTags.includes(tag.toLowerCase())) {
                newInputValue = existingPart + ' ';
            } else {
                newInputValue = existingPart + ' ' + tag + ', ';
            }
        } else {
            newInputValue = tag + ', ';
        }
        newInputValue = newInputValue.replace(/,\s*,/g, ',').replace(/,\s*$/, ', ').trim();
        if (!newInputValue.endsWith(',')) {
            newInputValue += ', ';
        }
        newInputValue = newInputValue.replace(/\s*,\s*/g, ', ').trim();
        if (!newInputValue.endsWith(',')) {
            newInputValue += ',';
        }
        newInputValue = newInputValue.replace(/,$/, ', ');

        const finalTagsArray = newInputValue.split(',').map(t => t.trim()).filter(t => t !== '');
        const uniqueFinalTags = Array.from(new Set(finalTagsArray));
        editPinPersonTags.value = uniqueFinalTags.join(', ') + (uniqueFinalTags.length > 0 ? ', ' : '');

        editPinPersonTags.focus();
        editPinPersonTags.selectionStart = editPinPersonTags.selectionEnd = editPinPersonTags.value.length;
        editPinPersonTagSuggestions.style.display = 'none';
    }

    editPinPersonTags.addEventListener('keydown', (e) => {
        const items = editPinPersonTagSuggestions.querySelectorAll('li');
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            editPinSelectedSuggestionIndex = (editPinSelectedSuggestionIndex + 1) % items.length;
            updateEditPinSelectedSuggestion();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            editPinSelectedSuggestionIndex = (editPinSelectedSuggestionIndex - 1 + items.length) % items.length;
            updateEditPinSelectedSuggestion();
        } else if (e.key === 'Enter') {
            if (editPinSelectedSuggestionIndex !== -1) {
                e.preventDefault();
                addEditPinPersonTagFromSuggestion(items[editPinSelectedSuggestionIndex].textContent);
            }
        }
    });

    function updateEditPinSelectedSuggestion() {
        const items = editPinPersonTagSuggestions.querySelectorAll('li');
        items.forEach((item, index) => {
            if (index === editPinSelectedSuggestionIndex) {
                item.classList.add('selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
            }
        });
    }

    editPinPersonTags.addEventListener('blur', () => {
        setTimeout(() => {
            if (!editPinPersonTagSuggestions.contains(document.activeElement)) {
                editPinPersonTagSuggestions.style.display = 'none';
            }
        }, 100);
    });

    editPinPersonTagSuggestions.addEventListener('mousedown', (e) => {
        e.preventDefault();
    });


    cancelEditPinBtn.addEventListener('click', () => {
        editPinOverlay.classList.remove('active');
    });

    editPinForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const pinId = editPinId.value;
        const updatedData = {
            id: pinId,
            title: editPinTitle.value.trim(),
            photo_description: editPinPhotoDescription.value.trim(),
            description: editPinDescription.value.trim(),
            display_type: document.querySelector('input[name="edit_display_type"]:checked').value,
            categories: Array.from(currentEditPinCategories),
            personTags: editPinPersonTags.value.split(',').map(tag => tag.trim()).filter(tag => tag !== ''),
            level: editPinLevel.value
        };

        try {
            const response = await makeApiRequest('pins.php?action=update_pin', 'POST', updatedData);
            if (response.success) {
                showMessage('Pin berhasil diperbarui!', false);
                editPinOverlay.classList.remove('active');
                fetchPinsForAdmin(); // Refresh the pin list
            } else {
                showMessage('Gagal memperbarui pin: ' + response.message, true);
            }
        } catch (error) {
            showMessage('Kesalahan memperbarui pin: ' + error.message, true);
        }
    });


    // Muatan awal untuk panel admin
    fetchOverviewStats(); // Load overview first
});
