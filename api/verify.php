<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProFarm Security Check</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #333;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
            animation: fadeIn 0.5s ease-out;
        }

        h1 {
            color: #2e7d32;
            /* ProFarm Green */
            font-size: 24px;
            margin-bottom: 10px;
        }

        p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #e0e0e0;
            border-top: 5px solid #2e7d32;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .success-icon {
            font-size: 64px;
            color: #2e7d32;
            display: none;
            margin-bottom: 20px;
            animation: scaleIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>

<body>
    <div class="card">
        <div id="loading">
            <div class="spinner"></div>
            <h1>Securing Connection</h1>
            <p>Please wait while we verify your browser to ensure secure access to ProFarm data.</p>
        </div>

        <div id="success" style="display: none;">
            <div class="success-icon">✓</div>
            <h1>Connection Verified</h1>
            <p>You may now close this window and return to the application.</p>
        </div>

        <div class="footer">
            ProFarm Security Shield &copy; <?php echo date('Y'); ?>
        </div>
    </div>

    <script>
        // Check for the security cookie or simply wait for the challenge to pass
        // InfinityFree usually sets a cookie named '__test'.
        // If this page loaded without being intercepted by the challenge, likely we are good or the challenge ran before this content was served.

        function checkStatus() {
            // In a real scenario, the mere fact this JS is running means the HTML was served, 
            // so the browser challenge (if any) was passed or skipped.
            // We'll simulate a brief "finishing" state then show success.

            setTimeout(() => {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('success').style.display = 'block';

                // Try to communicate back to the opener if possible
                try {
                    if (window.opener) {
                        window.opener.postMessage('security_handshake_success', '*');
                        setTimeout(() => window.close(), 2000);
                    }
                } catch (e) {
                    console.log('Cannot communicate with opener');
                }
            }, 1500);
        }

        window.onload = checkStatus;
    </script>
</body>

</html>