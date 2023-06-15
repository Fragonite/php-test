<?php

$sql_host = "localhost";
$sql_user = get_current_user();
$sql_pass = null;
$sql_db = "";
$create_table = false;
$file = null;
$dry_run = false;

$args = getopt("d:h:u:p:", ["file:", "create_table", "dry_run", "help"]);
var_dump($args);

$help_output = "Description:
    This example script can create and insert data into a table called \"users\".
    It takes a CSV file with three columns: name, surname, email.

Usage:
    user_upload.php -d <database> [options...]

Arguments:
    -d                MySQL database name.
    -h                MySQL hostname. [default: $sql_host]
    -u                MySQL username. [default: $sql_user]
    -p                MySQL password. [default: $sql_pass]
    --create_table    Create a table called \"users\" in the database.
    --file            CSV file path used to insert data into \"users\" table.
    --dry_run         Run the script without inserting data into \"users\" table.
    --help            Show this help message.";

if (isset($args["help"])) {
    echo $help_output;
    exit(0);
}

if (isset($args["d"])) {
    $sql_db = $args["d"];
} else {
    echo "Error: Database name is required.\n";
    echo $help_output;
    exit(1);
}

if (isset($args["h"])) {
    $sql_host = $args["h"];
}
if (isset($args["u"])) {
    $sql_user = $args["u"];
}
if (isset($args["p"])) {
    $sql_pass = $args["p"];
}

if (isset($args["create_table"])) {
    $create_table = true;
}

if ($create_table) {
    $conn = new mysqli($sql_host, $sql_user, $sql_pass, $sql_db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(30) NOT NULL,
        surname VARCHAR(30) NOT NULL,
        email VARCHAR(50) NOT NULL UNIQUE
    )";

    if ($conn->query($sql) === false) {
        echo "Error creating table: " . $conn->error . "\n";
        $conn->close();
        exit(1);
    }

    echo "Table created successfully.\n";
    $conn->close();
}