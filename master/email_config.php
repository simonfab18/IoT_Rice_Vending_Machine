<?php
// Email Configuration for Rice Dispenser IoT System
// Gmail SMTP Settings

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'farmartricestore@gmail.com');
define('SMTP_PASSWORD', 'nmib tnyc clxw tdbh');
define('SMTP_ENCRYPTION', 'tls');

// Email Settings
define('FROM_EMAIL', 'farmartricestore@gmail.com');
define('FROM_NAME', 'Rice Dispenser IoT System');
define('ADMIN_EMAIL', 'farmartricestore@gmail.com');

// Email Templates
class EmailTemplates {
    
    public static function getTransactionNotificationTemplate($transactionData) {
        $subject = "New Transaction - Rice Dispenser";
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .transaction-details { background-color: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>🍚 New Rice Transaction</h2>
            </div>
            <div class='content'>
                <h3>Transaction Details:</h3>
                <div class='transaction-details'>
                    <p><strong>Transaction ID:</strong> #" . $transactionData['id'] . "</p>
                    <p><strong>Rice Type:</strong> " . $transactionData['rice_name'] . "</p>
                    <p><strong>Quantity:</strong> " . $transactionData['kilos'] . " kg</p>
                    <p><strong>Price per kg:</strong> ₱" . $transactionData['price_per_kg'] . "</p>
                    <p><strong>Total Amount:</strong> ₱" . $transactionData['amount'] . "</p>
                    <p><strong>Date & Time:</strong> " . date('M d, Y H:i:s') . "</p>
                </div>
                <p>This transaction has been successfully recorded in the system.</p>
            </div>
            <div class='footer'>
                <p>Rice Dispenser IoT System - Automated Notification</p>
            </div>
        </body>
        </html>";
        
        return ['subject' => $subject, 'body' => $body];
    }
    
    public static function getLowStockAlertTemplate($riceData) {
        $subject = "⚠️ Low Stock Alert - " . $riceData['name'];
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background-color: #ff9800; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; background-color: #fff3e0; }
                .alert-details { background-color: white; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #ff9800; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>⚠️ Low Stock Alert</h2>
            </div>
            <div class='content'>
                <h3>Inventory Alert:</h3>
                <div class='alert-details'>
                    <p><strong>Rice Type:</strong> " . $riceData['name'] . "</p>
                    <p><strong>Current Stock:</strong> " . $riceData['stock'] . " kg</p>
                    <p><strong>Maximum Capacity:</strong> " . $riceData['capacity'] . " kg</p>
                    <p><strong>Stock Level:</strong> " . round(($riceData['stock'] / $riceData['capacity']) * 100, 1) . "%</p>
                    <p><strong>Alert Time:</strong> " . date('M d, Y H:i:s') . "</p>
                </div>
                <p><strong>Action Required:</strong> Please refill the rice dispenser to avoid stockout.</p>
            </div>
            <div class='footer'>
                <p>Rice Dispenser IoT System - Automated Alert</p>
            </div>
        </body>
        </html>";
        
        return ['subject' => $subject, 'body' => $body];
    }
    
    public static function getExpirationAlertTemplate($riceData, $daysLeft) {
        $urgency = $daysLeft <= 0 ? 'EXPIRED' : ($daysLeft <= 7 ? 'URGENT' : 'WARNING');
        $color = $daysLeft <= 0 ? '#f44336' : ($daysLeft <= 7 ? '#ff5722' : '#ff9800');
        
        $subject = $urgency . " - Rice Expiration Alert - " . $riceData['name'];
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background-color: " . $color . "; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; background-color: #fff3e0; }
                .alert-details { background-color: white; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid " . $color . "; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>" . $urgency . " - Expiration Alert</h2>
            </div>
            <div class='content'>
                <h3>Rice Expiration Alert:</h3>
                <div class='alert-details'>
                    <p><strong>Rice Type:</strong> " . $riceData['name'] . "</p>
                    <p><strong>Manufacturer:</strong> " . $riceData['manufacturer'] . "</p>
                    <p><strong>Expiration Date:</strong> " . date('M d, Y', strtotime($riceData['expiration_date'])) . "</p>
                    <p><strong>Days Left:</strong> " . ($daysLeft <= 0 ? 'EXPIRED' : $daysLeft . ' days') . "</p>
                    <p><strong>Current Stock:</strong> " . $riceData['stock'] . " kg</p>
                    <p><strong>Alert Time:</strong> " . date('M d, Y H:i:s') . "</p>
                </div>
                <p><strong>Action Required:</strong> " . 
                    ($daysLeft <= 0 ? 'Remove expired rice from inventory immediately!' : 
                     ($daysLeft <= 7 ? 'Consider discounting or removing rice soon!' : 
                      'Plan for restocking before expiration!')) . "</p>
            </div>
            <div class='footer'>
                <p>Rice Dispenser IoT System - Automated Alert</p>
            </div>
        </body>
        </html>";
        
        return ['subject' => $subject, 'body' => $body];
    }
}
?>
