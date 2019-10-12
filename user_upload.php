<?php
require('libs/class.db.php');
require('libs/class.process.php');


function terminate($msg)
{
	if (file_exists('./.user_upload.lock'))
	{
		unlink('.user_upload.lock');
	}
	exit($msg."\n\n");

}

// first check if another process is running first
if (file_exists('.user_upload.lock'))
{
	if (time() - filemtime('.user_upload.lock') > 5 * 60)
	{
		// force clear lock if running for more than 5 mins
		unlink('.user_upload.lock');
	}
	else
	{
		terminate('Another process is already running');
	}
}
else
{
	touch('.user_upload.lock');
}

// define all accepted arguments via command line
// short options: -u, -p, -h, -d
// long options: --file, --create_table, --dry_run, --help
$options = getopt("u:p:h:d:", array('help::', 'file:', 'create_table', 'dry_run'));

// parse arguments first
if (isset($options['help']))
{
	// print out help commands and finish
	terminate('Usage: php user_upload.php [options] [--file] <file>

  --file <file>	   The CSV file to be parsed (Required)
  --create_table   Database tables will be created without file being parsed (Optional)
  --dry_run        CSV file will be parsed. However, no data will be inserted
  -u               MySQL username (Required for any DB functions)
  -p               MySQL password (Required for any DB functions)
  -h               MySQL host (Required for any DB functions)
  -d               MySQL database name to use (Required for any DB functions)
  
  -h               This help');
}

if (isset($options['create_table']) && (!isset($options['u']) || $options['p'] || $options['h'] || $options['d']))
{
	terminate("Usage: php user_upload.php --create_table -u username -p password -h host -d database");
}

if (!isset($options['file']))
{
	terminate("No --file parameter provided. Please check --help for all options");
}

?>