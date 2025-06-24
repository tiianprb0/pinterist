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
            font-size: 20px;
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

    <div id="pinDetailOverlay">
        <div class="pin-detail-content">
            <div class="pin-detail-back-container">
                <button class="pin-detail-back-button" onclick="closePinDetail()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
            </div>

            <!-- Header info untuk pin detail -->
            <div class="pin-detail-header-info">
                <h2 id="pinDetailTitle"></h2>
                <div class="pin-detail-meta-row">
                    <p class="uploaded-by-text"><strong id="pinDetailUploadedBy"></strong></p>
                    <!-- NEW: Tombol share dipindahkan di sini -->
                    <button id="pinDetailShareButton" class="icon-button-small">
                        <i class="fas fa-share-alt"></i>
                    </button>
                </div>
            </div>

            <div class="pin-detail-img-main-container">
                <!-- Gambar pin akan dimuat di sini, bisa stacked atau slider -->
                <!-- Ini adalah container utama untuk gambar -->
                <!-- NEW: Tombol save dan download dipindahkan di sini -->
                <button id="pinDetailImageSaveButton" class="pin-save-button image-overlay-button">Save</button>
                <button id="pinDetailDownloadButton" class="pin-download-button image-overlay-button">
                    <i class="fas fa-download"></i>
                </button>
            </div>

            <p id="pinDetailDescription" class="pin-detail-description"></p>
            <div class="dashed-line"></div>
            <div id="pinDetailCategories" class="pin-categories"></div>

            <!-- OLD BUTTONS REMOVED FROM HERE -->
            <!-- <div class="pin-detail-actions">
                <button id="pinDetailShareButton" class="secondary">
                    <i class="fas fa-share-alt"></i> Share
                </button>
                <button id="pinDetailDownloadButton" class="secondary">
                    <i class="fas fa-download"></i> Download
                </button>
            </div> -->
        </div>
    </div>

    <div id="notificationOverlay">
        <div class="overlay-header">
            <h2>Notifications</h2>
            <button class="icon-button" aria-label="Close Notifications" id="closeNotificationButton">
                <i class="fas fa-times"></i> </button>
        </div>
        <div class="notification-page-container">
            <div class="notification-list" id="notificationListContainer">
                <p style="text-align: center; color: #767676;">No notifications.</p>
            </div>
        </div>
    </div>

    <div id="searchOverlay">
        <div class="overlay-header search-overlay-header">
            <div class="search-container">
                <div class="search-icon-wrapper">
                    <i class="fas fa-search"></i> <input type="search" placeholder="Search ideas" id="mobileSearchInputOverlay">
                </div>
            </div>
            <button id="closeSearchOverlay">Cancel</button>
        </div>
        <div class="overlay-content search-overlay-content">
            <div id="mobileSearchHistory"><h3>Search History</h3><ul id="mobileSearchHistoryList" class="search-history-list"></ul></div>
            <ul id="searchSuggestions"></ul>
            <div id="searchResults" class="pin-grid" style="display: none;"></div>
            <div id="searchLoadingIndicator" style="text-align: center; padding: 20px; font-style: italic; color: #767676; display: none;">Searching...</div>
            <h3 id="categoryExploreTitle">Explore Categories</h3>
            <div class="category-grid" id="categoryGridMobile"></div>
        </div>
    </div>

    <div id="mobileProfileDropdown">
        <span class="username-display" id="mobileDropdownUsernameDisplay"></span>
        <button class="secondary" id="mobileDropdownMyProfile" style="display: none;">My Profile</button>
        <button class="secondary" id="mobileDropdownAdminPanel" style="display: none;">Admin Panel</button>
        <button class="secondary" id="mobileDropdownLogout">Logout</button>
        <button class="secondary" id="mobileDropdownClose">Close</button>
    </div>

    <main class="tag-page-layout">
        <div class="back-button-container">
            <button class="back-button" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>
        <section class="tag-page-header">
            <h1>Pins about "<?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $categoryName))); ?>"</h1>
            <p>Explore ideas related to this category.</p>
        </section>
        <div class="pin-grid" id="pinGrid">
            <p style="text-align: center; color: #767676;">Loading pins...</p>
        </div>
        <div id="loading-indicator">Loading more pins...</div>
    </main>

    <div id="customAlert" class="custom-alert"></div>

    <script>
        // --- Global State ---
        let currentUser = null;
        let currentSearchQuery = '<?php echo htmlspecialchars(str_replace('-', ' ', $categoryName)); ?>';
        let allPinsData = [];
        const NOTIFICATION_LS_KEY_READ_STATUS = 'spicette_notifications_read';
        let searchSuggestionsData = [];
        let selectedSuggestionIndex = -1;

        // NEW: Search History variables
        const SEARCH_HISTORY_LS_KEY = 'spicette_search_history';
        const MAX_SEARCH_HISTORY = 5;
        let searchHistory = [];

        // --- API Endpoints ---
        const API_BASE_URL = '../api/';

        // --- Helper Function for API Requests ---
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
                console.error('API Request Failed:', error);
                return { success: false, message: 'Network or server error.' };
            }
        }

        // --- Function to Display Messages ---
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

        // --- Pin Detail Overlay Logic ---
        const pinDetailOverlay = document.getElementById('pinDetailOverlay');
        const pinDetailImageMainContainer = document.querySelector('.pin-detail-img-main-container');
        const pinDetailTitle = document.getElementById('pinDetailTitle');
        const pinDetailUploadedBy = document.getElementById('pinDetailUploadedBy');
        const pinDetailDescription = document.getElementById('pinDetailDescription');
        const pinDetailCategories = document.getElementById('pinDetailCategories');
        const pinDetailImageSaveButton = document.getElementById('pinDetailImageSaveButton');
        const pinDetailDownloadButton = document.getElementById('pinDetailDownloadButton');
        const pinDetailShareButton = document.getElementById('pinDetailShareButton');

        function updatePinDetailImageSaveButton(pinData) {
            if (currentUser && currentUser.savedPins && currentUser.savedPins.includes(pinData.id)) {
                pinDetailImageSaveButton.textContent = 'Saved';
                pinDetailImageSaveButton.style.backgroundColor = '#767676';
                pinDetailImageSaveButton.onclick = async (e) => {
                    e.stopPropagation();
                    const response = await makeApiRequest('pins.php?action=unsave', 'POST', { pinId: pinData.id });
                    if (response.success) {
                        showMessage('Pin successfully removed from saved list!', 'success');
                        currentUser.savedPins = currentUser.savedPins.filter(id => id !== pinData.id);
                        updatePinDetailImageSaveButton(pinData);
                        const gridSaveButton = document.querySelector(`.pin[data-id="${pinData.id}"] .pin-save-button`);
                        if (gridSaveButton) {
                            gridSaveButton.textContent = 'Save';
                            gridSaveButton.style.backgroundColor = '#e60023';
                        }
                    } else {
                        showMessage('Failed to remove pin from saved list: ' + response.message, 'error');
                    }
                };
            } else {
                pinDetailImageSaveButton.textContent = 'Save';
                pinDetailImageSaveButton.style.backgroundColor = '#e60023';
                pinDetailImageSaveButton.onclick = async (e) => {
                    e.stopPropagation();
                    if (!currentUser) {
                        showMessage('Please login to save pins.', 'info');
                        window.location.href = '/Spicette/login.html';
                        return;
                    }
                    const response = await makeApiRequest('pins.php?action=save', 'POST', { pinId: pinData.id });
                    if (response.success) {
                        showMessage('Pin saved successfully!', 'success');
                        if (currentUser.savedPins) {
                            currentUser.savedPins.push(pinData.id);
                        } else {
                            currentUser.savedPins = [pinData.id];
                        }
                        updatePinDetailImageSaveButton(pinData);
                        const gridSaveButton = document.querySelector(`.pin[data-id="${pinData.id}"] .pin-save-button`);
                        if (gridSaveButton) {
                            gridSaveButton.textContent = 'Saved';
                            gridSaveButton.style.backgroundColor = '#767676';
                        }
                    } else {
                        showMessage('Failed to save pin: ' + response.message, 'error');
                    }
                };
            }
        }


        function openPinDetail(pinData) {
            pinDetailTitle.textContent = pinData.title || 'Untitled';
            pinDetailUploadedBy.textContent = `oleh ${pinData.uploadedBy || 'Anonim'}`;

            // Mengelola deskripsi umum pin
            if (pinData.description) {
                pinDetailDescription.textContent = pinData.description;
                pinDetailDescription.style.display = 'block';
            } else {
                pinDetailDescription.textContent = '';
                pinDetailDescription.style.display = 'none';
            }
            
            // Mengosongkan dan mengisi container gambar utama
            pinDetailImageMainContainer.innerHTML = '';
            pinDetailImageMainContainer.appendChild(pinDetailImageSaveButton); // Pastikan tombol save selalu ada
            pinDetailImageMainContainer.appendChild(pinDetailDownloadButton); // NEW: Tombol download selalu ada di container gambar

            // Menentukan tampilan gambar berdasarkan display_type
            if (pinData.images && pinData.images.length > 0) {
                if (pinData.display_type === 'slider' && pinData.images.length > 1) {
                    // Tampilan Slider
                    const sliderWrapper = document.createElement('div');
                    sliderWrapper.className = 'slider-wrapper';
                    sliderWrapper.id = 'pinSlider'; // Memberikan ID untuk inisialisasi slider

                    pinData.images.forEach((image, index) => {
                        const slide = document.createElement('div');
                        slide.className = 'slider-slide';
                        const imgElement = document.createElement('img');
                        imgElement.src = image.url;
                        imgElement.alt = `${pinData.title} - ${index + 1}`;
                        imgElement.onerror = function() { 
                            this.onerror=null; 
                            this.src='https://placehold.co/500x700/cccccc/000000?text=Image+Load+Error';
                        };
                        slide.appendChild(imgElement);
                        sliderWrapper.appendChild(slide);
                    });

                    // Tambahkan navigasi slider
                    sliderWrapper.innerHTML += `
                        <button class="slider-button prev" onclick="moveSlide(-1, 'pinSlider')">&#10094;</button>
                        <button class="slider-button next" onclick="moveSlide(1, 'pinSlider')">&#10095;</button>
                        <div class="slider-dots"></div>
                    `;
                    pinDetailImageMainContainer.appendChild(sliderWrapper);
                    initializeSlider('pinSlider');

                } else {
                    // Tampilan Berjejer ke Bawah (Stacked) atau jika hanya 1 gambar
                    pinData.images.forEach(image => {
                        const imgDiv = document.createElement('div');
                        imgDiv.className = 'stacked-image-item';
                        const imgElement = document.createElement('img');
                        imgElement.src = image.url;
                        imgElement.alt = pinData.title;
                        imgElement.onerror = function() { 
                            this.onerror=null; 
                            this.src='https://placehold.co/500x700/cccccc/000000?text=Image+Load+Error';
                        };
                        imgDiv.appendChild(imgElement);

                        if (image.description) {
                            const descP = document.createElement('p');
                            descP.className = 'image-individual-description';
                            descP.textContent = image.description;
                            imgDiv.appendChild(descP);
                        }
                        pinDetailImageMainContainer.appendChild(imgDiv);
                    });
                }
            } else {
                 // Fallback jika tidak ada gambar (meskipun harus ada karena required di create)
                const noImage = document.createElement('img');
                noImage.src = 'https://placehold.co/500x700/cccccc/000000?text=No+Image';
                noImage.alt = 'Tidak Ada Gambar';
                pinDetailImageMainContainer.appendChild(noImage);
            }

            updatePinDetailImageSaveButton(pinData);

            // Mengelola kategori
            pinDetailCategories.innerHTML = '';
            if (pinData.category) {
                const span = document.createElement('span');
                span.classList.add('pin-category-tag');
                const categoryPermalink = pinData.category.trim().toLowerCase().replace(/ /g, '-');
                span.innerHTML = `<a href="/Spicette/tag/${encodeURIComponent(categoryPermalink)}" style="color: inherit; text-decoration: none;">#${pinData.category.trim()}</a>`;
                pinDetailCategories.appendChild(span);
                pinDetailCategories.style.display = 'flex';
            } else {
                pinDetailCategories.style.display = 'none';
            }

            // Share button logic
            if (navigator.share) {
                pinDetailShareButton.style.display = 'flex';
                pinDetailShareButton.onclick = async (e) => {
                    e.stopPropagation();
                    try {
                        await navigator.share({
                            title: pinData.title || 'Spicette Pin',
                            text: pinData.description || pinData.title || 'Check out this pin!',
                            url: window.location.href
                        });
                        showMessage('Pin berhasil dibagikan!', 'success');
                    } catch (error) {
                        console.error('Error sharing:', error);
                        showMessage('Gagal membagikan pin.', 'error');
                    }
                };
            } else {
                pinDetailShareButton.style.display = 'none';
            }

            // Download button logic (NEW: dipindahkan ke container gambar)
            pinDetailDownloadButton.onclick = (e) => { 
                e.stopPropagation(); 
                let imageUrlToDownload = '';
                if (pinData.images && pinData.images.length > 0) {
                    if (pinData.display_type === 'slider' && pinData.images.length > 1) {
                        const slider = document.getElementById('pinSlider');
                        if (slider) {
                            const currentSlideIndex = slideIndexes['pinSlider'];
                            const activeSlideImg = slider.querySelectorAll('.slider-slide img')[currentSlideIndex];
                            if (activeSlideImg) {
                                imageUrlToDownload = activeSlideImg.src;
                            }
                        }
                    } else {
                        imageUrlToDownload = pinData.images[0].url;
                    }
                }
                
                if (imageUrlToDownload) {
                    downloadPin(imageUrlToDownload); 
                } else {
                    showMessage('Tidak ada gambar untuk diunduh.', 'info');
                }
            };

            pinDetailOverlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('pin', pinData.id);
            history.pushState({ pinId: pinData.id }, '', newUrl.toString());
        }

        // --- GLOBAL FUNCTION for closePinDetail ---
        // Moved outside DOMContentLoaded to be accessible by onclick attribute
        function closePinDetail() {
            document.getElementById('pinDetailOverlay').style.display = 'none';
            document.body.style.overflow = '';

            const currentUrl = new URL(window.location.href);
            if (currentUrl.searchParams.has('pin')) {
                currentUrl.searchParams.delete('pin');
                history.pushState(null, '', currentUrl.toString());
            }
        }

        window.onpopstate = (event) => {
            const urlParams = new URLSearchParams(window.location.search);
            const pinIdFromUrl = urlParams.get('pin');
            if (pinIdFromUrl) {
                const pinToOpen = allPinsData.find(pin => pin.id === pinIdFromUrl);
                if (pinToOpen) {
                    openPinDetail(pinToOpen);
                } else {
                    closePinDetail();
                }
            } else {
                closePinDetail();
            }
        };

        // --- Fungsi Slider (untuk penggunaan di pinDetailOverlay) ---
        let slideIndexes = {}; // Objek untuk menyimpan indeks slide per slider ID

        function initializeSlider(sliderId) {
            slideIndexes[sliderId] = 0;
            showSlides(0, sliderId); // Tampilkan slide pertama
        }

        function moveSlide(n, sliderId) {
            showSlides(slideIndexes[sliderId] += n, sliderId);
        }

        function currentSlide(n, sliderId) {
            showSlides(slideIndexes[sliderId] = n, sliderId);
        }

        function showSlides(n, sliderId) {
            const slider = document.getElementById(sliderId);
            if (!slider) return;

            const slides = slider.getElementsByClassName('slider-slide');
            const dotsContainer = slider.querySelector('.slider-dots');
            let dots = [];

            // Buat dots jika belum ada
            if (dotsContainer && dotsContainer.innerHTML === '') {
                 for (let i = 0; i < slides.length; i++) {
                    const dot = document.createElement('span');
                    dot.className = 'slider-dot';
                    dot.onclick = () => currentSlide(i, sliderId);
                    dotsContainer.appendChild(dot);
                    dots.push(dot);
                }
            } else if (dotsContainer) {
                dots = Array.from(dotsContainer.getElementsByClassName('slider-dot'));
            }


            if (n >= slides.length) { slideIndexes[sliderId] = 0; }
            if (n < 0) { slideIndexes[sliderId] = slides.length - 1; }

            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = 'none';
            }
            for (let i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(' active', '');
            }

            if (slides.length > 0) {
                slides[slideIndexes[sliderId]].style.display = 'flex'; // Changed to flex for centering
                if (dots[slideIndexes[sliderId]]) {
                    dots[slideIndexes[sliderId]].className += ' active';
                }
            }
        }


        // --- Notification Overlay Logic ---
        const notificationOverlay = document.getElementById('notificationOverlay');
        const closeNotificationButton = document.getElementById('closeNotificationButton');
        const notificationListContainer = document.getElementById('notificationListContainer');

        async function openNotificationPage() {
            notificationListContainer.innerHTML = '<p style="text-align: center; color: #767676;">Memuat notifikasi...</p>';
            const response = await makeApiRequest('notifications.php?action=fetch_all');
            
            if (response.success && response.notifications.length > 0) {
                notificationListContainer.innerHTML = '';
                response.notifications.forEach(notif => {
                    const notifItem = document.createElement('div');
                    notifItem.classList.add('notification-item');
                    if (notif.read) {
                        notifItem.classList.add('read');
                    }
                    notifItem.innerHTML = `${notif.text} <span style="font-size:0.8em; color:#999; display:block; margin-top:5px;">(${notif.timestamp || 'Tanggal tidak diketahui'})</span>`;
                    notificationListContainer.appendChild(notifItem);
                });
                markAllNotificationsAsRead();
            } else {
                notificationListContainer.innerHTML = '<p style="text-align: center; color: #767676;">Tidak ada notifikasi baru.</p>';
            }

            notificationOverlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeNotificationPage() {
            notificationOverlay.style.display = 'none';
            document.body.style.overflow = '';
        }

        closeNotificationButton.addEventListener('click', closeNotificationPage);

        // --- Loading Pins & Categories ---
        const mainElement = document.querySelector('main');
        const pinGrid = document.getElementById('pinGrid');
        const loadingIndicator = document.getElementById('loading-indicator');
        let pinsPerPage = 20; 
        let loadedPinsCount = 0; 

        function createPinElement(pinData) {
            const pinDiv = document.createElement('div');
            pinDiv.classList.add('pin');
            pinDiv.dataset.id = pinData.id;

            const firstImageUrl = pinData.images && pinData.images.length > 0 ? pinData.images[0].url : 'https://placehold.co/250x350/cccccc/000000?text=No+Image';

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
            
            if (currentUser && currentUser.savedPins && currentUser.savedPins.includes(pinData.id)) {
                saveButton.textContent = 'Saved';
                saveButton.style.backgroundColor = '#767676';
            } else {
                saveButton.textContent = 'Save';
                saveButton.style.backgroundColor = '#e60023';
            }
            
            saveButton.onclick = async (e) => { 
                e.stopPropagation(); 
                if (!currentUser) {
                    showMessage('Harap login untuk menyimpan pin.', 'info');
                    window.location.href = 'login.html';
                    return;
                }

                if (currentUser.savedPins && currentUser.savedPins.includes(pinData.id)) {
                    const response = await makeApiRequest('pins.php?action=unsave', 'POST', { pinId: pinData.id });
                    if (response.success) {
                        showMessage('Pin berhasil dihapus dari daftar simpan!', 'success');
                        saveButton.textContent = 'Save';
                        saveButton.style.backgroundColor = '#e60023';
                        currentUser.savedPins = currentUser.savedPins.filter(id => id !== pinData.id);
                        if (pinDetailOverlay.style.display === 'flex' && pinDetailImageSaveButton && pinDetailImageSaveButton.textContent === 'Saved') {
                            updatePinDetailImageSaveButton(pinData);
                        }
                        const currentTab = document.querySelector('.nav-button.active')?.dataset.nav;
                        if (currentTab === 'saved') {
                            loadedPinsCount = 0;
                            await loadPins(pinsPerPage, false);
                        }
                    } else {
                        showMessage('Gagal menghapus pin dari daftar simpan: ' + response.message, 'error');
                    }
                } else {
                    const response = await makeApiRequest('pins.php?action=save', 'POST', { pinId: pinData.id });
                    if (response.success) {
                        showMessage('Pin berhasil disimpan!', 'success');
                        saveButton.textContent = 'Saved';
                        saveButton.style.backgroundColor = '#767676';
                        if (currentUser.savedPins) {
                            currentUser.savedPins.push(pinData.id);
                        } else {
                            currentUser.savedPins = [pinData.id];
                        }
                        if (pinDetailOverlay.style.display === 'flex' && pinDetailImageSaveButton && pinDetailImageSaveButton.textContent === 'Save') {
                            updatePinDetailImageSaveButton(pinData);
                        }
                    } else {
                        showMessage('Gagal menyimpan pin: ' + response.message, 'error');
                    }
                }
            };
            overlayTop.appendChild(saveButton);

            // NEW: Image count overlay for pins with multiple images
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
                 link.href = `tag/${encodeURIComponent(pinData.category.trim().toLowerCase().replace(/ /g, '-'))}`;
                 link.target = '_blank';
                 link.textContent = pinData.category;
                 link.onclick = (e) => e.stopPropagation(); 
                 infoDiv.appendChild(link);
            } else {
                infoDiv.textContent = 'Tanpa Kategori';
                infoDiv.style.opacity = '0.7'; 
            }
            bottomActions.appendChild(infoDiv);

            // Removed download button from here, it's now inside pin-detail-img-main-container
            // const downloadButton = document.createElement('button');
            // downloadButton.classList.add('pin-action-icon');
            // downloadButton.innerHTML = `<i class="fas fa-download"></i>`;
            // downloadButton.onclick = (e) => { e.stopPropagation(); downloadPin(firstImageUrl); }; 
            // bottomActions.appendChild(downloadButton);

            overlayBottom.appendChild(bottomActions); 
            
            overlayDiv.appendChild(overlayTop);
            overlayDiv.appendChild(overlayBottom);

            pinDiv.appendChild(img);
            pinDiv.appendChild(overlayDiv);
            
            pinDiv.onclick = () => openPinDetail(pinData);
            
            return pinDiv;
        }

        async function loadPins(count, append = true) {
            console.log(`loadPins called. count: ${count}, append: ${append}, currentSearchQuery: "${currentSearchQuery}"`);
            loadingIndicator.style.display = 'block';
            const currentTab = document.querySelector('.nav-button.active')?.dataset.nav;
            let endpoint = '';
            let params = '';

            if (currentSearchQuery) { 
                endpoint = 'pins.php?action=search';
                params = `&query=${encodeURIComponent(currentSearchQuery)}`;
            } else if (currentTab === 'saved') {
                if (!currentUser) {
                    pinGrid.innerHTML = '<p style="text-align: center; color: #767676; margin-top: 50px;">Harap login untuk melihat pin yang Anda simpan.</p>';
                    loadingIndicator.style.display = 'none';
                    return;
                }
                endpoint = 'pins.php?action=fetch_saved';
            } else {
                endpoint = 'pins.php?action=fetch_all';
            }

            const response = await makeApiRequest(`${endpoint}${params}`);
            loadingIndicator.style.display = 'none';

            if (response.success) {
                if (!currentTab || currentTab === 'home' || currentSearchQuery === '') { // Hanya perbarui allPinsData untuk home/pencarian kosong
                   allPinsData = response.pins || []; 
                } 
                console.log("Pins berhasil diambil. Total pin dari API:", response.pins ? response.pins.length : 0);

                const pinsToDisplay = response.pins || [];
                
                const startIndex = append ? loadedPinsCount : 0;
                const slicedPinsToDisplay = pinsToDisplay.slice(startIndex, startIndex + count);

                if (!append) {
                    pinGrid.innerHTML = '';
                    loadedPinsCount = 0;
                    console.log("Grid pin dikosongkan untuk pemuatan non-append.");
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
                    console.log("pinGrid.style.display diatur ke 'column' karena pin dimuat atau ini tampilan beranda.");
                }


                if (slicedPinsToDisplay.length === 0 && loadedPinsCount === 0) {
                    if (currentSearchQuery) {
                        pinGrid.innerHTML = `<p style="text-align: center; color: #767676; margin-top: 50px;">Tidak ada pin ditemukan untuk "${currentSearchQuery}".</p>`;
                    } else {
                        pinGrid.innerHTML = '<p style="text-align: center; color: #767676; margin-top: 50px;">Tidak ada pin untuk ditampilkan.</p>';
                    }
                }
            } else {
                showMessage('Gagal memuat pin: ' + response.message, 'error');
                pinGrid.innerHTML = '<p style="text-align: center; color: #e60023; margin-top: 50px;">Error memuat pin. Silakan coba lagi nanti.</p>';
            }
            console.log("loadPins selesai. pinGrid.style.display:", pinGrid.style.display);
            console.log("Gaya yang dihitung setelah loadPins:", window.getComputedStyle(pinGrid).display);
        }

        // --- Search History functions ---
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
            desktopSearchHistoryList.innerHTML = '';
            mobileSearchHistoryList.innerHTML = '';

            if (searchHistory.length === 0) {
                desktopSearchHistoryContainer.style.display = 'none';
                mobileSearchHistoryContainer.style.display = 'none';
                return;
            }

            desktopSearchHistoryContainer.style.display = 'block';
            mobileSearchHistoryContainer.style.display = 'block';

            searchHistory.forEach(historyItem => {
                const desktopLi = document.createElement('li');
                desktopLi.textContent = historyItem;
                desktopLi.addEventListener('click', () => {
                    // Redirect to search.php
                    window.location.href = `search.php?query=${encodeURIComponent(historyItem)}`;
                });
                desktopSearchHistoryList.appendChild(desktopLi);

                const mobileLi = document.createElement('li');
                mobileLi.textContent = historyItem;
                mobileLi.addEventListener('click', () => {
                    // Redirect to search.php
                    window.location.href = `search.php?query=${encodeURIComponent(historyItem)}`;
                });
                mobileSearchHistoryList.appendChild(mobileLi);
            });
        }

        // --- Search Suggestions Logic ---
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
            
            if (inputElement === desktopSearchInput) {
                desktopSearchHistoryContainer.style.display = 'none';
            } else {
                mobileSearchHistoryContainer.style.display = 'none';
            }

            const filteredSuggestions = allPinsData.filter(pin => 
                (pin.title && pin.title.toLowerCase().includes(query.toLowerCase())) ||
                (pin.description && pin.description.toLowerCase().includes(query.toLowerCase())) || // Check pin description
                (pin.category && pin.category.toLowerCase().includes(query.toLowerCase())) || // Check single category
                (pin.images && pin.images.some(img => img.description && img.description.toLowerCase().includes(query.toLowerCase()))) // Check image descriptions
            ).map(pin => pin.title || pin.category).filter(Boolean).slice(0, 5); // Ambil title atau category

            const uniqueSuggestions = [...new Set(filteredSuggestions)];

            if (uniqueSuggestions.length > 0) {
                uniqueSuggestions.forEach(suggestion => {
                    const listItem = document.createElement('li');
                    listItem.textContent = suggestion;
                    listItem.addEventListener('click', () => {
                        // Redirect to search.php on suggestion click
                        window.location.href = `search.php?query=${encodeURIComponent(suggestion)}`;
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

        // This performSearch is for index.html. It now redirects.
        async function performSearch(query) {
            window.location.href = `search.php?query=${encodeURIComponent(query)}`;
        }

        // Mobile search input listener (within the overlay on index.html)
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
                    performSearch(query); // Calls performSearch which now redirects
                    closeSearch(); // Close the mobile search overlay
                }
            }
        });

        // Desktop search input listener (on the header of index.html)
        desktopSearchInput.addEventListener('focus', () => {
            mainElement.classList.add('desktop-search-active');
            pinGrid.style.display = 'none'; 
            loadingIndicator.style.display = 'none';
            desktopSearchInput.value = currentSearchQuery; 
            renderSearchHistory(); 
            searchSuggestionsListDesktop.style.display = 'none'; 
        });

        desktopSearchInput.addEventListener('blur', (event) => {
            setTimeout(() => {
                const searchArea = document.querySelector('header .search-container');
                const isFocusStillInsideSearchRelatedArea = searchArea.contains(event.relatedTarget) ||
                                             desktopSearchHistoryContainer.contains(event.relatedTarget) ||
                                             searchSuggestionsListDesktop.contains(event.relatedTarget); 
                
                if (!isFocusStillInsideSearchRelatedArea) {
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
                    window.location.href = `search.php?query=${encodeURIComponent(query)}`; // Redirect to search.php
                }
            }
        });
        
        // Helper function to reset view to home page
        async function resetMainContentToHome() {
            desktopSearchInput.value = ''; 
            currentSearchQuery = ''; 
            searchSuggestionsListDesktop.style.display = 'none';
            desktopSearchHistoryContainer.style.display = 'none';
            loadedPinsCount = 0; 
            mainElement.classList.remove('desktop-search-active'); 
            
            pinGrid.style.removeProperty('display'); 
            pinGrid.style.display = 'column'; 
            
            await loadPins(pinsPerPage, false); 
        }

        // Handle clicks outside search elements to close them and revert to home page view
        document.addEventListener('click', (event) => {
            const headerSearchContainer = document.querySelector('header .search-container');
            const isClickInsideDesktopSearchUI = headerSearchContainer.contains(event.target) ||
                                             searchSuggestionsListDesktop.contains(event.target) ||
                                             desktopSearchHistoryContainer.contains(event.target);
        
            if (!desktopSearchInput.matches(':focus') && !isClickInsideDesktopSearchUI) {
                resetMainContentToHome();
            }

            // If clicking outside mobile search overlay, close it
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
                    // Filter out empty category names
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
                                // Categories now redirect to the tag.php page
                                const categoryPermalink = cat.name.trim().toLowerCase().replace(/ /g, '-');
                                window.location.href = `tag/${encodeURIComponent(categoryPermalink)}`;
                            };
                            fragment.appendChild(catItem);
                        });
                        container.appendChild(fragment);
                    });
                } else {
                    console.error('Failed to load categories:', response.message);
                    categoryContainers.forEach(container => {
                        if (container) container.innerHTML = '<p style="text-align: center; color: #767676;">Gagal memuat kategori.</p>';
                    });
                }
            } catch (error) {
                console.error('Error fetching categories:', error);
                categoryContainers.forEach(container => {
                    if (container) container.innerHTML = '<p style="text-align: center; color: #767676;">Kesalahan jaringan saat memuat kategori.</p>';
                });
            }
        }
        
        loadCategories();

        // --- Infinite Scroll ---
        window.addEventListener('scroll', () => {
            if (loadingIndicator.style.display === 'none' && 
                searchOverlay.style.display === 'none' && 
                mobileProfileDropdown.style.display === 'none' &&
                pinDetailOverlay.style.display === 'none' && 
                notificationOverlay.style.display === 'none' 
            ) {
                if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 400) {
                    // Load more pins only if not currently searching (which redirects now)
                    if (!currentSearchQuery) { 
                        loadPins(pinsPerPage, true);
                    }
                }
            }
        });

        // --- Desktop Search/Category Interaction ---
        // mainElement.classList.add('desktop-search-active') and similar toggles handled by focus/blur listeners.
        
        // --- Header Button Logic ---
        const headerNavButtons = document.querySelectorAll('.header-nav-links .nav-button');
        const desktopCreateButton = document.getElementById('desktopCreateButton');

        desktopCreateButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (!currentUser) {
                window.location.href = 'login.html'; 
            } else {
                window.location.href = 'create.html';
            }
        });

        headerNavButtons.forEach(button => {
            if (button.id === 'desktopCreateButton') return; 

            button.addEventListener('click', async function() {
                headerNavButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                const navAction = this.dataset.nav;
                currentSearchQuery = ''; 

                desktopSearchHistoryContainer.style.display = 'none';
                searchSuggestionsListDesktop.style.display = 'none';
                mainElement.classList.remove('desktop-search-active'); 

                pinGrid.style.display = 'column'; 


                if (navAction === 'home') {
                    loadedPinsCount = 0;
                    await loadPins(pinsPerPage, false);
                } else if (navAction === 'saved') {
                    if (!currentUser) {
                        showMessage('Harap login untuk melihat pin yang Anda simpan.', 'error');
                        headerNavButtons.forEach(btn => btn.classList.remove('active'));
                        document.querySelector('.nav-button[data-nav="home"]').classList.add('active');
                        window.location.href = 'login.html'; 
                        return;
                    }
                    loadedPinsCount = 0;
                    await loadPins(pinsPerPage, false);
                }
            });
        });


        // --- Mobile Search Overlay Logic ---
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

        closeSearchBtn.onclick = closeSearch;
        

        // --- Mobile Navigation Logic ---
        const mobileNavItems = document.querySelectorAll('.mobile-bottom-nav .mobile-nav-item');
        mobileNavItems.forEach(item => {
            item.addEventListener('click', async function() {
                const action = this.dataset.action;

                closePinDetail();
                closeNotificationPage();
                closeSearch(); 
                
                if (action === 'search') {
                    openSearch();
                } else if (action === 'create-pin') { 
                     if (!currentUser) {
                        window.location.href = 'login.html';
                        return;
                    }
                    window.location.href = 'create.html'; 
                }
                else if (action === 'profile') { 
                    if (!currentUser) {
                        window.location.href = 'login.html';
                        return;
                    }
                    if (currentUser.isAdmin) {
                        window.location.href = 'admin.php';
                    } else {
                        window.location.href = 'user.php';
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
                    currentSearchQuery = ''; 
                    
                    mainElement.classList.remove('desktop-search-active');
                    pinGrid.style.display = 'column';


                    if (action === 'home') {
                        loadedPinsCount = 0;
                        await loadPins(pinsPerPage, false);
                    } else if (action === 'saved') { 
                        if (!currentUser) {
                            window.location.href = 'login.html';
                            mobileNavItems.forEach(i => i.classList.remove('active'));
                            document.querySelector('.mobile-nav-item[data-action="home"]').classList.add('active');
                            return;
                        }
                        loadedPinsCount = 0;
                        await loadPins(pinsPerPage, false);
                    }
                }
            });
        });

        // --- Login/Register Overlay Logic ---
        const profileButton = document.getElementById('profileButton');
        const profileIconLetter = document.getElementById('profileIconLetter');

        profileButton.addEventListener('click', () => {
            if (!currentUser) {
                window.location.href = 'login.html'; 
            } else {
                if (currentUser.isAdmin) {
                    window.location.href = 'admin.php';
                } else {
                    window.location.href = 'user.php';
                }
            }
        });

        // --- User Profile Display & Header Dropdown ---
        function updateProfileDisplay() {
            const mobileProfileIcon = document.querySelector('.mobile-nav-item[data-action="profile"] .profile-icon');
            if (currentUser) {
                profileIconLetter.textContent = currentUser.username.charAt(0).toUpperCase();
                profileIconLetter.style.backgroundColor = '#e60023';
                profileIconLetter.style.color = 'white';

                if (mobileProfileIcon) {
                    mobileProfileIcon.textContent = currentUser.username.charAt(0).toUpperCase();
                    mobileProfileIcon.style.backgroundColor = '#fff';
                    mobileProfileIcon.style.color = '#111';
                }
            } else {
                profileIconLetter.textContent = 'G';
                profileIconLetter.style.backgroundColor = '#ddd';
                profileIconLetter.style.color = '#767676';

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
                        notificationBadge.textContent = unreadCount;
                        notificationBadge.style.display = 'flex';
                        mobileNotificationBadge.textContent = unreadCount;
                        mobileNotificationBadge.style.display = 'flex';
                    } else {
                        notificationBadge.style.display = 'none';
                        mobileNotificationBadge.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Gagal memperbarui lencana notifikasi:', error);
                }
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

        notificationButton.addEventListener('click', openNotificationPage);
        mobileNotificationButton.addEventListener('click', openNotificationPage);


        // --- Pin Functions (Download) ---
        function downloadPin(imageUrl) {
            const link = document.createElement('a');
            link.href = imageUrl;
            const filename = imageUrl.substring(imageUrl.lastIndexOf('/') + 1) || 'pin_image.jpg';
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showMessage(`Mengunduh pin.`);
        }

        // --- Desktop More Accounts Dropdown ---
        const moreAccountsButton = document.getElementById('moreAccountsButton');
        const moreAccountsDropdown = document.getElementById('moreAccountsDropdown');
        const dropdownUsernameDisplay = document.getElementById('dropdownUsernameDisplay');
        const dropdownMyProfile = document.getElementById('dropdownMyProfile');
        const dropdownAdminPanel = document.getElementById('dropdownAdminPanel');
        const dropdownLogout = document.getElementById('dropdownLogout');

        function toggleDropdown() {
            updateHeaderDropdown();
            moreAccountsDropdown.style.display = moreAccountsDropdown.style.display === 'block' ? 'none' : 'block';
        }

        function updateHeaderDropdown() {
            if (currentUser) {
                dropdownUsernameDisplay.textContent = `Masuk sebagai ${currentUser.username}`;
                dropdownMyProfile.style.display = 'block';
                dropdownAdminPanel.style.display = currentUser.isAdmin ? 'block' : 'none';
                dropdownLogout.style.display = 'block';
            } else {
                dropdownUsernameDisplay.textContent = 'Selamat datang, Tamu!';
                dropdownMyProfile.style.display = 'none';
                dropdownAdminPanel.style.display = 'none';
                dropdownLogout.style.display = 'none';
            }
        }

        moreAccountsButton.addEventListener('click', toggleDropdown);

        document.addEventListener('click', function(event) {
            if (!moreAccountsButton.contains(event.target) && !moreAccountsDropdown.contains(event.target)) {
                moreAccountsDropdown.style.display = 'none';
            }
        });

        dropdownMyProfile.addEventListener('click', () => {
            if (currentUser) {
                window.location.href = 'user.php';
            }
            moreAccountsDropdown.style.display = 'none';
        });

        dropdownAdminPanel.addEventListener('click', () => {
            if (currentUser && currentUser.isAdmin) {
                window.location.href = 'admin.php';
            } else {
                showMessage('Akses ditolak: Diperlukan hak administrator.', 'Error');
            }
            moreAccountsDropdown.style.display = 'none';
        });

        dropdownLogout.addEventListener('click', async () => {
            const response = await makeApiRequest('auth.php?action=logout', 'POST', null); 
            if (response.success) {
                currentUser = null;
                updateProfileDisplay();
                updateHeaderDropdown();
                showMessage('Logout berhasil.', 'Logout');
                loadedPinsCount = 0;
                currentSearchQuery = ''; 
                await loadPins(pinsPerPage, false);
                mobileNavItems.forEach(i => i.classList.remove('active'));
                document.querySelector('.mobile-nav-item[data-action="home"]').classList.add('active');
                headerNavButtons.forEach(btn => btn.classList.remove('active'));
                document.querySelector('.nav-button[data-nav="home"]').classList.add('active');
            } else {
                showMessage('Logout gagal: ' + response.message, 'Error');
            }
            mobileAccountsDropdown.style.display = 'none';
        });

        // Mobile Profile Options (for mobile bottom nav profile icon)
        const mobileProfileDropdown = document.getElementById('mobileProfileDropdown');
        const mobileDropdownUsernameDisplay = document.getElementById('mobileDropdownUsernameDisplay');
        const mobileDropdownMyProfile = document.getElementById('mobileDropdownMyProfile');
        const mobileDropdownAdminPanel = document.getElementById('mobileDropdownAdminPanel');
        const mobileDropdownLogout = document.getElementById('mobileDropdownLogout');
        const mobileDropdownClose = document.getElementById('mobileDropdownClose');

        function showMobileProfileOptions() {
            if (!currentUser) return;

            mobileDropdownUsernameDisplay.textContent = `Masuk sebagai ${currentUser.username}`;
            mobileDropdownMyProfile.style.display = 'block';
            mobileDropdownAdminPanel.style.display = currentUser.isAdmin ? 'block' : 'none';
            
            mobileProfileDropdown.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Event listener for mobile profile icon to show dropdown
        document.querySelector('.mobile-nav-item[data-action="profile"]').addEventListener('click', () => {
            if (currentUser) {
                showMobileProfileOptions();
            } else {
                window.location.href = 'login.html'; 
            }
        });

        mobileDropdownMyProfile.addEventListener('click', () => {
            if (currentUser) {
                window.location.href = 'user.php';
            }
            mobileProfileDropdown.style.display = 'none';
            document.body.style.overflow = '';
        });

        mobileDropdownAdminPanel.addEventListener('click', () => {
            if (currentUser && currentUser.isAdmin) {
                window.location.href = 'admin.php';
            } else {
                showMessage('Akses ditolak: Diperlukan hak administrator.', 'Error');
            }
            mobileProfileDropdown.style.display = 'none';
            document.body.style.overflow = '';
        });

        mobileDropdownLogout.addEventListener('click', async () => {
            const response = await makeApiRequest('auth.php?action=logout', 'POST', null); 
            if (response.success) {
                currentUser = null;
                updateProfileDisplay();
                updateHeaderDropdown();
                showMessage('Logout berhasil.', 'Logout');
                loadedPinsCount = 0;
                currentSearchQuery = ''; 
                await loadPins(pinsPerPage, false);
                mobileNavItems.forEach(i => i.classList.remove('active'));
                document.querySelector('.mobile-nav-item[data-action="home"]').classList.add('active');
                headerNavButtons.forEach(btn => btn.classList.remove('active'));
                document.querySelector('.nav-button[data-nav="home"]').classList.add('active');
            } else {
                showMessage('Logout gagal: ' + response.message, 'Error');
            }
            mobileProfileDropdown.style.display = 'none';
            document.body.style.overflow = '';
        });

        mobileDropdownClose.addEventListener('click', () => {
            mobileProfileDropdown.style.display = 'none';
            document.body.style.overflow = '';
        });

        // --- Check Initial Session and Load ---
        document.addEventListener('DOMContentLoaded', async function initializeApp() {
            loadSearchHistory(); 
            const urlParams = new URLSearchParams(window.location.search);

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
            updateProfileDisplay();
            updateHeaderDropdown();
            updateNotificationBadge();
            
            const allPinsResponse = await makeApiRequest('pins.php?action=fetch_all');
            if (allPinsResponse.success) {
                allPinsData = allPinsResponse.pins; 
            } else {
                console.error("Gagal memuat semua pin saat inisialisasi:", allPinsResponse.message);
            }

            await loadPins(pinsPerPage, false);

            const pinIdFromUrl = urlParams.get('pin');
            if (pinIdFromUrl) {
                const pinToOpen = allPinsData.find(pin => pin.id === pinIdFromUrl);
                if (pinToOpen) {
                    openPinDetail(pinToOpen);
                } else {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('pin');
                    history.replaceState(null, '', currentUrl.toString());
                }
            }
        });
    </script>
</body>
</html>
