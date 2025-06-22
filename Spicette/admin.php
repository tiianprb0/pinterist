<?php
// ENSURE NO CHARACTERS BEFORE THIS LINE
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header('Location: index.html?login=true'); // Redirect to index with login overlay
    exit();
}
$adminUsername = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spicette Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f9f9f9;
            color: #333;
        }
        header {
             box-shadow: none !important;
        }

        /* Dashboard Layout */
        .admin-dashboard-layout {
            display: flex;
            min-height: calc(100vh - 70px); /* Adjust for header height */
        }

        /* Sidebar Navigation */
        .admin-sidebar {
            width: 250px;
            background-color: #fff;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            flex-shrink: 0;
            transition: transform 0.3s ease-in-out;
        }
        .admin-sidebar h2 {
            font-size: 24px;
            color: #111;
            margin-bottom: 30px;
            text-align: center;
        }
        .admin-sidebar-nav ul {
            list-style: none;
            padding: 0;
        }
        .admin-sidebar-nav li {
            margin-bottom: 10px;
        }
        .admin-sidebar-nav button {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 12px 15px;
            border: none;
            background-color: transparent;
            font-size: 16px;
            font-weight: bold;
            color: #555;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .admin-sidebar-nav button:hover {
            background-color: #f0f0f0;
            color: #111;
        }
        .admin-sidebar-nav button.active {
            background-color: #E60023;
            color: white;
        }
        .admin-sidebar-nav button.active svg {
            fill: white;
        }
        .admin-sidebar-nav button svg {
            margin-right: 10px;
            width: 20px;
            height: 20px;
            fill: #767676;
            transition: fill 0.2s ease;
        }
        .admin-sidebar-nav button:hover svg {
            fill: #111;
        }

        /* Main Content Area */
        .admin-content-area {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
        }

        /* Hamburger menu for mobile */
        .hamburger-menu-icon {
            display: none; /* Hidden by default on desktop */
            margin-left: 15px; /* Adjust spacing */
        }

        /* Mobile specific styles for sidebar */
        @media (max-width: 768px) {
            .admin-dashboard-layout {
                flex-direction: column;
            }
            .admin-sidebar {
                position: fixed;
                top: 70px; /* Below header */
                left: 0;
                height: calc(100vh - 70px);
                transform: translateX(-100%);
                z-index: 1050;
                box-shadow: 5px 0 15px rgba(0,0,0,0.2);
            }
            .admin-sidebar.active {
                transform: translateX(0%);
            }
            .hamburger-menu-icon {
                display: flex !important; /* Show hamburger icon */
            }
            .admin-page-header .admin-logout-btn {
                margin-left: auto; /* Push logout to right */
            }
            .admin-content-area {
                padding: 15px; /* Smaller padding on mobile */
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo" onclick="window.location.href='index.html'"></div>
        <div class="header-nav-links">
            <button class="nav-button" data-nav="home" onclick="window.location.href='index.html'">Home</button>
            <button class="nav-button active">Admin Panel</button>
        </div>

        <div class="search-container" style="flex-grow: 1;"></div>
        <div class="header-icons">
            <button class="icon-button" aria-label="Admin Profile" onclick="window.location.href='admin.php'"><div class="profile-icon">A</div></button>
            <button class="icon-button" aria-label="Logout Admin" id="adminLogoutBtnHeader">
                <svg viewBox="0 0 24 24" fill="#5f5f5f" width="24px" height="24px"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"></path></svg>
            </button>
            <button class="icon-button hamburger-menu-icon" aria-label="Menu" id="hamburgerMenuButton">
                <svg viewBox="0 0 24 24" fill="#5f5f5f" width="24px" height="24px"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"></path></svg>
            </button>
        </div>
    </header>

    <main class="admin-dashboard-layout">
        <aside class="admin-sidebar" id="adminSidebar">
            <h2>Spicette Admin</h2>
            <nav class="admin-sidebar-nav">
                <ul>
                    <li>
                        <button class="admin-tab-button active" data-tab="notifications">
                            <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"></path></svg>
                            Notifications
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="categories">
                            <svg viewBox="0 0 24 24" fill="#000000"><path d="M12 2L2 6v12l10 4 10-4V6L12 2zm0 2.56L18.77 7 12 9.44 5.23 7 12 4.56zM4 8.78l7 2.8v8.44L4 17.22V8.78zm16 8.44l-7 2.8V11.58l7-2.8v8.44z"></path></svg>
                            Categories
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="users">
                            <svg viewBox="0 0 24 24" fill="#000000"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
                            Users
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="pins">
                            <svg viewBox="0 0 24 24" fill="#000000"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm-3.88 12.06c-.45.45-1.2.45-1.65 0-.45-.45-.45-1.2 0-1.65L10.3 9.48 8.89 8.07c-.39-.39-.39-1.02 0-1.41.39-.39 1.02-.39 1.41 0l2.12 2.12c.39.39.39 1.02 0 1.41L11.5 12.22l-1.38 1.84zM13 9V3.5L18.5 9H13z"></path></svg>
                            Pins
                        </button>
                    </li>
                </ul>
            </nav>
        </aside>

        <section class="admin-content-area" id="adminContentArea">
            <div class="admin-page-header">
                <h1>Admin Dashboard</h1>
                <button class="admin-logout-btn" id="adminLogoutBtnContent">Logout</button>
            </div>

            <div id="message"></div>

            <div id="notificationsTabContent" class="admin-tab-content active">
                <div class="admin-section">
                    <h2>Notification Settings</h2>
                    <form class="admin-form" id="addNotificationForm">
                        <label for="newNotificationText">New Notification Text:</label>
                        <textarea id="newNotificationText" placeholder="Enter new notification text"></textarea>
                        <button type="submit">Add Notification</button>
                    </form>
                    <h3>Active Notifications</h3>
                    <div class="admin-list" id="notificationList">
                        <p style="text-align: center; color: #767676;">Loading notifications...</p>
                    </div>
                </div>
            </div>

            <div id="categoriesTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Category Management</h2>
                    <form class="admin-form" id="addCategoryForm">
                        <label for="newCategoryName">New Category Name:</label>
                        <input type="text" id="newCategoryName" placeholder="Example: Graphic Design" required>
                        <label for="newCategoryImageUrl">Category Image URL (optional):</label>
                        <input type="url" id="newCategoryImageUrl" placeholder="Example: https://picsum.photos/200/200">
                        <button type="submit">Add Category</button>
                    </form>
                    <h3>Active Categories</h3>
                    <div class="admin-list" id="categoryList">
                        <p style="text-align: center; color: #767676;">Loading category list...</p>
                    </div>
                </div>
            </div>

            <div id="usersTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>User Management</h2>
                    <div class="admin-list" id="userList">
                        <p style="text-align: center; color: #767676;">Loading user list...</p>
                    </div>
                </div>
            </div>

            <div id="pinsTabContent" class="admin-tab-content">
                <div class="admin-section">
                    <h2>Pin Management</h2>
                    <div class="admin-list" id="pinList">
                        <p style="text-align: center; color: #767676;">Loading pin list...</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userList = document.getElementById('userList');
            const pinList = document.getElementById('pinList');
            const notificationList = document.getElementById('notificationList');
            const messageDiv = document.getElementById('message');
            const adminLogoutBtnHeader = document.getElementById('adminLogoutBtnHeader');
            const adminLogoutBtnContent = document.getElementById('adminLogoutBtnContent'); // New logout button in content area

            const addNotificationForm = document.getElementById('addNotificationForm');
            const newNotificationText = document.getElementById('newNotificationText');

            const addCategoryForm = document.getElementById('addCategoryForm');
            const newCategoryName = document.getElementById('newCategoryName');
            const newCategoryImageUrl = document.getElementById('newCategoryImageUrl');
            const categoryList = document.getElementById('categoryList');

            const adminUsername = '<?php echo $adminUsername; ?>';

            // Admin tab elements
            const adminTabButtons = document.querySelectorAll('.admin-tab-button');
            const adminTabContents = document.querySelectorAll('.admin-tab-content');
            const hamburgerMenuButton = document.getElementById('hamburgerMenuButton');
            const adminSidebar = document.getElementById('adminSidebar');

            // --- API Base URL ---
            const API_BASE_URL = 'api/';

            // --- Helper Function for API Requests ---
            async function makeApiRequest(endpoint, method = 'GET', data = null) {
                try {
                    const options = { method };
                    if (data !== null && typeof data !== 'undefined' && method !== 'GET') {
                        options.headers = { 'Content-Type': 'application/json' };
                        options.body = JSON.stringify(data);
                    } else if (method === 'POST' && (data === null || typeof data === 'undefined')) {
                        // No need to remove options.headers; just leave it if there's no body.
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
            function showMessage(msg, isError = false) {
                messageDiv.textContent = msg;
                messageDiv.className = isError ? 'error' : '';
                messageDiv.style.display = 'block';
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            }

            // --- Admin Logout Functionality ---
            async function handleAdminLogout() {
                try {
                    const response = await makeApiRequest('auth.php?action=logout', 'POST', null); 
                    if (response.success) {
                        window.location.href = 'index.html';
                    } else {
                        showMessage('Logout failed: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error during logout process: ' + error.message, true);
                }
            }
            adminLogoutBtnHeader.addEventListener('click', handleAdminLogout);
            adminLogoutBtnContent.addEventListener('click', handleAdminLogout);


            // --- Admin Tab Functionality ---
            adminTabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;

                    // Remove active class from all buttons and contents
                    adminTabButtons.forEach(btn => btn.classList.remove('active'));
                    adminTabContents.forEach(content => content.classList.remove('active'));

                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab + 'TabContent').classList.add('active');

                    // Hide sidebar on mobile after selection
                    if (window.innerWidth <= 768) {
                        adminSidebar.classList.remove('active');
                    }

                    // Reload data for the selected tab
                    switch(targetTab) {
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
                    }
                });
            });

            // Hamburger menu toggle for mobile
            hamburgerMenuButton.addEventListener('click', () => {
                adminSidebar.classList.toggle('active');
            });

            // Close sidebar if clicked outside (on mobile)
            document.addEventListener('click', (event) => {
                const isClickInsideSidebar = adminSidebar.contains(event.target);
                const isClickOnHamburger = hamburgerMenuButton.contains(event.target);

                if (window.innerWidth <= 768 && adminSidebar.classList.contains('active') && !isClickInsideSidebar && !isClickOnHamburger) {
                    adminSidebar.classList.remove('active');
                }
            });

            // --- Notification Management ---
            async function fetchNotifications() {
                notificationList.innerHTML = '<p style="text-align: center; color: #767676;">Loading notifications...</p>';
                try {
                    const response = await makeApiRequest('notifications.php?action=fetch_all');
                    if (response.success) {
                        renderNotificationList(response.notifications);
                    } else {
                        showMessage('Failed to load notifications: ' + response.message, true);
                        notificationList.innerHTML = `<p style="text-align: center; color: red;">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Error fetching notification list: ' + error.message, true);
                    notificationList.innerHTML = `<p style="text-align: center; color: red;">${error.message}</p>`;
                }
            }

            function renderNotificationList(notifications) {
                notificationList.innerHTML = '';
                if (notifications.length === 0) {
                    notificationList.innerHTML = '<p style="text-align: center; color: #767676;">No active notifications.</p>';
                    return;
                }

                notifications.forEach(notif => {
                    const notifItem = document.createElement('div');
                    notifItem.classList.add('list-item');
                    notifItem.innerHTML = `
                        <div class="list-item-content">
                            <strong>${notif.text}</strong>
                            <span>ID: ${notif.id}</span>
                            <span>Added: ${notif.timestamp || 'N/A'}</span>
                            <span>Status: ${notif.read ? 'Read' : 'Unread'}</span>
                        </div>
                        <div class="list-item-actions">
                            <button class="delete-btn" data-id="${notif.id}">Delete</button>
                        </div>
                    `;
                    const deleteBtn = notifItem.querySelector('.delete-btn');
                    if (deleteBtn) {
                         deleteBtn.onclick = () => deleteNotification(notif.id);
                    }
                    notificationList.appendChild(notifItem);
                });
            }

            addNotificationForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const text = newNotificationText.value.trim();
                if (!text) {
                    showMessage('Notification text cannot be empty.', true);
                    return;
                }

                try {
                    const response = await makeApiRequest('notifications.php?action=add', 'POST', { text });
                    if (response.success) {
                        showMessage('Notification added successfully!', false);
                        newNotificationText.value = '';
                        fetchNotifications();
                    } else {
                        showMessage('Failed to add notification: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error adding notification: ' + error.message, true);
                }
            });

            async function deleteNotification(id) {
                if (!confirm(`Are you sure you want to delete this notification?`)) {
                    return;
                }
                try {
                    const response = await makeApiRequest('notifications.php?action=delete', 'POST', { id });
                    if (response.success) {
                        showMessage('Notification deleted successfully!', false);
                        fetchNotifications();
                    } else {
                        showMessage('Failed to delete notification: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error deleting notification: ' + error.message, true);
                }
            }

            // --- Category Management ---
            async function fetchCategoriesAdmin() {
                categoryList.innerHTML = '<p style="text-align: center; color: #767676;">Loading category list...</p>';
                try {
                    const response = await makeApiRequest('categories.php?action=fetch_all');
                    if (response.success) {
                        renderCategoryList(response.categories);
                    } else {
                        showMessage('Failed to load categories: ' + response.message, true);
                        categoryList.innerHTML = `<p style="text-align: center; color: red;">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Error fetching category list: ' + error.message, true);
                    categoryList.innerHTML = `<p style="text-align: center; color: red;">${error.message}</p>`;
                }
            }

            function renderCategoryList(categories) {
                categoryList.innerHTML = '';
                if (categories.length === 0) {
                    categoryList.innerHTML = '<p style="text-align: center; color: #767676;">No categories.</p>';
                    return;
                }
                categories.forEach(category => {
                    const categoryItem = document.createElement('div');
                    categoryItem.classList.add('list-item');
                    categoryItem.innerHTML = `
                        <img src="${category.imageUrl || 'https://placehold.co/60x60/e0e0e0/767676?text=No+Image'}" alt="Category Image" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/60x60/e0e0e0/767676?text=No+Image';">
                        <div class="list-item-content">
                            <strong>${category.name}</strong>
                            <span>Image URL: ${category.imageUrl || 'N/A'}</span>
                        </div>
                        <div class="list-item-actions">
                            <button class="delete-btn" data-name="${category.name}">Delete</button>
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
                const categoryImageUrl = newCategoryImageUrl.value.trim();
                if (!categoryName) {
                    showMessage('Category name cannot be empty.', true);
                    return;
                }
                try {
                    const response = await makeApiRequest('categories.php?action=add', 'POST', { name: categoryName, imageUrl: categoryImageUrl });
                    if (response.success) {
                        showMessage('Category added successfully!', false);
                        newCategoryName.value = '';
                        newCategoryImageUrl.value = '';
                        fetchCategoriesAdmin();
                    } else {
                        showMessage('Failed to add category: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error adding category: ' + error.message, true);
                }
            });

            async function deleteCategory(name) {
                if (!confirm(`Are you sure you want to delete category "${name}"?`)) {
                    return;
                }
                try {
                    const response = await makeApiRequest('categories.php?action=delete', 'POST', { name });
                    if (response.success) {
                        showMessage('Category deleted successfully!', false);
                        fetchCategoriesAdmin();
                    } else {
                        showMessage('Failed to delete category: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error deleting category: ' + error.message, true);
                }
            }


            // --- User Management ---
            async function fetchUsers() {
                userList.innerHTML = '<p style="text-align: center; color: #767676;">Loading user list...</p>';
                try {
                    const response = await makeApiRequest('pins.php?action=fetch_all_users');
                    if (response.success) {
                        renderUserList(response.users);
                    } else {
                        showMessage('Failed to load users: ' + response.message, true);
                        userList.innerHTML = `<p style="text-align: center; color: red;">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Error fetching user list: ' + error.message, true);
                    userList.innerHTML = `<p style="text-align: center; color: red;">${error.message}</p>`;
                }
            }

            function renderUserList(users) {
                userList.innerHTML = '';
                if (users.length === 0) {
                    userList.innerHTML = '<p style="text-align: center; color: #767676;">No registered users.</p>';
                    return;
                }

                users.forEach(user => {
                    const userItem = document.createElement('div');
                    userItem.classList.add('list-item');
                    
                    let deleteButtonHtml = '';
                    if (user.username === adminUsername) {
                        deleteButtonHtml = `<button class="delete-btn" disabled title="You cannot delete your own account">You</button>`;
                    } else if (user.isAdmin) {
                         deleteButtonHtml = `<button class="delete-btn" disabled title="Cannot delete another admin">Admin</button>`;
                    } else {
                        deleteButtonHtml = `<button class="delete-btn" data-username="${user.username}">Delete</button>`;
                    }

                    userItem.innerHTML = `
                        <div class="list-item-content">
                            <strong>${user.username}</strong>
                            <span>Status: ${user.isAdmin ? 'Admin' : 'Regular User'}</span>
                        </div>
                        <div class="list-item-actions">
                            ${deleteButtonHtml}
                        </div>
                    `;
                    const deleteBtn = userItem.querySelector('.delete-btn');
                    if (deleteBtn && !deleteBtn.disabled) { // Only attach click listener if not disabled
                         deleteBtn.onclick = () => deleteUser(user.username);
                    }
                    userList.appendChild(userItem);
                });
            }

            async function deleteUser(username) {
                if (!confirm(`Are you sure you want to delete user ${username} and all their saved pins?`)) {
                    return;
                }
                try {
                    const response = await makeApiRequest('pins.php?action=delete_user', 'POST', { username });
                    if (response.success) {
                        showMessage(`User ${username} deleted successfully!`, false);
                        fetchUsers();
                    } else {
                        showMessage('Failed to delete user: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error deleting user: ' + error.message, true);
                }
            }

            // --- Pin Management (Delete only for admin here) ---
            async function fetchPinsForAdmin() {
                pinList.innerHTML = '<p style="text-align: center; color: #767676;">Loading pin list...</p>';
                try {
                    const response = await makeApiRequest('pins.php?action=fetch_all');
                    if (response.success) {
                        renderAdminPinList(response.pins);
                    } else {
                        showMessage('Failed to load pins: ' + response.message, true);
                        pinList.innerHTML = `<p style="text-align: center; color: red;">${response.message}</p>`;
                    }
                } catch (error) {
                    showMessage('Error fetching pin list: ' + error.message, true);
                    pinList.innerHTML = `<p style="text-align: center; color: red;">${error.message}</p>`;
                }
            }

            function renderAdminPinList(pins) {
                pinList.innerHTML = '';
                if (pins.length === 0) {
                    pinList.innerHTML = '<p style="text-align: center; color: #767676;">No pins available.</p>';
                    return;
                }

                pins.forEach(pin => {
                    const pinItem = document.createElement('div');
                    pinItem.classList.add('list-item');
                    pinItem.innerHTML = `
                        <img src="${pin.img}" alt="Pin Image" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/60x60/e0e0e0/767676?text=No+Image';">
                        <div class="list-item-content">
                            <strong>ID: ${pin.id}</strong>
                            <span>Source: ${pin.source || 'N/A'}</span>
                            <span>Uploaded by: ${pin.uploadedBy || 'N/A'}</span>
                            <span>Title: ${pin.title || 'N/A'}</span>
                        </div>
                        <div class="list-item-actions">
                            <button class="delete-btn" data-id="${pin.id}">Delete</button>
                        </div>
                    `;
                    const deleteBtn = pinItem.querySelector('.delete-btn');
                    if (deleteBtn) {
                         deleteBtn.onclick = () => deletePin(pin.id);
                    }
                    pinList.appendChild(pinItem);
                });
            }

            async function deletePin(pinId) {
                if (!confirm(`Are you sure you want to delete pin ${pinId}?`)) {
                    return;
                }
                try {
                    const response = await makeApiRequest('pins.php?action=delete_pin', 'POST', { pinId });
                    if (response.success) {
                        showMessage('Pin deleted successfully!', false);
                        fetchPinsForAdmin();
                    } else {
                        showMessage('Failed to delete pin: ' + response.message, true);
                    }
                } catch (error) {
                    showMessage('Error deleting pin: ' + error.message, true);
                }
            }

            // Initial load for admin panel (load default tab)
            fetchNotifications();
        });
    </script>
</body>
</html>