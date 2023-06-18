# php-test

This repo contains two example PHP scripts. 

### foobar.php
This is a simple fizzbuzz/foobar implementation.

### user_upload.php
This script can connect to a database, create/rebuild a 'users' table, and insert data from a valid CSV file.

Due to the requirements being fairly simple, I opted for bare PHP. If I were to continue working on this script, I would refactor the code to work with Composer, Symfony Console and PHPUnit and use an object-oriented style approach instead of a functional one.

One option that could be added to improve security would be entering passwords without passing them as arguments. I added the option to log unhandled exceptions and to skip invalid data in the CSV file instead of immediately exiting the program.

Running `php user_upload.php` will present the following information:
```
Description:
    This example script can create and insert data into a table called 'users'.
    It takes a CSV file with three columns: name, surname, email.

Usage:
    user_upload.php -d <database> [options...]

Arguments:
    -d                MySQL database name.
    -h                MySQL hostname. Example: 203.0.113.50:3306 [default: localhost]
    -u                MySQL username. [default: user]
    -p                MySQL password. [default: ]
    --create_table    Create/truncate a table called 'users' in the database. Data can be permanently deleted!
    --file            CSV file path used to insert data into 'users' table.
    --dry_run         Run the script without inserting data into 'users' table.
    --no_log          Do not log exceptions to 'user_upload.log'.
    --skip_invalid    Skip invalid CSV records instead of exiting.
    --help            Show this help message.
```

If the script won't run, make sure `mysqli` and `fileinfo` are available. On Ubuntu, you can try running `sudo apt install php-mysqli`.
