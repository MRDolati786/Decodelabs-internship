<?php
http_response_code(200);
echo json_encode([
    'status' => 'OK',
    'message' => 'Server is running',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>