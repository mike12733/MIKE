<?php
class EmailNotification {
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->fromEmail = 'noreply@lnhs.edu.ph';
        $this->fromName = 'LNHS Documents Portal';
    }
    
    public function sendNotification($toEmail, $toName, $subject, $message) {
        // In a production environment, you would use PHPMailer or similar
        // For now, this is a basic implementation using PHP's mail() function
        
        $headers = [
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        $htmlMessage = $this->buildEmailTemplate($toName, $subject, $message);
        
        // In development, you might want to log emails instead of sending them
        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
            $this->logEmail($toEmail, $subject, $htmlMessage);
            return true;
        }
        
        return mail($toEmail, $subject, $htmlMessage, implode("\r\n", $headers));
    }
    
    public function sendRequestStatusUpdate($userEmail, $userName, $requestNumber, $status, $message = null) {
        $statusMessages = [
            'processing' => 'Your request is now being processed by our staff.',
            'approved' => 'Great news! Your request has been approved.',
            'denied' => 'Unfortunately, your request has been denied.',
            'ready_for_pickup' => 'Your document is ready for pickup at the registrar\'s office.',
            'completed' => 'Your request has been completed. Thank you for using our service.'
        ];
        
        $subject = "LNHS Document Request Update - {$requestNumber}";
        $defaultMessage = $statusMessages[$status] ?? 'Your request status has been updated.';
        $fullMessage = $message ?? $defaultMessage;
        
        return $this->sendNotification($userEmail, $userName, $subject, $fullMessage);
    }
    
    public function sendWelcomeEmail($userEmail, $userName, $userType) {
        $subject = 'Welcome to LNHS Documents Request Portal';
        $message = "Welcome to the LNHS Documents Request Portal! Your {$userType} account has been successfully created. You can now request documents online anytime.";
        
        return $this->sendNotification($userEmail, $userName, $subject, $message);
    }
    
    private function buildEmailTemplate($name, $subject, $message) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$subject}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎓 LNHS Documents Portal</h1>
                </div>
                <div class='content'>
                    <h2>Hello, {$name}!</h2>
                    <p>{$message}</p>
                    <p>If you have any questions, please contact the registrar's office.</p>
                    <a href='http://localhost/lnhs_index.php' class='button'>Visit Portal</a>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " LNHS Documents Request Portal. All rights reserved.</p>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function logEmail($to, $subject, $message) {
        $logEntry = "=== EMAIL LOG ===\n";
        $logEntry .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $logEntry .= "To: {$to}\n";
        $logEntry .= "Subject: {$subject}\n";
        $logEntry .= "Message: {$message}\n";
        $logEntry .= "==================\n\n";
        
        $logFile = '../logs/email.log';
        
        // Create logs directory if it doesn't exist
        if (!is_dir('../logs')) {
            mkdir('../logs', 0777, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>