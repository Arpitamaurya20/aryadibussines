<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyGate Platform - Secure Sign In</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --primary-hover: #0f172a;
            --accent-cyan: #06b6d4;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
        }

        .myg-split-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left Side: Visual Panel */
        .myg-visual-panel {
            flex: 1.2;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 60px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        /* Abstract glowing graphic element */
        .myg-visual-panel::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
            pointer-events: none;
        }

        .myg-visual-header {
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 2;
        }

        .myg-visual-logo {
            font-size: 24px;
            color: var(--accent-cyan);
        }

        .myg-visual-brand {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .myg-visual-content {
            max-width: 480px;
            margin: auto 0;
            z-index: 2;
        }

        .myg-visual-title {
            font-size: 2.75rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 24px;
            letter-spacing: -1px;
        }

        .myg-visual-title span {
            color: var(--accent-cyan);
        }

        .myg-visual-features {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .myg-vis-feat-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            font-size: 15px;
            color: #cbd5e1;
            line-height: 1.5;
        }

        .myg-vis-feat-item i {
            font-size: 20px;
            color: var(--accent-cyan);
            flex-shrink: 0;
        }

        .myg-visual-footer {
            font-size: 13px;
            color: #94a3b8;
            z-index: 2;
        }

        /* Right Side: Form Panel */
        .myg-form-panel {
            flex: 1;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 40px;
            position: relative;
        }

        .myg-form-card {
            width: 100%;
            max-width: 400px;
        }

        .myg-shield-badge {
            width: 52px;
            height: 52px;
            background-color: rgba(30, 58, 138, 0.05);
            color: var(--primary-color);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 24px;
        }

        .myg-form-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .myg-form-desc {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 36px;
        }

        .myg-form-group {
            margin-bottom: 24px;
        }

        .myg-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: block;
        }

        .myg-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .myg-input-icon {
            position: absolute;
            left: 16px;
            color: var(--text-muted);
            font-size: 16px;
            pointer-events: none;
            transition: var(--transition);
        }

        .myg-input {
            border: 1.5px solid var(--border-color);
            border-radius: 12px;
            padding: 13px 16px 13px 44px;
            font-size: 14.5px;
            color: var(--text-dark);
            width: 100%;
            transition: var(--transition);
            background-color: var(--bg-light);
        }

        .myg-input::placeholder {
            color: var(--text-light);
        }

        .myg-input:focus {
            background-color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.08);
            outline: none;
        }

        .myg-input:focus ~ .myg-input-icon {
            color: var(--primary-color);
        }

        .myg-password-toggle {
            position: absolute;
            right: 16px;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 18px;
            transition: var(--transition);
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
        }

        .myg-password-toggle:hover {
            color: var(--text-dark);
        }

        .myg-btn-submit {
            background-color: var(--primary-color);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            transition: var(--transition);
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
        }

        .myg-btn-submit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.2);
        }

        .myg-btn-submit:active {
            transform: translateY(0);
        }

        .myg-hint-box {
            margin-top: 36px;
            padding: 14px 18px;
            background-color: var(--bg-light);
            border: 1.5px dashed var(--border-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12.5px;
            color: var(--text-muted);
        }

        .myg-copy-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-weight: 700;
            padding: 0;
            font-size: 12.5px;
            transition: var(--transition);
        }

        .myg-copy-btn:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        /* ==========================================================================
           RESPONSIVENESS
           ========================================================================== */
        @media (max-width: 991.98px) {
            .myg-visual-panel {
                display: none;
            }

            .myg-form-panel {
                padding: 60px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="myg-split-container">
        <!-- Left Side: Visual Panel -->
        <div class="myg-visual-panel">
            <div class="myg-visual-header">
                <i class="bi bi-shield-fill-check myg-visual-logo"></i>
                <span class="myg-visual-brand">MyGate Platform</span>
            </div>
            
            <div class="myg-visual-content">
                <h1 class="myg-visual-title">Smart Security. <span>Seamless Access.</span></h1>
                <ul class="myg-visual-features">
                    <li class="myg-vis-feat-item">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <strong>Secure Verification</strong><br>
                            Ensure 100% verified entries for guests, delivery executives, and support staff.
                        </div>
                    </li>
                    <li class="myg-vis-feat-item">
                        <i class="bi bi-bell"></i>
                        <div>
                            <strong>Real-Time Alerts</strong><br>
                            Receive instant check-in notifications and confirm gate entry directly from your mobile.
                        </div>
                    </li>
                    <li class="myg-vis-feat-item">
                        <i class="bi bi-building"></i>
                        <div>
                            <strong>Estate Operations</strong><br>
                            Manage complaints, facility bookings, notice boards, and community billing under one roof.
                        </div>
                    </li>
                </ul>
            </div>

            <div class="myg-visual-footer">
                &copy; 2026 Aryadi Business Systems. All rights reserved.
            </div>
        </div>

        <!-- Right Side: Form Panel -->
        <div class="myg-form-panel">
            <div class="myg-form-card">
                <div class="myg-shield-badge">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h1 class="myg-form-title">Welcome Back</h1>
                <p class="myg-form-desc">Sign in to access your society operations control dashboard.</p>
                
                <form action="#" method="POST">
                    <div class="myg-form-group">
                        <label for="myg-email" class="myg-label">Email Address</label>
                        <div class="myg-input-wrapper">
                            <i class="bi bi-envelope-fill myg-input-icon"></i>
                            <input type="email" id="myg-email" class="myg-input" value="admin@mygate.com" placeholder="name@domain.com" required autocomplete="email">
                        </div>
                    </div>
                    
                    <div class="myg-form-group">
                        <label for="myg-password" class="myg-label">Password</label>
                        <div class="myg-input-wrapper">
                            <i class="bi bi-lock-fill myg-input-icon"></i>
                            <input type="password" id="myg-password" class="myg-input" value="Admin@123" placeholder="••••••••" required autocomplete="current-password">
                            <button type="button" class="myg-password-toggle" id="password-toggle-btn" aria-label="Toggle Password Visibility">
                                <i class="bi bi-eye-slash-fill" id="toggle-icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="myg-btn-submit">Sign In to Dashboard</button>
                </form>
                
                <div class="myg-hint-box">
                    <span>Default: <strong>admin@mygate.com</strong> / <strong>Admin@123</strong></span>
                    <button class="myg-copy-btn" onclick="copyCredentials()" id="copy-btn">Copy</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Password visibility toggle and copy credentials helper -->
    <script>
        const passwordInput = document.getElementById('myg-password');
        const toggleBtn = document.getElementById('password-toggle-btn');
        const toggleIcon = document.getElementById('toggle-icon');

        toggleBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            if (type === 'password') {
                toggleIcon.classList.remove('bi-eye-fill');
                toggleIcon.classList.add('bi-eye-slash-fill');
            } else {
                toggleIcon.classList.remove('bi-eye-slash-fill');
                toggleIcon.classList.add('bi-eye-fill');
            }
        });

        function copyCredentials() {
            const textToCopy = "admin@mygate.com / Admin@123";
            navigator.clipboard.writeText(textToCopy).then(() => {
                const copyBtn = document.getElementById('copy-btn');
                copyBtn.innerText = "Copied!";
                copyBtn.style.color = "#10b981"; // success green
                setTimeout(() => {
                    copyBtn.innerText = "Copy";
                    copyBtn.style.color = "var(--primary-color)";
                }, 2000);
            }).catch(err => {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>
