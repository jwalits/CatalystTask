<?php

class ProcessCSV
{
	// define all accepted arguments via command line
	// short options: -u, -p, -h, -d
	// long options: --file, --create_table, --dry_run, --help
	private $shortoptions = "u:p:h:d:";
	private $longoptions  = array('help', 'file:', 'create_table', 'dry_run');

	private $processFile = true;
	private $filenameToProcess;
	private $requireDatabase = false;
	private $createTable = false;
	private $dryRun = false;

	private $dataToInsert = array();

	private $db;
	private $username;
	private $password;
	private $host;
	private $databaseName;

	function run()
	{
		$this->processInput();

		if ($this->processFile)
		{
			$this->processFile();
		}

		if ($this->dryRun)
		{
			$this->terminate("Dry run complete. No data inserted into the the database");
		}

		if ($this->requireDatabase)
		{
			$this->connectDB();
			$this->createDatabaseTable();

			// now populate the database 
			if (!$this->createTable)
			{
				$this->populateDatabase();
			}
		}

		$this->terminate();
	}

	function processInput()
	{
		$options = getopt($this->shortoptions, $this->longoptions);

		// parse arguments first
		if (isset($options['help']))
		{
			// print out help commands and finish
			$this->terminate("Usage: php user_upload.php [options] [--file] <file>\n--file <file>	   The CSV file to be parsed (Required)\n--create_table   Database tables will be created without file being parsed (Optional)\n--dry_run        CSV file will be parsed. However, no data will be inserted\n-u               MySQL username (Required for any DB functions)\n-p               MySQL password (Required for any DB functions)\n-h               MySQL host (Required for any DB functions)\n-d               MySQL database name to use (Required for any DB functions)\n\n-h               This help");
		}

		if (isset($options['file']))
		{
			$this->filenameToProcess = $options['file'];
		}

		if (isset($options['dry_run']))
		{
			if (!isset($options['file']))
			{
				$this->terminate("No --file parameter provided. Please check --help for all options");
			}

			$this->dryRun = true;
			return;
		}


		if (isset($options['create_table']) && (!isset($options['u']) || !isset($options['p']) || !isset($options['h']) || !isset($options['d'])))
		{
			$this->terminate("Usage: php user_upload.php --create_table -u username -p password -h host -d database");
		}

		if (!isset($options['dry_run']) && (!isset($options['u']) || !isset($options['p']) || !isset($options['h']) || !isset($options['d'])))
		{
			$this->terminate("Database information required. Please check --help for usage");
		}

		// now store all the database values
		if (isset($options['u']))
		{
			$this->username = $options['u'];
		}
		if (isset($options['p']))
		{
			$this->password = $options['p'];
		}
		if (isset($options['h']))
		{
			$this->host = $options['h'];
		}
		if (isset($options['d']))
		{
			$this->databaseName = $options['d'];
		}

		$this->requireDatabase = !isset($options['dry_run']);

		if (isset($options['create_table']))
		{
			$this->processFile = false;
			$this->createTable = isset($options['create_table']);
			return;
		}
	}

	function processFile()
	{

		if (!file_exists($this->filenameToProcess))
		{
			$this->terminate('Filename: '.$this->filenameToProcess.' not found.');
		}

		if (($fileHandle = fopen($this->filenameToProcess, "r")) !== false)
		{
			$row = 0;
			while (($rowData = fgetcsv($fileHandle, 0, ",")) !== false)
			{
				if ($row > 0)
				{
					$this->writeToConsole('Processing row: '.implode(',', $rowData));

					// assumed struture for the csv file [0] => name, [1]=> surname, [2] => email
					if (count($rowData) < 3)
					{
						$this->writeToConsole("\tInvalid row data found - it will not be inserted");
						continue;
					}

					// now validate the inputs
					if (!filter_var(trim($rowData[2]), FILTER_VALIDATE_EMAIL))
					{
						$this->writeToConsole("\tInvalid email address ".$rowData[2]." found. Row will not be inserted");
						continue;
					}

					// prepare the object to be inserted
					$personObject = new stdClass();
					$personObject->name = $this->cleanName($rowData[0]);
					$personObject->surname = $this->cleanName($rowData[1]);
					$personObject->email = strtolower(trim($rowData[2]));

					$this->dataToInsert[] = $personObject;
				}
				$row++;
			}
			fclose($fileHandle);
		}
	}

	function cleanName($str)
	{
		$output = ucwords(strtolower(trim($str)));

		// now have to check for special cases, i.e O', St., Mc, D', L', etc..
		$specialChars = array("O'", "L'", "D'", 'St.', 'Mc');
		foreach ($specialChars as $delimiter)
		{
			$words = explode($delimiter, $output);
			// now capitalize first letter of each word
			$newWord = array();
			foreach ($words as $word)
			{
				$newWord[] = ucfirst($word);
			}
			$output = implode($delimiter, $newWord);
		}

		return $output;
	}

	function connectDB()
	{
		$this->db = new DB($this->username, $this->password, $this->host, $this->databaseName);
		$this->db->connect();
	}

	function createDatabaseTable()
	{
		// create the users table if it doesn't exist
		if ($this->db->query("SELECT name FROM users LIMIT 1") === false)
		{
			if (!$this->db->query('CREATE TABLE IF NOT EXISTS `users` ( `name` VARCHAR(50) NOT NULL , `surname` VARCHAR(50) NOT NULL , `email` VARCHAR(200) NOT NULL , UNIQUE (`email`))'))
			{
				$this->terminate('MySQL table creation failed - '.$this->db->sql_error());
			}
			else
			{
				$this->writeToConsole('MySQL table creation successfull');
			}
		}
		else
		{
			$this->writeToConsole('MySQL table already exists.');
		}
	}

	function populateDatabase()
	{
		if (empty($this->dataToInsert))
		{
			$this->writeToConsole('No data found to insert. No further action to be taken');
		}
		else
		{
			$this->writeToConsole('Preparing data to be inserted into database');
			foreach ($this->dataToInsert as $insertObj)
			{
				$this->writeToConsole('Preparing row: '.$insertObj->name.', '.$insertObj->surname.', '.$insertObj->email);

				// first check if email address exists, if exists - update data, otherwise insert
				if ($this->db->numRows($this->db->query("SELECT * FROM users WHERE email = '".$this->db->escapeString($insertObj->email)."'")) > 0)
				{
					if ($this->db->query("UPDATE users SET name = '".$this->db->escapeString($insertObj->name)."', surname = '".$this->db->escapeString($insertObj->surname)."' WHERE email = '".$this->db->escapeString($insertObj->email)."'"))
					{
						$this->writeToConsole("\tRow updated successfully");
					}
					else
					{
						$this->writeToConsole("\tRow could not be updated - ".$this->db->sql_error());
					}
				}
				else
				{
					if ($this->db->query("INSERT INTO users (name, surname, email) VALUES ('".$this->db->escapeString($insertObj->name)."', '".$this->db->escapeString($insertObj->surname)."', '".$this->db->escapeString($insertObj->email)."');"))
					{
						$this->writeToConsole("\tRow inserted into database");
					}
					else
					{
						$this->writeToConsole("\tRow could not be inserted - ".$this->db->sql_error());
					}
				}
			}
		}
		$this->writeToConsole("Database processing finished");

	}

	function terminate($msg='')
	{
		if (file_exists('.user_upload.lock'))
		{
			unlink('.user_upload.lock');
		}

		exit($msg."\n\n");
	}

	function writeToConsole($msg)
	{
		echo $msg."\n";
	}

	
}