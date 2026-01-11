<?php
require_once '../../includes/config.php';
require_once '../../includes/response.php';

http_response_code(500);
ApiResponse::serverError('Internal server error');
?>