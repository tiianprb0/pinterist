<?php
// ENSURE NO CHARAKTER APAPUN SEBELUM BARIS INI
session_start();
// Include API utility functions for consistency
require_once 'api/utils.php';

$searchQuery = $_GET['query'] ?? ''; // Get search query from URL query parameter

$username = $_SESSION['username'] ?? null;
$isAdmin = $_SESSION['isAdmin'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search results for "<?php echo htmlspecialchars($searchQuery); ?>" - Spicette</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Spicette/style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Import Playfair Display font */
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap');

        /* Add any specific styles for the search page here if needed */
        main {
            padding-top: 20px;
        }
        .search-page-header { /* Changed from tag-page-header */
            max-width: 1200px;
            margin: 0 auto 30px auto;
            padding: 0 20px;
        }
        .search-page-header h1 { /* Changed from tag-page-header */
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            color: #111;
            margin-bottom: 10px;
        }
        .search-page-header p { /* Changed from tag-page-header */
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            color: #5f5f5f;
        }
        /* Ensure pin-grid on search page does not get affected by desktop-search-active class in main */
        .search-page-layout .pin-grid { /* Changed from tag-page-layout */
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
        <div class="overlay-header">
            <button class="icon-button" aria-label="Close Pin Detail" id="closePinDetailButton">
                <i class="fas fa-times"></i> </button>
        </div>
        <div class="pin-detail-content">
            <div class="pin-detail-img-container">
                <img id="pinDetailImage" src="" alt="Pin Detail">
                <button id="pinDetailImageSaveButton" class="pin-save-button image-overlay-button">Save</button>
            </div>
            <div class="pin-detail-info">
                <h2 id="pinDetailTitle"></h2>
                <p class="uploaded-by-text"><strong id="pinDetailUploadedBy"></strong></p>
                <p id="pinDetailDescription" class="pin-detail-description"></p>
                <div class="dashed-line"></div>
                <div id="pinDetailCategories" class="pin-categories"></div>
            </div>
            <div class="pin-detail-actions">
                <button id="pinDetailShareButton" class="secondary">
                    <i class="fas fa-share-alt"></i> Share
                </button>
                <button id="pinDetailDownloadButton" class="secondary">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
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

    <main class="search-page-layout"> <div class="back-button-container">
            <button class="back-button" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>
        <section class="search-page-header"> <h1>Search results for "<?php echo htmlspecialchars($searchQuery); ?>"</h1>
            <p>Explore pins matching your search.</p>
        </section>
        <div class="pin-grid" id="pinGrid">
            <p style="text-align: center; color: #767676;">Loading pins...</p>
        </div>
        <div id="loading-indicator">Loading more pins...</div>
    </main>

    <div id="customAlert" class="custom-alert"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Global State ---
            let currentUser = null;
            // Initialize currentSearchQuery from the PHP variable
            let currentSearchQuery = '<?php echo htmlspecialchars($searchQuery); ?>';
            let allPinsData = [];
            const NOTIFICATION_LS_KEY_READ_STATUS = 'spicette_notifications_read';
            let searchSuggestionsData = [];
            let selectedSuggestionIndex = -1;

            // NEW: Search History variables
            const SEARCH_HISTORY_LS_KEY = 'spicette_search_history';
            const MAX_SEARCH_HISTORY = 5;
            let searchHistory = [];

            // --- API Endpoints ---
            const API_BASE_URL = '/Spicette/api/'; // Corrected to absolute path

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
            const closePinDetailButton = document.getElementById('closePinDetailButton');
            const pinDetailImage = document.getElementById('pinDetailImage');
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
                pinDetailImage.src = pinData.img;
                pinDetailImage.onerror = function() {
                    this.onerror=null;
                    this.src='https://thumbs.dreamstime.com/b/no-thumbnail-image-placeholder-forums-blogs-websites-148010362.jpg';
                };
                pinDetailTitle.textContent = pinData.title || 'Untitled';
                pinDetailUploadedBy.textContent = `by ${pinData.uploadedBy || 'Anonymous'}`;

                if (pinData.content) {
                    pinDetailDescription.textContent = pinData.content;
                    pinDetailDescription.style.display = 'block';
                } else {
                    pinDetailDescription.textContent = '';
                    pinDetailDescription.style.display = 'none';
                }

                pinDetailCategories.innerHTML = '';
                if (pinData.categories && pinData.categories.length > 0) {
                    pinData.categories.forEach(category => {
                        const span = document.createElement('span');
                        span.classList.add('pin-category-tag');
                        const categoryPermalink = category.trim().toLowerCase().replace(/ /g, '-');
                        span.innerHTML = `<a href="/Spicette/tag/${encodeURIComponent(categoryPermalink)}" style="color: inherit; text-decoration: none;">#${category.trim()}</a>`;
                        pinDetailCategories.appendChild(span);
                    });
                    pinDetailCategories.style.display = 'flex';
                } else {
                    pinDetailCategories.style.display = 'none';
                }

                updatePinDetailImageSaveButton(pinData);

                if (navigator.share) {
                    pinDetailShareButton.style.display = 'flex';
                    pinDetailShareButton.onclick = async (e) => {
                        e.stopPropagation();
                        try {
                            await navigator.share({
                                title: pinData.title || 'Spicette Pin',
                                text: pinData.content || pinData.title || 'Check out this pin!',
                                url: window.location.href
                            });
                            showMessage('Pin shared successfully!', 'success');
                        } catch (error) {
                            console.error('Error sharing:', error);
                            showMessage('Failed to share pin.', 'error');
                        }
                    };
                } else {
                    pinDetailShareButton.style.display = 'none';
                }

                pinDetailDownloadButton.onclick = (e) => { e.stopPropagation(); downloadPin(pinData.img); };

                pinDetailOverlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';

                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('pin', pinData.id);
                history.pushState({ pinId: pinData.id }, '', newUrl.toString());
            }

            function closePinDetail() {
                pinDetailOverlay.style.display = 'none';
                document.body.style.overflow = '';

                const currentUrl = new URL(window.location.href);
                if (currentUrl.searchParams.has('pin')) {
                    currentUrl.searchParams.delete('pin');
                    history.pushState(null, '', currentUrl.toString());
                }
            }

            closePinDetailButton.addEventListener('click', closePinDetail);

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


            // --- Notification Overlay Logic ---
            const notificationOverlay = document.getElementById('notificationOverlay');
            const closeNotificationButton = document.getElementById('closeNotificationButton');
            const notificationListContainer = document.getElementById('notificationListContainer');

            async function openNotificationPage() {
                notificationListContainer.innerHTML = '<p style="text-align: center; color: #767676;">Loading notifications...</p>';
                const response = await makeApiRequest('notifications.php?action=fetch_all');

                if (response.success && response.notifications.length > 0) {
                    notificationListContainer.innerHTML = '';
                    response.notifications.forEach(notif => {
                        const notifItem = document.createElement('div');
                        notifItem.classList.add('notification-item');
                        if (notif.read) {
                            notifItem.classList.add('read');
                        }
                        notifItem.innerHTML = `${notif.text} <span style="font-size:0.8em; color:#999; display:block; margin-top:5px;">(${notif.timestamp || 'Date unknown'})</span>`;
                        notificationListContainer.appendChild(notifItem);
                    });
                    markAllNotificationsAsRead();
                } else {
                    notificationListContainer.innerHTML = '<p style="text-align: center; color: #767676;">No new notifications.</p>';
                }

                notificationOverlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            function closeNotificationPage() {
                notificationOverlay.style.display = 'none';
                document.body.style.overflow = '';
            }

            closeNotificationButton.addEventListener('click', closeNotificationPage);

            // --- Loading Pins ---
            const mainElement = document.querySelector('main');
            const pinGrid = document.getElementById('pinGrid');
            const loadingIndicator = document.getElementById('loading-indicator');
            let pinsPerPage = 20;
            let loadedPinsCount = 0;

            function createPinElement(pinData) {
                const pinDiv = document.createElement('div');
                pinDiv.classList.add('pin');
                pinDiv.dataset.id = pinData.id;

                const img = document.createElement('img');
                img.src = pinData.img;
                img.alt = 'Pin Image';
                img.onerror = function() {
                    this.onerror=null;
                    this.src='https://thumbs.dreamstime.com/b/no-thumbnail-image-placeholder-forums-blogs-websites-148010362.jpg';
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
                        showMessage('Please login to save pins.', 'info');
                        window.location.href = '/Spicette/login.html';
                        return;
                    }

                    if (currentUser.savedPins && currentUser.savedPins.includes(pinData.id)) {
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
                    } else {
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
                    }
                };
                overlayTop.appendChild(saveButton);

                const overlayBottom = document.createElement('div');
                overlayBottom.classList.add('pin-overlay-bottom');

                const pinTitle = document.createElement('div');
                pinTitle.classList.add('pin-title');
                pinTitle.textContent = pinData.title || 'No Title';
                overlayBottom.appendChild(pinTitle);

                const bottomActions = document.createElement('div');
                bottomActions.classList.add('pin-bottom-actions');

                const infoDiv = document.createElement('div');
                infoDiv.classList.add('pin-info');
                if (pinData.source) {
                    const link = document.createElement('a');
                    link.href = `http://${pinData.source}`;
                    link.target = '_blank';
                    link.textContent = pinData.source;
                    link.onclick = (e) => e.stopPropagation();
                    infoDiv.appendChild(link);
                } else {
                    infoDiv.textContent = 'No Source';
                    infoDiv.style.opacity = '0.7';
                }
                bottomActions.appendChild(infoDiv);

                const downloadButton = document.createElement('button');
                downloadButton.classList.add('pin-action-icon');
                downloadButton.innerHTML = `<i class="fas fa-download"></i>`;
                downloadButton.onclick = (e) => { e.stopPropagation(); downloadPin(pinData.img); };

                bottomActions.appendChild(downloadButton);
                overlayBottom.appendChild(bottomActions);

                overlayDiv.appendChild(overlayTop);
                overlayDiv.appendChild(overlayBottom);

                pinDiv.appendChild(img);
                pinDiv.appendChild(overlayDiv);

                pinDiv.onclick = () => openPinDetail(pinData);

                return pinDiv;
            }

            async function loadPins(count, append = true) {
                loadingIndicator.style.display = 'block';
                // Use currentSearchQuery to load pins
                let endpoint = 'pins.php?action=search';
                let params = `&query=${encodeURIComponent(currentSearchQuery)}`;


                const response = await makeApiRequest(`${endpoint}${params}`);
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

                    if (slicedPinsToDisplay.length > 0) {
                        pinGrid.style.removeProperty('display');
                        pinGrid.style.display = 'column';
                    } else if (loadedPinsCount === 0) {
                        pinGrid.innerHTML = `<p style="text-align: center; color: #767676; margin-top: 50px;">No pins found for "${currentSearchQuery}".</p>`;
                    }
                } else {
                    showMessage('Failed to load pins: ' + response.message, 'error');
                    pinGrid.innerHTML = '<p style="text-align: center; color: #e60023; margin-top: 50px;">Error loading pins. Please try again later.</p>';
                }
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

            const mobileSearchHistoryContainer = document.getElementById('mobileSearchHistory');
            const mobileSearchHistoryList = document.getElementById('mobileSearchHistoryList');


            function renderSearchHistory() {
                mobileSearchHistoryList.innerHTML = '';

                if (searchHistory.length === 0) {
                    mobileSearchHistoryContainer.style.display = 'none';
                    return;
                }

                mobileSearchHistoryContainer.style.display = 'block';

                searchHistory.forEach(historyItem => {
                    const mobileLi = document.createElement('li');
                    mobileLi.textContent = historyItem;
                    mobileLi.addEventListener('click', () => {
                        // When history item is clicked, redirect to search results page
                        window.location.href = `/Spicette/search.php?query=${encodeURIComponent(historyItem)}`;
                    });
                    mobileSearchHistoryList.appendChild(mobileLi);
                });
            }

            // --- Search Suggestions Logic ---
            const mobileSearchInputOverlay = document.getElementById('mobileSearchInputOverlay');
            const searchSuggestionsListMobile = document.getElementById('searchSuggestions');
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

                const filteredSuggestions = allPinsData.filter(pin =>
                    (pin.title && pin.title.toLowerCase().includes(query.toLowerCase())) ||
                    (pin.source && pin.source.toLowerCase().includes(query.toLowerCase())) ||
                    (pin.content && pin.content.toLowerCase().includes(query.toLowerCase())) ||
                    (pin.categories && pin.categories.some(cat => cat.toLowerCase().includes(query.toLowerCase())))
                ).map(pin => pin.title || pin.source).filter(Boolean).slice(0, 5);

                const uniqueSuggestions = [...new Set(filteredSuggestions)];

                if (uniqueSuggestions.length > 0) {
                    uniqueSuggestions.forEach(suggestion => {
                        const listItem = document.createElement('li');
                        listItem.textContent = suggestion;
                        listItem.addEventListener('click', () => {
                            // When a suggestion is clicked, redirect to search results page
                            window.location.href = `/Spicette/search.php?query=${encodeURIComponent(suggestion)}`;
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

            // This performSearch function is for the current page (search.php), not index.html's search bar logic.
            async function performSearch(query) {
                currentSearchQuery = query; // Update current query
                addSearchToHistory(query);

                loadingIndicator.style.display = 'block'; // Use main loading indicator
                pinGrid.innerHTML = ''; // Clear main pin grid
                pinGrid.style.display = 'none'; // Hide main pin grid temporarily

                try {
                    const response = await makeApiRequest(`pins.php?action=search&query=${encodeURIComponent(query)}`);
                    loadingIndicator.style.display = 'none';

                    if (response.success && response.pins.length > 0) {
                        pinGrid.style.display = 'column'; // Display main pin grid
                        response.pins.forEach(pinData => {
                            pinGrid.appendChild(createPinElement(pinData));
                        });
                    } else {
                        pinGrid.style.display = 'block';
                        pinGrid.innerHTML = `<p style="text-align: center; color: #767676; margin-top: 50px;">No pins found for "${query}".</p>`;
                    }
                } catch (error) {
                    loadingIndicator.style.display = 'none';
                    pinGrid.style.display = 'block';
                    pinGrid.innerHTML = `<p style="text-align: center; color: #e60023; margin-top: 50px;">Error searching for pins: ${error.message}</p>`;
                }
            }

            // Mobile search input listener (within the overlay on search.php)
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
                        // On enter, redirect to this search page with the new query
                        window.location.href = `/Spicette/search.php?query=${encodeURIComponent(query)}`;
                    }
                }
            });


            // This click listener is for the search.php page's general click handling.
            document.addEventListener('click', (event) => {
                const isClickInsideMobileSearchOverlay = searchOverlay.contains(event.target);

                // If mobile search overlay is open and click is outside it, close it.
                if (searchOverlay.style.display === 'flex' && !isClickInsideMobileSearchOverlay) {
                    closeSearch();
                }
            });


            async function loadCategories() {
                const categoryContainers = [
                    document.getElementById('categoryGridMobile')
                ];

                try {
                    const response = await makeApiRequest('categories.php?action=fetch_all');
                    if (response.success && response.categories) {
                        const categories = response.categories;

                        categoryContainers.forEach(container => {
                            if (!container) return;
                            container.innerHTML = '';
                            const fragment = document.createDocumentFragment();

                            categories.forEach((cat) => {
                                const catItem = document.createElement('div');
                                catItem.className = 'category-item';

                                const imageUrl = cat.imageUrl || `https://picsum.photos/200/200?random=${Math.random()}`;
                                catItem.style.backgroundImage = `url('${imageUrl}')`;

                                const catTitle = document.createElement('span');
                                catTitle.textContent = cat.name;

                                catItem.appendChild(catTitle);

                                catItem.onclick = function() {
                                    // When category is clicked, redirect to tag page
                                    const categoryPermalink = cat.name.trim().toLowerCase().replace(/ /g, '-');
                                    window.location.href = `/Spicette/tag/${encodeURIComponent(categoryPermalink)}`;
                                };
                                fragment.appendChild(catItem);
                            });
                            container.appendChild(fragment);
                        });
                    } else {
                        console.error('Failed to load categories:', response.message);
                        categoryContainers.forEach(container => {
                            if (container) container.innerHTML = '<p style="text-align: center; color: #767676;">Failed to load categories.</p>';
                        });
                    }
                } catch (error) {
                    console.error('Error fetching categories:', error);
                    categoryContainers.forEach(container => {
                        if (container) container.innerHTML = '<p style="text-align: center; color: #767676;">Network error when loading categories.</p>';
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
                        // For search results page, always load more if not displaying a separate search overlay
                        loadPins(pinsPerPage, true);
                    }
                }
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


            // --- User Profile Display & Notification functions ---
            const mobileProfileDropdown = document.getElementById('mobileProfileDropdown');
            const mobileDropdownUsernameDisplay = document.getElementById('mobileDropdownUsernameDisplay');
            const mobileDropdownMyProfile = document.getElementById('mobileDropdownMyProfile');
            const mobileDropdownAdminPanel = document.getElementById('mobileDropdownAdminPanel');
            const mobileDropdownLogout = document.getElementById('mobileDropdownLogout');
            const mobileDropdownClose = document.getElementById('mobileDropdownClose');

            function updateProfileDisplay() {
                const mobileProfileIcon = document.querySelector('.mobile-nav-item[data-action="profile"] .profile-icon');

                if (mobileProfileIcon) {
                    if (currentUser) {
                        mobileProfileIcon.textContent = currentUser.username.charAt(0).toUpperCase();
                        mobileProfileIcon.style.backgroundColor = '#fff';
                        mobileProfileIcon.style.color = '#111';
                    } else {
                        mobileProfileIcon.textContent = 'G';
                        mobileProfileIcon.style.backgroundColor = '#e0e0e0';
                        mobileProfileIcon.style.color = '#333';
                    }
                }
            }

            async function updateNotificationBadge() {
                try {
                    const response = await makeApiRequest('notifications.php?action=fetch_all');
                    if (response.success) {
                        const notifications = response.notifications || [];
                        const unreadCount = notifications.filter(notif => !notif.read).length;
                    }
                } catch (error) {
                    console.error('Failed to update notification badge:', error);
                }
            }

            // Pin functions (download) are generic and work as is.

            // Mobile Profile Dropdown event listeners (buttons inside the dropdown)
            mobileDropdownMyProfile.addEventListener('click', () => {
                if (currentUser) {
                    window.location.href = '/Spicette/user.php';
                }
                mobileProfileDropdown.style.display = 'none';
                document.body.style.overflow = '';
            });

            mobileDropdownAdminPanel.addEventListener('click', () => {
                if (currentUser && currentUser.isAdmin) {
                    window.location.href = '/Spicette/admin.php';
                } else {
                    showMessage('Access denied: Administrator privileges required.', 'Error');
                }
                mobileProfileDropdown.style.display = 'none';
                document.body.style.overflow = '';
            });

            mobileDropdownLogout.addEventListener('click', async () => {
                const response = await makeApiRequest('auth.php?action=logout', 'POST', null);
                if (response.success) {
                    currentUser = null;
                    updateProfileDisplay();
                    showMessage('Logout successful.', 'Logout');
                    window.location.href = '/Spicette/index.html'; // Redirect to home on logout
                } else {
                    showMessage('Logout failed: ' + response.message, 'Error');
                }
                mobileProfileDropdown.style.display = 'none';
                document.body.style.overflow = '';
            });

            mobileDropdownClose.addEventListener('click', () => {
                mobileProfileDropdown.style.display = 'none';
                document.body.style.overflow = '';
            });

            // --- Initialize App ---
            async function initializeApp() {
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
                updateNotificationBadge();

                // Fetch all pins initially for search suggestions (even if only current query pins are shown)
                const allPinsResponse = await makeApiRequest('pins.php?action=fetch_all');
                if (allPinsResponse.success) {
                    allPinsData = allPinsResponse.pins;
                } else {
                    console.error("Failed to load all pins on init:", allPinsResponse.message);
                }

                // Load pins for the current search query immediately
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
            }

            initializeApp();
        });
    </script>
</body>
</html>