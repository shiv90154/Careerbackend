<?php
require_once 'config.php';
require_once 'logger.php';

class EmailService {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Send email using PHP mail function or SMTP
     */
    public function sendEmail($to, $subject, $body, $isHtml = true) {
        try {
            $headers = [
                'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
                'Reply-To: ' . SMTP_FROM_EMAIL,
                'X-Mailer: PHP/' . phpversion()
            ];
            
            if ($isHtml) {
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=UTF-8';
            }
            
            $success = mail($to, $subject, $body, implode("\r\n", $headers));
            
            if ($success) {
                Logger::info("Email sent successfully", [
                    'to' => $to,
                    'subject' => $subject
                ]);
            } else {
                Logger::error("Failed to send email", [
                    'to' => $to,
                    'subject' => $subject
                ]);
            }
            
            return $success;
        } catch (Exception $e) {
            Logger::error("Email sending error: " . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject
            ]);
            return false;
        }
    }
    
    /**
     * Send OTP email
     */
    public function sendOTP($email, $otp, $purpose = 'registration') {
        $subject = 'Your OTP for ' . SITE_NAME;
        
        $body = $this->getOTPTemplate($otp, $purpose);
        
        return $this->sendEmail($email, $subject, $body, true);
    }
    
    /**
     * Send welcome email
     */
    public function sendWelcomeEmail($email, $name) {
        $subject = 'Welcome to ' . SITE_NAME;
        
        $body = $this->getWelcomeTemplate($name);
        
        return $this->sendEmail($email, $subject, $body, true);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $resetToken) {
        $subject = 'Password Reset - ' . SITE_NAME;
        
        $body = $this->getPasswordResetTemplate($resetToken);
        
        return $this->sendEmail($email, $subject, $body, true);
    }
    
    /**
     * Send enrollment confirmation email
     */
    public function sendEnrollmentConfirmation($email, $name, $courseName) {
        $subject = 'Course Enrollment Confirmation - ' . SITE_NAME;
        
        $body = $this->getEnrollmentTemplate($name, $courseName);
        
        return $this->sendEmail($email, $subject, $body, true);
    }
    
    /**
     * Send payment confirmation email
     */
    public function sendPaymentConfirmation($email, $name, $courseName, $amount, $transactionId) {
        $subject = 'Payment Confirmation - ' . SITE_NAME;
        
        $body = $this->getPaymentTemplate($name, $courseName, $amount, $transactionId);
        
        return $this->sendEmail($email, $subject, $body, true);
    }
    
    /**
     * Get OTP email template
     */
    private function getOTPTemplate($otp, $purpose) {
        $purposeText = $purpose === 'registration' ? 'complete your registration' : 'reset your password';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>OTP Verification</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>" . SITE_NAME . "</h2>
                <h3>OTP Verification</h3>
                <p>Your OTP to $purposeText is:</p>
                <div style='background: #f3f4f6; padding: 20px; text-align: center; margin: 20px 0;'>
                    <h1 style='color: #2563eb; font-size: 32px; margin: 0;'>$otp</h1>
                </div>
                <p>This OTP is valid for " . OTP_EXPIRY_MINUTES . " minutes.</p>
                <p>If you didn't request this OTP, please ignore this email.</p>
                <hr style='margin: 30px 0;'>
                <p style='font-size: 12px; color: #666;'>
                    This is an automated email. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get welcome email template
     */
    private function getWelcomeTemplate($name) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Welcome</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>" . SITE_NAME . "</h2>
                <h3>Welcome, $name!</h3>
                <p>Thank you for joining " . SITE_NAME . ". We're excited to have you on board!</p>
                <p>You can now:</p>
                <ul>
                    <li>Browse our courses</li>
                    <li>Take practice tests</li>
                    <li>Access study materials</li>
                    <li>Join live classes</li>
                </ul>
                <p>If you have any questions, feel free to contact our support team.</p>
                <hr style='margin: 30px 0;'>
                <p style='font-size: 12px; color: #666;'>
                    This is an automated email. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get password reset email template
     */
    private function getPasswordResetTemplate($resetToken) {
        $resetUrl = BASE_URL . "/reset-password?token=$resetToken";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>" . SITE_NAME . "</h2>
                <h3>Password Reset Request</h3>
                <p>You requested a password reset for your account.</p>
                <p>Click the button below to reset your password:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$resetUrl' style='background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                </div>
                <p>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='word-break: break-all;'>$resetUrl</p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this reset, please ignore this email.</p>
                <hr style='margin: 30px 0;'>
                <p style='font-size: 12px; color: #666;'>
                    This is an automated email. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get enrollment confirmation template
     */
    private function getEnrollmentTemplate($name, $courseName) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Enrollment Confirmation</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>" . SITE_NAME . "</h2>
                <h3>Enrollment Confirmation</h3>
                <p>Dear $name,</p>
                <p>Congratulations! You have successfully enrolled in:</p>
                <div style='background: #f3f4f6; padding: 20px; margin: 20px 0; border-left: 4px solid #2563eb;'>
                    <h4 style='margin: 0; color: #2563eb;'>$courseName</h4>
                </div>
                <p>You can now access the course materials and start learning.</p>
                <p>Happy learning!</p>
                <hr style='margin: 30px 0;'>
                <p style='font-size: 12px; color: #666;'>
                    This is an automated email. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get payment confirmation template
     */
    private function getPaymentTemplate($name, $courseName, $amount, $transactionId) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Payment Confirmation</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>" . SITE_NAME . "</h2>
                <h3>Payment Confirmation</h3>
                <p>Dear $name,</p>
                <p>Your payment has been successfully processed!</p>
                <div style='background: #f3f4f6; padding: 20px; margin: 20px 0;'>
                    <h4>Payment Details:</h4>
                    <p><strong>Course:</strong> $courseName</p>
                    <p><strong>Amount:</strong> ₹$amount</p>
                    <p><strong>Transaction ID:</strong> $transactionId</p>
                    <p><strong>Date:</strong> " . date('d M Y, H:i') . "</p>
                </div>
                <p>You now have full access to the course materials.</p>
                <p>Thank you for your purchase!</p>
                <hr style='margin: 30px 0;'>
                <p style='font-size: 12px; color: #666;'>
                    This is an automated email. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ";
    }
}