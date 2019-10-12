<?php

class ProcessCSV
{
	// define all accepted arguments via command line
	// short options: -u, -p, -h, -d
	// long options: --file, --create_table, --dry_run, --help
	private $shortoptions = "u:p:h:d:";
	private $longoptions  = array('help', 'file:', 'create_table', 'dry_run');

	private $filenameToProcess;
	private $requireDatabase = false;

	private $dataToInsert = array();

	private $db;

	function run()
	{
		$this->processInput();
		$this->processFile();
		$this->terminate();
		$this->connectDB();
		$this->createDatabaseTable();
		$this->populateDatabase();
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

		if (isset($options['create_table']) && (!isset($options['u']) || $options['p'] || $options['h'] || $options['d']))
		{
			$this->terminate("Usage: php user_upload.php --create_table -u username -p password -h host -d database");
		}

		if (!isset($options['file']))
		{
			$this->terminate("No --file parameter provided. Please check --help for all options");
		}

		$this->filenameToProcess = $options['file'];
		$this->requireDatabase = !isset($options['dry_run']); 
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
						$this->writeToConsole("\tInvalid email address ".$rowData[2]." found. It will not be inserted");
						continue;
					}

				}
				$row++;
			}
			fclose($fileHandle);
		}
	}

	function connectDB()
	{
		$this->db = new DB();
	}

	function createDatabaseTable()
	{

	}

	function populateDatabase()
	{

	}

	function terminate($msg='')
	{
		if (file_exists('./.user_upload.lock'))
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