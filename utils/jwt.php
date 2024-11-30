<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Include the Composer autoloader for external libraries

use Firebase\JWT\JWT; // Import the Firebase JWT library
use Firebase\JWT\Key; // Import the Key class for JWT verification

class JWTHandler {
    private static $secretKey = 'your_secret_key'; // The secret key used to sign and verify JWTs (should be kept secret)

    // Method to generate a JWT token
    public static function generate($payload) {
        return JWT::encode($payload, self::$secretKey, 'HS256'); // Encode the payload using the secret key and HS256 algorithm
    }

    // Method to verify a JWT token
    public static function verify($token) {
        return JWT::decode($token, new Key(self::$secretKey, 'HS256')); // Decode and verify the token using the secret key and HS256 algorithm
    }
}
?>
