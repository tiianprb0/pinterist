<?php
session_start();
require_once 'api/utils.php';

$searchQuery = $_GET['query'] ?? '';

$username = $_SESSION['username'] ?? null;
$isAdmin = $_SESSION['isAdmin'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search: <?php echo htmlspecialchars($searchQuery); ?> - Spicette</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap');

        main {
            padding-top: 20px;
        }
        .search-page-header {
            max-width: 1200px;
            margin: 0 auto 30px auto;
            padding: 0 20px;
        }
        .search-page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            color: #111;
            margin-bottom: 10px;
        }
        .search-page-header p {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px;
            color: #5f5f5f;
        }
        .back-button-container {
            max-width: 1200px;
            margin: 0 auto 20px auto;
            padding: 0 20px;
            text-align: left;
        }
        .back-button {
            background-color: #f0f0f0;
            color: #5f5f5f;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s ease;
        }
        .back-button:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>

    <!-- NEW: Global Loading Overlay -->
    <div id="globalLoadingOverlay">
        <div class="spinner"></div>
    </div>

    <main class="tag-page-layout">
        <div class="back-button-container">
            <button class="back-button" onclick="navigateBackInAppHistory()">
                <i class="fas fa-arrow-left"></i> Kembali
            </button>
        </div>
        <section class="search-page-header">
            <h1>Hasil pencarian untuk "<?php echo htmlspecialchars($searchQuery); ?>"</h1>
            <p id="searchResultsCount"></p>
        </section>
        <div class="pin-grid" id="pinGrid">
            <p style="text-align: center; color: #767676;">Memuat pin...</p>
        </div>
        <div id="loading-indicator">Memuat lebih banyak pin...</div>
    </main>

    <div id="customAlert" class="custom-alert"></div>

    <script>
        let currentUser = null;
        let currentSearchQuery = '<?php echo htmlspecialchars($searchQuery); ?>';
        let loadedPinsCount = 0;
        let pinsPerPage = 20;

        // NEW: Fungsi Global Loading Overlay
        const globalLoadingOverlay = document.getElementById('globalLoadingOverlay');

        function showGlobalLoading() {
            if (globalLoadingOverlay) {
                globalLoadingOverlay.classList.add('show');
                document.body.classList.add('no-scroll');
            }
        }

        function hideGlobalLoading() {
            if (globalLoadingOverlay) {
                globalLoadingOverlay.classList.remove('show');
                document.body.classList.remove('no-scroll');
            }
        }

        const API_BASE_URL = 'api/'; // Relatif terhadap search.php

        async function makeApiRequest(endpoint, method = 'GET', data = null) {
            try {
                const options = { method };
                if (data !== null && typeof data !== 'undefined') {
                    options.headers = { 'Content-Type': 'application/json' };
                    options.body = JSON.stringify(data);
                } else if (method === 'POST') {
                    delete options.headers;
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
                return { success: false, message: 'Kesalahan jaringan atau server.' };
            }
        }

        const customAlert = document.getElementById('customAlert');
        let alertTimeout;

        function showMessage(msg, type = 'info') {
            clearTimeout(alertTimeout);
            customAlert.textContent = msg;
            customAlert.className = 'custom-alert';
            if (type === 'error') {
                customAlert.classList.add('error');
            } else if (type === 'success') {
                customAlert.classList.add('success');
            }
            customAlert.classList.add('show');

            alertTimeout = setTimeout(() => {
                customAlert.classList.remove('show');
            }, 3000);
        }

        function getCorrectedImagePath(originalPath) {
            // Asumsi search.php berada di Spicette/
            // dan gambar ada di Spicette/uploads/pins/
            // Jadi, jika path gambar adalah ./uploads/pins/image.jpeg atau uploads/pins/image.jpeg
            // kita perlu mengubahnya menjadi uploads/pins/image.jpeg (relatif ke search.php)
            // dan kemudian pastikan URL absolut
            if (originalPath.startsWith('./uploads/pins/')) {
                return originalPath; // Path sudah benar relatif ke search.php
            }
            if (originalPath.startsWith('uploads/pins/')) {
                return './' + originalPath; // Tambahkan './' agar relatif ke search.php
            }
            // Jika ini adalah URL absolut atau path yang tidak dikenal, kembalikan apa adanya
            return originalPath;
        }

        const pinGrid = document.getElementById('pinGrid');
        const loadingIndicator = document.getElementById('loading-indicator');
        const searchResultsCountElement = document.getElementById('searchResultsCount');

        function createPinElement(pinData) {
            const pinDiv = document.createElement('div');
            pinDiv.classList.add('pin');
            pinDiv.dataset.id = pinData.id;

            const firstImageUrl = pinData.images && pinData.images.length > 0 ? getCorrectedImagePath(pinData.images[0].url) : 'https://placehold.co/250x350/cccccc/000000?text=No+Image';

            const img = document.createElement('img');
            img.src = firstImageUrl;
            img.alt = 'Gambar Pin';
            img.onerror = function() {
                this.onerror=null;
                this.src='https://placehold.co/250x350/cccccc/000000?text=Image+Error';
            };

            const overlayDiv = document.createElement('div');
            overlayDiv.classList.add('pin-overlay');
            
            const overlayTop = document.createElement('div');
            overlayTop.classList.add('pin-overlay-top');
            
            const saveButton = document.createElement('button');
            saveButton.classList.add('pin-save-button');
            
            // Logika simpan/unsave tombol ini akan ditangani oleh index.html
            // Untuk halaman ini, itu hanya placeholder visual atau pemicu pengalihan
            if (currentUser && currentUser.savedPins && currentUser.savedPins.includes(pinData.id)) {
                saveButton.textContent = 'Disimpan';
                saveButton.style.backgroundColor = '#767676';
            } else {
                saveButton.textContent = 'Simpan';
                saveButton.style.backgroundColor = '#e60023';
            }
            saveButton.onclick = (e) => {
                e.stopPropagation(); // Mencegah klik pin
                showMessage('Fungsi simpan ditangani di halaman utama.', 'info');
                // Bisa juga mengalihkan ke index.html dengan ID pin
                showGlobalLoading();
                window.location.href = `index.html?pin=${pinData.id}`;
            };
            overlayTop.appendChild(saveButton);

            if (pinData.images && pinData.images.length > 1) {
                const imageCountOverlay = document.createElement('div');
                imageCountOverlay.className = 'pin-image-count-overlay';
                imageCountOverlay.innerHTML = `
                    <i class="fas fa-camera"></i>
                    <span>${pinData.images.length}</span>
                `;
                overlayTop.appendChild(imageCountOverlay);
            }

            const overlayBottom = document.createElement('div');
            overlayBottom.classList.add('pin-overlay-bottom');

            const pinTitle = document.createElement('div');
            pinTitle.classList.add('pin-title');
            pinTitle.textContent = pinData.title || 'Tanpa Judul';
            overlayBottom.appendChild(pinTitle);

            const bottomActions = document.createElement('div');
            bottomActions.classList.add('pin-bottom-actions');

            const infoDiv = document.createElement('div');
            infoDiv.classList.add('pin-info');
            if (pinData.category) {
                 const link = document.createElement('a');
                 // Tautan ini juga akan mengalihkan ke index.html
                 link.href = `index.html?category=${encodeURIComponent(pinData.category.trim().toLowerCase().replace(/ /g, '-'))}`;
                 link.target = '_self'; // Buka di tab yang sama
                 link.textContent = pinData.category;
                 link.onclick = (e) => {
                    e.stopPropagation(); // Mencegah klik pin
                    showGlobalLoading(); // Tampilkan overlay loading
                 };
                 infoDiv.appendChild(link);
            } else {
                infoDiv.textContent = 'Tanpa Kategori';
                infoDiv.style.opacity = '0.7';
            }
            bottomActions.appendChild(infoDiv);
            
            overlayBottom.appendChild(bottomActions);
            
            overlayDiv.appendChild(overlayTop);
            overlayDiv.appendChild(overlayBottom);

            pinDiv.appendChild(img);
            pinDiv.appendChild(overlayDiv);
            
            // Dimodifikasi: Arahkan ke index.html dengan ID pin saat diklik
            pinDiv.onclick = () => {
                showGlobalLoading(); // Tampilkan overlay loading
                window.location.href = `index.html?pin=${pinData.id}`;
            };
            
            return pinDiv;
        }

        async function loadPins(count, append = true) {
            loadingIndicator.style.display = 'block';
            const response = await makeApiRequest(`pins.php?action=search&query=${encodeURIComponent(currentSearchQuery)}`);
            loadingIndicator.style.display = 'none';

            if (response.success) {
                const pinsToDisplay = response.pins || [];

                if (pinsToDisplay.length > 0) {
                    searchResultsCountElement.textContent = `Ditemukan ${pinsToDisplay.length} pin.`;
                } else {
                    searchResultsCountElement.textContent = `Tidak ada pin ditemukan.`;
                }

                const startIndex = append ? loadedPinsCount : 0;
                const slicedPinsToDisplay = pinsToDisplay.slice(startIndex, startIndex + count);

                if (!append) {
                    pinGrid.innerHTML = '';
                    loadedPinsCount = 0;
                }

                const fragment = document.createDocumentFragment();
                slicedPinsToDisplay.forEach(pinData => {
                    const pinElement = createPinElement(pinData);
                    fragment.appendChild(pinElement);
                    loadedPinsCount++;
                });
                pinGrid.appendChild(fragment);

                if (pinsToDisplay.length === 0) {
                    pinGrid.innerHTML = `<p style="text-align: center; color: #767676; margin-top: 50px;">Tidak ada pin ditemukan untuk "${currentSearchQuery}".</p>`;
                }
            } else {
                showMessage('Gagal memuat pin: ' + response.message, 'error');
                pinGrid.innerHTML = '<p style="text-align: center; color: #e60023; margin-top: 50px;">Error memuat pin. Silakan coba lagi nanti.</p>';
            }
        }

        // Fungsi navigateBackInAppHistory yang disederhanakan
        function navigateBackInAppHistory() {
            // Gunakan history.back() untuk kembali ke halaman sebelumnya
            history.back();
        }

        document.addEventListener('DOMContentLoaded', async function() {
            const response = await makeApiRequest('auth.php?action=check_session');
            if (response.success && response.user) {
                currentUser = response.user;
                const savedResponse = await makeApiRequest(`pins.php?action=fetch_saved`);
                if (savedResponse.success) {
                    currentUser.savedPins = savedResponse.pins.map(pin => pin.id);
                } else {
                    currentUser.savedPins = [];
                }
            } else {
                currentUser = null;
            }
            await loadPins(pinsPerPage, false);
            hideGlobalLoading(); // Sembunyikan loading setelah pin dimuat
        });

        window.addEventListener('scroll', () => {
            if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 400 && loadingIndicator.style.display === 'none') {
                loadPins(pinsPerPage, true);
            }
        });
    </script>
</body>
</html>
