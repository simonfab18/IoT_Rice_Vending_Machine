<?php
require_once 'email_config.php';

// Simple PHPMailer-like implementation for Gmail SMTP
class SimpleMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    
    public function __construct() {
        $this->host = SMTP_HOST;
        $this->port = SMTP_PORT;
        $this->username = SMTP_USERNAME;
        $this->password = SMTP_PASSWORD;
        $this->encryption = SMTP_ENCRYPTION;
    }
    
    public function sendMail($to, $subject, $body, $fromName = FROM_NAME) {
        try {
            // Use PHP's mail function with proper headers for HTML emails
            $headers = array(
                'From' => $fromName . ' <' . FROM_EMAIL . '>',
                'Reply-To' => FROM_EMAIL,
                'X-Mailer' => 'PHP/' . phpversion(),
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Transfer-Encoding' => '8bit'
            );
            
            $headerString = '';
            foreach ($headers as $key => $value) {
                $headerString .= $key . ': ' . $value . "\r\n";
            }
            
            // Add additional headers for better email delivery
            $headerString .= "X-Priority: 3\r\n";
            $headerString .= "Importance: Normal\r\n";
            
            $result = mail($to, $subject, $body, $headerString);
            
            if ($result) {
                error_log("Email sent successfully to: $to - Subject: $subject");
                return true;
            } else {
                error_log("Failed to send email to: $to - Subject: $subject");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
}

// Enhanced Email Notifications with better error handling
class EnhancedEmailNotifications {
    
    private static $mailer;
    
    public static function init() {
        if (!self::$mailer) {
            self::$mailer = new SimpleMailer();
        }
    }
    
    public static function sendEmail($to, $subject, $body) {
        self::init();
        return self::$mailer->sendMail($to, $subject, $body);
    }
    
    public static function sendTransactionNotification($transactionData) {
        try {
            $emailTemplate = EmailTemplates::getTransactionNotificationTemplate($transactionData);
            
            $result = self::sendEmail(
                ADMIN_EMAIL,
                $emailTemplate['subject'],
                $emailTemplate['body']
            );
            
            if ($result) {
                error_log("Transaction notification sent successfully for transaction ID: " . $transactionData['id']);
            } else {
                error_log("Failed to send transaction notification for transaction ID: " . $transactionData['id']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Transaction notification error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendLowStockAlert($riceData) {
        try {
            $emailTemplate = EmailTemplates::getLowStockAlertTemplate($riceData);
            
            $result = self::sendEmail(
                ADMIN_EMAIL,
                $emailTemplate['subject'],
                $emailTemplate['body']
            );
            
            if ($result) {
                error_log("Low stock alert sent successfully for rice: " . $riceData['name']);
            } else {
                error_log("Failed to send low stock alert for rice: " . $riceData['name']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Low stock alert error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendExpirationAlert($riceData, $daysLeft) {
        try {
            $emailTemplate = EmailTemplates::getExpirationAlertTemplate($riceData, $daysLeft);
            
            $result = self::sendEmail(
                ADMIN_EMAIL,
                $emailTemplate['subject'],
                $emailTemplate['body']
            );
            
            if ($result) {
                error_log("Expiration alert sent successfully for rice: " . $riceData['name'] . " (Days left: $daysLeft)");
            } else {
                error_log("Failed to send expiration alert for rice: " . $riceData['name'] . " (Days left: $daysLeft)");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Expiration alert error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendTestEmail() {
        try {
            $subject = "Test Email - Rice Dispenser IoT System";
            $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { background-color: #2196F3; color: white; padding: 15px; text-align: center; }
                    .content { padding: 20px; background-color: #f0f8ff; }
                    .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>📧 Email System Test</h2>
                </div>
                <div class='content'>
                    <h3>Test Successful!</h3>
                    <p>The email notification system is working correctly.</p>
                    <p><strong>Test Time:</strong> " . date('M d, Y H:i:s') . "</p>
                    <p>You will now receive notifications for:</p>
                    <ul>
                        <li>New transactions</li>
                        <li>Low stock alerts</li>
                        <li>Rice expiration warnings</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>Rice Dispenser IoT System - Email Test</p>
                </div>
            </body>
            </html>";
            
            $result = self::sendEmail(ADMIN_EMAIL, $subject, $body);
            
            if ($result) {
                error_log("Test email sent successfully");
            } else {
                error_log("Failed to send test email");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Test email error: " . $e->getMessage());
            return false;
        }
    }
}
?>
