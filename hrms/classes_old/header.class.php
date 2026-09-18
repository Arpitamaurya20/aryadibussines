<?php 
class Header extends Core
{
	public function __construct()
	{
	}
	public function setHeaderCenterFilter($url,$UserType)
	{	

		$page_name = basename($url);
		$page_name = pathinfo($page_name, PATHINFO_FILENAME);
		$json = file_get_contents('../navigation/header_navigation.json');
		$nav_array = json_decode($json);
		$nav_array = get_object_vars($nav_array);
		$center_filter_pages_array = $nav_array['center-filter-pages'];
		$center_filter_role_array = $nav_array['center-filter-roles'];
		if(in_array($page_name,$center_filter_pages_array) && in_array($UserType,$center_filter_role_array))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

}
?>