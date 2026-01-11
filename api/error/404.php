<?php
require_once '../../includes/config.php';
require_once '../../includes/response.php';

http_response_code(404);
ApiResponse::notFound('API endpoint not found');
?>