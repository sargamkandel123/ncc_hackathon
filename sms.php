<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Nearby - Send Local Messages</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        .header h1 {
            margin: 0;
            color: white;
            font-size: 2em;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .container {
            flex: 1;
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .location-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #e8f5e8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .location-info button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
        }
        #map {
            height: 200px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .compose-area {
            display: flex;
            flex-direction: column;
        }
        .compose-area label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        #message {
            width: 100%;
            height: 120px;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            resize: vertical;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        #message:focus {
            border-color: #667eea;
            outline: none;
        }
        .char-count {
            text-align: right;
            color: #888;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .send-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            align-self: flex-end;
            transition: background 0.3s;
            margin-top: 20px;
        }
        .send-btn:hover {
            background: linear-gradient(135deg, #45a049, #4CAF50);
        }
        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .preview {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            display: none;
        }
        .status {
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            margin-top: 10px;
        }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        @media (max-width: 600px) {
            .container { padding: 0 10px; }
            .card { padding: 20px; }
            #message { height: 100px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📡 Broadcast Nearby</h1>
        <p style="color: rgba(255,255,255,0.8);">Send messages to users within 1km—connect locally!</p>
    </div>
    <div class="container">
        <div class="card">
            <div class="location-info">
                <div id="location-display">Detecting your location...</div>
                <button onclick="getLocation()">🔄 Refresh Location</button>
            </div>
            <div id="map"></div>
        </div>
        <div class="card">
            <form id="broadcast-form" class="compose-area">
                <label for="message">Your Message (max 160 chars for SMS):</label>
                <textarea id="message" placeholder="Hey neighbors! Quick update: ..." maxlength="160" required></textarea>
                <div class="char-count"><span id="char-count">0</span>/160</div>
                <div class="preview" id="preview"></div>
                <button type="submit" class="send-btn" id="send-btn" disabled>🚀 Send to Nearby (1km Radius)</button>
            </form>
            <div id="status"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map, userLat, userLng;
        const form = document.getElementById('broadcast-form');
        const messageInput = document.getElementById('message');
        const charCount = document.getElementById('char-count');
        const preview = document.getElementById('preview');
        const sendBtn = document.getElementById('send-btn');
        const status = document.getElementById('status');

        // Get user location
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;
                        document.getElementById('location-display').textContent = `Your spot: ${userLat.toFixed(4)}, ${userLng.toFixed(4)}`;
                        initMap();
                        checkFormReady();
                    },
                    (error) => {
                        status.innerHTML = '<div class="status error">Location access denied. Enable GPS for nearby targeting.</div>';
                    },
                    { enableHighAccuracy: true, maximumAge: 60000, timeout: 10000 }
                );
            } else {
                status.innerHTML = '<div class="status error">Geolocation not supported.</div>';
            }
        }

        // Init simple map
        function initMap() {
            map = L.map('map').setView([userLat, userLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
            L.marker([userLat, userLng]).addTo(map)
                .bindPopup('You are here! Messages will reach 1km around.')
                .openPopup();
        }

        // Char count and preview
        messageInput.addEventListener('input', () => {
            const length = messageInput.value.length;
            charCount.textContent = length;
            if (length > 0 && length <= 160) {
                preview.innerHTML = `<strong>Preview:</strong> ${messageInput.value}`;
                preview.style.display = 'block';
                checkFormReady();
            } else {
                preview.style.display = 'none';
            }
        });

        function checkFormReady() {
            sendBtn.disabled = !userLat || messageInput.value.trim() === '';
        }

        // Form submit - Send to backend
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!userLat) {
                status.innerHTML = '<div class="status error">Location required!</div>';
                return;
            }
            const formData = new FormData();
            formData.append('message', messageInput.value);
            formData.append('lat', userLat);
            formData.append('lng', userLng);
            formData.append('radius', 1); // km

            try {
                sendBtn.disabled = true;
                sendBtn.textContent = 'Sending...';
                const response = await fetch('/api/broadcast', { // Replace with your endpoint
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    status.innerHTML = `<div class="status success">Message sent! Reached ${result.recipients} nearby users via SMS.</div>`;
                    form.reset();
                    preview.style.display = 'none';
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                status.innerHTML = `<div class="status error">Send failed: ${error.message}</div>`;
            } finally {
                sendBtn.disabled = false;
                sendBtn.textContent = '🚀 Send to Nearby (1km Radius)';
            }
        });

        // Auto-detect location on load
        getLocation();
    </script>
</body>
</html>