<?php

include 'config.php';
if(isset($_GET['lat'])){
?>
<?php
// Get coordinates from URL parameters
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 27.7172; // Default to Kathmandu
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : 85.3240; // Default to Kathmandu
$zoom = isset($_GET['zoom']) ? intval($_GET['zoom']) : 15; // Default zoom level

// Validate coordinates
if ($lat < -90 || $lat > 90) {
    $lat = 27.7172;
}
if ($lng < -180 || $lng > 180) {
    $lng = 85.3240;
}
if ($zoom < 1 || $zoom > 20) {
    $zoom = 15;
}

$location_name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Selected Location';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Location - <?php echo $location_name; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            overflow: hidden;
        }

        .navbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #6366f1;
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: #64748b;
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            color: #334155;
            background-color: #f1f5f9;
        }

        .nav-link.active {
            color: #6366f1;
            background-color: #eef2ff;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1000;
        }

        .header h1 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .header p {
            opacity: 0.9;
            margin-top: 0.15rem;
            font-size: 0.9rem;
        }

        #map {
            height: calc(100vh - 125px);
            width: 100%;
            position: relative;
        }

        .info-panel {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 1.5rem;
            min-width: 300px;
            max-width: 400px;
        }

        .info-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .info-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e293b;
        }

        .coordinate-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .coordinate-item:last-child {
            border-bottom: none;
        }

        .coordinate-label {
            font-weight: 500;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .coordinate-value {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            background: #f8fafc;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            color: #1e293b;
            font-weight: 500;
        }

        .action-buttons {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .loading.hidden {
            display: none;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .zoom-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .zoom-btn {
            width: 40px;
            height: 40px;
            background: white;
            border: none;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #667eea;
            transition: all 0.2s;
        }

        .zoom-btn:hover {
            background: #f8fafc;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .info-panel {
                bottom: 10px;
                right: 10px;
                left: 10px;
                min-width: auto;
                max-width: none;
                width: calc(100% - 20px);
            }
            
            .zoom-controls {
                right: 10px;
                top: 10px;
            }
            
            .navbar {
                padding: 0 1rem;
            }
            
            .header {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">📢 Aawaz आवाज</div>
        <ul class="navbar-nav">
            <li><a href="index.php" class="nav-link">🏠 Home</a></li>
            <li><a href="#" class="nav-link">📢 Campaigns</a></li>
            <li><a href="#" class="nav-link">📈 Dashboard</a></li>
            <li><a href="#" class="nav-link">🏆 Leaderboard</a></li>
            <li><a href="notice.php" class="nav-link">🔔 Notices</a></li>
            <li><a href="admin.php" class="nav-link">⚙️ Admin</a></li>
        </ul>
    </nav>

    <div class="header">
        <h1><i class="fas fa-map-marker-alt"></i> Location Map</h1>
        <p>Viewing location: <?php echo $location_name; ?></p>
    </div>

    <div id="map"></div>

    <div class="info-panel">
        <div class="info-header">
            <div class="info-icon">
                <i class="fas fa-map-pin"></i>
            </div>
            <div class="info-title"><?php echo $location_name; ?></div>
        </div>

        <div class="coordinate-item">
            <div class="coordinate-label">
                <i class="fas fa-compass"></i>
                Latitude
            </div>
            <div class="coordinate-value" id="lat-display"><?php echo number_format($lat, 6); ?></div>
        </div>

        <div class="coordinate-item">
            <div class="coordinate-label">
                <i class="fas fa-compass"></i>
                Longitude
            </div>
            <div class="coordinate-value" id="lng-display"><?php echo number_format($lng, 6); ?></div>
        </div>

        <div class="coordinate-item">
            <div class="coordinate-label">
                <i class="fas fa-search-plus"></i>
                Zoom Level
            </div>
            <div class="coordinate-value" id="zoom-display"><?php echo $zoom; ?></div>
        </div>

        <div class="action-buttons">
            <button class="btn btn-primary" onclick="copyCoordinates()">
                <i class="fas fa-copy"></i> Copy Coords
            </button>
            <button class="btn btn-secondary" onclick="shareLocation()">
                <i class="fas fa-share"></i> Share
            </button>
            <button class="btn btn-success" onclick="openInGoogleMaps()">
                <i class="fas fa-external-link-alt"></i> Google Maps
            </button>
        </div>
    </div>

    <div class="zoom-controls">
        <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">
            <i class="fas fa-plus"></i>
        </button>
        <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">
            <i class="fas fa-minus"></i>
        </button>
        <button class="zoom-btn" onclick="centerMap()" title="Center Map">
            <i class="fas fa-crosshairs"></i>
        </button>
    </div>

    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Loading map...</p>
    </div>

    <script>
        let map;
        let marker;
        const targetLat = <?php echo $lat; ?>;
        const targetLng = <?php echo $lng; ?>;
        const targetZoom = <?php echo $zoom; ?>;

        function initMap() {
            const targetLocation = { lat: targetLat, lng: targetLng };
            
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: targetZoom,
                center: targetLocation,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true,
                styles: [
                    {
                        featureType: "administrative",
                        elementType: "labels",
                        stylers: [{ visibility: "on" }]
                    },
                    {
                        featureType: "poi",
                        elementType: "labels",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "road",
                        elementType: "labels.icon",
                        stylers: [{ visibility: "on" }]
                    },
                    {
                        featureType: "water",
                        elementType: "geometry",
                        stylers: [{ color: "#e9e9e9" }, { lightness: 17 }]
                    },
                    {
                        featureType: "landscape",
                        elementType: "geometry",
                        stylers: [{ color: "#f5f5f5" }, { lightness: 20 }]
                    }
                ]
            });

            // Create red arrow marker using Google Maps default red marker
            marker = new google.maps.Marker({
                position: targetLocation,
                map: map,
                title: '<?php echo addslashes($location_name); ?>',
                animation: google.maps.Animation.DROP
            });

            // Create info window
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 1rem; max-width: 300px;">
                        <h3 style="margin: 0 0 0.5rem 0; color: #1e293b;"><?php echo addslashes($location_name); ?></h3>
                        <p style="margin: 0 0 0.75rem 0; color: #64748b;">
                            <strong>Coordinates:</strong><br>
                            Lat: ${targetLat.toFixed(6)}<br>
                            Lng: ${targetLng.toFixed(6)}
                        </p>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button onclick="copyCoordinates()" style="background: #3b82f6; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                                📋 Copy Coords
                            </button>
                            <button onclick="openInGoogleMaps()" style="background: #10b981; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                                🗺️ Google Maps
                            </button>
                        </div>
                    </div>
                `
            });

            // Open info window on marker click
            marker.addListener('click', function() {
                infoWindow.open(map, marker);
            });

            // Update coordinates display when map moves
            map.addListener('center_changed', function() {
                const center = map.getCenter();
                document.getElementById('lat-display').textContent = center.lat().toFixed(6);
                document.getElementById('lng-display').textContent = center.lng().toFixed(6);
            });

            // Update zoom display when zoom changes
            map.addListener('zoom_changed', function() {
                document.getElementById('zoom-display').textContent = map.getZoom();
            });

            document.getElementById('loading').classList.add('hidden');
        }

        function zoomIn() {
            const currentZoom = map.getZoom();
            map.setZoom(currentZoom + 1);
        }

        function zoomOut() {
            const currentZoom = map.getZoom();
            map.setZoom(currentZoom - 1);
        }

        function centerMap() {
            map.setCenter({ lat: targetLat, lng: targetLng });
            map.setZoom(targetZoom);
        }

        function copyCoordinates() {
            const coords = `${targetLat}, ${targetLng}`;
            navigator.clipboard.writeText(coords).then(function() {
                // Show success feedback
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = '#10b981';
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = '#3b82f6';
                }, 2000);
            }).catch(function() {
                alert('Coordinates: ' + coords);
            });
        }

        function shareLocation() {
            const url = window.location.href;
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($location_name); ?>',
                    text: 'Check out this location on the map',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url).then(function() {
                    alert('Location URL copied to clipboard!');
                }).catch(function() {
                    alert('Share URL: ' + url);
                });
            }
        }

        function openInGoogleMaps() {
            const url = `https://www.google.com/maps?q=${targetLat},${targetLng}&z=${targetZoom}`;
            window.open(url, '_blank');
        }

        // Initialize map when page loads
        window.initMap = initMap;
    </script>

    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA04g5uPfBSXraUtweYOmYfrTwI9dQK7S8&callback=initMap"></script>
</body>
</html>
<?php
}
else{

$problems = [];

try {
    $sql = "SELECT id, user_id, title, description, category, photo_url, 
                   location_name, latitude, longitude, status, priority,
                   views_count, likes_count, created_at, updated_at, 
                   identity, comments_count
            FROM problem_posts 
            WHERE latitude IS NOT NULL 
            AND longitude IS NOT NULL 
            AND latitude != 0
            AND longitude != 0
            ORDER BY created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $problems[] = $row;
        }
    }
    
} catch(Exception $e) {
    error_log("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problem Reports Map</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1000;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .header p {
            opacity: 0.9;
            margin-top: 0.25rem;
        }

        #map {
            height: calc(100vh - 80px);
            width: 100%;
            position: relative;
        }

       .map-controls {
    position: absolute;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    padding: 2rem;
    min-width: 320px;
    max-width: 400px;
}

.legend {
    margin-bottom: 2rem;
}

.legend h3 {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    color: #333;
    font-weight: 600;
}

.legend-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    font-size: 1rem;
    font-weight: 500;
}

.legend-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin-right: 1rem;
    border: 3px solid white;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

.filter-controls {
    border-top: 2px solid #eee;
    padding-top: 2rem;
}

.filter-controls h3 {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    color: #333;
    font-weight: 600;
}

.filter-checkbox {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    font-size: 1rem;
    font-weight: 500;
}

.filter-checkbox input {
    margin-right: 1rem;
    transform: scale(1.3);
}

.filter-checkbox label {
    cursor: pointer;
}


       .info-window {
    max-width: 600px;
    min-width: 500px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.popup-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    margin: -15px -15px 15px -15px;
    border-radius: 12px 12px 0 0;
}

.popup-title {
    font-weight: 600;
    font-size: 1.4rem;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.popup-category {
    font-size: 1rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
}

.popup-category i {
    margin-right: 0.5rem;
    font-size: 1.1rem;
}

.popup-image {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: transform 0.2s;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.popup-image:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.popup-description {
    margin-bottom: 1rem;
    color: #555;
    line-height: 1.6;
    font-size: 1rem;
    padding: 0.5rem;
}

.popup-user {
    display: flex;
    align-items: center;
    margin-bottom: 0;
    font-size: 1rem;
    color: #666;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    font-weight: 500;
}

.popup-user i {
    margin-right: 0.75rem;
    color: #667eea;
    font-size: 1.1rem;
}

        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .loading.hidden {
            display: none;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
        }

        .modal-image {
            max-width: 100%;
            max-height: 100%;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0.5rem;
        }

        .location-button {
            position: absolute;
            top: 90px;
            right: 10px;
            z-index: 1000;
            background: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .location-button:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .location-button i {
            color: #667eea;
            font-size: 1.1rem;
        }
@media (max-width: 768px) {
    .map-controls {
        right: 10px;
        bottom: 10px;
        padding: 1.5rem;
        min-width: 280px;
        max-width: 320px;
    }
    
    .legend h3, .filter-controls h3 {
        font-size: 1.1rem;
    }
    
    .legend-item, .filter-checkbox {
        font-size: 0.9rem;
    }
}

 .navbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #6366f1;
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: #64748b;
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            color: #334155;
            background-color: #f1f5f9;
        }

        .nav-link.active {
            color: #6366f1;
            background-color: #eef2ff;
        }

        .map-controls {
   position: absolute;
   bottom: 20px;
   right: 20px;
   z-index: 1000;
   background: white;
   border-radius: 12px;
   box-shadow: 0 4px 20px rgba(0,0,0,0.15);
   padding: 1.5rem;
   min-width: 280px;
   max-width: 320px;
}

.legend h3, .filter-controls h3 {
   font-size: 1rem;
   margin-bottom: 0.75rem;
   color: #333;
   font-weight: 600;
}

.legend-item, .filter-checkbox {
   margin-bottom: 0.5rem;
   font-size: 0.85rem;
   font-weight: 500;
}

.filter-controls {
   border-top: 2px solid #eee;
   padding-top: 1.5rem;
}

.info-window {
   max-width: 300px;
   min-width: 280px;
   font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.popup-header {
   background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
   color: white;
   padding: 1rem;
   margin: -10px -10px 10px -10px;
   border-radius: 8px 8px 0 0;
}

.popup-title {
   font-weight: 600;
   font-size: 1.1rem;
   margin-bottom: 0.25rem;
   line-height: 1.2;
}

.popup-category {
   font-size: 0.85rem;
   opacity: 0.9;
   display: flex;
   align-items: center;
}

.popup-image {
   width: 100%;
   height: 180px;
   object-fit: cover;
   border-radius: 6px;
   margin-bottom: 0.75rem;
   cursor: pointer;
   transition: transform 0.2s;
   box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.popup-description {
   margin-bottom: 0.75rem;
   color: #555;
   line-height: 1.5;
   font-size: 0.9rem;
   padding: 0.25rem;
   max-height: 60px;
   overflow-y: auto;
}

.popup-user {
   display: flex;
   align-items: center;
   margin-bottom: 0;
   font-size: 0.85rem;
   color: #666;
   padding: 0.75rem;
   background: #f8f9fa;
   border-radius: 6px;
   font-weight: 500;
}

.header {
   background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
   color: white;
   padding: 0.75rem 2rem;
   box-shadow: 0 2px 10px rgba(0,0,0,0.1);
   position: relative;
   z-index: 1000;
}

.header h1 {
   font-size: 1.25rem;
   font-weight: 600;
}

.header p {
   opacity: 0.9;
   margin-top: 0.15rem;
   font-size: 0.9rem;
}

#map {
   height: calc(100vh - 125px);
   width: 100%;
   position: relative;
}
    </style>
</head>
<body>
     <nav class="navbar">
        <div class="navbar-brand"> 📢 Aawaz आवाज</div>
        <ul class="navbar-nav">
            <li><a href="index.php" class="nav-link">🏠 Home</a></li>
            <li><a href="campaign.php" class="nav-link">📢 Campaigns</a></li>
            <li><a href="map.php" class="nav-link active">📈 Map</a></li>
            <li><a href="notice.php" class="nav-link">🔔 Notices</a></li>
            <li><a href="admin.php" class="nav-link">⚙️ Admin</a></li>
        </ul>
    </nav>
    <div class="header">
        <h1><i class="fas fa-map-marked-alt"></i> Problem Reports Map</h1>
        <p>View and track reported issues in your area</p>
    </div>

    <div id="map"></div>

    <div class="map-controls">
        <div class="legend">
            <h3><i class="fas fa-palette"></i> Problem Categories</h3>
            <div class="legend-item">
                <div class="legend-dot" style="background-color: #e53e3e;"></div>
                <span>Safety Issues</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background-color: #3182ce;"></div>
                <span>Water Problems</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background-color: #38a169;"></div>
                <span>Infrastructure</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background-color: #d69e2e;"></div>
                <span>Environmental</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background-color: #805ad5;"></div>
                <span>Other</span>
            </div>
        </div>

        <div class="filter-controls">
            <h3><i class="fas fa-filter"></i> Filter by Category</h3>
            <div class="filter-checkbox">
                <input type="checkbox" id="filter-safety" checked>
                <label for="filter-safety">Safety Issues</label>
            </div>
            <div class="filter-checkbox">
                <input type="checkbox" id="filter-water" checked>
                <label for="filter-water">Water Problems</label>
            </div>
            <div class="filter-checkbox">
                <input type="checkbox" id="filter-infrastructure" checked>
                <label for="filter-infrastructure">Infrastructure</label>
            </div>
            <div class="filter-checkbox">
                <input type="checkbox" id="filter-environmental" checked>
                <label for="filter-environmental">Environmental</label>
            </div>
            <div class="filter-checkbox">
                <input type="checkbox" id="filter-other" checked>
                <label for="filter-other">Other</label>
            </div>
        </div>
    </div>

    <button class="location-button" onclick="centerOnUserLocation()" title="Center on my location">
        <i class="fas fa-crosshairs"></i>
    </button>

    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Loading map and problem reports...</p>
    </div>

    <div id="imageModal" class="modal">
        <button class="modal-close" onclick="closeImageModal()">&times;</button>
        <div class="modal-content">
            <img id="modalImage" class="modal-image" src="" alt="Problem Image">
        </div>
    </div>

    <script>
        let map;
        let markers = [];
        let userLocation = null;
        let infoWindows = [];

        const categoryConfig = {
            'safety': { color: '#e53e3e', icon: 'warning' },
            'water': { color: '#3182ce', icon: 'water_drop' },
            'infrastructure': { color: '#38a169', icon: 'construction' },
            'environmental': { color: '#d69e2e', icon: 'eco' },
            'other': { color: '#805ad5', icon: 'help' }
        };

        const problemData = <?php echo json_encode($problems); ?>;

    function initMap() {
            const defaultCenter = { lat: 27.7172, lng: 85.3240 };
            
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                center: defaultCenter,
                styles: [
                    {
                        featureType: "administrative",
                        elementType: "labels",
                        stylers: [{ visibility: "on" }]
                    },
                    {
                        featureType: "poi",
                        elementType: "labels",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "poi.business",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "road",
                        elementType: "labels.icon",
                        stylers: [{ visibility: "on" }]
                    },
                    {
                        featureType: "road.local",
                        elementType: "labels",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "transit",
                        elementType: "labels",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "water",
                        elementType: "geometry",
                        stylers: [{ color: "#e9e9e9" }, { lightness: 17 }]
                    },
                    {
                        featureType: "landscape",
                        elementType: "geometry",
                        stylers: [{ color: "#f5f5f5" }, { lightness: 20 }]
                    }
                ]
            });

            getUserLocation();
            loadProblemMarkers();
            setupFilters();
            document.getElementById('loading').classList.add('hidden');
        }


        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        
                        map.setCenter(userLocation);
                        map.setZoom(13);
                        
                        new google.maps.Marker({
                            position: userLocation,
                            map: map,
                            title: "Your Location",
                            icon: {
                                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="8" fill="#667eea" stroke="white" stroke-width="2"/>
                                        <circle cx="12" cy="12" r="3" fill="white"/>
                                    </svg>
                                `),
                                scaledSize: new google.maps.Size(24, 24),
                                anchor: new google.maps.Point(12, 12)
                            }
                        });
                    },
                    function(error) {
                        console.log("Location access denied or unavailable:", error);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            }
        }

        function centerOnUserLocation() {
            if (userLocation) {
                map.setCenter(userLocation);
                map.setZoom(15);
            } else {
                getUserLocation();
            }
        }

        function loadProblemMarkers() {
            problemData.forEach(problem => {
                createProblemMarker(problem);
            });
        }

        function createProblemMarker(problem) {
            const category = problem.category ? problem.category.toLowerCase() : 'other';
            const config = categoryConfig[category] || categoryConfig['other'];
            
            const marker = new google.maps.Marker({
                position: {
                    lat: parseFloat(problem.latitude),
                    lng: parseFloat(problem.longitude)
                },
                map: map,
                title: problem.title,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30">
                            <circle cx="15" cy="15" r="12" fill="${config.color}" stroke="white" stroke-width="3"/>
                            <text x="15" y="20" text-anchor="middle" fill="white" font-size="10" font-family="Arial">!</text>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(50, 50),
                    anchor: new google.maps.Point(15, 15)
                },
                animation: google.maps.Animation.DROP
            });

            const infoWindow = new google.maps.InfoWindow({
                content: createInfoWindowContent(problem),
                maxWidth: 450
            });

            marker.addListener('click', function() {
                infoWindows.forEach(iw => iw.close());
                infoWindow.open(map, marker);
            });

            marker.problemData = problem;
            markers.push(marker);
            infoWindows.push(infoWindow);
        }

        function createInfoWindowContent(problem) {
            const config = categoryConfig[problem.category ? problem.category.toLowerCase() : 'other'] || categoryConfig['other'];
            
            return `
                <div class="info-window">
                    <div class="popup-header">
                        <div class="popup-title">${problem.title || 'Problem Report'}</div>
                        <div class="popup-category">
                            <i class="fas fa-circle" style="color: ${config.color};"></i>
                            ${problem.category ? problem.category.charAt(0).toUpperCase() + problem.category.slice(1) : 'Other'}
                        </div>
                    </div>
                    
                    ${problem.photo_url ? `
                        <img src="uploads/${problem.photo_url}" 
                             alt="${problem.title || 'Problem Image'}" 
                             class="popup-image"
                             onclick="openImageModal('${problem.photo_url}', '${problem.title || 'Problem Image'}')">
                    ` : '<div style="height: 200px; background: #f8f9fa; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #999; margin-bottom: 0.75rem;"><i class="fas fa-image" style="font-size: 2rem;"></i></div>'}
                    
                    <div class="popup-description">${problem.description || 'No description available'}</div>
                    
                    <div class="popup-user">
                        <i class="fas fa-user"></i>
                        <span>Reported by: User #${problem.user_id || 'Unknown'}</span>
                    </div>
                </div>
            `;
        }

        function formatDate(dateString) {
            if (!dateString) return 'Unknown';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric' 
            });
        }

        function setupFilters() {
            const filterCheckboxes = document.querySelectorAll('.filter-checkbox input');
            filterCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', filterMarkers);
            });
        }

        function filterMarkers() {
            const activeFilters = [];
            
            document.querySelectorAll('.filter-checkbox input:checked').forEach(checkbox => {
                const category = checkbox.id.replace('filter-', '');
                activeFilters.push(category);
            });

            markers.forEach(marker => {
                const problemCategory = marker.problemData.category ? marker.problemData.category.toLowerCase() : 'other';
                if (activeFilters.includes(problemCategory)) {
                    marker.setVisible(true);
                } else {
                    marker.setVisible(false);
                }
            });
        }

        function openImageModal(imageUrl, title) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            
            modalImage.src = 'uploads/'+imageUrl;
            modalImage.alt = title;
            modal.style.display = 'block';
            
            modal.onclick = function(event) {
                if (event.target === modal) {
                    closeImageModal();
                }
            };
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        });

        window.initMap = initMap;
    </script>

    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA04g5uPfBSXraUtweYOmYfrTwI9dQK7S8&callback=initMap"></script>
</body>
</html>


<?php } ?>