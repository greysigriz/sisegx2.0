<?php
// C:\xampp\htdocs\SISE\api\cors.php
// Enhanced CORS configuration for development

// Define allowed origins
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
    'http://sise.local:5173',
    'http://localhost:5175',
    'http://localhost:5174'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Check if the origin is allowed and set header
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Default to localhost:5173 if no origin detected
    header("Access-Control-Allow-Origin: http://localhost:5173");
}

// Essential CORS headers
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-Auth-Token, Cache-Control, Pragma");
header("Access-Control-Max-Age: 86400"); // 24 hours

// Handle preflight OPTIONS requests IMMEDIATELY
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Return success for preflight
    http_response_code(200);
    // Ensure no additional output
    exit(0);
}
?>