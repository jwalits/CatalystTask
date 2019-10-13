**This repository is created as part of the Catalyst-IT code task.**

## Part 1: user_upload.php

This PHP script is to be executed from the command line, which accepts a CSV file as an input and processed. The parsed data will be inserted into a MySQL database.

**Accepted Inputs**

- --file [CSV file name] : This is the CSV file to be processed and inserted into the database.
- --create_table : This command will create the database structure, but no file will be processed.
- --dry_run : This command can be used in association with the --file command to process the file without making any database changes.
- -u : The MySQL username
- -p : The MySQL password
- -h : The MySQL host
- -d : The MySQL database to be used.
- --help : This command will display the above information.

---

## Part 2: foobar.php

This PHP script is to be executed from the command line. This file doesn't accept any inputs. It simply outputs numbers 1 to 100 with the following rules

1. If number is divisible by 3, "foo" is printed instead of the number.
2. If number is divisible by 5, "bar" is printed instead of the number.
3. If number is divisible by both 3 and 5, "foobar" is printed instead of the number.