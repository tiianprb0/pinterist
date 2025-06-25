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

            <div class="user-section">
                <h2>Pin Anda</h2>
                <div class="pin-grid" id="userPinsGrid">
                    <p style="text-align: center; color: #767676;">Memuat pin Anda...</p>
                </div>
                <div id="loading-indicator" style="display: none;">Memuat lebih banyak pin...</div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageDiv = document.getElementById('message');
            const userLogoutBtn = document.getElementById('userLogoutBtn');
            const userLogoutBtnHeader = document.getElementById('userLogoutBtnHeader');
            const userPinsGrid = document.getElementById('userPinsGrid');
            const loadingIndicator = document.getElementById('loading-indicator');
            let pinsPerPage = 10;
            let loadedPinsCount = 0;
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
                    const response = await makeApiRequest('auth.php?action=logout', 'POST', null); // Data is null
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

            // --- Muat Pin yang Diposting Pengguna ---
            function createPinElement(pinData) {
                const pinDiv = document.createElement('div');
                pinDiv.classList.add('pin');
                pinDiv.dataset.id = pinData.id;

                // --- Ambil URL Gambar ---
                let imageUrl = 'https://placehold.co/236x300/e0e0e0/767676?text=Image+Not+Found'; // Default placeholder
                if (Array.isArray(pinData.images) && pinData.images.length > 0) { // Struktur baru: 'images' array
                    imageUrl = pinData.images[0].url;
                } else if (typeof pinData.img === 'string' && pinData.img) { // Struktur lama: 'img' string
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

            async function loadUserPins(count, append = true) {
                loadingIndicator.style.display = 'block';
                try {
                    const response = await makeApiRequest(`pins.php?action=fetch_user_pins&username=${currentUsername}`);
                    loadingIndicator.style.display = 'none';

                    if (response.success) {
                        const allUserPins = response.pins || [];

                        const startIndex = append ? loadedPinsCount : 0;
                        const pinsToDisplay = allUserPins.slice(startIndex, startIndex + count);

                        if (!append) {
                            userPinsGrid.innerHTML = '';
                            loadedPinsCount = 0;
                        }

                        const fragment = document.createDocumentFragment();
                        pinsToDisplay.forEach(pinData => {
                            fragment.appendChild(createPinElement(pinData));
                            loadedPinsCount++;
                        });
                        userPinsGrid.appendChild(fragment);

                        if (allUserPins.length === 0) {
                            userPinsGrid.innerHTML = '<p style="text-align: center; color: #767676; margin-top: 50px;">Anda belum memposting pin apa pun.</p>';
                        } else if (pinsToDisplay.length === 0 && loadedPinsCount > 0) {
                             // All pins loaded
                        }
                    } else {
                        showMessage('Gagal memuat pin Anda: ' + response.message, 'Error');
                        userPinsGrid.innerHTML = '<p style="text-align: center; color: #e60023; margin-top: 50px;">Kesalahan memuat pin. Silakan coba lagi.</p>';
                    }
                } catch (error) {
                    showMessage('Kesalahan memuat pin pengguna: ' + error.message, true);
                    userPinsGrid.innerHTML = '<p style="text-align: center; color: #e60023; margin-top: 50px;">Terjadi kesalahan. Silakan coba lagi nanti.</p>';
                }
            }

            window.addEventListener('scroll', () => {
                if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 400 && loadingIndicator.style.display === 'none') {
                    loadUserPins(pinsPerPage, true);
                }
            });

            loadUserPins(pinsPerPage, false);
        });
    </script>
</body>
</html>
