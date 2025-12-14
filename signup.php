<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Aawaz</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #e5e7eb 0%, #f3f4f6 50%, #e5e7eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        .floating-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        .shape-1 { width: 120px; height: 120px; border-radius: 50%; top: 10%; left: 10%; animation: float1 12s ease-in-out infinite; }
        .shape-2 { width: 80px; height: 80px; border-radius: 20px; top: 20%; right: 15%; animation: float2 8s ease-in-out infinite; }
        .shape-3 { width: 150px; height: 150px; border-radius: 30px; bottom: 15%; left: 15%; animation: float3 15s ease-in-out infinite; }
        .shape-4 { width: 60px; height: 60px; border-radius: 50%; bottom: 25%; right: 20%; animation: float4 10s ease-in-out infinite; }
        .shape-5 { width: 100px; height: 100px; border-radius: 15px; top: 50%; left: 5%; animation: float5 14s ease-in-out infinite; }
        .shape-6 { width: 90px; height: 90px; border-radius: 50%; top: 60%; right: 10%; animation: float6 11s ease-in-out infinite; }
        .shape-7 { width: 70px; height: 70px; border-radius: 25px; top: 80%; left: 50%; animation: float7 9s ease-in-out infinite; }
        @keyframes float1 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(20px, -30px) rotate(90deg); }
            50% { transform: translate(-10px, -20px) rotate(180deg); }
            75% { transform: translate(-25px, 15px) rotate(270deg); }
        }
        @keyframes float2 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(-30px, 20px) rotate(120deg); }
            66% { transform: translate(25px, -15px) rotate(240deg); }
        }
        @keyframes float3 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            20% { transform: translate(15px, -25px) rotate(72deg); }
            40% { transform: translate(-20px, -10px) rotate(144deg); }
            60% { transform: translate(-15px, 20px) rotate(216deg); }
            80% { transform: translate(30px, 10px) rotate(288deg); }
        }
        @keyframes float4 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -30px) rotate(180deg); }
        }
        @keyframes float5 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(-15px, 25px) rotate(90deg); }
            50% { transform: translate(20px, 15px) rotate(180deg); }
            75% { transform: translate(10px, -20px) rotate(270deg); }
        }
        @keyframes float6 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            30% { transform: translate(25px, -20px) rotate(108deg); }
            60% { transform: translate(-15px, 25px) rotate(216deg); }
        }
        @keyframes float7 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            40% { transform: translate(-25px, -15px) rotate(144deg); }
            80% { transform: translate(20px, -25px) rotate(288deg); }
        }
        .main-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            max-width: 1200px;
            width: 100%;
            z-index: 1;
            position: relative;
        }
        .auth-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 28px;
            padding: 3.5rem;
            width: 100%;
            max-width: 700px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.08), 0 10px 30px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.6), inset 0 -1px 0 rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.4);
            position: relative;
            padding: 3rem;
        }
        .features-panel {
            flex-direction: column;
            justify-content: center;
            padding: 2rem;
        }
        .features-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .features-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 1rem;
        }
        .features-subtitle {
            font-size: 1.1rem;
            color: #6b7280;
            line-height: 1.6;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .feature-icon {
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6366f1;
            font-size: 1.25rem;
        }
        .feature-content h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .feature-content p {
            color: #6b7280;
            line-height: 1.5;
            font-size: 0.95rem;
        }
        .status-indicators {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            justify-content: center;
        }
        .status-badge {
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        .status-pending { background: rgba(251, 191, 36, 0.2); color: #d97706; border: 1px solid rgba(251, 191, 36, 0.3); }
        .status-review { background: rgba(59, 130, 246, 0.2); color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.3); }
        .status-completed { background: rgba(16, 185, 129, 0.2); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); }
        .logo {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .logo-icon {
            font-size: 3rem;
            color: #374151;
            margin-bottom: 0.75rem;
        }
        .logo-text {
            font-size: 2rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        .logo-subtitle {
            color: #6b7280;
            font-size: 1rem;
            font-weight: 400;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .auth-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
            font-weight: 400;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(209, 213, 219, 0.6);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            color: #374151;
        }
        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: rgba(255, 255, 255, 0.9);
        }
        .form-input::placeholder {
            color: #9ca3af;
        }
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(209, 213, 219, 0.6);
            border-radius: 8px;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            cursor: pointer;
            transition: all 0.2s ease;
            color: #374151;
        }
        .form-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: rgba(255, 255, 255, 0.9);
        }
        .input-group {
            position: relative;
        }
        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }
        .input-icon:hover {
            color: #6366f1;
        }
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .checkbox-group input {
            width: 16px;
            height: 16px;
            margin-top: 0.125rem;
            accent-color: #6366f1;
        }
        .terms-link {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }
        .terms-link:hover {
            text-decoration: underline;
        }
        .submit-btn {
            width: 100%;
            background: #374151;
            color: white;
            border: none;
            padding: 0.875rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
        }
        .submit-btn:hover {
            background: #1f2937;
            transform: translateY(-1px);
        }
        .submit-btn:active {
            transform: translateY(0);
        }
        .auth-footer {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(229, 231, 235, 0.3);
        }
        .auth-link {
            color: #6b7280;
            font-size: 0.9rem;
        }
        .auth-link a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }
        .auth-link a:hover {
            text-decoration: underline;
        }
        input[type="file"] {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.7);
        }
        input[type="file"]::-webkit-file-upload-button {
            background: rgba(243, 244, 246, 0.8);
            border: 1px solid rgba(209, 213, 219, 0.6);
            border-radius: 6px;
            padding: 0.375rem 0.75rem;
            margin-right: 0.75rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        input[type="file"]::-webkit-file-upload-button:hover {
            background: rgba(229, 231, 235, 0.8);
        }
        .webcam-container {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .webcam-container video {
            width: 100%;
            max-width: 320px;
            border-radius: 8px;
            border: 1px solid rgba(209, 213, 219, 0.6);
        }
        .webcam-container img {
            width: 100%;
            max-width: 320px;
            border-radius: 8px;
            margin-top: 1rem;
            display: none;
        }
        .webcam-btn {
            background: #6366f1;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            margin: 0.5rem;
            transition: all 0.2s ease;
        }
        .webcam-btn:hover {
            background: #4f46e5;
            transform: translateY(-1px);
        }
        .webcam-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                max-width: 500px;
            }
            .features-panel { order: 2; }
            .auth-container { order: 1; }
        }
        @media (max-width: 640px) {
            body { padding: 1rem; }
            .auth-container { padding: 2rem 1.5rem; }
            .features-panel { padding: 1rem; }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
                margin-bottom: 0;
            }
            .logo-text { font-size: 1.75rem; }
            .auth-title { font-size: 1.25rem; }
            .features-title { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
        <div class="floating-shape shape-5"></div>
        <div class="floating-shape shape-6"></div>
        <div class="floating-shape shape-7"></div>
    </div>
    <div class="main-container">
        <div class="features-panel">
            <div class="features-header">
                <h2 class="features-title">Report Local Issues</h2>
                <p class="features-subtitle">Help make your community better by reporting local problems</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-camera"></i></div>
                <div class="feature-content">
                    <h3>Snap & Share</h3>
                    <p>Take a photo of potholes, broken streetlights, or any local issue and upload instantly</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-paper-plane"></i></div>
                <div class="feature-content">
                    <h3>Direct to Government</h3>
                    <p>Your reports go straight to the relevant government department for quick action</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <div class="feature-content">
                    <h3>Track Progress</h3>
                    <p>Monitor the status of your reports from pending to completion in real-time</p>
                </div>
            </div>
            <div class="status-indicators">
                <div class="status-badge status-pending">Pending</div>
                <div class="status-badge status-review">In Review</div>
                <div class="status-badge status-completed">Completed</div>
            </div>
        </div>
        <div class="auth-container">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-bullhorn"></i></div>
                <div class="logo-text">Aawaz</div>
                <div class="logo-subtitle">आवाज</div>
            </div>
            <div class="auth-header">
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join the community and make your voice heard</p>
            </div>
            <form action="signup_process.php" enctype="multipart/form-data" method="POST" id="signupForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" placeholder="Enter your first name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" placeholder="Enter your last name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-input" placeholder="Enter your phone number" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                        <i class="fas fa-eye input-icon" onclick="togglePassword('password')"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
                        <i class="fas fa-eye input-icon" onclick="togglePassword('confirm_password')"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="profile_picture">Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="form-input" required>
                    <img id="profilePreview" alt="Profile Picture Preview" style="display: none; width: 100%; max-width: 320px; border-radius: 8px; margin-top: 1rem;">
                </div>
                <div class="form-group webcam-container">
                    <label class="form-label">Citizenship - Front</label>
                    <video id="kyc1Video" autoplay style="display: none;"></video>
                    <img id="kyc1Preview" alt="Citizenship Front Preview">
                    <input type="hidden" id="kyc1Data" name="kyc1">
                    <div>
                        <button type="button" class="webcam-btn" onclick="startWebcam('kyc1')">Start Webcam</button>
                        <button type="button" class="webcam-btn" id="kyc1CaptureBtn" onclick="captureImage('kyc1')" disabled>Capture</button>
                        <button type="button" class="webcam-btn" id="kyc1RetakeBtn" onclick="retakeImage('kyc1')" style="display: none;">Retake</button>
                    </div>
                </div>
                <div class="form-group webcam-container">
                    <label class="form-label">Citizenship - Back</label>
                    <video id="kyc2Video" autoplay style="display: none;"></video>
                    <img id="kyc2Preview" alt="Citizenship Back Preview">
                    <input type="hidden" id="kyc2Data" name="kyc2">
                    <div>
                        <button type="button" class="webcam-btn" onclick="startWebcam('kyc2')">Start Webcam</button>
                        <button type="button" class="webcam-btn" id="kyc2CaptureBtn" onclick="captureImage('kyc2')" disabled>Capture</button>
                        <button type="button" class="webcam-btn" id="kyc2RetakeBtn" onclick="retakeImage('kyc2')" style="display: none;">Retake</button>
                    </div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="#" class="terms-link">Terms of Service</a> and <a href="#" class="terms-link">Privacy Policy</a></label>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus" style="margin-right: 0.5rem;"></i>Create Account
                </button>
                <p id="status" style="display: none;">Getting location...</p>
                <p><input type="text" name="lat" id="lat" hidden></p>
                <p><input type="text" name="lon" id="lon" hidden></p>
            </form>
            <div class="auth-footer">
                <p class="auth-link">Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>
    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = passwordInput.nextElementSibling;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        let activeStream = null;

        function stopAllStreams() {
            if (activeStream) {
                activeStream.getTracks().forEach(track => track.stop());
                activeStream = null;
            }
            ['kyc1', 'kyc2'].forEach(type => {
                const video = document.getElementById(`${type}Video`);
                const captureBtn = document.getElementById(`${type}CaptureBtn`);
                video.style.display = 'none';
                captureBtn.disabled = true;
            });
        }

        async function startWebcam(type) {
            stopAllStreams();
            const video = document.getElementById(`${type}Video`);
            const captureBtn = document.getElementById(`${type}CaptureBtn`);
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                video.style.display = 'block';
                captureBtn.disabled = false;
                activeStream = stream;
            } catch (err) {
                alert(`Failed to access webcam for ${type === 'kyc1' ? 'Citizenship Front' : 'Citizenship Back'}: ${err.message}`);
            }
        }

        function captureImage(type) {
            const video = document.getElementById(`${type}Video`);
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const imageData = canvas.toDataURL('image/jpeg');
            const preview = document.getElementById(`${type}Preview`);
            const dataInput = document.getElementById(`${type}Data`);
            const captureBtn = document.getElementById(`${type}CaptureBtn`);
            const retakeBtn = document.getElementById(`${type}RetakeBtn`);
            preview.src = imageData;
            preview.style.display = 'block';
            dataInput.value = imageData;
            video.style.display = 'none';
            captureBtn.style.display = 'none';
            retakeBtn.style.display = 'inline-block';
            stopAllStreams();
        }

        function retakeImage(type) {
            const video = document.getElementById(`${type}Video`);
            const preview = document.getElementById(`${type}Preview`);
            const dataInput = document.getElementById(`${type}Data`);
            const captureBtn = document.getElementById(`${type}CaptureBtn`);
            const retakeBtn = document.getElementById(`${type}RetakeBtn`);
            preview.style.display = 'none';
            dataInput.value = '';
            captureBtn.style.display = 'inline-block';
            retakeBtn.style.display = 'none';
            startWebcam(type);
        }

        // Preview for profile picture file upload
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            const profilePicture = document.getElementById('profile_picture').value;
            const kyc1Data = document.getElementById('kyc1Data').value;
            const kyc2Data = document.getElementById('kyc2Data').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return;
            }
            if (!terms) {
                e.preventDefault();
                alert('Please accept the terms and conditions!');
                return;
            }
            if (!profilePicture) {
                e.preventDefault();
                alert('Please upload a Profile Picture!');
                return;
            }
            if (!kyc1Data) {
                e.preventDefault();
                alert('Please capture Citizenship Front image!');
                return;
            }
            if (!kyc2Data) {
                e.preventDefault();
                alert('Please capture Citizenship Back image!');
                return;
            }
        });

        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(showPosition, showError);
        } else {
            document.getElementById("status").innerHTML = "Geolocation is not supported by this browser.";
        }

        function showPosition(position) {
            document.getElementById("status").innerHTML = "Live location detected!";
            document.getElementById("lat").value = position.coords.latitude;
            document.getElementById("lon").value = position.coords.longitude;
            console.log(position.coords.latitude, position.coords.longitude);
        }

        function showError(error) {
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    document.getElementById("status").innerHTML = "User denied the request for Geolocation.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    document.getElementById("status").innerHTML = "Location information is unavailable.";
                    break;
                case error.TIMEOUT:
                    document.getElementById("status").innerHTML = "The request to get user location timed out.";
                    break;
                case error.UNKNOWN_ERROR:
                    document.getElementById("status").innerHTML = "An unknown error occurred.";
                    break;
            }
        }
    </script>
</body>
</html>