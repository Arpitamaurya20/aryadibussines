<?php 
class Categories extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function getAllCategories()
	{
		$response = array();
		$sql = "Select * from  manage_categories ORDER BY CategoriesName ASC";
		$result=mysqli_query($this->conn,$sql);
		if($result->num_rows>0)	
		{
			while($row = $result->fetch_assoc())
			{
				extract($row);
				array_push($response,$row);
			}
		}
		return $response;
	}
	public function getAllCategoriesNameArray()
	{
		$response = array();
		$sql = "Select * from  manage_categories ORDER BY CategoriesName ASC";
		$result=mysqli_query($this->conn,$sql);
		if($result->num_rows>0)	
		{
			while($row = $result->fetch_assoc())
			{
				extract($row);
				array_push($response,$row['CategoriesName']);
			}
		}
		return $response;
	}
	public function getAllSubCategoriesNameArray()
	{
		$response = array();
		$sql = "Select * from  manage_subcategories ORDER BY SubCategoriesName ASC";
		$result=mysqli_query($this->conn,$sql);
		if($result->num_rows>0)	
		{
			while($row = $result->fetch_assoc())
			{
				extract($row);
				array_push($response,$row['SubCategoriesName']);
			}
		}
		return $response;
	}

	public function getAllSubCategoriesfromCategoryID($CategoryID)
	{
		$response = array();
		$sql = "Select * from  manage_subcategories where Categories = $CategoryID ORDER BY SubCategoriesName ASC";
		$result=mysqli_query($this->conn,$sql);
		if($result->num_rows>0)	
		{
			while($row = $result->fetch_assoc())
			{
				extract($row);
				array_push($response,$row);
			}
		}
		return $response;
	}

	public function getAllSubCategories()
	{
		$response = array();
		$sql = "Select * from  manage_subcategories ORDER BY SubCategoriesName ASC";
		$result=mysqli_query($this->conn,$sql);
		if($result->num_rows>0)	
		{
			while($row = $result->fetch_assoc())
			{
				extract($row);
				array_push($response,$row);
			}
		}
		return $response;
	}

	public function getCategoryIDfromCategoryName($CategoryName)
	{
		$response = array();
		$filter = " where CategoriesName = '$CategoryName'";
		$CategoryID = $this->_getTableDetails($this->conn,'manage_categories',$filter)['ID'];
		return $CategoryID;
	}

	public function setCategoriesArray()
	{
		$categories_array_raw = $this->getAllCategories();
		$categories_array = array();
		foreach($categories_array_raw as $category)
		{
			$CategoryID = $category['ID'];
			$CategoryName = $category['CategoriesName'];
			$categories_array[$CategoryID]['CategoryName'] = $CategoryName;
		}
		return $categories_array;
	}

	public function setSubCategoriesArray()
	{
		$subcategories_array_raw = $this->_getTableRecords($this->conn,'manage_subcategories','where 1');
		$sub_categories_array = array();
		foreach($subcategories_array_raw as $subcategory)
		{
			$SubCategoryID = $subcategory['ID'];
			$SubCategoriesName = $subcategory['SubCategoriesName'];
			$sub_categories_array[$SubCategoryID]['SubCategoryName'] = $SubCategoriesName;
		}
		return $sub_categories_array;
	}

	

}