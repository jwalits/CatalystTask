<?php
require('libs/class.db.php');
require('libs/class.process.php');


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
		exit('Another process is already running');
	}
}
else
{
	touch('.user_upload.lock');
}

$process = new ProcessCSV();
$process->run();


?>