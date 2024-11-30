<?php

class User {
    private $db;

    // Constructor to initialize the database connection
    public function __construct($db) {
        $this->db = $db;
    }

    // Find a user by email or username
    public function findByEmailOrUsername($emailOrUsername) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :emailOrUsername OR username = :emailOrUsername LIMIT 1");
        $stmt->bindValue(':emailOrUsername', $emailOrUsername, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    // Find a user by email
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    // Find a user by username
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    // Create a new user
    public function create($fullname, $username, $email, $password) {
        $stmt = $this->db->prepare("INSERT INTO users (fullname, username, email, password) VALUES (:fullname, :username, :email, :password)");
        $stmt->bindValue(':fullname', $fullname, SQLITE3_TEXT);
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':password', $password, SQLITE3_TEXT);
        $stmt->execute();
    }

    // Update a user's email and/or password
    public function update($id, $email, $password) {
        $stmt = $this->db->prepare("UPDATE users SET email = :email, password = :password WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':password', $password, SQLITE3_TEXT);
        $stmt->execute();
    }

    // Delete a user by their ID
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
    }
}
?>
