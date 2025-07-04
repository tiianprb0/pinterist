<?php
// ENSURE NO CHARAKTER APAPUN SEBELUM BARIS INI
session_start();
// Include API utility functions for consistency
require_once 'api/utils.php';

$categoryName = $_GET['category'] ?? ''; // Get category from URL query parameter

$username = $_SESSION['username'] ?? null;
$isAdmin = $_SESSION['isAdmin'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category: <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $categoryName))); ?> - Spicette</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Import Playfair Display font */
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap');

        /* Add any specific styles for the tag page here if needed */
        main {
            padding-top: 20px;
        }
        .tag-page-header {
            max-width: 1200px;
            margin: 0 auto 30px auto;
            padding: 0 20px;
        }
        .tag-page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            color: #111;
            margin-bottom: 10px;
        }
        .tag-page-header p {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px;
            color: #5f5f5f;
        }
        /* Ensure pin-grid on tag page does not get affected by desktop-search-active class in main */
        .tag-page-layout .pin-grid {
            display: column !important; /* Ensure masonry layout */
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
        <section class="tag-page-header">
            <h1>Pin tentang "<?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $categoryName))); ?>"</h1>
            <p>Jelajahi ide-ide terkait kategori ini.</p>
        </section>
        <div class="pin-grid" id="pinGrid">
            <p style="text-align: center; color: #767676;">Memuat pin...</p>
        </div>
        <div id="loading-indicator">Memuat lebih banyak pin...</div>
    </main>

    <div id="customAlert" class="custom-alert"></div>

    <script>
        // --- Global State (minimal for this page) ---
        let currentUser = null; // Akan diambil, tetapi tidak digunakan untuk logika detail pin di sini
        let currentSearchQuery = '<?php echo htmlspecialchars(str_replace('-', ' ', $categoryName)); ?>';
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

        // --- Titik Akhir API ---
        // API_BASE_URL yang dikoreksi untuk menunjuk ke direktori yang benar
        const API_BASE_URL = '../api/'; // Relatif terhadap tag.php, yang berada di subfolder


        // --- Fungsi Pembantu untuk Permintaan API ---
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

        // --- Fungsi untuk Menampilkan Pesan ---
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

        // Fungsi pembantu untuk mengoreksi path gambar
        function getCorrectedImagePath(originalPath) {
            // Asumsi tag.php berada di Spicette/ dan unggahan ada di Spicette/uploads/
            // Jadi, jika path adalah './uploads/pins/image.jpeg' atau 'uploads/pins/image.jpeg'
            // itu perlu menjadi '../uploads/pins/image.jpeg' relatif terhadap lokasi tag.php
            if (originalPath.startsWith('./uploads/pins/')) {
                return '../' + originalPath.substring(2); // Hapus './' dan tambahkan '../'
            }
            if (originalPath.startsWith('uploads/pins/')) {
                return '../' + originalPath; // Tambahkan '../'
            }
            // Jika sudah URL absolut atau relatif dengan benar, kembalikan apa adanya.
            return originalPath; 
        }

        // --- Memuat Pin ---
        const pinGrid = document.getElementById('pinGrid');
        const loadingIndicator = document.getElementById('loading-indicator');

        function createPinElement(pinData) {
            const pinDiv = document.createElement('div');
            pinDiv.classList.add('pin');
            pinDiv.dataset.id = pinData.id;

            const firstImageUrl = getCorrectedImagePath(pinData.images && pinData.images.length > 0 ? pinData.images[0].url : 'https://placehold.co/250x350/cccccc/000000?text=No+Image');

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
                window.location.href = `../index.html?pin=${pinData.id}`;
            };
            overlayTop.appendChild(saveButton);

            // Overlay jumlah gambar untuk pin dengan banyak gambar
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
            // Menggunakan category di pinData langsung
            if (pinData.category) {
                 const link = document.createElement('a');
                 // Tautan ini juga akan mengalihkan ke index.html
                 link.href = `../index.html?category=${encodeURIComponent(pinData.category.trim().toLowerCase().replace(/ /g, '-'))}`;
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
                window.location.href = `../index.html?pin=${pinData.id}`;
            };
            
            return pinDiv;
        }

        async function loadPins(count, append = true) {
            loadingIndicator.style.display = 'block';
            // Ambil pin berdasarkan nama kategori
            const response = await makeApiRequest(`pins.php?action=search&query=${encodeURIComponent(currentSearchQuery)}`);
            loadingIndicator.style.display = 'none';

            if (response.success) {
                const pinsToDisplay = response.pins || [];
                
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

                if (slicedPinsToDisplay.length > 0 || currentSearchQuery === '') {
                    pinGrid.style.removeProperty('display');
                    pinGrid.style.display = 'column';
                }


                if (slicedPinsToDisplay.length === 0 && loadedPinsCount === 0) {
                    pinGrid.innerHTML = `<p style="text-align: center; color: #767676; margin-top: 50px;">Tidak ada pin ditemukan untuk kategori "${currentSearchQuery}".</p>`;
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

        // --- Periksa Sesi Awal dan Muat ---
        document.addEventListener('DOMContentLoaded', async function initializeApp() {
            // Periksa sesi untuk menentukan apakah pengguna login (untuk visual tombol simpan)
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

        // --- Infinite Scroll ---
        window.addEventListener('scroll', () => {
            if (loadingIndicator.style.display === 'none') {
                if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 400) {
                    loadPins(pinsPerPage, true);
                }
            }
        });
    </script>
</body>
</html>
