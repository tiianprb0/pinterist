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
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif; /* Menggunakan font Plus Jakarta Sans */
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
            margin: 0 auto 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .pin-grid {
            column-count: 3;
            column-gap: 15px;
            padding: 0 20px;
            margin-top: 30px;
        }
        @media (max-width: 992px) { .pin-grid { column-count: 2; } }
        @media (max-width: 576px) { .pin-grid { column-count: 1; } }
        .pin {
            display: inline-block; width: 100%; margin-bottom: 15px; border-radius: 16px; overflow: hidden; position: relative; cursor: pointer; background-color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .pin img { width: 100%; height: auto; display: block; border-radius: 16px; }

        /* Gaya untuk tab */
        .tab-navigation {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            margin-bottom: 20px;
            gap: 10px;
        }
        .tab-button {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            background-color: #efefef;
            color: #333;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }
        .tab-button.active {
            background-color: #111;
            color: #fff;
        }
        .tab-button:hover:not(.active) {
            background-color: #e0e0e0;
        }
        .tab-content {
            display: none; /* Sembunyikan semua konten tab secara default */
        }
        .tab-content.active {
            display: block; /* Tampilkan konten tab yang aktif */
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
            <button class="icon-button" aria-label="Profil Pengguna" onclick="window.location.href='user.php'"><div class="profile-icon"><?php echo strtoupper(substr($username, 0, 1)); ?></div></button>
            <button class="icon-button" aria-label="Keluar Pengguna" id="userLogoutBtnHeader">
                <svg viewBox="0 0 24 24" fill="#5f5f5f" width="24px" height="24px"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"></path></svg>
            </button>
        </div>
    </header>

    <main>
        <div class="user-page-container">
            <div class="user-page-header">
                <h1>Profil Pengguna</h1>
                <button class="user-logout-btn" id="userLogoutBtn">Keluar</button>
            </div>

            <div id="message" style="display: none; margin-top: 15px; padding: 10px; border-radius: 5px; color: green; background-color: #e6ffe6; border: 1px solid green;"></div>

            <div class="user-profile-avatar">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            <div class="user-profile-info">
                <p>Selamat datang di profil Anda,</p>
                <strong><?php echo htmlspecialchars($username); ?></strong>
                <p>Status: <?php echo $isAdmin ? 'Administrator' : 'Pengguna Biasa'; ?></p>
            </div>

            <div class="user-profile-actions">
                <button onclick="showMessage('Fitur Edit Profil belum tersedia.', 'Fitur')" class="secondary">Edit Profil</button>
                <?php if ($isAdmin): ?>
                    <button onclick="window.location.href='admin.php'">Pergi ke Panel Admin</button>
                <?php endif; ?>
            </div>

            <div class="tab-navigation">
                <button class="tab-button active" data-tab="created">Dibuat</button>
                <button class="tab-button" data-tab="saved">Disimpan</button>
            </div>

            <div id="createdPinsContent" class="tab-content active user-section">
                <h2>Pin Dibuat</h2>
                <div class="pin-grid" id="createdPinsGrid">
                    <p style="text-align: center; color: #767676;">Memuat pin Anda...</p>
                </div>
                <div id="loading-indicator-created" style="display: none;">Memuat lebih banyak pin...</div>
            </div>

            <div id="savedPinsContent" class="tab-content user-section">
                <h2>Pin Disimpan</h2>
                <div class="pin-grid" id="savedPinsGrid">
                    <p style="text-align: center; color: #767676;">Memuat pin yang disimpan...</p>
                </div>
                <div id="loading-indicator-saved" style="display: none;">Memuat lebih banyak pin...</div>
            </div>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageDiv = document.getElementById('message');
            const userLogoutBtn = document.getElementById('userLogoutBtn');
            const userLogoutBtnHeader = document.getElementById('userLogoutBtnHeader');

            const tabButtons = document.querySelectorAll('.tab-button');
            const createdPinsContent = document.getElementById('createdPinsContent');
            const savedPinsContent = document.getElementById('savedPinsContent');
            const createdPinsGrid = document.getElementById('createdPinsGrid');
            const savedPinsGrid = document.getElementById('savedPinsGrid');
            const loadingIndicatorCreated = document.getElementById('loading-indicator-created');
            const loadingIndicatorSaved = document.getElementById('loading-indicator-saved');

            let pinsPerPage = 10;
            let loadedCreatedPinsCount = 0;
            let loadedSavedPinsCount = 0;
            let currentActiveTab = 'created'; // Default active tab

            const currentUsername = '<?php echo $username; ?>';

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
            window.showMessage = function(msg, isError = false) {
                messageDiv.textContent = msg;
                messageDiv.className = isError ? 'error' : '';
                messageDiv.style.display = 'block';
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
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
            userLogoutBtnHeader.addEventListener('click', handleUserLogout);

            // --- Fungsi untuk membuat elemen Pin ---
            function createPinElement(pinData) {
                const pinDiv = document.createElement('div');
                pinDiv.classList.add('pin');
                pinDiv.dataset.id = pinData.id;

                let imageUrl = 'https://placehold.co/236x300/e0e0e0/767676?text=Image+Not+Found';
                if (Array.isArray(pinData.images) && pinData.images.length > 0) {
                    imageUrl = pinData.images[0].url;
                } else if (typeof pinData.img === 'string' && pinData.img) {
                    imageUrl = pinData.img;
                }

                const img = document.createElement('img');
                img.src = imageUrl;
                img.alt = 'Pin Image';
                img.onerror = function() {
                    this.onerror=null;
                    this.src='https://placehold.co/236x300/e0e0e0/767676?text=Image+Not+Found';
                };

                pinDiv.appendChild(img);
                
                return pinDiv;
            }

            // --- Muat Pin Berdasarkan Tipe (Dibuat atau Disimpan) ---
            async function loadPins(type, count, append = true) {
                let targetGrid, loadingIndicator, loadedCountVar, endpoint;

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
                } else {
                    console.error('Tipe pin tidak valid:', type);
                    return;
                }

                loadingIndicator.style.display = 'block';
                try {
                    const response = await makeApiRequest(endpoint);
                    loadingIndicator.style.display = 'none';

                    if (response.success) {
                        const allPins = response.pins || [];

                        const startIndex = append ? loadedCountVar : 0;
                        const pinsToDisplay = allPins.slice(startIndex, startIndex + count);

                        if (!append) {
                            targetGrid.innerHTML = '';
                            if (type === 'created') loadedCreatedPinsCount = 0;
                            else loadedSavedPinsCount = 0;
                        }

                        const fragment = document.createDocumentFragment();
                        pinsToDisplay.forEach(pinData => {
                            fragment.appendChild(createPinElement(pinData));
                            if (type === 'created') loadedCreatedPinsCount++;
                            else loadedSavedPinsCount++;
                        });
                        targetGrid.appendChild(fragment);

                        if (allPins.length === 0) {
                            targetGrid.innerHTML = `<p style="text-align: center; color: #767676; margin-top: 50px;">Anda belum memiliki pin ${type === 'created' ? 'dibuat' : 'disimpan'} apa pun.</p>`;
                        } else if (pinsToDisplay.length === 0 && loadedCountVar > 0) {
                             // Semua pin sudah dimuat
                        }
                    } else {
                        showMessage(`Gagal memuat pin ${type === 'created' ? 'dibuat' : 'disimpan'}: ` + response.message, 'Error');
                        targetGrid.innerHTML = `<p style="text-align: center; color: #e60023; margin-top: 50px;">Kesalahan memuat pin. Silakan coba lagi.</p>`;
                    }
                } catch (error) {
                    showMessage(`Kesalahan memuat pin ${type === 'created' ? 'dibuat' : 'disimpan'}: ` + error.message, true);
                    targetGrid.innerHTML = `<p style="text-align: center; color: #e60023; margin-top: 50px;">Terjadi kesalahan. Silakan coba lagi nanti.</p>`;
                }
            }

            // --- Penanganan Pergantian Tab ---
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    const tab = this.dataset.tab;
                    currentActiveTab = tab; // Update active tab state

                    if (tab === 'created') {
                        createdPinsContent.classList.add('active');
                        savedPinsContent.classList.remove('active');
                        loadPins('created', pinsPerPage, false); // Muat ulang pin dibuat
                    } else if (tab === 'saved') {
                        savedPinsContent.classList.add('active');
                        createdPinsContent.classList.remove('active');
                        loadPins('saved', pinsPerPage, false); // Muat ulang pin disimpan
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
                }

                if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 400 && currentLoadingIndicator.style.display === 'none') {
                    loadPins(currentActiveTab, pinsPerPage, true);
                }
            });

            // Muat pin dibuat secara default saat halaman dimuat
            loadPins('created', pinsPerPage, false);
        });
    </script>
</body>
</html>
