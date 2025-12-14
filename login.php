<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aawaz</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(200, 200, 200, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(240, 240, 240, 0.4) 0%, transparent 50%);
            pointer-events: none;
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
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.03),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .shape-1 {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            top: 10%;
            left: 10%;
            animation: float1 12s ease-in-out infinite;
        }

        .shape-2 {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            top: 20%;
            right: 15%;
            animation: float2 8s ease-in-out infinite;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            border-radius: 30px;
            bottom: 15%;
            left: 15%;
            animation: float3 15s ease-in-out infinite;
        }

        .shape-4 {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            bottom: 25%;
            right: 20%;
            animation: float4 10s ease-in-out infinite;
        }

        .shape-5 {
            width: 100px;
            height: 100px;
            border-radius: 15px;
            top: 50%;
            left: 5%;
            animation: float5 14s ease-in-out infinite;
        }

        .shape-6 {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            top: 60%;
            right: 10%;
            animation: float6 11s ease-in-out infinite;
        }

        .shape-7 {
            width: 70px;
            height: 70px;
            border-radius: 25px;
            top: 80%;
            left: 50%;
            animation: float7 9s ease-in-out infinite;
        }

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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4rem;
            width: 100%;
            max-width: 1400px;
            z-index: 1;
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 28px;
            padding: 3.5rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 
                0 25px 80px rgba(0, 0, 0, 0.08),
                0 10px 30px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.6),
                inset 0 -1px 0 rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.4);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .info-panel {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 28px;
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 2px 2px 4px silver;
            /* border: 1px solid rgba(255, 255, 255, 0.4); */
            position: relative;
            overflow: hidden;
            z-index: 1;
            height: fit-content;
        }

        .info-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 2;
        }

        .info-title {
            font-size: 2rem;
            font-weight: bold;
            color: #374151;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 15px rgba(55, 65, 81, 0.1);
        }

        .info-subtitle {
            color: #6b7280;
            font-size: 1rem;
            opacity: 0.8;
            margin-bottom: 1rem;
        }

        .feature-showcase {
            position: relative;
            z-index: 2;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
            animation: slideInRight 0.8s ease-out;
        }

        .feature-item:nth-child(2) {
            animation-delay: 0.2s;
        }

        .feature-item:nth-child(3) {
            animation-delay: 0.4s;
        }

        .feature-item:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6b7280;
            border: 1px solid rgba(255, 255, 255, 0.3);
            flex-shrink: 0;
        }

        .feature-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }

        .feature-content p {
            font-size: 0.9rem;
            color: #6b7280;
            line-height: 1.4;
        }

        .demo-app {
            position: absolute;
            top: 15px;
            left: 15px;
            right: 15px;
            bottom: 15px;
            background: white;
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .camera-icon {
            font-size: 3rem;
            color: #6b7280;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        .demo-text {
            font-size: 0.7rem;
            color: #374151;
            text-align: center;
            font-weight: 500;
        }

        .status-indicators {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.15);
            color: #f59e0b;
        }

        .status-completed {
            background: rgba(34, 197, 94, 0.15);
            color: #16a34a;
        }

        .status-review {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes phoneFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(2deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            pointer-events: none;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .logo-icon {
            font-size: 3rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(107, 114, 128, 0.3);
        }

        .logo-text {
            font-size: 2rem;
            font-weight: bold;
            color: #374151;
            text-shadow: 0 2px 15px rgba(55, 65, 81, 0.1);
        }

        .logo-subtitle {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 0.25rem;
            opacity: 0.8;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .auth-title {
            font-size: 1.75rem;
            font-weight: bold;
            color: #374151;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 15px rgba(55, 65, 81, 0.1);
        }

        .auth-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 18px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #374151;
            box-shadow: 
                0 4px 15px rgba(0, 0, 0, 0.03),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .form-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 8px 25px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.6),
                0 0 0 4px rgba(107, 114, 128, 0.1);
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: #9ca3af;
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
            transition: all 0.3s ease;
            z-index: 3;
        }

        .input-icon:hover {
            color: #6b7280;
            transform: translateY(-50%) scale(1.1);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            position: relative;
            z-index: 2;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4b5563;
        }

        .checkbox-group input {
            width: 16px;
            height: 16px;
            accent-color: #6b7280;
        }

        .forgot-link {
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.25rem;
            border-radius: 8px;
        }

        .forgot-link:hover {
            color: #374151;
            background: rgba(255, 255, 255, 0.1);
            text-decoration: underline;
        }

        .submit-btn {
            width: 100%;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            color: #374151;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 1rem;
            border-radius: 18px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
            position: relative;
            z-index: 2;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.18);
            box-shadow: 
                0 15px 40px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 2;
        }

        .auth-link {
            color: #6b7280;
        }

        .auth-link a {
            color: #4b5563;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.25rem 0.5rem;
            border-radius: 8px;
        }

        .auth-link a:hover {
            color: #374151;
            background: rgba(255, 255, 255, 0.1);
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            position: relative;
            z-index: 2;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.1);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.2);
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.1);
        }

        @media (max-width: 480px) {
            .main-container {
                flex-direction: column;
                gap: 2rem;
            }

            .auth-container,
            .info-panel {
                padding: 2.5rem 1.5rem;
                margin: 1rem;
                border-radius: 24px;
                max-width: none;
            }
            
            .logo-text {
                font-size: 1.5rem;
            }
            
            .auth-title {
                font-size: 1.5rem;
            }

            .info-title {
                font-size: 1.5rem;
            }

            .form-input {
                padding: 0.875rem 1rem;
            }

            .submit-btn {
                padding: 0.875rem;
            }

            .demo-phone {
                width: 160px;
                height: 280px;
            }

            .demo-screen {
                width: 130px;
                height: 230px;
            }

            .feature-item {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }

            .feature-icon {
                margin-bottom: 0.5rem;
            }
        }

        @media (max-width: 1200px) {
            .main-container {
                flex-direction: column;
                gap: 3rem;
            }

            .info-panel {
                max-width: 600px;
            }
        }

        /* Subtle floating animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        .auth-container {
            animation: float 6s ease-in-out infinite;
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
        <div class="auth-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="logo-text">Aawaz</div>
                <div class="logo-subtitle">आवाज</div>
            </div>

            <div class="auth-header">
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to your account</p>
            </div>

            <form action="login_process.php" method="POST" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password"
                            required
                        >
                        <i class="fas fa-eye input-icon" onclick="togglePassword()"></i>
                    </div>
                </div>

                <div class="form-options">
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="auth-footer">
                <p class="auth-link">Don't have an account? <a href="signup.php">Sign up here</a></p>
            </div>
        </div>

        <div class="info-panel">
            <div class="info-header">
                <h2 class="info-title">Report Local Issues</h2>
                <p class="info-subtitle">Help make your community better by reporting local problems</p>
            </div>

            <!-- <div class="demo-phone">
                <div class="demo-screen">
                    <div class="demo-app">
                        <i class="fas fa-camera camera-icon"></i>
                        <div class="demo-text">
                            Click & Report<br>
                            Local Problems
                        </div>
                    </div>
                </div>
            </div> -->

            <div class="feature-showcase">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Snap & Share</h3>
                        <p>Take a photo of potholes, broken streetlights, or any local issue and upload instantly</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Direct to Government</h3>
                        <p>Your reports go straight to the relevant government department for quick action</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Track Progress</h3>
                        <p>Monitor the status of your reports from pending to completion in real-time</p>
                    </div>
                </div>
            </div>

            <div class="status-indicators">
                <div class="status-badge status-pending">Pending</div>
                <div class="status-badge status-review">In Review</div>
                <div class="status-badge status-completed">Completed</div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.querySelector('.input-icon');
            
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

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });
    </script>
</body>
</html>