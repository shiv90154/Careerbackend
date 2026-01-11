<?php
/**
 * Email Configuration for Career Pathway Shimla
 * Using PHPMailer with SMTP configuration
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->setupSMTP();
    }
    
    private function setupSMTP() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = 'info@thecareespath.com'; // SMTP username
            $this->mailer->Password   = 'your-app-password'; // SMTP password (use app password for Gmail)
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = 587;
            
            // Default sender
            $this->mailer->setFrom('info@thecareespath.com', 'Career Pathway Shimla');
            
            // Character set
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }
    
    public function sendOTP($email, $otp, $name = '') {
        try {
            // Recipients
            $this->mailer->addAddress($email, $name);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Email Verification - Career Pathway Shimla';
            
            $htmlBody = $this->getOTPEmailTemplate($otp, $name);
            $this->mailer->Body = $htmlBody;
            
            // Plain text version
            $this->mailer->AltBody = "Your OTP for email verification is: $otp\n\nThis OTP will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nCareer Pathway Shimla Team";
            
            $result = $this->mailer->send();
            
            // Clear addresses for next email
            $this->mailer->clearAddresses();
            
            return [
                'success' => true,
                'message' => 'OTP sent successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send OTP email'
            ];
        }
    }
    
    public function sendWelcomeEmail($email, $name) {
        try {
            // Recipients
            $this->mailer->addAddress($email, $name);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Welcome to Career Pathway Shimla!';
            
            $htmlBody = $this->getWelcomeEmailTemplate($name);
            $this->mailer->Body = $htmlBody;
            
            // Plain text version
            $this->mailer->AltBody = "Welcome to Career Pathway Shimla, $name!\n\nThank you for joining our learning platform. We're excited to help you achieve your career goals.\n\nYou can now access all our courses, tests, and current affairs content.\n\nBest regards,\nCareer Pathway Shimla Team";
            
            $result = $this->mailer->send();
            
            // Clear addresses for next email
            $this->mailer->clearAddresses();
            
            return [
                'success' => true,
                'message' => 'Welcome email sent successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Welcome email error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send welcome email'
            ];
        }
    }
    
    private function getOTPEmailTemplate($otp, $name) {
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verification - Career Pathway Shimla</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #334155; background-color: #F5F7FA; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background-color: #FFFFFF; }
                .header { background: linear-gradient(135deg, #0B1C2D 0%, #1E3A5F 100%); color: #FFFFFF; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 40px 30px; }
                .otp-box { background-color: #F7E600; color: #0B1C2D; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; margin: 30px 0; border-radius: 8px; letter-spacing: 5px; }
                .info-box { background-color: #F5F7FA; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #0B1C2D; }
                .footer { background-color: #0B1C2D; color: #FFFFFF; padding: 20px; text-align: center; font-size: 14px; }
                .btn { display: inline-block; background-color: #F7E600; color: #0B1C2D; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
                .contact-info { margin-top: 20px; font-size: 12px; color: #64748B; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Career Pathway Shimla</h1>
                    <p>Email Verification Required</p>
                </div>
                
                <div class='content'>
                    <h2>Hello " . ($name ? $name : 'Student') . ",</h2>
                    
                    <p>Thank you for registering with Career Pathway Shimla! To complete your registration, please verify your email address using the OTP below:</p>
                    
                    <div class='otp-box'>$otp</div>
                    
                    <div class='info-box'>
                        <strong>Important:</strong>
                        <ul>
                            <li>This OTP is valid for 10 minutes only</li>
                            <li>Do not share this OTP with anyone</li>
                            <li>If you didn't request this, please ignore this email</li>
                        </ul>
                    </div>
                    
                    <p>Once verified, you'll have access to:</p>
                    <ul>
                        <li>📚 Comprehensive courses for competitive exams</li>
                        <li>📝 Practice tests and mock exams</li>
                        <li>📰 Daily current affairs updates</li>
                        <li>🎓 Certificates upon course completion</li>
                        <li>👨‍🏫 Expert guidance and support</li>
                    </ul>
                    
                    <p>If you have any questions, feel free to contact us at <strong>info@thecareespath.com</strong> or call us at <strong>+91-98052 91450</strong>.</p>
                    
                    <p>Best regards,<br>
                    <strong>Career Pathway Shimla Team</strong></p>
                </div>
                
                <div class='footer'>
                    <p><strong>Career Pathway Shimla</strong></p>
                    <p>D D Tower Building, Opposite Jubbal House, Above Homeopathic Clinic<br>
                    Sanjauli, Shimla - 171006, Himachal Pradesh</p>
                    <div class='contact-info'>
                        <p>📧 info@thecareespath.com | 📞 +91-98052 91450<br>
                        🌐 www.thecareerspathway.com | 📘 Facebook: Careerpoint.sml</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getWelcomeEmailTemplate($name) {
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Welcome to Career Pathway Shimla</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #334155; background-color: #F5F7FA; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background-color: #FFFFFF; }
                .header { background: linear-gradient(135deg, #0B1C2D 0%, #1E3A5F 100%); color: #FFFFFF; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 40px 30px; }
                .welcome-box { background: linear-gradient(135deg, #F7E600 0%, #F9EC33 100%); color: #0B1C2D; padding: 30px; border-radius: 8px; text-align: center; margin: 20px 0; }
                .feature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0; }
                .feature-item { background-color: #F5F7FA; padding: 20px; border-radius: 8px; text-align: center; }
                .footer { background-color: #0B1C2D; color: #FFFFFF; padding: 20px; text-align: center; font-size: 14px; }
                .btn { display: inline-block; background-color: #F7E600; color: #0B1C2D; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
                .contact-info { margin-top: 20px; font-size: 12px; color: #64748B; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Career Pathway Shimla</h1>
                    <p>Welcome to Your Learning Journey!</p>
                </div>
                
                <div class='content'>
                    <div class='welcome-box'>
                        <h2>🎉 Welcome, $name!</h2>
                        <p>Your account has been successfully created and verified.</p>
                    </div>
                    
                    <p>We're thrilled to have you join the Career Pathway Shimla family! You now have access to our comprehensive learning platform designed to help you achieve your career goals.</p>
                    
                    <h3>What's Available for You:</h3>
                    
                    <div class='feature-grid'>
                        <div class='feature-item'>
                            <h4>📚 Courses</h4>
                            <p>Expert-designed courses for competitive exams and skill development</p>
                        </div>
                        <div class='feature-item'>
                            <h4>📝 Tests</h4>
                            <p>Practice tests, mock exams, and live assessments</p>
                        </div>
                        <div class='feature-item'>
                            <h4>📰 Current Affairs</h4>
                            <p>Daily updates on current events and important news</p>
                        </div>
                        <div class='feature-item'>
                            <h4>🎓 Certificates</h4>
                            <p>Industry-recognized certificates upon course completion</p>
                        </div>
                    </div>
                    
                    <p style='text-align: center;'>
                        <a href='https://www.thecareerspathway.com/student/dashboard' class='btn'>Start Learning Now</a>
                    </p>
                    
                    <h3>Need Help?</h3>
                    <p>Our team is here to support you every step of the way:</p>
                    <ul>
                        <li>📧 Email us at: <strong>info@thecareespath.com</strong></li>
                        <li>📞 Call us at: <strong>+91-98052 91450</strong></li>
                        <li>🏢 Visit us at our Shimla center</li>
                        <li>📘 Follow us on Facebook: <strong>Careerpoint.sml</strong></li>
                    </ul>
                    
                    <p>Best regards,<br>
                    <strong>Career Pathway Shimla Team</strong></p>
                </div>
                
                <div class='footer'>
                    <p><strong>Career Pathway Shimla</strong></p>
                    <p>D D Tower Building, Opposite Jubbal House, Above Homeopathic Clinic<br>
                    Sanjauli, Shimla - 171006, Himachal Pradesh</p>
                    <div class='contact-info'>
                        <p>📧 info@thecareespath.com | 📞 +91-98052 91450<br>
                        🌐 www.thecareerspathway.com | 📘 Facebook: Careerpoint.sml</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>