<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Check if admin user already exists
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin'");
    $checkStmt->execute(['admin@careerpath.com']);
    
    if ($checkStmt->rowCount() > 0) {
        echo "Admin user already exists!\n";
        
        // Update the existing admin user
        $updateStmt = $db->prepare("
            UPDATE users SET 
                full_name = ?, 
                password = ?, 
                phone = ?, 
                address = ?, 
                city = ?, 
                state = ?, 
                is_active = 1, 
                email_verified = 1,
                updated_at = NOW()
            WHERE email = ? AND role = 'admin'
        ");
        
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $updateStmt->execute([
            'Career Pathway Admin',
            $hashedPassword,
            '+91-98052-91450',
            'D D Tower Building, Opposite Jubbal House, Above Homeopathic Clinic',
            'Shimla',
            'Himachal Pradesh',
            'admin@careerpath.com'
        ]);
        
        echo "✓ Admin user updated with Career Pathway Shimla details\n";
    } else {
        // Create new admin user
        $insertStmt = $db->prepare("
            INSERT INTO users (role, full_name, email, password, phone, address, city, state, 
                              is_active, email_verified, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, NOW())
        ");
        
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $insertStmt->execute([
            'admin',
            'Career Pathway Admin',
            'admin@careerpath.com',
            $hashedPassword,
            '+91-98052-91450',
            'D D Tower Building, Opposite Jubbal House, Above Homeopathic Clinic',
            'Shimla',
            'Himachal Pradesh'
        ]);
        
        echo "✓ New admin user created\n";
    }
    
    // Create a demo student user
    $checkStudentStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
    $checkStudentStmt->execute(['student@example.com']);
    
    if ($checkStudentStmt->rowCount() == 0) {
        $insertStudentStmt = $db->prepare("
            INSERT INTO users (role, full_name, email, password, phone, city, state, 
                              is_active, email_verified, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, NOW())
        ");
        
        $studentPassword = password_hash('student123', PASSWORD_DEFAULT);
        $insertStudentStmt->execute([
            'student',
            'Demo Student',
            'student@example.com',
            $studentPassword,
            '+91-98765-43210',
            'Shimla',
            'Himachal Pradesh'
        ]);
        
        echo "✓ Demo student user created\n";
    } else {
        echo "Demo student user already exists\n";
    }
    
    // Display login credentials
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "LOGIN CREDENTIALS\n";
    echo str_repeat("=", 50) . "\n";
    echo "ADMIN LOGIN:\n";
    echo "Email: admin@careerpath.com\n";
    echo "Password: password\n";
    echo "\nSTUDENT LOGIN:\n";
    echo "Email: student@example.com\n";
    echo "Password: student123\n";
    echo str_repeat("=", 50) . "\n";
    
    // Show database statistics
    $stats = [];
    $tables = ['users', 'categories', 'tests', 'current_affairs', 'materials', 'blogs', 'live_classes'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
        $result = $stmt->fetch();
        $stats[$table] = $result['count'];
    }
    
    echo "\nDATABASE STATISTICS:\n";
    echo str_repeat("-", 30) . "\n";
    foreach ($stats as $table => $count) {
        echo sprintf("%-15s: %d records\n", ucfirst($table), $count);
    }
    echo str_repeat("-", 30) . "\n";
    
    echo "\n✅ Admin setup completed successfully!\n";
    echo "You can now login to the admin panel and test all functionality.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>