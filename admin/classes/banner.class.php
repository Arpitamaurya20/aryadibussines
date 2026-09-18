<?php

class Banner extends Core
{


  private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

  public function addBanners($data){
  
    $response = array();
		$Banner = $data['banner'];
		$CreatedDate = $data['added_on'];
		$sql_insert = "INSERT INTO banners(banner,added_on) VALUES ('$Banner','$CreatedDate')";
		return $this->_InsertTableRecords($this->conn,$sql_insert);
  }


}








?>