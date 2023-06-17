<?php

$sql_host = "localhost";
$sql_user = get_current_user();
$sql_pass = null;
$sql_db = "";
$create_table = false;
$file = null;
$dry_run = false;
$no_log = false;
$skip_invalid = false;

set_exception_handler("exception_handler");

$args = getopt("d:h:u:p:", ["file:", "create_table", "dry_run", "help", "no_log", "skip_invalid"]);
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
    --no_log          Do not log errors to \"user_upload.log\".
    --skip_invalid    Skip invalid CSV records instead of exiting.
    --help            Show this help message.
";

if (isset($args["no_log"])) {
    $no_log = true;
} else {
    set_exception_handler("exception_handler_with_log");
}
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
if (isset($args["skip_invalid"])) {
    $skip_invalid = true;
}

if (isset($args["create_table"])) {
    $create_table = true;
}

if ($create_table) {
    $conn = new mysqli($sql_host, $sql_user, $sql_pass, $sql_db);
    if ($conn->connect_error) {
        fwrite(STDERR, "Error: Connection failed: " . $conn->connect_error . "\n");
        exit(1);
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
        fwrite(STDERR, "Error: File does not exist or is not readable.\n");
        exit(1);
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file);
    finfo_close($finfo);

    if (!($mime === "text/plain" || $mime === "text/csv")) {
        fwrite(STDERR, "Error: Invalid file type. Only CSV files are allowed ($mime type found).\n");
        exit(1);
    }

    $file_pointer = fopen($file, "r");
    $row = fgetcsv($file_pointer);
    $row = array_map("trim", $row);
    $row = array_map("strtolower", $row);
    if (count($row) !== 3 || $row[0] !== "name" || $row[1] !== "surname" || $row[2] !== "email") {
        fwrite(STDERR, "Error: Expected three columns in $file: name, surname, email\n");
        exit(1);
    }

    $line_number = 2; // We already read line 1 (index 0)
    $lines_read = 0;
    $data = [];
    while (($row = fgetcsv($file_pointer)) !== false) {
        if (count($row) !== 3) {
            fwrite(STDERR, "Error: Expected three columns on line $line_number.\n");
            if ($skip_invalid) {
                echo "Skipping line $line_number.\n";
                $line_number++;
                continue;
            }
            exit(1);
        }

        $row = array_map("trim", $row);

        // Check if email is valid
        if (!filter_var($row[2], FILTER_VALIDATE_EMAIL)) {
            fwrite(STDERR, "Error: Invalid email address on line $line_number: $row[2]\n");
            if ($skip_invalid) {
                echo "Skipping line $line_number.\n";
                $line_number++;
                continue;
            }
            exit(1);
        }

        // Capitalise the first letter in name and surname
        // Note this will wrongly capitalise surnames such as von der Leyen
        // https://www.kalzumeus.com/2010/06/17/falsehoods-programmers-believe-about-names/
        $row[0] = ucfirst($row[0]);
        $row[1] = ucfirst($row[1]);
        $data[] = $row;
        $line_number++;
        $lines_read++;
    }

    fclose($file_pointer);
    if ($lines_read === 0) {
        fwrite(STDERR, "Error: No valid data found in $file.\n");
        exit(1);
    }
    echo "File read successfully. $lines_read lines read.\n";

    foreach ($data as $entry) {
        echo "Inserting $entry[0] $entry[1] $entry[2] into database.\n";
    }

    $conn = new mysqli($sql_host, $sql_user, $sql_pass, $sql_db);
    if ($conn->connect_error) {
        fwrite(STDERR, "Error: Connection failed: " . $conn->connect_error . "\n");
        exit(1);
    }
    $statement = $conn->prepare("INSERT INTO users (name, surname, email) VALUES (?, ?, ?)");
    $entries_inserted = 0;
    foreach ($data as $entry) {
        $statement->bind_param("sss", $entry[0], $entry[1], $entry[2]);
        try {
            $statement->execute();
        } catch (Exception $e) {
            fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
            echo "Skipping.\n";
            $entries_inserted--;
        }
        $entries_inserted++;
    }
    $statement->close();
    $conn->close();
    if ($entries_inserted === 0) {
        fwrite(STDERR, "Error: No entries inserted.\n");
        exit(1);
    }
    echo "$entries_inserted entries inserted successfully.\n";
    exit(0);
}

function exception_handler_with_log($exception)
{
    $timestamp = date("Y-m-d H:i:s");
    error_log("[$timestamp] " . $exception . "\n", 3, "user_upload.log");
    fwrite(STDERR, "Error: " . $exception->getMessage() . "\n");
    exit(1);
}

function exception_handler($exception)
{
    fwrite(STDERR, "Error: " . $exception->getMessage() . "\n");
    exit(1);
}