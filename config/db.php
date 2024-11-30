<?php

// Define the path to the SQLite database file
// This variable stores the location of the database file where user data will be stored
$databaseFile = __DIR__ . '/../database.db';

// Create a new SQLite3 instance to connect to the database
// The $mysqli object represents the connection to the SQLite database
$mysqli = new SQLite3($databaseFile);

// SQL query to create the `users` table if it doesn't already exist
// The query defines the structure of the `users` table, with the following columns:
// - id: An auto-incrementing primary key to uniquely identify each user
// - fullname: A text field to store the user's full name, cannot be null
// - username: A text field to store the user's chosen username, must be unique, cannot be null
// - email: A text field to store the user's email address, must be unique, cannot be null
// - password: A text field to store the hashed password, cannot be null
$createTableQuery = "CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fullname TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL
)";

// Execute the query to create the table in the database
// The `exec` method runs the SQL query and ensures the `users` table is created
$mysqli->exec($createTableQuery);
