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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

    <div id="pinDetailOverlay">
        <div class="pin-detail-content">
            <div class="pin-detail-back-container">
                <button class="pin-detail-back-button" onclick="closePinDetail()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
            </div>

            <div class="pin-header-info">
                <h2 id="pinDetailTitle"></h2>
                <div class="uploaded-by-and-share">
                    <p class="uploaded-by-text">oleh <strong id="pinDetailUploadedBy"></strong></p>
                    <button id="pinDetailShareButton" class="secondary">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                </div>
            </div>

            <div class="pin-detail-img-main-container">
                <button id="pinDetailImageSaveButton" class="pin-save-button image-overlay-button">Save</button>
                </div>

            <p id="pinDetailDescription" class="pin-detail-description"></p>
            <div class="dashed-line"></div>
            <div id="pinDetailCategories" class="pin-categories"></div>

        </div>
    </div>

    <div id="customAlert" class="custom-alert"></div>

    <div id="fullImageOverlay" class="full-image-overlay">
        <button class="close-full-image-button" onclick="window.closeFullImageOverlay()">&times;</button>
        <img id="fullImageDisplay" src="" alt="Full Size Image">
        <button id="fullImageDownloadButton" class="download-button-on-image">
            <i class="fas fa-download"></i> Download
        </button>
    </div>

    <main class="tag-page-layout">
        <div class="back-button-container">
            <button class="back-button" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>
        <section class="search-page-header">
            <h1>Search results for "<?php echo htmlspecialchars($searchQuery); ?>"</h1>
            <p id="searchResultsCount"></p>
        </section>
        <div class="pin-grid" id="pinGrid">
            <p style="text-align: center; color: #767676;">Loading pins...</p>
        </div>
        <div id="loading-indicator">Loading more pins...</div>
    </main>

    <script>
        let currentUser = null;
        let currentSearchQuery = '<?php echo htmlspecialchars($searchQuery); ?>';
        let allPinsData = []; // Store all pins data for direct access by ID
        let loadedPinsCount = 0;
        let pinsPerPage = 20;

        const API_BASE_URL = 'api/';

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

        const pinDetailOverlay = document.getElementById('pinDetailOverlay');
        const pinDetailImageMainContainer = document.querySelector('.pin-detail-img-main-container');
        const pinDetailTitle = document.getElementById('pinDetailTitle');
        const pinDetailUploadedBy = document.getElementById('pinDetailUploadedBy');
        const pinDetailDescription = document.getElementById('pinDetailDescription');
        const pinDetailCategories = document.getElementById('pinDetailCategories');
        const pinDetailImageSaveButton = document.getElementById('pinDetailImageSaveButton');
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
                        window.location.href = 'login.html';
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

        function getCorrectedImagePath(originalPath) {
            if (originalPath.startsWith('./uploads/pins/')) {
                return originalPath;
            }
            if (originalPath.startsWith('uploads/pins/')) {
                return './' + originalPath;
            }
            return originalPath;
        }

        function openPinDetail(pinData) {
            pinDetailTitle.textContent = pinData.title || 'Untitled';
            pinDetailUploadedBy.textContent = ` ${pinData.uploadedBy || 'Anonim'}`;

            if (pinData.description) {
                pinDetailDescription.textContent = pinData.description;
                pinDetailDescription.style.display = 'block';
            } else {
                pinDetailDescription.textContent = '';
                pinDetailDescription.style.display = 'none';
            }
            
            pinDetailImageMainContainer.innerHTML = '';
            pinDetailImageMainContainer.appendChild(pinDetailImageSaveButton);

            if (pinData.images && pinData.images.length > 0) {
                if (pinData.display_type === 'slider' && pinData.images.length > 1) {
                    const sliderWrapper = document.createElement('div');
                    sliderWrapper.className = 'slider-wrapper';
                    const swipeInner = document.createElement('div');
                    swipeInner.className = 'swipe-inner';

                    pinData.images.forEach((image, index) => {
                        const slide = document.createElement('div');
                        slide.className = 'slider-slide';
                        const imgElement = document.createElement('img');
                        imgElement.src = getCorrectedImagePath(image.url);
                        imgElement.alt = `${pinData.title} - ${index + 1}`;
                        imgElement.onerror = function() {
                            this.onerror=null;
                            this.src='https://placehold.co/500x700/cccccc/000000?text=Image+Load+Error';
                        };
                        imgElement.onclick = (e) => {
                            e.stopPropagation();
                            window.openFullImageOverlay(imgElement.src);
                        };
                        slide.appendChild(imgElement);
                        swipeInner.appendChild(slide);
                    });
                    sliderWrapper.appendChild(swipeInner);
                    pinDetailImageMainContainer.appendChild(sliderWrapper);
                    setupTouchSlider(sliderWrapper);
                } else {
                    pinData.images.forEach(image => {
                        const imgDiv = document.createElement('div');
                        imgDiv.className = 'stacked-image-item';
                        const imgElement = document.createElement('img');
                        imgElement.src = getCorrectedImagePath(image.url);
                        imgElement.alt = pinData.title;
                        imgElement.onerror = function() {
                            this.onerror=null;
                            this.src='https://placehold.co/500x700/cccccc/000000?text=Image+Load+Error';
                        };
                        imgElement.onclick = (e) => {
                            e.stopPropagation();
                            window.openFullImageOverlay(imgElement.src);
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
                const noImage = document.createElement('img');
                noImage.src = 'https://placehold.co/500x700/cccccc/000000?text=No+Image';
                noImage.alt = 'Tidak Ada Gambar';
                noImage.onclick = (e) => {
                    e.stopPropagation();
                    window.openFullImageOverlay(noImage.src);
                };
                pinDetailImageMainContainer.appendChild(noImage);
            }

            updatePinDetailImageSaveButton(pinData);

            pinDetailCategories.innerHTML = '';
            if (pinData.category) {
                const span = document.createElement('span');
                span.classList.add('pin-category-tag');
                const categoryPermalink = pinData.category.trim().toLowerCase().replace(/ /g, '-');
                span.innerHTML = `<a href="tag.php?category=${encodeURIComponent(categoryPermalink)}" style="color: inherit; text-decoration: none;">#${pinData.category.trim()}</a>`;
                pinDetailCategories.appendChild(span);
                pinDetailCategories.style.display = 'flex';
            } else {
                pinDetailCategories.style.display = 'none';
            }

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

            pinDetailOverlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('pin', pinData.id);
            history.pushState({ pinId: pinData.id }, '', newUrl.toString());
        }

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

        function setupTouchSlider(slider) {
            if (!slider) return;

            let touchStartX = 0;
            let touchEndX = 0;
            let isDragging = false;
            const minMovementThreshold = 10;

            slider.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                isDragging = false;
            }, { passive: true });

            slider.addEventListener('touchmove', (e) => {
                if (Math.abs(e.changedTouches[0].screenX - touchStartX) > minMovementThreshold) {
                    isDragging = true;
                }
            }, { passive: true });

            slider.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                if (isDragging) {
                    e.stopPropagation();
                }
            }, { passive: true });
        }

        const fullImageOverlay = document.getElementById('fullImageOverlay');
        const fullImageDisplay = document.getElementById('fullImageDisplay');
        const fullImageDownloadButton = document.getElementById('fullImageDownloadButton');

        window.openFullImageOverlay = function(imageUrl) {
            fullImageDisplay.src = imageUrl;
            fullImageDisplay.onerror = function() {
                this.onerror=null;
                this.src='https://placehold.co/1000x1200/cccccc/000000?text=Image+Load+Error';
            };
            fullImageOverlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            fullImageDownloadButton.onclick = (e) => {
                e.stopPropagation();
                downloadPin(imageUrl);
            };
        };

        window.closeFullImageOverlay = function() {
            fullImageOverlay.style.display = 'none';
            document.body.style.overflow = '';
        };

        const pinGrid = document.getElementById('pinGrid');
        const loadingIndicator = document.getElementById('loading-indicator');
        const searchResultsCountElement = document.getElementById('searchResultsCount'); // Get the new element

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
                 link.href = `tag.php?category=${encodeURIComponent(pinData.category.trim().toLowerCase().replace(/ /g, '-'))}`;
                 link.target = '_blank';
                 link.textContent = pinData.category;
                 link.onclick = (e) => e.stopPropagation();
                 infoDiv.appendChild(link);
            } else {
                infoDiv.textContent = 'Tanpa Kategori';
                infoDiv.style.opacity = '0.7';
            }
            bottomActions.appendChild(infoDiv);

            const downloadButton = document.createElement('button');
            downloadButton.classList.add('pin-action-icon');
            downloadButton.innerHTML = `<i class="fas fa-download"></i>`;
            downloadButton.onclick = (e) => { e.stopPropagation(); downloadPin(firstImageUrl); };
            
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
            const response = await makeApiRequest(`pins.php?action=search&query=${encodeURIComponent(currentSearchQuery)}`);
            loadingIndicator.style.display = 'none';

            if (response.success) {
                allPinsData = response.pins || [];

                if (allPinsData.length > 0) {
                    searchResultsCountElement.textContent = `Ditemukan ${allPinsData.length} pin.`;
                } else {
                    searchResultsCountElement.textContent = `Tidak ada pin ditemukan.`;
                }

                const startIndex = append ? loadedPinsCount : 0;
                const pinsToDisplay = allPinsData.slice(startIndex, startIndex + count);

                if (!append) {
                    pinGrid.innerHTML = '';
                    loadedPinsCount = 0;
                }

                const fragment = document.createDocumentFragment();
                pinsToDisplay.forEach(pinData => {
                    const pinElement = createPinElement(pinData);
                    fragment.appendChild(pinElement);
                    loadedPinsCount++;
                });
                pinGrid.appendChild(fragment);

                if (allPinsData.length === 0) {
                    pinGrid.innerHTML = `<p style="text-align: center; color: #767676; margin-top: 50px;">Tidak ada pin ditemukan untuk "${currentSearchQuery}".</p>`;
                }
            } else {
                showMessage('Gagal memuat pin: ' + response.message, 'error');
                pinGrid.innerHTML = '<p style="text-align: center; color: #e60023; margin-top: 50px;">Error memuat pin. Silakan coba lagi nanti.</p>';
            }
        }

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

            const urlParams = new URLSearchParams(window.location.search);
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

        window.addEventListener('scroll', () => {
            if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 400 && loadingIndicator.style.display === 'none') {
                loadPins(pinsPerPage, true);
            }
        });
    </script>
</body>
</html>