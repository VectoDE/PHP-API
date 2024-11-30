<?php
require_once __DIR__ . '/../../utils/jwt.php';

class AuthMiddleware
{
    // Static method to check authentication
    public static function checkAuth()
    {
        // Retrieve all headers from the incoming request
        $headers = getallheaders();

        // Check if the 'Authorization' header is set
        if (isset($headers['Authorization'])) {
            // Extract the token from the 'Authorization' header
            // It assumes the token is passed in the format "Bearer <token>"
            $token = str_replace('Bearer ', '', $headers['Authorization']);

            try {
                // Verify the token using the JWTHandler class
                // If the token is valid, the 'verify' method will decode it and return the decoded data
                $decoded = JWTHandler::verify($token);

                // Return the decoded token data if verification is successful
                return $decoded;
            } catch (Exception $e) {
                // If token verification fails, return false (authentication failed)
                return false;
            }
        }

        // Return false if the 'Authorization' header is not set or the token is invalid
        return false;
    }
}
