<?php
require_once 'email_config.php';
require_once 'phpmailer_email.php';

class EmailNotifications {
    
    private static function sendEmail($to, $subject, $body) {
        try {
            // Use PHP's built-in mail function with SMTP
            $headers = array(
                'From' => FROM_NAME . ' <' . FROM_EMAIL . '>',
                'Reply-To' => FROM_EMAIL,
                'X-Mailer' => 'PHP/' . phpversion(),
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/html; charset=UTF-8'
            );
            
            $headerString = '';
            foreach ($headers as $key => $value) {
                $headerString .= $key . ': ' . $value . "\r\n";
            }
            
            $result = mail($to, $subject, $body, $headerString);
            
            if ($result) {
                error_log("Email sent successfully to: $to");
                return true;
            } else {
                error_log("Failed to send email to: $to");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function sendTransactionNotification($transactionData) {
        return EnhancedEmailNotifications::sendTransactionNotification($transactionData);
    }
    
    public static function sendLowStockAlert($riceData) {
        return EnhancedEmailNotifications::sendLowStockAlert($riceData);
    }
    
    public static function sendExpirationAlert($riceData, $daysLeft) {
        return EnhancedEmailNotifications::sendExpirationAlert($riceData, $daysLeft);
    }
    
    public static function sendTestEmail() {
        return EnhancedEmailNotifications::sendTestEmail();
    }
}
?>
