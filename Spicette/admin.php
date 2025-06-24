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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Global Reset and Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f0f2f5; /* Light, airy background */
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }

        /* Header Styles */
        header {
            background-color: #ffffff;
            padding: 18px 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); /* Softer, wider shadow */
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid #e2e8f0;
        }

        .logo {
            color: #E60023; /* Logo text color */
            font-weight: 800; /* Extra-bold */
            font-size: 28px;
            cursor: pointer;
            letter-spacing: -0.5px;
            transition: transform 0.2s ease-out;
        }
        .logo:hover {
            transform: scale(1.02);
        }

        .header-nav-links {
            display: flex;
            margin-left: 40px;
            gap: 15px; /* Spacing between buttons */
        }

        .nav-button {
            background: none;
            border: none;
            padding: 10px 18px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16px;
            font-weight: 600; /* Semi-bold */
            color: #667085;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .nav-button:hover {
            background-color: #f0f2f5;
            color: #1a202c;
        }
        .nav-button.active {
            background-color: #E60023;
            color: white;
            box-shadow: 0 4px 10px rgba(230,0,35,0.2);
            font-weight: 700;
        }

        .search-container {
            flex-grow: 1;
            display: flex;
            justify-content: flex-end;
            margin-right: 30px;
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 15px; /* Spacing between icons */
        }

        .icon-button {
            background: #f0f2f5; /* Light background for icons */
            border: none;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667085;
            font-size: 18px;
        }
        .icon-button:hover {
            background-color: #e2e8f0;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .profile-icon {
            width: 38px;
            height: 38px;
            background-color: #5d5dff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 17px;
            box-shadow: 0 2px 10px rgba(93,93,255,0.3);
        }

        /* Main Dashboard Layout */
        main.admin-dashboard-layout {
            display: flex;
            flex: 1;
            padding: 30px; /* Overall padding for the main area */
            gap: 30px; /* Space between sidebar and content */
        }

        /* Sidebar Navigation */
        .admin-sidebar {
            width: 280px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 15px; /* Softer corners */
            box-shadow: 0 8px 30px rgba(0,0,0,0.05); /* Lighter, broader shadow */
            flex-shrink: 0;
            transition: transform 0.3s ease-in-out, width 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .admin-sidebar h2 {
            font-size: 26px;
            color: #1a202c;
            margin-bottom: 40px;
            text-align: center;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .admin-sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }
        .admin-sidebar-nav li {
            margin-bottom: 12px;
        }
        .admin-sidebar-nav button {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 16px 20px;
            border: none;
            background-color: transparent;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 17px;
            font-weight: 600;
            color: #555;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .admin-sidebar-nav button:hover {
            background-color: #f5f8fa; /* Very light hover */
            color: #1a202c;
            transform: translateX(5px);
        }
        .admin-sidebar-nav button.active {
            background-color: #E60023;
            color: white;
            box-shadow: 0 6px 18px rgba(230, 0, 35, 0.28);
            transform: translateX(0);
            font-weight: 700;
        }
        .admin-sidebar-nav button.active .fa-solid {
            color: white;
        }
        .admin-sidebar-nav button .fa-solid {
            margin-right: 15px;
            width: 22px;
            height: 22px;
            color: #8898aa;
            transition: color 0.2s ease;
        }
        .admin-sidebar-nav button:hover .fa-solid {
            color: #1a202c;
        }

        /* Content Area */
        .admin-content-area {
            flex-grow: 1;
            padding: 0; /* Padding handled by main.admin-dashboard-layout */
            overflow-y: auto;
            background-color: transparent; /* Seamless with body background */
        }

        .admin-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eef2f6;
        }

        .admin-page-header h1 {
            font-size: 38px;
            font-weight: 800;
            color: #1a202c;
            margin: 0;
            letter-spacing: -1px;
        }

        .admin-logout-btn {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(255,107,107,0.25);
        }
        .admin-logout-btn:hover {
            background-color: #e04f4f;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255,107,107,0.4);
        }

        /* Message Display */
        #message {
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: none;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: fadeInOut 4s forwards; /* Animation for fade in and out */
        }
        #message.error {
            background-color: #ffe0e0;
            color: #c0392b;
            border: 1px solid #ffb3b3;
        }
        #message:not(.error) {
            background-color: #e0fff0;
            color: #27ae60;
            border: 1px solid #b3ffcc;
        }
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-10px); }
            10% { opacity: 1; transform: translateY(0); }
            90% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); }
        }


        /* Tab Content Sections */
        .admin-tab-content {
            display: none;
            animation: fadeIn 0.6s ease-out;
        }
        .admin-tab-content.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .admin-section {
            background-color: #ffffff;
            padding: 35px;
            border-radius: 18px; /* Even softer corners */
            box-shadow: 0 10px 40px rgba(0,0,0,0.06); /* Broader, more subtle shadow */
            margin-bottom: 30px;
            border: 1px solid #eef2f6;
        }

        .admin-section h2 {
            font-size: 28px;
            color: #1a202c;
            margin-top: 0;
            margin-bottom: 28px;
            font-weight: 700;
            border-bottom: 1px solid #eef2f6;
            padding-bottom: 20px;
            letter-spacing: -0.5px;
        }
        .admin-section h3 {
            font-size: 24px;
            color: #333;
            margin-top: 40px;
            margin-bottom: 25px;
            font-weight: 600;
            letter-spacing: -0.2px;
        }

        /* Forms */
        .admin-form label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: #4a5568;
            font-size: 16px;
        }

        .admin-form input[type="text"],
        .admin-form input[type="url"],
        .admin-form textarea {
            width: 100%; /* Full width */
            padding: 16px;
            margin-bottom: 25px;
            border: 1px solid #cbd5e0; /* Softer border */
            border-radius: 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16px;
            color: #333;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
            background-color: #fbfdff; /* Slightly off-white background */
        }
        .admin-form input[type="text"]:focus,
        .admin-form input[type="url"]:focus,
        .admin-form textarea:focus {
            border-color: #E60023;
            box-shadow: 0 0 0 5px rgba(230, 0, 35, 0.2);
            outline: none;
            background-color: #ffffff;
        }

        .admin-form textarea {
            resize: vertical;
            min-height: 120px;
        }

        .admin-form button[type="submit"] {
            background-color: #E60023;
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 17px;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 6px 15px rgba(230,0,35,0.35);
        }
        .admin-form button[type="submit"]:hover {
            background-color: #b8001e;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(230,0,35,0.45);
        }

        /* List Items */
        .admin-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* Responsive grid for items */
            gap: 25px; /* Larger gap */
        }

        .list-item {
            display: flex;
            flex-direction: column; /* Stack image and content */
            background-color: #ffffff;
            padding: 25px;
            border-radius: 15px;
            border: 1px solid #eef2f6;
            box-shadow: 0 6px 20px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden; /* For image overflow */
        }
        .list-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }

        .list-item-img {
            width: 100%; /* Make image fill card width */
            height: 180px; /* Fixed height for consistency */
            border-radius: 12px;
            object-fit: cover; /* Ensure image covers area */
            margin-bottom: 15px;
            border: 1px solid #eef2f6;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .list-item-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            padding-bottom: 10px; /* Space for actions */
        }

        .list-item-content strong {
            font-size: 20px;
            color: #1a202c;
            margin-bottom: 8px;
            font-weight: 700;
            line-height: 1.3;
        }

        .list-item-content span {
            font-size: 14px;
            color: #667085;
            margin-bottom: 5px;
            display: block; /* Ensure each span is on new line */
        }
        .list-item-content span:last-of-type {
            margin-bottom: 0;
        }

        .list-item-actions {
            margin-top: auto; /* Push actions to the bottom */
            padding-top: 15px;
            border-top: 1px solid #f0f2f5;
            display: flex;
            justify-content: flex-end; /* Align to right */
            opacity: 0; /* Hide by default */
            transition: opacity 0.3s ease;
        }
        .list-item:hover .list-item-actions {
            opacity: 1; /* Show on hover */
        }

        .list-item-actions .delete-btn {
            background-color: #ff4d4f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(255,77,79,0.2);
        }
        .list-item-actions .delete-btn:hover:not(:disabled) {
            background-color: #e03f41;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255,77,79,0.3);
        }
        .list-item-actions .delete-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            opacity: 0.7;
            box-shadow: none;
        }

        /* Hamburger Menu Icon (Redesigned) */
        .hamburger-menu-icon {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            position: relative;
            width: 30px;
            height: 24px;
            transition: all 0.3s ease;
            z-index: 1100; /* Ensure it's above sidebar */
        }
        .hamburger-menu-icon span {
            display: block;
            position: absolute;
            height: 3px;
            width: 100%;
            background: #667085;
            border-radius: 5px;
            opacity: 1;
            left: 0;
            transform: rotate(0deg);
            transition: all 0.3s ease-in-out;
        }
        .hamburger-menu-icon span:nth-child(1) { top: 0px; }
        .hamburger-menu-icon span:nth-child(2) { top: 10px; }
        .hamburger-menu-icon span:nth-child(3) { top: 20px; }

        .hamburger-menu-icon.active span:nth-child(1) { top: 10px; transform: rotate(135deg); background: #E60023; }
        .hamburger-menu-icon.active span:nth-child(2) { opacity: 0; left: -60px; }
        .hamburger-menu-icon.active span:nth-child(3) { top: 10px; transform: rotate(-135deg); background: #E60023; }


        /* Custom Confirmation Modal */
        #customConfirmationModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7); /* Darker overlay */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            font-family: 'Plus Jakarta Sans', sans-serif;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
        }
        #customConfirmationModal.show {
            opacity: 1;
            visibility: visible;
        }
        #customConfirmationModal > div {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            max-width: 480px;
            text-align: center;
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.3s ease-in-out;
        }
        #customConfirmationModal.show > div {
            transform: scale(1);
            opacity: 1;
        }
        #customConfirmationModal p {
            font-size: 20px;
            margin-bottom: 35px;
            color: #333;
            font-weight: 600;
            line-height: 1.5;
        }
        #customConfirmationModal button {
            border: none;
            padding: 14px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 17px;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        #customConfirmationModal #confirmYes {
            background-color: #E60023;
            color: white;
            box-shadow: 0 4px 12px rgba(230,0,35,0.3);
        }
        #customConfirmationModal #confirmYes:hover {
            background-color: #b8001e;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(230,0,35,0.4);
        }
        #customConfirmationModal #confirmNo {
            background-color: #eef2f6;
            color: #4a5568;
            margin-left: 20px;
        }
        #customConfirmationModal #confirmNo:hover {
            background-color: #d8e2ed;
            transform: translateY(-2px);
        }

        /* Mobile specific styles */
        @media (max-width: 992px) { /* Adjust breakpoint for larger tablets/laptops */
            header {
                padding: 15px 25px;
            }
            .header-nav-links, .search-container {
                display: none;
            }
            .hamburger-menu-icon {
                display: block; /* Show hamburger icon */
                margin-left: auto;
            }
            main.admin-dashboard-layout {
                flex-direction: column;
                padding: 20px;
                gap: 20px;
            }
            .admin-sidebar {
                position: fixed;
                top: 75px; /* Adjust for header height */
                left: 0;
                height: calc(100vh - 75px);
                transform: translateX(-100%);
                z-index: 1050;
                box-shadow: 5px 0 20px rgba(0,0,0,0.25);
                width: 70%;
                padding-top: 20px;
                overflow-y: auto;
                border-radius: 0 15px 15px 0; /* Rounded only on right side */
            }
            .admin-sidebar.active {
                transform: translateX(0%);
            }
            .admin-sidebar h2 {
                font-size: 24px;
                margin-bottom: 30px;
            }
            .admin-sidebar-nav button {
                font-size: 16px;
                padding: 14px 20px;
            }
            .admin-sidebar-nav button .fa-solid {
                margin-right: 12px;
                width: 20px;
                height: 20px;
            }

            .admin-content-area {
                padding: 0;
            }
            .admin-page-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 30px;
                padding-bottom: 15px;
            }
            .admin-page-header h1 {
                font-size: 32px;
            }
            .admin-page-header .admin-logout-btn {
                margin-top: 15px;
                width: 100%;
                justify-content: center;
                padding: 10px 20px;
                font-size: 15px;
            }
            .admin-section {
                padding: 25px;
                margin-bottom: 25px;
            }
            .admin-section h2 {
                font-size: 24px;
                margin-bottom: 20px;
                padding-bottom: 15px;
            }
            .admin-section h3 {
                font-size: 20px;
                margin-top: 30px;
                margin-bottom: 20px;
            }
            .admin-form input, .admin-form textarea {
                padding: 14px;
                font-size: 15px;
            }
            .admin-form button[type="submit"] {
                padding: 12px 25px;
                font-size: 16px;
            }
            .admin-list {
                grid-template-columns: 1fr; /* Single column on small screens */
            }
            .list-item {
                padding: 20px;
            }
            .list-item-img {
                height: 150px;
            }
            .list-item-content strong {
                font-size: 18px;
            }
            .list-item-content span {
                font-size: 13px;
            }
            .list-item-actions {
                opacity: 1; /* Always show actions on mobile */
                justify-content: center;
                padding-top: 10px;
            }
            .list-item-actions .delete-btn {
                width: 100%;
                justify-content: center;
                padding: 9px 18px;
                font-size: 14px;
            }

            #customConfirmationModal > div {
                max-width: 90%;
                padding: 30px;
            }
            #customConfirmationModal p {
                font-size: 18px;
            }
            #customConfirmationModal button {
                padding: 10px 20px;
                font-size: 16px;
            }
        }

        @media (max-width: 576px) { /* Even smaller mobile */
            header {
                padding: 12px 15px;
            }
            .logo {
                font-size: 24px;
            }
            .icon-button {
                padding: 8px;
                font-size: 16px;
            }
            .profile-icon {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
            .hamburger-menu-icon {
                width: 26px;
                height: 20px;
            }
            .hamburger-menu-icon span {
                height: 2px;
            }
            .hamburger-menu-icon span:nth-child(2) { top: 9px; }
            .hamburger-menu-icon span:nth-child(3) { top: 18px; }
            .hamburger-menu-icon.active span:nth-child(1),
            .hamburger-menu-icon.active span:nth-child(3) { top: 9px; }

            main.admin-dashboard-layout {
                padding: 15px;
                gap: 15px;
            }
            .admin-sidebar {
                top: 60px;
                height: calc(100vh - 60px);
                width: 85%;
            }
            .admin-sidebar h2 {
                font-size: 22px;
                margin-bottom: 25px;
            }
            .admin-sidebar-nav button {
                font-size: 14px;
                padding: 10px 15px;
            }
            .admin-page-header h1 {
                font-size: 26px;
            }
            .admin-section {
                padding: 18px;
                margin-bottom: 18px;
            }
            .admin-section h2 {
                font-size: 20px;
                margin-bottom: 15px;
                padding-bottom: 10px;
            }
            .admin-section h3 {
                font-size: 17px;
                margin-top: 20px;
                margin-bottom: 15px;
            }
            .admin-form input, .admin-form textarea {
                padding: 10px;
                font-size: 13px;
                margin-bottom: 15px;
            }
            .admin-form label {
                font-size: 14px;
                margin-bottom: 8px;
            }
            .admin-form button[type="submit"] {
                padding: 10px 20px;
                font-size: 14px;
            }
            .list-item {
                padding: 15px;
            }
            .list-item-img {
                height: 120px;
            }
            .list-item-content strong {
                font-size: 16px;
            }
            .list-item-content span {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo" onclick="window.location.href='index.html'">Spicette</div>
        <div class="header-nav-links">
            <button class="nav-button" data-nav="home" onclick="window.location.href='index.html'">Home</button>
            <button class="nav-button active">Admin Panel</button>
        </div>

        <div class="search-container"></div> <!-- Empty for now, but ready for future search input -->
        <div class="header-icons">
            <button class="icon-button" aria-label="Admin Profile" onclick="window.location.href='admin.php'"><div class="profile-icon">A</div></button>
            <button class="icon-button" aria-label="Logout Admin" id="adminLogoutBtnHeader">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
            <button class="hamburger-menu-icon" aria-label="Menu" id="hamburgerMenuButton">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <main class="admin-dashboard-layout">
        <aside class="admin-sidebar" id="adminSidebar">
            <h2>Dashboard</h2>
            <nav class="admin-sidebar-nav">
                <ul>
                    <li>
                        <button class="admin-tab-button active" data-tab="notifications">
                            <i class="fa-solid fa-bell"></i>
                            Notifications
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="categories">
                            <i class="fa-solid fa-layer-group"></i>
                            Categories
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="users">
                            <i class="fa-solid fa-users"></i>
                            Users
                        </button>
                    </li>
                    <li>
                        <button class="admin-tab-button" data-tab="pins">
                            <i class="fa-solid fa-thumbtack"></i>
                            Pins
                        </button>
                    </li>
                </ul>
            </nav>
        </aside>

        <section class="admin-content-area" id="adminContentArea">
            <div class="admin-page-header">
                <h1>Admin Dashboard</h1>
                <button class="admin-logout-btn" id="adminLogoutBtnContent">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </button>
            </div>

            <div id="message"></div>

            <div id="notificationsTabContent" class="admin-tab-content active">
                <div class="admin-section">
                    <h2>Notification Settings</h2>
                    <form class="admin-form" id="addNotificationForm">
                        <label for="newNotificationText">New Notification Text:</label>
                        <textarea id="newNotificationText" placeholder="Enter new notification text" required></textarea>
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
                // Reset and re-add class to restart animation if message comes quickly
                messageDiv.classList.remove('fadeInOut');
                void messageDiv.offsetWidth; // Trigger reflow
                messageDiv.classList.add('fadeInOut');

                // The animation handles hiding, so no need for setTimeout here unless for non-animated fallback
                // For non-animated fallback or if animation ends, display: none is handled by the animation itself.
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
                    if (window.innerWidth <= 992) { /* Use updated breakpoint */
                        adminSidebar.classList.remove('active');
                        hamburgerMenuButton.classList.remove('active'); /* Deactivate hamburger icon */
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
                hamburgerMenuButton.classList.toggle('active');
            });

            // Close sidebar if clicked outside (on mobile)
            document.addEventListener('click', (event) => {
                const isClickInsideSidebar = adminSidebar.contains(event.target);
                const isClickOnHamburger = hamburgerMenuButton.contains(event.target);

                if (window.innerWidth <= 992 && adminSidebar.classList.contains('active') && !isClickInsideSidebar && !isClickOnHamburger) {
                    adminSidebar.classList.remove('active');
                    hamburgerMenuButton.classList.remove('active');
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
                            <button class="delete-btn" data-id="${notif.id}"><i class="fa-solid fa-trash-can"></i> Delete</button>
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
                showCustomConfirmation('Are you sure you want to delete this notification?', async () => {
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
                });
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
                        <img src="${category.imageUrl || 'https://placehold.co/700x400/e0e0e0/767676?text=No+Category+Image'}" alt="Category Image" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/700x400/e0e0e0/767676?text=No+Category+Image';">
                        <div class="list-item-content">
                            <strong>${category.name}</strong>
                            <span>Image URL: ${category.imageUrl || 'N/A'}</span>
                        </div>
                        <div class="list-item-actions">
                            <button class="delete-btn" data-name="${category.name}"><i class="fa-solid fa-trash-can"></i> Delete</button>
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
                showCustomConfirmation(`Are you sure you want to delete category "${name}"?`, async () => {
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
                });
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
                        deleteButtonHtml = `<button class="delete-btn" data-username="${user.username}"><i class="fa-solid fa-trash-can"></i> Delete</button>`;
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
                showCustomConfirmation(`Are you sure you want to delete user ${username} and all their saved pins?`, async () => {
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
                });
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
                        <img src="${pin.img}" alt="Pin Image" class="list-item-img" onerror="this.onerror=null;this.src='https://placehold.co/700x400/e0e0e0/767676?text=No+Pin+Image';">
                        <div class="list-item-content">
                            <strong>ID: ${pin.id}</strong>
                            <span>Source: ${pin.source || 'N/A'}</span>
                            <span>Uploaded by: ${pin.uploadedBy || 'N/A'}</span>
                            <span>Title: ${pin.title || 'N/A'}</span>
                        </div>
                        <div class="list-item-actions">
                            <button class="delete-btn" data-id="${pin.id}"><i class="fa-solid fa-trash-can"></i> Delete</button>
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
                showCustomConfirmation(`Are you sure you want to delete pin ${pinId}?`, async () => {
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
                });
            }

            // Custom Confirmation Modal (replacing window.confirm)
            function showCustomConfirmation(message, onConfirm) {
                const modalId = 'customConfirmationModal';
                let modal = document.getElementById(modalId);
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = modalId;
                    modal.innerHTML = `
                        <div>
                            <p>${message}</p>
                            <div style="display: flex; justify-content: center; gap: 15px;">
                                <button id="confirmYes">Yes</button>
                                <button id="confirmNo">No</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                } else {
                    modal.querySelector('p').textContent = message;
                }

                modal.classList.add('show');

                const confirmYes = modal.querySelector('#confirmYes');
                const confirmNo = modal.querySelector('#confirmNo');

                const newConfirmYes = confirmYes.cloneNode(true);
                const newConfirmNo = confirmNo.cloneNode(true);
                confirmYes.parentNode.replaceChild(newConfirmYes, confirmYes);
                confirmNo.parentNode.replaceChild(newConfirmNo, confirmNo);

                newConfirmYes.onclick = () => {
                    modal.classList.remove('show');
                    onConfirm();
                };
                newConfirmNo.onclick = () => {
                    modal.classList.remove('show');
                };
            }

            // Initial load for admin panel (load default tab)
            fetchNotifications();
        });
    </script>
</body>
</html>
