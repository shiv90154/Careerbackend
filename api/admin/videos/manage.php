          <?php
          
require_once '../../../includes/functions.php';
          requireAdmin();

          require_once '../../../includes/config.php';
          require_once '../../../includes/database.php';

          $db = (new Database())->getConnection();

          $stmt = $db->query("
            SELECT v.id, v.title, v.youtube_id, c.title as category
            FROM videos v
            JOIN categories c ON v.category_id = c.id
            ORDER BY v.created_at DESC
          ");

          echo json_encode($stmt->fetchAll());
