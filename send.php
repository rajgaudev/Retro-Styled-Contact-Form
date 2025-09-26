<?php
// Set the header to indicate JSON content
header('Content-Type: application/json');

// --- CONFIGURATION ---
$botToken = "BOT TOKEN NO";
$chatId = "ACCOUNT CHAT ID";

// --- SCRIPT START ---
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
    // --- GATHER INFORMATION ---
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $timestamp = date("Y-m-d H:i:s");
    $userMessage = htmlspecialchars($_POST['message'] ?? 'No message provided.');

    $messageBody = "📬 *New Message via Web Portal* 📬\n\n"
                 . "*Sent at:* " . $timestamp . "\n"
                 . "*From IP:* " . $ipAddress . "\n\n"
                 . "--- Message ---\n"
                 . $userMessage;

    // --- SEND THE TEXT MESSAGE FIRST ---
    $textData = [
        'chat_id' => $chatId,
        'text' => $messageBody,
        'parse_mode' => 'Markdown'
    ];
    $textUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($textData);
    file_get_contents($textUrl);


    // --- HANDLE FILE UPLOADS ---
    $filesUploaded = isset($_FILES['files']) && !empty($_FILES['files']['name'][0]);
    if ($filesUploaded) {
        $fileCount = count($_FILES['files']['name']);
        $media = [];
        $postFields = ['chat_id' => $chatId];
        $limit = min($fileCount, 10);

        for ($i = 0; $i < $limit; $i++) {
            $filePath = $_FILES['files']['tmp_name'][$i];
            $fileName = $_FILES['files']['name'][$i];
            $attachKey = 'file' . $i;
            $postFields[$attachKey] = new CURLFile(realpath($filePath), mime_content_type($filePath), $fileName);
            $media[] = ['type' => 'document', 'media' => 'attach://' . $attachKey];
        }
        
        $postFields['media'] = json_encode($media);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot$botToken/sendMediaGroup");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_exec($ch);
        curl_close($ch);
    }
    
    // ✨ NEW: Send a success JSON response
    echo json_encode(['status' => 'success', 'message' => 'Your message and files have been sent successfully!']);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    // ✨ NEW: Send an error JSON response
    echo json_encode(['status' => 'error', 'message' => 'An error occurred on the server: ' . $e->getMessage()]);
}

?>