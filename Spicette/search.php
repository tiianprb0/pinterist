<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM BARIS INI
// PASTIKAN FILE INI DI-ENCODE SEBAGAI UTF-8 TANPA BOM
header('Content-Type: text/html; charset=UTF-8');
session_start();

// Include utilities for JSON file operations
require_once 'api/utils.php'; //

// Path to pins.json
$pinsFile = 'data/pins.json'; //

// Helper function to read all pins
function getAllPinsForSearch() {
    global $pinsFile;
    return readJsonFile($pinsFile); //
}

// Get search query from URL
$searchQuery = $_GET['query'] ?? null; //

// Filter pins based on search query
$filteredPins = []; //
$allPins = getAllPinsForSearch(); //
$queryLower = strtolower($searchQuery); //

if ($allPins && !empty($searchQuery)) { //
    foreach ($allPins as $pin) { //
        $match = false; //
        // Search in title
        if (isset($pin['title']) && stripos($pin['title'], $queryLower) !== false) { //
            $match = true; //
        }
        // Search in description (formerly content)
        if (!$match && isset($pin['description']) && stripos($pin['description'], $queryLower) !== false) { //
            $match = true; //
        }
        // Search in single category field
        if (!$match && isset($pin['category']) && is_string($pin['category']) && stripos($pin['category'], $queryLower) !== false) { //
            $match = true; //
        }
        // Search in individual image descriptions
        if (!$match && isset($pin['images']) && is_array($pin['images'])) { //
            foreach ($pin['images'] as $image) { //
                if (isset($image['description']) && stripos($image['description'], $queryLower) !== false) { //
                    $match = true; //
                    break; //
                }
            }
        }

        if ($match) { //
            $filteredPins[] = $pin; //
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>" - Spicette</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* This section mirrors styles from style.css for .pin-grid and .pin */
        /* and also combines some specific overrides from tag.php */

        .search-page-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px; /* Aligned with .pin-grid padding in style.css */
        }
        .search-page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .search-page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            color: #111;
            margin-bottom: 10px;
        }
        .search-page-header p {
            font-size: 16px;
            color: #555;
        }
        .search-pins-grid {
            column-count: 5; /* Default for desktop, same as .pin-grid */
            column-gap: 15px; /* Same as .pin-grid */
            padding: 0; /* Keep this as 0, as .search-page-container already handles padding */
            display: column; /* Ensure masonry layout, consistent with .pin-grid */
        }
        .search-pin-item {
            display: inline-block;
            width: 100%;
            margin-bottom: 15px;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .search-pin-item img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 16px;
        }
        .search-pin-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0) 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 12px;
        }
        .search-pin-item:hover .search-pin-overlay {
            opacity: 1;
        }
        .search-pin-overlay-top {
            display: flex;
            justify-content: flex-end;
        }
        .search-pin-overlay-bottom {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .search-pin-title {
            color: white;
            font-size: 14px;
            font-weight: bold;
            text-shadow: 0 1px 3px rgba(0,0,0,0.7);
            margin-bottom: 5px;
        }
        .search-pin-info {
            color: white;
            font-size: 12px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.7);
            width: 100%;
        }
        .search-pin-info a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .search-pin-bottom-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-top: 5px;
        }
        .search-pin-action-icon {
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            padding: 0;
            transition: background-color 0.2s ease;
        }
        .search-pin-action-icon .fas {
            font-size: 18px;
            color: #111;
        }

        /* Image Count Overlay (already in style.css, but ensure it applies) */
        .pin-image-count-overlay {
            background-color: rgba(0, 0, 0, 0.2);
            color: white;
            border-radius: 8px;
            padding: 5px 8px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
            z-index: 10;
            margin-left: auto;
        }
        .pin-image-count-overlay .fa-camera {
            font-size: 0.8em;
        }

        @media (max-width: 768px) {
            .search-pins-grid {
                column-count: 2;
                column-gap: 10px;
                padding: 0 10px;
            }
            .search-pin-item {
                margin-bottom: 10px;
            }
        }
        @media (max-width: 480px) {
            .search-pins-grid {
                column-count: 1;
            }
            .search-page-header h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo" onclick="window.location.href='../index.html'"></div>
        <div class="header-nav-links">
            <button class="nav-button" onclick="window.location.href='../index.html'">Home</button>
            <button class="nav-button" onclick="window.location.href='../create.html'">Create</button>
            <button class="nav-button" onclick="window.location.href='../index.html?tab=saved'">Saved</button>
        </div>
        <div class="search-container" style="flex-grow: 1;"></div>
        <div class="header-icons">
            <button class="icon-button" aria-label="Notifications"><i class="fas fa-bell"></i></button>
            <button class="icon-button" aria-label="Profile"><div class="profile-icon">G</div></button>
            <button class="icon-button" aria-label="More accounts"><i class="fas fa-caret-down"></i></button>
        </div>
    </header>

    <main class="search-page-container">
        <div class="search-page-header">
            <h1>Hasil Pencarian untuk "<?php echo htmlspecialchars($searchQuery); ?>"</h1>
            <p>Ditemukan <?php echo count($filteredPins); ?> pin.</p>
        </div>

        <div class="search-pins-grid">
            <?php if (!empty($filteredPins)): ?>
                <?php foreach ($filteredPins as $pin): ?>
                    <div class="search-pin-item" onclick="window.location.href='../index.html?pin=<?php echo htmlspecialchars($pin['id']); ?>'">
                        <?php 
                        $imageUrl = isset($pin['images'][0]['url']) ? htmlspecialchars($pin['images'][0]['url']) : 'https://placehold.co/250x350/cccccc/000000?text=No+Image';
                        ?>
                        <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($pin['title'] ?? 'Pin Image'); ?>" onerror="this.onerror=null;this.src='https://placehold.co/250x350/cccccc/000000?text=Image+Error';">
                        <div class="search-pin-overlay">
                            <div class="search-pin-overlay-top">
                                <?php if (isset($pin['images']) && count($pin['images']) > 1): ?>
                                    <div class="pin-image-count-overlay">
                                        <i class="fas fa-camera"></i>
                                        <span><?php echo count($pin['images']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="search-pin-overlay-bottom">
                                <div class="search-pin-title"><?php echo htmlspecialchars($pin['title'] ?? 'Untitled'); ?></div>
                                <div class="search-pin-bottom-actions">
                                    <div class="search-pin-info">
                                        <?php if (isset($pin['category'])): ?>
                                            <a href="tag/<?php echo urlencode(strtolower(str_replace(' ', '-', $pin['category']))); ?>"><?php echo htmlspecialchars($pin['category']); ?></a>
                                        <?php else: ?>
                                            No Category
                                        <?php endif; ?>
                                    </div>
                                    <button class="search-pin-action-icon" onclick="event.stopPropagation(); downloadPin('<?php echo $imageUrl; ?>');">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #767676; grid-column: 1 / -1;">Tidak ada pin ditemukan untuk pencarian "<?php echo htmlspecialchars($searchQuery); ?>".</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Spicette. All rights reserved.</p>
    </footer>

    <script>
        function downloadPin(imageUrl) {
            const link = document.createElement('a');
            link.href = imageUrl;
            const filename = imageUrl.substring(imageUrl.lastIndexOf('/') + 1) || 'pin_image.jpg';
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            alert(`Mengunduh pin: ${filename}`); // Use alert for simplicity, replace with custom modal if needed
        }
    </script>
</body>
</html>