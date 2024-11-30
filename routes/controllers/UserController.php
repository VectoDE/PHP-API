<?php
class UserController
{
    private $userModel;

    // Constructor to initialize the User model
    // The constructor accepts an instance of the User model and assigns it to the $userModel property.
    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    // Get all users
    // This method retrieves all users from the database by calling the 'getAll' method from the User model.
    // It returns a response containing the list of users.
    public function getUsers()
    {
        $users = $this->userModel->getAll();  // Retrieve all users
        return ['users' => $users];  // Return the list of users
    }

    // Find a user by their ID
    // This method accepts a user ID and attempts to find the corresponding user in the database.
    // If the user is found, it returns the user details. If not, it returns an error message.
    public function findUserById($id)
    {
        $user = $this->userModel->findById($id);  // Search for user by ID
        if ($user) {
            return ['user' => $user];  // Return the user data if found
        }
        return ['error' => 'User not found'];  // Return error if user is not found
    }

    // Find a user by their username
    // This method accepts a username and attempts to find the corresponding user.
    // If the user is found, it returns the user details. If not, it returns an error message.
    public function findUserByUsername($username)
    {
        $user = $this->userModel->findByUsername($username);  // Search for user by username
        if ($user) {
            return ['user' => $user];  // Return the user data if found
        }
        return ['error' => 'User not found'];  // Return error if user is not found
    }

    // Find a user by their email
    // This method accepts an email and attempts to find the corresponding user.
    // If the user is found, it returns the user details. If not, it returns an error message.
    public function findUserByEmail($email)
    {
        $user = $this->userModel->findByEmail($email);  // Search for user by email
        if ($user) {
            return ['user' => $user];  // Return the user data if found
        }
        return ['error' => 'User not found'];  // Return error if user is not found
    }

    // Create a new user
    // This method accepts the user's full name, username, email, and password.
    // It first checks if the email or username already exists in the database.
    // If either is found, an error message is returned. Otherwise, the user is created.
    public function createUser($fullname, $username, $email, $password)
    {
        // Check if the email already exists
        if ($this->userModel->findByEmail($email)) {
            return ['error' => 'Email already exists'];  // Return error if email exists
        }

        // Check if the username already exists
        if ($this->userModel->findByUsername($username)) {
            return ['error' => 'Username already exists'];  // Return error if username exists
        }

        // Create the user
        $this->userModel->create($fullname, $username, $email, $password);  // Call create method from User model
        return ['message' => 'User created successfully'];  // Return success message
    }

    // Update a user by their ID
    // This method accepts a user ID, email, and password to update the user’s details.
    // It calls the update method from the User model to make changes in the database.
    public function updateUser($id, $email, $password)
    {
        $this->userModel->update($id, $email, $password);  // Call update method from User model
        return ['message' => 'User updated successfully.'];  // Return success message
    }

    // Delete a user by their ID
    // This method accepts a user ID and deletes the corresponding user from the database.
    // It calls the delete method from the User model.
    public function deleteUser($id)
    {
        $this->userModel->delete($id);  // Call delete method from User model
        return ['message' => 'User deleted successfully.'];  // Return success message
    }
}
