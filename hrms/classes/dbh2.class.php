<?php 
/**
 * DB Connect 
 */
class Dbh2
{ 
	private $dbname;
	private $servername;
	private $dbusername;
	private $password;
	function __construct()
	{
		if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
		    $this->servername = "localhost";
		    $this->dbusername = "root";
		    $this->password = "";
		    $this->dbname = "online_quiz";
		}
		else
		{
			$this->servername = "localhost";
		    $this->dbusername = "tathastu_testseries";
		    $this->password = "Tathastu@123ICS";
		    $this->dbname = "tathastu_testseries";
		}
	}

	public function _connectodb()
	{
		$connect = new mysqli($this->servername,$this->dbusername,$this->password,$this->dbname);
		if($connect->connect_error)
		{
			print_r("Connection Error: " . $connect->connect_error);
			return false;
		}
		else
		{
			return $connect;
		}
	}
}