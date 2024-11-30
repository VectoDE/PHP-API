<?php

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/models/User.php';

$userModel = new User($mysqli); // Create an instance of the User model, passing in the database connection
$authController = new AuthController($userModel); // Create an instance of the AuthController
$userController = new UserController($userModel); // Create an instance of the UserController

// Helper function to send JSON responses
function sendJsonResponse($data, $statusCode = 200)
{
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data); // Return the data as a JSON response
    exit;
}

// Request URI and Method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // Extract the URI from the request
$requestMethod = $_SERVER['REQUEST_METHOD']; // Extract the HTTP method (GET, POST, etc.)

// Route processing
switch ($requestUri) {
    // Case for the /register route, accepting only POST requests
    case '/register':
        if ($requestMethod === 'POST') {
            $data = json_decode(file_get_contents("php://input")); // Get the POST data from the request body
            sendJsonResponse($authController->register($data->fullname, $data->username, $data->email, $data->password)); // Register the user
        }
        break;

    // Case for the /login route, accepting only POST requests
    case '/login':
        if ($requestMethod === 'POST') {
            $data = json_decode(file_get_contents("php://input")); // Get the POST data from the request body
            sendJsonResponse($authController->login($data->emailOrUsername, $data->password)); // Login with email/username and password
        }
        break;

    // Case for the /logout route, accepting only POST requests
    case '/logout':
        if ($requestMethod === 'POST') {
            sendJsonResponse($authController->logout()); // Logout the user
        }
        break;

    // Case for the /users route
    case '/users':
        if ($requestMethod === 'GET') {
            // Check for authentication before fetching the users
            $auth = AuthMiddleware::checkAuth();
            if ($auth) {
                sendJsonResponse($userController->getUsers()); // Get a list of users
            } else {
                sendJsonResponse(['error' => 'Unauthorized'], 401); // Unauthorized error if not authenticated
            }
        } elseif ($requestMethod === 'POST') {
            // Create a new user
            $data = json_decode(file_get_contents("php://input")); // Get POST data
            sendJsonResponse($userController->createUser($data->fullname, $data->username, $data->email, $data->password)); // Create the user
        }
        break;

    // Case for the /users/{id} route (for a specific user by ID)
    case '/users/{id}':
        // Extract user ID from the request URI using regex
        if (preg_match('/\/users\/(\d+)/', $requestUri, $matches)) {
            $userId = $matches[1]; // Extracted user ID

            if ($requestMethod === 'GET') {
                sendJsonResponse($userController->findUserById($userId)); // Get the user by ID
            } elseif ($requestMethod === 'PUT') {
                $data = json_decode(file_get_contents("php://input")); // Get the PUT data from the request body
                sendJsonResponse($userController->updateUser($userId, $data->email, $data->password)); // Update the user's email and password
            } elseif ($requestMethod === 'DELETE') {
                sendJsonResponse($userController->deleteUser($userId)); // Delete the user by ID
            }
        }
        break;

    // Case for the /users/{email} route (for finding a user by email)
    case '/users/{email}':
        if (preg_match('/\/users\/email\/([^\/]+)/', $requestUri, $matches)) {
            $email = $matches[1]; // Extract the email
            if ($requestMethod === 'GET') {
                sendJsonResponse($userController->findUserByEmail($email)); // Get the user by email
            }
        }
        break;

    // Case for the /users/{username} route (for finding a user by username)
    case '/users/{username}':
        if (preg_match('/\/users\/username\/([^\/]+)/', $requestUri, $matches)) {
            $username = $matches[1]; // Extract the username
            if ($requestMethod === 'GET') {
                sendJsonResponse($userController->findUserByUsername($username)); // Get the user by username
            }
        }
        break;

    // Default case when the route does not match any of the above
    default:
        sendJsonResponse(['error' => 'Route not found'], 404); // Return a 404 error if no route matches
        break;
}
?>
