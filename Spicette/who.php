<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
// ATAU SETELAH TAG PENUTUP PHP DI AKHIR FILE.
// Karakter tak terlihat atau spasi/baris baru di luar tag PHP dapat menyebabkan SyntaxError di JavaScript.
session_start();
require_once 'api/utils.php';

$personName = $_GET['name'] ?? '';

$username = $_SESSION['username'] ?? null;
$isAdmin = $_SESSION['isAdmin'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Person: <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $personName))); ?> - Spicette</title>
    <!-- Perbaikan path CSS: Menggunakan style.css karena who.php diasumsikan berada di direktori yang sama dengan style.css -->
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap');

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
        /* Gaya untuk pin grid utama */
        .tag-page-layout .pin-grid {
            display: column !important; /* Memastikan layout masonry */
            padding-left: 10px; /* Padding kiri 0px */
            padding-right: 10px; /* Padding kanan 0px */
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
        /* NEW: Global Loading Overlay */
        #globalLoadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        #globalLoadingOverlay.show {
            opacity: 1;
            visibility: visible;
        }

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: #e60023;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        body.no-scroll {
            overflow: hidden;
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
    </style>
</head>
<body>

    <!-- NEW: Global Loading Overlay -->
    <div id="globalLoadingOverlay">
        <div class="spinner"></div>
    </div>

    <div id="notificationOverlay">
        <div class="overlay-header">
            <h2>Notifikasi</h2>
            <button class="icon-button" aria-label="Tutup Notifikasi" id="closeNotificationButton">
                <i class="fas fa-times"></i> </button>
        </div>
        <div class="notification-page-container">
            <div class="notification-list" id="notificationListContainer">
                <p style="text-align: center; color: #767676;">Tidak ada notifikasi.</p>
            </div>
        </div>
    </div>

    <div id="searchOverlay">
        <div class="overlay-header search-overlay-header">
            <div class="search-container">
                <div class="search-icon-wrapper">
                    <i class="fas fa-search"></i> <input type="search" placeholder="Cari ide" id="mobileSearchInputOverlay">
                </div>
            </div>
            <button id="closeSearchOverlay">Batal</button>
        </div>
        <div class="overlay-content search-overlay-content">
            <div id="mobileSearchHistory"><h3>Riwayat Pencarian</h3><ul id="mobileSearchHistoryList" class="search-history-list"></ul></div>
            <ul id="searchSuggestions"></ul>
            <div id="searchResults" class="pin-grid" style="display: none;"></div>
            <div id="searchLoadingIndicator" style="text-align: center; padding: 20px; font-style: italic; color: #767676; display: none;">Mencari...</div>
            <h3 id="categoryExploreTitle">Jelajahi Kategori</h3>
            <div class="category-grid" id="categoryGridMobile"></div>
        </div>
    </div>

    <div id="mobileProfileDropdown">
        <span class="username-display" id="mobileDropdownUsernameDisplay"></span>
        <button class="secondary" id="mobileDropdownMyProfile" style="display: none;">Profil Saya</button>
        <button class="secondary" id="mobileDropdownAdminPanel" style="display: none;">Panel Admin</button>
        <button class="secondary" id="mobileDropdownLogout">Keluar</button>
        <button class="secondary" id="mobileDropdownClose">Tutup</button>
    </div>

    <main class="tag-page-layout">
        <div class="back-button-container">
            <button class="back-button" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Kembali
            </button>
        </div>
        <section class="tag-page-header">
            <h1>Pin tentang "<?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $personName))); ?>"</h1>
            <p>Jelajahi ide-ide terkait orang ini.</p>
        </section>
        <div class="pin-grid" id="pinGrid">
            <p style="text-align: center; color: #767676;">Memuat pin...</p>
        </div>
        <div id="loading-indicator">Memuat lebih banyak pin...</div>
    </main>

    <div id="customAlert" class="custom-alert"></div>

    <script>
        let currentUser = null;
        // Fix: Use json_encode to properly escape the PHP variable for JavaScript.
        // Ensure that the value passed to json_encode is always explicitly cast to a string
        // and trim any potential whitespace from the PHP side before encoding.
        // This line is at the root of the "Unexpected token ')'" error.
        let currentPersonName = <?php echo json_encode(trim((string)str_replace('-', ' ', $personName))); ?>; 
        let allPinsData = [];
        const NOTIFICATION_LS_KEY_READ_STATUS = 'spicette_notifications_read';
        let searchSuggestionsData = [];
        let selectedSuggestionIndex = -1;

        const SEARCH_HISTORY_LS_KEY = 'spicette_search_history';
        const MAX_SEARCH_HISTORY = 5;
        let searchHistory = [];

        // Mengubah API_BASE_URL menjadi path absolut dari root domain
        const API_BASE_URL = '/Spicette/api/'; 

        // NEW: Global Loading Overlay Functions
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
                    throw new Error(`HTTP error! Status: ${response.status} - ${errorText}`);
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

        // Modified: Use display_url directly from pinData
        function getCorrectedImagePath(pinImageObject) {
            // pinImageObject should already contain the correct display_url from the backend
            if (pinImageObject && pinImageObject.display_url) {
                // Ensure the path is relative to the web root if it starts with './'
                if (pinImageObject.display_url.startsWith('./')) {
                    return pinImageObject.display_url;
                }
                // If it's just 'uploads/pins/...' then add './'
                if (pinImageObject.display_url.startsWith('uploads/')) {
                    return './' + pinImageObject.display_url;
                }
                return pinImageObject.display_url; // Return as is if it's an absolute URL or already correctly relative
            }
            return 'https://placehold.co/250x350/cccccc/000000?text=No+Image';
        }

        const pinGrid = document.getElementById('pinGrid');
        const loadingIndicator = document.getElementById('loading-indicator');
        let pinsPerPage = 20; 
        let loadedPinsCount = 0; 

        function createPinElement(pinData) {
            const pinDiv = document.createElement('div');
            pinDiv.classList.add('pin');
            pinDiv.dataset.id = pinData.id;

            // Use display_url directly from the first image object
            const firstImage = pinData.images && pinData.images.length > 0 ? pinData.images[0] : null;
            const imageUrlToDisplay = getCorrectedImagePath(firstImage);

            const img = document.createElement('img');
            img.src = imageUrlToDisplay;
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
            
            if (currentUser && currentUser.savedPins && currentUser.savedPins.includes(pinData.id)) {
                saveButton.textContent = 'Disimpan';
                saveButton.style.backgroundColor = '#767676';
            } else {
                saveButton.textContent = 'Simpan';
                saveButton.style.backgroundColor = '#e60023';
            }
            
            saveButton.onclick = async (e) => { 
                e.stopPropagation(); 
                showMessage('Fungsi simpan ditangani di halaman utama.', 'info');
                showGlobalLoading();
                window.location.href = `/Spicette/index.html?pin=${pinData.id}`; // Redirect to index.html for saving
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
            if (pinData.categories && Array.isArray(pinData.categories) && pinData.categories.length > 0) { 
                 const link = document.createElement('a');
                 link.href = `/Spicette/tag/${encodeURIComponent(pinData.categories[0].trim().toLowerCase().replace(/ /g, '-'))}`; // Updated path
                 link.target = '_self'; // Open in same tab
                 link.textContent = pinData.categories[0];
                 link.onclick = (e) => {
                    e.stopPropagation(); // Prevent pin click
                    showGlobalLoading(); // Show loading overlay
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
                pinDiv.onclick = (e) => {
                    e.stopPropagation(); // Prevent opening pin detail
                    window.location.href = '/Spicette/chat'; // Redirect to chat page
                };
            } else {
                // Modified: Redirect to index.html with pin ID on click (only if not blurred)
                pinDiv.onclick = () => {
                    showGlobalLoading(); // Show loading overlay
                    window.location.href = `/Spicette/index.html?pin=${pinData.id}`;
                };
            }
            
            return pinDiv;
        }

        async function loadPins(count, append = true) {
            loadingIndicator.style.display = 'block';
            const response = await makeApiRequest(`pins.php?action=getPinsByPersonTag&name=${encodeURIComponent(currentPersonName)}`); // Corrected endpoint
            loadingIndicator.style.display = 'none';

            if (response.success) {
                allPinsData = response.pins || []; 

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

                if (slicedPinsToDisplay.length > 0 || allPinsData.length > 0) { // Check allPinsData to see if any pins exist
                    pinGrid.style.removeProperty('display');
                    pinGrid.style.display = 'column';
                }

                if (allPinsData.length === 0) { // Check allPinsData for overall empty state
                    pinGrid.innerHTML = `<p style="text-align: center; color: #767676; margin-top: 50px;">Tidak ada pin ditemukan untuk "${currentPersonName}".</p>`;
                }
            } else {
                showMessage('Gagal memuat pin: ' + response.message, 'error');
                pinGrid.innerHTML = '<p style="text-align: center; color: #e60023; margin-top: 50px;">Error memuat pin. Silakan coba lagi nanti.</p>';
            }
        }

        function loadSearchHistory() {
            const historyJson = localStorage.getItem(SEARCH_HISTORY_LS_KEY);
            if (historyJson) {
                searchHistory = JSON.parse(historyJson);
            } else {
                searchHistory = [];
            }
        }

        function saveSearchHistory() {
            localStorage.setItem(SEARCH_HISTORY_LS_KEY, JSON.stringify(searchHistory));
        }

        function addSearchToHistory(query) {
            query = query.toLowerCase().trim();
            if (!query) return;

            searchHistory = searchHistory.filter(item => item !== query);
            searchHistory.unshift(query);
            if (searchHistory.length > MAX_SEARCH_HISTORY) {
                searchHistory = searchHistory.slice(0, MAX_SEARCH_HISTORY);
            }
            saveSearchHistory();
            renderSearchHistory();
        }

        const desktopSearchHistoryContainer = document.getElementById('desktopSearchHistory');
        const desktopSearchHistoryList = document.getElementById('desktopSearchHistoryList');
        const mobileSearchHistoryContainer = document.getElementById('mobileSearchHistory');
        const mobileSearchHistoryList = document.getElementById('mobileSearchHistoryList');

        function renderSearchHistory() {
            if (desktopSearchHistoryList) { 
                desktopSearchHistoryList.innerHTML = '';
            }
            if (mobileSearchHistoryList) { 
                mobileSearchHistoryList.innerHTML = '';
            }

            if (searchHistory.length === 0) {
                if (desktopSearchHistoryContainer) desktopSearchHistoryContainer.style.display = 'none';
                if (mobileSearchHistoryContainer) mobileSearchHistoryContainer.style.display = 'none';
                return;
            }

            if (desktopSearchHistoryContainer) desktopSearchHistoryContainer.style.display = 'block';
            if (mobileSearchHistoryContainer) mobileSearchHistoryContainer.style.display = 'block';

            searchHistory.forEach(historyItem => {
                if (desktopSearchHistoryList) {
                    const desktopLi = document.createElement('li');
                    desktopLi.textContent = historyItem;
                    desktopLi.addEventListener('click', () => {
                        window.location.href = `/Spicette/search?query=${encodeURIComponent(historyItem)}`;
                    });
                    desktopSearchHistoryList.appendChild(desktopLi);
                }

                if (mobileSearchHistoryList) {
                    const mobileLi = document.createElement('li');
                    mobileLi.textContent = historyItem;
                    mobileLi.addEventListener('click', () => {
                        window.location.href = `/Spicette/search?query=${encodeURIComponent(historyItem)}`;
                    });
                    mobileSearchHistoryList.appendChild(mobileLi);
                }
            });
        }

        const mobileSearchInputOverlay = document.getElementById('mobileSearchInputOverlay'); 
        const desktopSearchInput = document.getElementById('desktopSearchInput'); 
        const searchSuggestionsListMobile = document.getElementById('searchSuggestions');
        const searchSuggestionsListDesktop = document.getElementById('desktopSearchSuggestions'); 
        const categoryExploreTitle = document.getElementById('categoryExploreTitle');
        const categoryGridMobile = document.getElementById('categoryGridMobile');

        let searchTimeout;

        function showSuggestions(inputElement, suggestionsList, query) {
            suggestionsList.innerHTML = '';
            suggestionsList.style.display = 'none'; 

            if (query.length < 2) { 
                categoryExploreTitle.style.display = 'block';
                categoryGridMobile.style.display = 'grid';
                renderSearchHistory(); 
                return;
            }
            
            if (inputElement && inputElement.id === 'desktopSearchInput' && desktopSearchHistoryContainer) { 
                desktopSearchHistoryContainer.style.display = 'none';
            } else if (mobileSearchHistoryContainer) { 
                mobileSearchHistoryContainer.style.display = 'none';
            }

            const filteredSuggestions = allPinsData.filter(pin => 
                (pin.title && pin.title.toLowerCase().includes(query.toLowerCase())) ||
                (pin.description && pin.description.toLowerCase().includes(query.toLowerCase())) || 
                (pin.categories && pin.categories.some(cat => cat.toLowerCase().includes(query.toLowerCase()))) || // Changed to categories array
                (pin.images && pin.images.some(img => img.description && img.description.toLowerCase().includes(query.toLowerCase()))) 
            ).map(pin => pin.title || (pin.categories && pin.categories.length > 0 ? pin.categories[0] : '')).filter(Boolean).slice(0, 5); 

            const uniqueSuggestions = [...new Set(filteredSuggestions)];

            if (uniqueSuggestions.length > 0) {
                uniqueSuggestions.forEach(suggestion => {
                    const listItem = document.createElement('li');
                    listItem.textContent = suggestion;
                    listItem.addEventListener('click', () => {
                        window.location.href = `/Spicette/search?query=${encodeURIComponent(suggestion)}`;
                    });
                    suggestionsList.appendChild(listItem);
                });
                suggestionsList.style.display = 'block';
                categoryExploreTitle.style.display = 'none';
                categoryGridMobile.style.display = 'none';
            } else {
                suggestionsList.style.display = 'none';
                categoryExploreTitle.style.display = 'block'; 
                categoryGridMobile.style.display = 'grid';
            }
        }

        async function performSearch(query) {
            window.location.href = `/Spicette/search?query=${encodeURIComponent(query)}`;
        }

        mobileSearchInputOverlay.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            searchTimeout = setTimeout(() => {
                showSuggestions(mobileSearchInputOverlay, searchSuggestionsListMobile, query);
            }, 300);
        });

        mobileSearchInputOverlay.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault(); 
                const query = mobileSearchInputOverlay.value.trim();
                if (query) {
                    performSearch(query); 
                    closeSearch(); 
                }
            }
        });

        if (desktopSearchInput) { 
            desktopSearchInput.addEventListener('focus', () => {
                mainElement.classList.add('desktop-search-active');
                pinGrid.style.display = 'none'; 
                loadingIndicator.style.display = 'none';
                desktopSearchInput.value = ''; // Clear input on focus for a new search
                renderSearchHistory(); 
                if (searchSuggestionsListDesktop) searchSuggestionsListDesktop.style.display = 'none';
            });

            desktopSearchInput.addEventListener('blur', (event) => {
                setTimeout(() => {
                    const searchArea = document.querySelector('header .search-container');
                    const isClickInsideDesktopSearchUI = searchArea && (searchArea.contains(event.relatedTarget) ||
                                                 (searchSuggestionsListDesktop && searchSuggestionsListDesktop.contains(event.relatedTarget)) ||
                                                 (desktopSearchHistoryContainer && desktopSearchHistoryContainer.contains(event.relatedTarget)));
                    
                    if (!isClickInsideDesktopSearchUI) {
                        resetMainContentToHome();
                    }
                }, 100); 
            });

            desktopSearchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                currentSearchQuery = query; 
                searchTimeout = setTimeout(() => {
                    showSuggestions(desktopSearchInput, searchSuggestionsListDesktop, query);
                }, 300);
            });

            desktopSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault(); 
                    const query = desktopSearchInput.value.trim();
                    if (query) {
                        addSearchToHistory(query);
                        window.location.href = `/Spicette/search?query=${encodeURIComponent(query)}`; 
                    }
                }
            });
        }
        
        async function resetMainContentToHome() {
            if (desktopSearchInput) desktopSearchInput.value = ''; 
            currentSearchQuery = ''; 
            if (searchSuggestionsListDesktop) searchSuggestionsListDesktop.style.display = 'none';
            if (desktopSearchHistoryContainer) desktopSearchHistoryContainer.style.display = 'none';
            loadedPinsCount = 0; 
            mainElement.classList.remove('desktop-search-active'); 
            
            pinGrid.style.removeProperty('display'); 
            pinGrid.style.display = 'column'; 
            
            await loadPins(pinsPerPage, false); 
        }

        document.addEventListener('click', (event) => {
            const headerSearchContainer = document.querySelector('header .search-container');
            const isClickInsideDesktopSearchUI = headerSearchContainer && (headerSearchContainer.contains(event.target) ||
                                                 (searchSuggestionsListDesktop && searchSuggestionsListDesktop.contains(event.target)) ||
                                                 (desktopSearchHistoryContainer && desktopSearchHistoryContainer.contains(event.target)));
        
            if (desktopSearchInput && !desktopSearchInput.matches(':focus') && !isClickInsideDesktopSearchUI) {
                resetMainContentToHome();
            }

            const isClickInsideMobileSearchOverlay = searchOverlay.contains(event.target);
            const mobileSearchTriggerButton = document.querySelector('.mobile-nav-item[data-action="search"]'); 
            const isMobileSearchTriggerButtonClick = mobileSearchTriggerButton && mobileSearchTriggerButton.contains(event.target);
            
            if (searchOverlay.style.display === 'flex' && !isClickInsideMobileSearchOverlay && !isMobileSearchTriggerButtonClick) {
                closeSearch();
            }
        });

        async function loadCategories() {
            const categoryContainers = [
                document.getElementById('categoryGridMobile'),
                document.getElementById('categoryGridDesktop') 
            ];

            try {
                const response = await makeApiRequest('categories.php?action=fetch_all');
                if (response.success && response.categories) {
                    const categories = response.categories; 
                    const filteredCategories = categories.filter(cat => cat.name && cat.name.trim() !== '');

                    categoryContainers.forEach(container => {
                        if (!container) return; 
                        container.innerHTML = ''; 
                        const fragment = document.createDocumentFragment();

                        filteredCategories.forEach((cat) => {
                            const catItem = document.createElement('div');
                            catItem.className = 'category-item';
                            
                            const imageUrl = cat.imageUrl || `https://picsum.photos/200/200?random=${Math.random()}`; 
                            catItem.style.backgroundImage = `url('${imageUrl}')`;

                            const catTitle = document.createElement('span');
                            catTitle.textContent = cat.name; 
                            
                            catItem.appendChild(catTitle);
                            
                            catItem.onclick = function() {
                                const categoryPermalink = cat.name.trim().toLowerCase().replace(/ /g, '-');
                                window.location.href = `/Spicette/tag/${encodeURIComponent(categoryPermalink)}`;
                            };
                            fragment.appendChild(catItem);
                        });
                        container.appendChild(fragment);
                    });
                } else {
                    console.error('Gagal memuat kategori:', response.message);
                    categoryContainers.forEach(container => {
                        if (container) container.innerHTML = '<p style="text-align: center; color: #767676;">Gagal memuat kategori.</p>';
                    });
                }
            } catch (error) {
                console.error('Error mengambil kategori:', error);
                categoryContainers.forEach(container => {
                    if (container) container.innerHTML = '<p style="text-align: center; color: #767676;">Kesalahan jaringan saat memuat kategori.</p>';
                });
            }
        }
        
        loadCategories();

        window.addEventListener('scroll', () => {
            if (loadingIndicator.style.display === 'none' && 
                searchOverlay.style.display === 'none' && 
                mobileProfileDropdown.style.display === 'none' &&
                notificationOverlay.style.display === 'none' 
            ) {
                if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 400) {
                    loadPins(pinsPerPage, true); // Always load more pins on scroll in this page
                }
            }
        });
        
        const headerNavButtons = document.querySelectorAll('.header-nav-links .nav-button');
        const desktopCreateButton = document.getElementById('desktopCreateButton');

        if (desktopCreateButton) { 
            desktopCreateButton.addEventListener('click', (e) => {
                e.preventDefault();
                if (!currentUser) {
                    window.location.href = '/Spicette/login.html'; 
                } else if (!['Naughty', 'Sinful'].includes(currentUser.level)) { // Check user level for upload permission
                    showMessage('Anda tidak memiliki izin untuk membuat pin. Hanya pengguna Naughty dan Sinful yang diizinkan.', 'error');
                } else {
                    window.location.href = '/Spicette/create.html'; 
                }
            });
        }

        headerNavButtons.forEach(button => {
            if (button.id === 'desktopCreateButton') return; 

            button.addEventListener('click', async function() {
                headerNavButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                const navAction = this.dataset.nav;
                // currentSearchQuery = ''; // Do not clear search query for this page
                
                desktopSearchHistoryContainer.style.display = 'none';
                searchSuggestionsListDesktop.style.display = 'none';
                mainElement.classList.remove('desktop-search-active'); 

                pinGrid.style.display = 'column'; 


                if (navAction === 'home') {
                    window.location.href = '/Spicette/'; // Redirect to home
                } else if (navAction === 'saved') {
                    if (!currentUser) {
                        showMessage('Harap login untuk melihat pin yang Anda simpan.', 'error');
                        headerNavButtons.forEach(btn => btn.classList.remove('active'));
                        document.querySelector('.nav-button[data-nav="home"]').classList.add('active');
                        window.location.href = '/Spicette/login.html'; 
                        return;
                    }
                    window.location.href = '/Spicette/?view=saved'; // Redirect to index.html saved tab
                }
            });
        });

        const searchOverlay = document.getElementById('searchOverlay');
        const closeSearchBtn = document.getElementById('closeSearchOverlay');
        
        const openSearch = () => {
            searchOverlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            mobileSearchInputOverlay.value = '';
            searchSuggestionsListMobile.innerHTML = '';
            searchSuggestionsListMobile.style.display = 'none';
            categoryExploreTitle.style.display = 'block';
            categoryGridMobile.style.display = 'grid';
            renderSearchHistory(); 
        };
        
        const closeSearch = () => {
            searchOverlay.style.display = 'none';
            document.body.style.overflow = '';
        };

        closeSearchBtn.addEventListener('click', closeSearch);

        const mobileNavItems = document.querySelectorAll('.mobile-bottom-nav .mobile-nav-item');
        mobileNavItems.forEach(item => {
            item.addEventListener('click', async function() {
                const action = this.dataset.action;

                closeNotificationPage();
                closeSearch(); 
                
                if (action === 'search') {
                    openSearch();
                } else if (action === 'create-pin') { 
                     if (!currentUser) {
                        window.location.href = '/Spicette/login.html'; 
                        return;
                    } else if (!['Naughty', 'Sinful'].includes(currentUser.level)) { // Check user level for upload permission
                        showMessage('Anda tidak memiliki izin untuk membuat pin. Hanya pengguna Naughty dan Sinful yang diizinkan.', 'error');
                        return;
                    }
                    window.location.href = '/Spicette/create.html'; 
                }
                else if (action === 'profile') { 
                    if (!currentUser) {
                        window.location.href = '/Spicette/login.html'; 
                        return;
                    }
                    if (currentUser.isAdmin) {
                        window.location.href = '/Spicette/admin'; 
                    } else {
                        window.location.href = '/Spicette/user'; 
                    }
                }
                else { 
                    mobileNavItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    if (action === 'home' || action === 'saved') { 
                        const desktopButton = document.querySelector(`.header-nav-links .nav-button[data-nav="${action}"]`);
                        if (desktopButton) {
                            headerNavButtons.forEach(btn => btn.classList.remove('active'));
                            desktopButton.classList.add('active');
                        }
                    }
                    // currentSearchQuery = ''; // Do not clear search query for this page
                    
                    mainElement.classList.remove('desktop-search-active');
                    pinGrid.style.display = 'column';


                    if (action === 'home') {
                        window.location.href = '/Spicette/'; // Redirect to home
                    } else if (action === 'saved') { 
                        if (!currentUser) {
                            window.location.href = '/Spicette/login.html'; 
                            mobileNavItems.forEach(i => i.classList.remove('active'));
                            document.querySelector('.mobile-nav-item[data-action="home"]').classList.add('active');
                            return;
                        }
                        window.location.href = '/Spicette/?view=saved'; // Redirect to index.html saved tab
                    }
                });
            });

        const profileButton = document.getElementById('profileButton');
        const profileIconLetter = document.getElementById('profileIconLetter');

        if (profileButton) { 
            profileButton.addEventListener('click', () => {
                if (!currentUser) {
                    window.location.href = '/Spicette/login.html'; 
                } else {
                    if (currentUser.isAdmin) {
                        window.location.href = '/Spicette/admin'; 
                    } else {
                        window.location.href = '/Spicette/user'; 
                    }
                }
            });
        }

        function updateProfileDisplay() {
            const mobileProfileIcon = document.querySelector('.mobile-nav-item[data-action="profile"] .profile-icon');
            if (currentUser) {
                if (profileIconLetter) { 
                    profileIconLetter.textContent = currentUser.username.charAt(0).toUpperCase();
                    profileIconLetter.style.backgroundColor = '#e60023';
                    profileIconLetter.style.color = 'white';
                }

                if (mobileProfileIcon) {
                    mobileProfileIcon.textContent = currentUser.username.charAt(0).toUpperCase();
                    mobileProfileIcon.style.backgroundColor = '#fff';
                    mobileProfileIcon.style.color = '#111';
                }
            } else {
                if (profileIconLetter) { 
                    profileIconLetter.textContent = 'G';
                    profileIconLetter.style.backgroundColor = '#ddd';
                    profileIconLetter.style.color = '#767676';
                }

                if (mobileProfileIcon) {
                    mobileProfileIcon.textContent = 'G';
                    mobileProfileIcon.style.backgroundColor = '#e0e0e0';
                    mobileProfileIcon.style.color = '#333';
                }
            }
        }

        const notificationButton = document.getElementById('notificationButton');
        const mobileNotificationButton = document.getElementById('mobileNotificationButton');
        const notificationBadge = document.getElementById('notificationBadge');
        const mobileNotificationBadge = document.getElementById('mobileNotificationBadge');

        async function updateNotificationBadge() {
            try {
                const response = await makeApiRequest('notifications.php?action=fetch_all');
                if (response.success) {
                    const notifications = response.notifications || [];
                    const unreadCount = notifications.filter(notif => !notif.read).length; 
                    
                    if (unreadCount > 0) {
                        if (notificationBadge) { notificationBadge.textContent = unreadCount; notificationBadge.style.display = 'flex'; }
                        if (mobileNotificationBadge) { mobileNotificationBadge.textContent = unreadCount; mobileNotificationBadge.style.display = 'flex'; }
                    } else {
                        if (notificationBadge) notificationBadge.style.display = 'none';
                        if (mobileNotificationBadge) mobileNotificationBadge.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Gagal memperbarui lencana notifikasi:', error);
            }
        }

        async function markAllNotificationsAsRead() {
            try {
                const response = await makeApiRequest('notifications.php?action=mark_as_read', 'POST', null); 
                if (response.success) {
                    updateNotificationBadge();
                }
            }
            catch (error) {
                console.error('Gagal menandai notifikasi sebagai sudah dibaca:', error);
            }
        }

        if (notificationButton) { 
            notificationButton.addEventListener('click', openNotificationPage);
        }
        if (mobileNotificationButton) { 
            mobileNotificationButton.addEventListener('click', openNotificationPage);
        }

        const moreAccountsButton = document.getElementById('moreAccountsButton');
        const moreAccountsDropdown = document.getElementById('moreAccountsDropdown');
        const dropdownUsernameDisplay = document.getElementById('dropdownUsernameDisplay');
        const dropdownMyProfile = document.getElementById('dropdownMyProfile');
        const dropdownAdminPanel = document.getElementById('dropdownAdminPanel');
        const dropdownLogout = document.getElementById('dropdownLogout');

        if (moreAccountsButton) { 
            function toggleDropdown() {
                if (moreAccountsDropdown) {
                    updateHeaderDropdown();
                    moreAccountsDropdown.style.display = moreAccountsDropdown.style.display === 'block' ? 'none' : 'block';
                }
            }

            function updateHeaderDropdown() {
                if (currentUser) {
                    if (dropdownUsernameDisplay) dropdownUsernameDisplay.textContent = `Masuk sebagai ${currentUser.username}`;
                    if (dropdownMyProfile) dropdownMyProfile.style.display = 'block';
                    if (dropdownAdminPanel) dropdownAdminPanel.style.display = currentUser.isAdmin ? 'block' : 'none';
                    if (dropdownLogout) dropdownLogout.style.display = 'block';
                } else {
                    if (dropdownUsernameDisplay) dropdownUsernameDisplay.textContent = 'Selamat datang, Tamu!';
                    if (dropdownMyProfile) dropdownMyProfile.style.display = 'none';
                    if (dropdownAdminPanel) dropdownAdminPanel.style.display = 'none';
                    if (dropdownLogout) dropdownLogout.style.display = 'none';
                }
            }

            moreAccountsButton.addEventListener('click', toggleDropdown);

            document.addEventListener('click', function(event) {
                if (moreAccountsButton && moreAccountsDropdown && !moreAccountsButton.contains(event.target) && !moreAccountsDropdown.contains(event.target)) {
                    moreAccountsDropdown.style.display = 'none';
                }
            });

            if (dropdownMyProfile) { 
                dropdownMyProfile.addEventListener('click', () => {
                    if (currentUser) {
                        window.location.href = '/Spicette/user';
                    }
                    if (moreAccountsDropdown) moreAccountsDropdown.style.display = 'none';
                });
            }

            if (dropdownAdminPanel) { 
                dropdownAdminPanel.addEventListener('click', () => {
                    if (currentUser && currentUser.isAdmin) {
                        window.location.href = '/Spicette/admin'; 
                    } else {
                        showMessage('Akses ditolak: Diperlukan hak administrator.', 'Error');
                    }
                    if (moreAccountsDropdown) moreAccountsDropdown.style.display = 'none';
                });
            }

            if (dropdownLogout) { 
                dropdownLogout.addEventListener('click', async () => {
                    const response = await makeApiRequest('auth.php?action=logout', 'POST', null); 
                    if (response.success) {
                        currentUser = null;
                        updateProfileDisplay();
                        updateHeaderDropdown();
                        showMessage('Logout berhasil.', 'Logout');
                        loadedPinsCount = 0;
                        // currentSearchQuery = ''; // Do not clear search query for this page
                        await loadPins(pinsPerPage, false);
                        const mobileNavItems = document.querySelectorAll('.mobile-bottom-nav .mobile-nav-item');
                        mobileNavItems.forEach(i => i.classList.remove('active'));
                        const homeMobileNav = document.querySelector('.mobile-nav-item[data-action="home"]');
                        if (homeMobileNav) homeMobileNav.classList.add('active'); 
                        
                        const headerNavButtons = document.querySelectorAll('.header-nav-links .nav-button');
                        headerNavButtons.forEach(btn => btn.classList.remove('active'));
                        const homeHeaderNav = document.querySelector('.nav-button[data-nav="home"]');
                        if (homeHeaderNav) homeHeaderNav.classList.add('active');
                    } else {
                        showMessage('Logout gagal: ' + response.message, 'Error');
                    }
                    if (moreAccountsDropdown) moreAccountsDropdown.style.display = 'none'; 
                });
            }
        } // End if (moreAccountsButton)

        // Mobile Profile Options (for mobile bottom nav profile icon)
        const mobileProfileDropdown = document.getElementById('mobileProfileDropdown');
        const mobileDropdownUsernameDisplay = document.getElementById('mobileDropdownUsernameDisplay');
        const mobileDropdownMyProfile = document.getElementById('mobileDropdownMyProfile');
        const mobileDropdownAdminPanel = document.getElementById('mobileDropdownAdminPanel');
        const mobileDropdownLogout = document.getElementById('mobileDropdownLogout');
        const mobileDropdownClose = document.getElementById('mobileDropdownClose');

        if (document.querySelector('.mobile-nav-item[data-action="profile"]')) { 
            document.querySelector('.mobile-nav-item[data-action="profile"]').addEventListener('click', () => {
                if (currentUser) {
                    if (mobileProfileDropdown) { 
                        mobileDropdownUsernameDisplay.textContent = `Masuk sebagai ${currentUser.username}`;
                        mobileDropdownMyProfile.style.display = 'block';
                        mobileDropdownAdminPanel.style.display = currentUser.isAdmin ? 'block' : 'none';
                        mobileProfileDropdown.style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    }
                } else {
                    window.location.href = '/Spicette/login.html'; 
                }
            });
        }
        
        if (mobileDropdownMyProfile) { 
            mobileDropdownMyProfile.addEventListener('click', () => {
                if (currentUser) {
                    window.location.href = '/Spicette/user'; 
                }
                if (mobileProfileDropdown) { mobileProfileDropdown.style.display = 'none'; }
                document.body.style.overflow = '';
            });
        }
        if (mobileDropdownAdminPanel) { 
            mobileDropdownAdminPanel.addEventListener('click', () => {
                if (currentUser && currentUser.isAdmin) {
                    window.location.href = '/Spicette/admin'; 
                } else {
                    showMessage('Akses ditolak: Diperlukan hak administrator.', 'Error');
                }
                if (mobileProfileDropdown) { mobileProfileDropdown.style.display = 'none'; }
                document.body.style.overflow = '';
            });
        }
        if (mobileDropdownLogout) { 
            mobileDropdownLogout.addEventListener('click', async () => {
                const response = await makeApiRequest('auth.php?action=logout', 'POST', null); 
                if (response.success) {
                    currentUser = null;
                    updateProfileDisplay();
                    showMessage('Logout berhasil.', 'Logout');
                    loadedPinsCount = 0;
                    // currentSearchQuery = ''; // Do not clear search query for this page
                    await loadPins(pinsPerPage, false);
                    const mobileNavItems = document.querySelectorAll('.mobile-bottom-nav .mobile-nav-item');
                    mobileNavItems.forEach(i => i.classList.remove('active'));
                    const homeMobileNav = document.querySelector('.mobile-nav-item[data-action="home"]');
                    if (homeMobileNav) homeMobileNav.classList.add('active');
                } else {
                    showMessage('Logout gagal: ' + response.message, 'Error');
                }
                if (mobileProfileDropdown) { mobileProfileDropdown.style.display = 'none'; }
                document.body.style.overflow = '';
            });
        }
        if (mobileDropdownClose) { 
            mobileDropdownClose.addEventListener('click', () => {
                if (mobileProfileDropdown) { mobileProfileDropdown.style.display = 'none'; }
                document.body.style.overflow = '';
            });
        }

        // --- Check Initial Session and Load ---
        document.addEventListener('DOMContentLoaded', async function initializeApp() {
            loadSearchHistory(); 
            const urlParams = new URLSearchParams(window.location.search);

            const response = await makeApiRequest('auth.php?action=check_session');
            if (response.success && response.user) {
                currentUser = response.user;
                if (typeof currentUser.canUpload === 'undefined') {
                    currentUser.canUpload = false; 
                }
                const savedResponse = await makeApiRequest(`pins.php?action=fetch_saved`);
                if (savedResponse.success) {
                    currentUser.savedPins = savedResponse.pins.map(pin => pin.id);
                } else {
                    currentUser.savedPins = [];
                }
            } else {
                currentUser = null;
            }
            updateProfileDisplay();
            updateNotificationBadge();
            
            const allPinsResponse = await makeApiRequest('pins.php?action=fetch_all');
            if (allPinsResponse.success) {
                allPinsData = allPinsResponse.pins; 
            } else {
                console.error("Gagal memuat semua pin saat inisialisasi:", allPinsResponse.message);
            }

            await loadPins(pinsPerPage, false);
            hideGlobalLoading(); // Sembunyikan loading setelah pin dimuat
        });
    </script>
</body>
</html>
