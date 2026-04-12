<?php
// Include the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../INCLUDES/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- CONFIGURATION ---
$senderEmail = 'bachelorofsis@gmail.com'; // Enter your Gmail here
$appPassword = 'eifk cvdc chwn tnbb'; // Enter the 16-letter App Password here

// 1. Fetch all Active Transactions that have a valid student email
$query = "SELECT s.full_name, s.email, i.item_name 
          FROM transactions t 
          JOIN students s ON t.student_id = s.student_id 
          JOIN items i ON t.item_id = i.item_id 
          WHERE t.transaction_status = 'Active' 
          AND s.email IS NOT NULL AND s.email != ''";

$result = $mysql->query($query);

if ($result->num_rows === 0) {
    die("No active transactions with emails found. Nothing to send.");
}

// 2. Group items by Student Email (so they only get ONE email if they borrowed multiple things)
$students = [];
while ($row = $result->fetch_assoc()) {
    $email = $row['email'];
    if (!isset($students[$email])) {
        $students[$email] = [
            'name' => $row['full_name'],
            'items' => []
        ];
    }
    $students[$email]['items'][] = $row['item_name'];
}

// 3. Setup and Send Emails
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $senderEmail;
    $mail->Password   = $appPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom($senderEmail, 'EquipTrack MIS');
    $mail->isHTML(true);
    $mail->Subject = 'URGENT: MIS Office Closes at 8:00 PM - Return Equipment';

    $sentCount = 0;

    // Loop through each student and send their custom email
    foreach ($students as $email => $data) {
        $mail->clearAddresses(); // Clear previous recipient
        $mail->addAddress($email);

        // Build the HTML Email Template
        $itemList = "<ul>";
        foreach ($data['items'] as $item) {
            $itemList .= "<li><strong>" . htmlspecialchars($item) . "</strong></li>";
        }
        $itemList .= "</ul>";

        $body = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>
            <h2 style='color: #dc3545;'>Notice: MIS Office Closing Soon</h2>
            <p>Dear <strong>" . htmlspecialchars($data['name']) . "</strong>,</p>
            <p>This is an automated reminder that the MIS Office will be closing at exactly <strong>8:00 PM</strong> tonight.</p>
            <p>Our records indicate that you currently have the following equipment checked out:</p>
            $itemList
            <p style='background-color: #fff3cd; padding: 10px; border-left: 5px solid #ffc107;'>
                <strong>Action Required:</strong> Please return these items to the MIS desk before 8:00 PM to ensure your transaction is cleared and to avoid any account penalties.
            </p>
            <p>Thank you for your cooperation,</p>
            <p><strong>EquipTrack MIS Team</strong></p>
        </div>";

        $mail->Body = $body;
        $mail->send();
        $sentCount++;
    }

    echo "Successfully sent $sentCount email notices!";

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>