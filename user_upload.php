<?php

$sql_host = "localhost";
$sql_user = get_current_user();
$sql_pass = null;
$sql_db = "";
$create_table = false;
$file = null;
$dry_run = false;

set_exception_handler("exception_handler");

$args = getopt("d:h:u:p:", ["file:", "create_table", "dry_run", "help"]);
// var_dump($args);

$help_output = "
Description:
    This example script can create and insert data into a table called \"users\".
    It takes a CSV file with three columns: name, surname, email.

Usage:
    user_upload.php -d <database> [options...]

Arguments:
    -d                MySQL database name.
    -h                MySQL hostname. Example: 203.0.113.50:3306 [default: $sql_host]
    -u                MySQL username. [default: $sql_user]
    -p                MySQL password. [default: $sql_pass]
    --create_table    Create a table called \"users\" in the database.
    --file            CSV file path used to insert data into \"users\" table.
    --dry_run         Run the script without inserting data into \"users\" table.
    --help            Show this help message.
";

if (isset($args["help"])) {
    echo $help_output;
    exit(0);
}

if (isset($args["d"])) {
    $sql_db = $args["d"];
} else {
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
    // try {
    $conn = new mysqli($sql_host, $sql_user, $sql_pass, $sql_db);
    // } catch (Exception $e) {
    //     echo "Error: " . $e->getMessage() . "\n";
    //     exit(1);
    // }
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

if (isset($args["file"])) {
    $file = $args["file"];

    if (!(is_readable($file))) {
        echo "Error: File does not exist or is not readable.\n";
        exit(1);
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file);
    finfo_close($finfo);

    if ($mime !== "text/plain" || $mime !== "text/csv") {
        echo "Error: Invalid file type. Only CSV files are allowed.\n";
        exit(1);
    }

    $file_pointer = fopen($file, "r");
    $row = fgetcsv($file_pointer);
    $row = array_map("trim", $row);
    $row = array_map("strtolower", $row);
    if (count($row) !== 3 || $row[0] !== "name" || $row[1] !== "surname" || $row[2] !== "email") {
        echo "Error: Expected three columns in $file: name, surname, email\n";
        exit(1);
    }

    $line_number = 1;
    while (($row = fgetcsv($file_pointer)) !== false) {
        if (count($row) !== 3) {
            fwrite(STDERR, "Error: Expected three columns on line $line_number\n");
            exit(1);
        }


        // Check if email is valid
        if (!filter_var($row[2], FILTER_VALIDATE_EMAIL)) {
            fwrite(STDERR, "Error: Invalid email address on line $line_number: $row[2]\n");
            exit(1);
        }

        // Capitalise the first letter in name and surname
        // Note this will wrongly capitalise surnames such as von der Leyen
        // It is assumed surnames like McDonald are capitalised correctly
        $row[0] = ucfirst($row[0]);
        $row[1] = ucfirst($row[1]);
        $line_number++;
    }
}


function exception_handler($exception)
{
    $timestamp = date("Y-m-d H:i:s");
    error_log("[$timestamp] " . $exception . "\n", 3, "user_upload.log");
    fwrite(STDERR, "Error: " . $exception->getMessage() . "\n");
    exit(1);
}