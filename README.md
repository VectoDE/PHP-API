# PHP REST API with SQLite and JWT Authentication

This is a simple REST API written in PHP, which utilizes SQLite for local database storage and JWT (JSON Web Token) for secure authentication. It allows user registration, login, and user management (CRUD operations) via API endpoints.

## Requirements

- PHP 8.2+
- Composer (for managing dependencies)
- SQLite (for local database storage)

## Installation

1. Clone the repository or download the files.
2. Navigate to the project directory and run `composer install` to install the necessary PHP dependencies, especially for JWT authentication.

   ```bash
   composer install
   ```

3. Set up your environment:

   - The API uses a local SQLite database (database.db). The database and required tables will be automatically created if they don't already exist.
   - Modify the database configuration in config/db.php if needed.

4. Start the PHP built-in server:

```bash
php -S localhost:8000 -t public/
```

This will serve your API at http://localhost:8000.

## API Endpoints

1. POST /register
Registers a new user with `fullname`, `username`, `email`, and `password`.

Request Body:

```json
{
  "fullname": "Test User",
  "username": "testuser",
  "email": "test@test.com",
  "password": "yourpassword"
}
```

Response:

```json
{
  "message": "User registered successfully"
}
```

Error Responses:

```json
{
  "error": "Email already in use"
}
```

2. POST /login
Logs in a user using email or username and password. Returns a JWT token upon successful login.

Request Body:

```json
{
  "emailOrUsername": "testuser",
  "password": "yourpassword"
}
```

Response:

```json
{
  "token": "your_jwt_token"
}
```

Error Responses:

```json
{
  "error": "Invalid email/username or password"
}
```

1. GET /users
Fetches all users. Requires authentication (JWT).

Headers:
    - ``Authorization: Bearer your_jwt_token``

Response:

```json
{
  "users": [
    {
      "id": 1,
      "fullname": "Test User",
      "username": "testuser",
      "email": "test@test.com"
    }
  ]
}
```

Error Responses:

```json
{
  "error": "Unauthorized"
}
```

1. PUT /users/{id}
Updates a user's information. Requires authentication (JWT).

Request Body:

```json
{
  "email": "newemail@test.com",
  "password": "newpassword"
}
```

Response:

```json
{
  "message": "User updated successfully."
}
```

5. DELETE /users/{id}
Deletes a user. Requires authentication (JWT).

Response:

```json
{
  "message": "User deleted successfully."
}
```

## Starting

```cmd
php -S localhost:8000 -t public
```

## How the Authentication Works

- JWT (JSON Web Token): JWT is used to authenticate users in the API. When a user logs in with valid credentials, the server responds with a JWT token.
- Authorization: For routes requiring user data (like /users), include the JWT token in the Authorization header like so:

    ```makefile
    Authorization: Bearer your_jwt_token
    ```

## Structure

- public/: Public directory that contains the entry point (index.php) and serves the API.
- config/: Contains the database configuration (db.php).
- controllers/: Contains the controller files like AuthController.php, UserController.php, which handle the logic of registration, login, and user management.
- middleware/: Contains middleware like AuthMiddleware.php, which checks if the user is authenticated.
- models/: Contains the User.php file, which interacts with the database (SQLite).
- utils/: Contains utility files like jwt.php, which handles JWT token creation and verification.

## Technologies Used

- PHP 8.2
- SQLite (for local database)
- JWT (JSON Web Token) for Authentication
- Composer for managing dependencies

## Troubleshooting

- SQLite Errors: Ensure that your SQLite database file (database.db) is created and located in the correct directory as specified in config/db.php.
- JWT Errors: If JWT is not working, make sure your jwt.php file has the correct secret key and the JWT library is installed.

## License

[MIT License](https://github.com/VectoDE/PHP-API/blob/main/LICENSE)