<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
header('Content-Type: text/html; charset=UTF-8'); // Pastikan header ini ada untuk HTML
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.html?login=true'); // Redirect to index with login overlay
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
    <title>User Profile - Spicette</title>
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
            <button class="nav-button" data-nav="home" onclick="window.location.href='index.html'">Home</button>
            <button class="nav-button active">Profile</button>
        </div>

        <div class="search-container" style="flex-grow: 1;"></div>
        <div class="header-icons">
            <button class="icon-button" aria-label="User Profile" onclick="window.location.href='user.php'"><div class="profile-icon"><?php echo strtoupper(substr($username, 0, 1)); ?></div></button>
            <button class="icon-button" aria-label="Logout User" id="userLogoutBtnHeader">
                <svg viewBox="0 0 24 24" fill="#5f5f5f" width="24px" height="24px"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"></path></svg>
            </button>
        </div>
    </header>

    <main>
        <div class="user-page-container">
            <div class="user-page-header">
                <h1>User Profile</h1>
                <button class="user-logout-btn" id="userLogoutBtn">Logout</button>
            </div>

            <div id="message" style="display: none; margin-top: 15px; padding: 10px; border-radius: 5px; color: green; background-color: #e6ffe6; border: 1px solid green;"></div>

            <div class="user-profile-avatar">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            <div class="user-profile-info">
                <p>Welcome to your profile,</p>
                <strong><?php echo htmlspecialchars($username); ?></strong>
                <p>Status: <?php echo $isAdmin ? 'Administrator' : 'Regular User'; ?></p>
            </div>

            <div class="user-profile-actions">
                <button onclick="showMessage('Edit Profile feature is not yet available.', 'Feature')" class="secondary">Edit Profile</button>
                <?php if ($isAdmin): ?>
                    <button onclick="window.location.href='admin.php'">Go to Admin Panel</button>
                <?php endif; ?>
            </div>

            <div class="user-section">
                <h2>Your Pins</h2>
                <div class="pin-grid" id="userPinsGrid">
                    <p style="text-align: center; color: #767676;">Loading your pins...</p>
                </div>
                <div id="loading-indicator" style="display: none;">Loading more pins...</div>
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

            // --- API Base URL ---
            const API_BASE_URL = 'api/';

            // --- Helper Function for API Requests ---
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
                    showMessage(`Network or server error: ${error.message}`, true);
                    return { success: false, message: 'Network or server error.' };
                }
            }

            // --- Function to Display Messages ---
            // The showMessage function still uses a local div for the user page
            window.showMessage = function(msg, isError = false) {
                messageDiv.textContent = msg;
                messageDiv.className = isError ? 'error' : '';
                messageDiv.style.display = 'block';
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            }

            // --- User Logout Functionality ---
            async function handleUserLogout() {
                try {
                    const response = await makeApiRequest('auth.php?action=logout', 'POST', null); // Data is null
                    if (response.success) {
                        window.location.href = 'index.html';
                    } else {
                        showMessage('Logout failed: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error during logout process: ' + error.message, true);
                }
            }
            userLogoutBtn.addEventListener('click', handleUserLogout);
            userLogoutBtnHeader.addEventListener('click', handleUserLogout);

            // --- Load User's Posted Pins ---
            function createPinElement(pinData) {
                const pinDiv = document.createElement('div');
                pinDiv.classList.add('pin');
                pinDiv.dataset.id = pinData.id;

                const img = document.createElement('img');
                img.src = pinData.img;
                img.alt = 'Pin Image';
                img.onerror = function() {
                    this.onerror=null;
                    this.src='https://placehold.co/236x300/e0e0e0/767676?text=Image+Not+Found';
                };

                pinDiv.appendChild(img);
                // pinDiv.onclick = () => showMessage('Viewing your pin: ' + pinData.img); // Removed or replaced if detail is desired
                
                // If the user page also needs pin details, a separate openPinDetail function
                // or duplication of logic from index.html is needed. For now, I'll leave it empty
                // because the user did not request details on user.php.
                
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
                            userPinsGrid.innerHTML = '<p style="text-align: center; color: #767676; margin-top: 50px;">You haven\'t posted any pins yet.</p>';
                        } else if (pinsToDisplay.length === 0 && loadedPinsCount > 0) {
                             // All pins loaded
                        }
                    } else {
                        showMessage('Failed to load your pins: ' + response.message, 'Error');
                        userPinsGrid.innerHTML = '<p style="text-align: center; color: #e60023; margin-top: 50px;">Error loading pins. Please try again.</p>';
                    }
                } catch (error) {
                    showMessage('Error loading user pins: ' + error.message, true);
                    userPinsGrid.innerHTML = '<p style="text-align: center; color: #e60023; margin-top: 50px;">An error occurred. Please try again later.</p>';
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