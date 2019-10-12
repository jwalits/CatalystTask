<?php

class DB
{

	private $username;
	private $password;
	private $host;
	private $databaseName;

	private $classQuery;

	public $link;

	function __construct($username, $password, $host, $databaseName)
	{
		$this->host = $host;
		$this->username = $user;
		$this->password = $pass;
		$this->databaseName = $databaseName;
	}

	function connect()
	{
		$this->link = new mysqli($this->host, $this->username, $this->password, $this->databaseName);
		if (mysqli_connect_error())
		{
			echo mysqli_connect_error();
			exit();
		}
		else
		{
			return $this->link;
		}
	}

	// Executes a database query
	function query($query)
	{
		$this->classQuery = $query;
		return $this->link->query($query);
	}
	
	function escapeString($query)
	{
		return $this->link->escape_string($query);
	}
	
	// Get the data return int result
	function numRows($result)
	{
		return $result->num_rows;
	}
	
	function lastInsertedID()
	{
		return $this->link->insert_id;
	}
	
	// Get query using assoc method
	function fetchAssoc($result)
	{
		return $result->fetch_assoc();
	}
	
	// Gets array of query results
	function fetchArray($result, $resultType = MYSQLI_ASSOC)
	{
		return $result->fetch_array( $resultType );
	}
	
	// Fetches all result rows as an associative array, a numeric array, or both
	function fetchAll($result, $resultType = MYSQLI_ASSOC)
	{
		return $result->fetch_all( $resultType );
	}
	
	// Get a result row as an enumerated array
	function fetchRow($result)
	{
		return $result->fetch_row();
	}
	
	// Free all MySQL result memory
	function freeResult($result)
	{
		$this->link->free_result( $result );
	}
	
	//Closes the database connection
	function close() 
	{
		$this->link->close();
	}
	
	function sql_error()
	{
		if(empty($error))
		{
			$errno = $this->link->errno;
			$error = $this->link->error;
		}
		return $errno . ' : ' . $error;
	}
}
