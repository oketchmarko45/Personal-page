<?php
date_default_timezone_set('Africa/Nairobi');

// Replace with your credentials
$consumerKey = 'jGQ0g6bYzAhOl2KaJr0urdh8zTqsDp6mp4Esx6s6VAR3GRgf';
$consumerSecret = 'ydhif5vcMyCp1XdfMt9f6ZEBlzhAtHnjb2GE7SUuyX6OfDcAzGZiafBqS2u4n0me';
$BusinessShortCode = '8861950';
$Passkey = 'YOUR_PASS_KEY';  // Add your passkey here

// Get form data
$phone = $_POST['phone'];
$amount = $_POST['amount'];

// Format phone number
if (substr($phone, 0, 1) === '0') {
    $phone = '254' . substr($phone, 1);
}

// Get timestamp and password
$Timestamp = date('YmdHis');
$Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

// Get access token
$credentials = base64_encode("$consumerKey:$consumerSecret");
$url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch);
    exit;
}

curl_close($ch);
$responseData = json_decode($response);

if (isset($responseData->access_token)) {
    $access_token = $responseData->access_token;
} else {
    echo 'Failed to obtain access token.';
    exit;
}

// STK push request
$stkPushUrl = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
];

$callbackURL = 'http://markocallback.infinityfreeapp.com/callback.php'; // The URL that will handle both STK and callback

$postData = [
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $Password,
    'Timestamp' => $Timestamp,
    'TransactionType' => 'CustomerBuyGoodsOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callbackURL,  // You use the same URL for both the STK push and callback
    'AccountReference' => 'SupportMark',
    'TransactionDesc' => 'Donation Support'
];

// Send the STK push request
$ch = curl_init($stkPushUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch);
    exit;
}

curl_close($ch);

// Prompt the user
echo "Prompt sent to $phone for KES $amount. Please check your phone.";

// Callback handling (uncomment and adjust accordingly)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Callback response from Safaricom
    $callbackData = file_get_contents('php://input');
    $transactionData = json_decode($callbackData, true);

    if ($transactionData) {
        // Extract the necessary fields from the callback response
        $resultCode = $transactionData['ResultCode']; // Success or failure status
        $resultDesc = $transactionData['ResultDesc']; // Description of the result
        $merchantRequestID = $transactionData['MerchantRequestID']; // Merchant request ID
        $checkoutRequestID = $transactionData['CheckoutRequestID']; // Checkout request ID
        
        // Log the transaction data (optional)
        file_put_contents('mpesa_transaction_log.txt', json_encode($transactionData) . "\n", FILE_APPEND);

        // Process the result
        if ($resultCode == 0) {
            // If the transaction was successful, update your database or take necessary actions
            echo "Transaction successful. Thank you for your donation!";
        } else {
            // Handle failed transaction (you may want to retry or notify the user)
            echo "Transaction failed: " . $resultDesc;
        }
    } else {
        echo "Invalid data received from Safaricom.";
    }
}
?>
