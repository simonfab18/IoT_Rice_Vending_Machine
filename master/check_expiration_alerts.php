<?php
require_once 'database.php';
require_once 'email_notifications.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get all rice inventory with expiration dates
    $stmt = $conn->query("SELECT * FROM rice_inventory WHERE expiration_date IS NOT NULL ORDER BY expiration_date ASC");
    $riceInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $today = new DateTime();
    $alertsCreated = 0;
    
    foreach ($riceInventory as $rice) {
        if ($rice['expiration_date']) {
            $expirationDate = new DateTime($rice['expiration_date']);
            $daysLeft = $today->diff($expirationDate)->days;
            
            $riceName = $rice['name'];
            $alertMessage = '';
            $alertType = 'expiration';
            
            if ($expirationDate < $today) {
                // Rice is expired
                $alertMessage = "EXPIRED: {$riceName} has expired on " . $expirationDate->format('M d, Y') . " - Remove from inventory immediately!";
                
                // Check if this alert already exists
                $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
                $stmt->execute(["%{$riceName}%expired%"]);
                $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
                
                if ($alertCount == 0) {
                    $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, ?, 'active')");
                    $stmt->execute([$alertMessage, $alertType]);
                    $alertsCreated++;
                    
                    // Send email notification for expired rice
                    try {
                        $emailSent = EmailNotifications::sendExpirationAlert($rice, $daysLeft);
                        if ($emailSent) {
                            error_log("Expiration email sent for expired rice: " . $rice['name']);
                        } else {
                            error_log("Failed to send expiration email for expired rice: " . $rice['name']);
                        }
                    } catch (Exception $e) {
                        error_log("Expiration email error: " . $e->getMessage());
                    }
                }
                
            } elseif ($daysLeft <= 7) {
                // Rice is expiring within 7 days
                $alertMessage = "URGENT: {$riceName} expires in {$daysLeft} days on " . $expirationDate->format('M d, Y') . " - Consider discounting or removing!";
                
                // Check if this alert already exists
                $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
                $stmt->execute(["%{$riceName}%expires in%"]);
                $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
                
                if ($alertCount == 0) {
                    $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, ?, 'active')");
                    $stmt->execute([$alertMessage, $alertType]);
                    $alertsCreated++;
                    
                    // Send email notification for urgent expiration
                    try {
                        $emailSent = EmailNotifications::sendExpirationAlert($rice, $daysLeft);
                        if ($emailSent) {
                            error_log("Urgent expiration email sent for rice: " . $rice['name']);
                        } else {
                            error_log("Failed to send urgent expiration email for rice: " . $rice['name']);
                        }
                    } catch (Exception $e) {
                        error_log("Urgent expiration email error: " . $e->getMessage());
                    }
                }
                
            } elseif ($daysLeft <= 30) {
                // Rice is expiring within 30 days
                $alertMessage = "WARNING: {$riceName} expires in {$daysLeft} days on " . $expirationDate->format('M d, Y') . " - Plan for restocking!";
                
                // Check if this alert already exists
                $stmt = $conn->prepare("SELECT COUNT(*) as alert_count FROM alerts WHERE message LIKE ? AND status = 'active'");
                $stmt->execute(["%{$riceName}%expires in%"]);
                $alertCount = $stmt->fetch(PDO::FETCH_ASSOC)['alert_count'];
                
                if ($alertCount == 0) {
                    $stmt = $conn->prepare("INSERT INTO alerts (message, type, status) VALUES (?, ?, 'active')");
                    $stmt->execute([$alertMessage, $alertType]);
                    $alertsCreated++;
                    
                    // Send email notification for warning expiration
                    try {
                        $emailSent = EmailNotifications::sendExpirationAlert($rice, $daysLeft);
                        if ($emailSent) {
                            error_log("Warning expiration email sent for rice: " . $rice['name']);
                        } else {
                            error_log("Failed to send warning expiration email for rice: " . $rice['name']);
                        }
                    } catch (Exception $e) {
                        error_log("Warning expiration email error: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Expiration check completed. {$alertsCreated} new alerts created.",
        'alerts_created' => $alertsCreated
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
