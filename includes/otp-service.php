<?php
/**
 * OTP Service for Email Verification
 * Career Pathway Shimla
 */

require_once 'database.php';
require_once 'email-config.php';

class OTPService {
    private $db;
    private $emailService;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->emailService = new EmailService();
    }
    
    /**
     * Generate and send OTP to email
     */
    public function generateAndSendOTP($email, $purpose = 'registration', $name = '') {
        try {
            // Generate 6-digit OTP
            $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Set expiry time (10 minutes from now)
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Clean up old OTPs for this email and purpose
            $this->cleanupOldOTPs($email, $purpose);
            
            // Insert new OTP
            $stmt = $this->db->prepare("
                INSERT INTO email_otps (email, otp_code, purpose, expires_at) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->bind_param("ssss", $email, $otp, $purpose, $expiresAt);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to save OTP");
            }
            
            // Send OTP via email
            $emailResult = $this->emailService->sendOTP($email, $otp, $name);
            
            if (!$emailResult['success']) {
                throw new Exception($emailResult['message']);
            }
            
            return [
                'success' => true,
                'message' => 'OTP sent successfully to your email',
                'expires_in' => 600 // 10 minutes in seconds
            ];
            
        } catch (Exception $e) {
            error_log("OTP Generation Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.'
            ];
        }
    }
    
    /**
     * Verify OTP
     */
    public function verifyOTP($email, $otp, $purpose = 'registration') {
        try {
            $stmt = $this->db->prepare("
                SELECT id, expires_at, is_used 
                FROM email_otps 
                WHERE email = ? AND otp_code = ? AND purpose = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            $stmt->bind_param("sss", $email, $otp, $purpose);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid OTP code'
                ];
            }
            
            $otpData = $result->fetch_assoc();
            
            // Check if OTP is already used
            if ($otpData['is_used']) {
                return [
                    'success' => false,
                    'message' => 'OTP has already been used'
                ];
            }
            
            // Check if OTP is expired
            if (strtotime($otpData['expires_at']) < time()) {
                return [
                    'success' => false,
                    'message' => 'OTP has expired. Please request a new one.'
                ];
            }
            
            // Mark OTP as used
            $updateStmt = $this->db->prepare("
                UPDATE email_otps 
                SET is_used = 1 
                WHERE id = ?
            ");
            $updateStmt->bind_param("i", $otpData['id']);
            $updateStmt->execute();
            
            return [
                'success' => true,
                'message' => 'OTP verified successfully'
            ];
            
        } catch (Exception $e) {
            error_log("OTP Verification Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to verify OTP. Please try again.'
            ];
        }
    }
    
    /**
     * Check if email has pending OTP
     */
    public function hasPendingOTP($email, $purpose = 'registration') {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM email_otps 
                WHERE email = ? AND purpose = ? AND is_used = 0 AND expires_at > NOW()
            ");
            
            $stmt->bind_param("ss", $email, $purpose);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            
            return $data['count'] > 0;
            
        } catch (Exception $e) {
            error_log("Check Pending OTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get remaining time for OTP
     */
    public function getOTPRemainingTime($email, $purpose = 'registration') {
        try {
            $stmt = $this->db->prepare("
                SELECT expires_at 
                FROM email_otps 
                WHERE email = ? AND purpose = ? AND is_used = 0 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            $stmt->bind_param("ss", $email, $purpose);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return 0;
            }
            
            $data = $result->fetch_assoc();
            $expiresAt = strtotime($data['expires_at']);
            $now = time();
            
            return max(0, $expiresAt - $now);
            
        } catch (Exception $e) {
            error_log("Get OTP Remaining Time Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Clean up old OTPs
     */
    private function cleanupOldOTPs($email, $purpose) {
        try {
            // Mark old unused OTPs as used
            $stmt = $this->db->prepare("
                UPDATE email_otps 
                SET is_used = 1 
                WHERE email = ? AND purpose = ? AND is_used = 0
            ");
            $stmt->bind_param("ss", $email, $purpose);
            $stmt->execute();
            
            // Delete expired OTPs older than 24 hours
            $cleanupStmt = $this->db->prepare("
                DELETE FROM email_otps 
                WHERE expires_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $cleanupStmt->execute();
            
        } catch (Exception $e) {
            error_log("OTP Cleanup Error: " . $e->getMessage());
        }
    }
    
    /**
     * Send welcome email after successful registration
     */
    public function sendWelcomeEmail($email, $name) {
        return $this->emailService->sendWelcomeEmail($email, $name);
    }
}
?>