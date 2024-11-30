<?php
// Include the database configuration file and the routing file

// Including the `db.php` file which handles the database connection
// The `dirname(__DIR__)` function ensures that the path is relative to the parent directory of the current file, making it portable
// This file establishes the connection to the SQLite database
require_once dirname(__DIR__) . '/config/db.php';

// Including the `api.php` file where the main routing and request handling logic is implemented
// This file defines the routes (e.g., `/register`, `/login`) and processes the HTTP requests made to the API
require_once dirname(__DIR__) . '/routes/api.php';
