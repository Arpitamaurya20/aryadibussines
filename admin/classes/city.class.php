<?php 
class City extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function getMappedCitiesofCityLead($EmployeeID,$department)
	{
		$response = array();
		if($department = "Corporate")
		{
			$where = " where CorporateLead = $EmployeeID ORDER BY CityName ASC";
		}
		else
		{
			$where = " where CityLead = $EmployeeID ORDER BY CityName ASC";
		}
		$cities = _getTableRecords($this->conn,'citydata', $where);
		return $cities;

	}

	public function getCitiesbyState($StateID)
	{
		if($StateID != -1)
		{
			$where = " where StateID = $StateID ORDER BY CityName ASC";
		}
		else
		{
			$where = " where 1  ORDER BY CityName ASC";
		}
		$cities = $this->_getTableRecords($this->conn,'citydata', $where);
		return $cities;
	}

	public function getAllCities()
	{
		$where = " where 1  ORDER BY CityName ASC";
		$cities = $this->_getTableRecords($this->conn,'citydata', $where);
		return $cities;
	}

	

}