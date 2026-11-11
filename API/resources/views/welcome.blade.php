<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Status</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            /* background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); */
            background: #38ef7d;
        }

        .network-status-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 50px 60px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            min-width: 350px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }

        .icon-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .status-icon {
            font-size: 64px;
            color: #28a745;
            transition: all 0.3s ease;
        }

        .pulse-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #28a745;
            animation: pulse 2s ease-out infinite;
            opacity: 0;
        }

        @keyframes pulse {
            0% {
                transform: translate(-50%, -50%) scale(0.8);
                opacity: 0.8;
            }

            100% {
                transform: translate(-50%, -50%) scale(1.5);
                opacity: 0;
            }
        }

        .status-text {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #28a745;
        }

        .status-subtext {
            font-size: 14px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        @media (max-width: 480px) {
            .network-status-container {
                padding: 40px 30px;
                min-width: auto;
                width: 90%;
            }
        }
    </style>
</head>

<body>
    <div class="network-status-container">
        <div class="status-indicator">
            <div class="icon-wrapper">
                <i class="fas fa-server status-icon"></i>
                <div class="pulse-ring"></div>
            </div>
            <span class="status-text">Online</span>
            <span class="status-subtext">API Status</span>
        </div>
        <p class="message">API is operational and responding normally.</p>
    </div>
</body>

</html>
