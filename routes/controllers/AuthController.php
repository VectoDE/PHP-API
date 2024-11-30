<?php

// Including the JWT utility file which contains logic for token generation and verification
require_once __DIR__ . '/../../utils/jwt.php';

class AuthController
{
    private $userModel;

    // Constructor receives the User model object that handles user-related database operations
    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    // Register a new user with fullname, username, email, and password
    public function register($fullname, $username, $email, $password)
    {
        // Check if the email already exists in the database
        if ($this->userModel->findByEmail($email)) {
            return ['error' => 'Email already in use'];
        }
        // Check if the username already exists in the database
        if ($this->userModel->findByUsername($username)) {
            return ['error' => 'Username already in use'];
        }

        // Hash the password before saving to the database
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Create the user in the database with the provided data
        $this->userModel->create($fullname, $username, $email, $hashedPassword);

        return ['message' => 'User registered successfully'];
    }

    // Login user by email or username and password
    public function login($emailOrUsername, $password)
    {
        // Find the user by either email or username
        $user = $this->userModel->findByEmailOrUsername($emailOrUsername);

        // If user exists and the password is correct, generate a JWT token
        if ($user && password_verify($password, $user['password'])) {
            // Create JWT token with user data (ID, email, and username)
            $token = JWTHandler::generate(['id' => $user['id'], 'email' => $user['email'], 'username' => $user['username']]);
            return ['token' => $token];
        }

        return ['error' => 'Invalid email/username or password'];
    }

    // Logout function - Since JWT is stateless, we can instruct the client to delete the token
    public function logout()
    {
        return [
            'message' => 'User logged out successfully. Please remove the token from client side (e.g., from localStorage, sessionStorage, or cookies).'
        ];
    }
}
