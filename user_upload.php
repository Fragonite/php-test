<?php

$sql_host = "localhost";
$sql_user = get_current_user();
$sql_pass = "";
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