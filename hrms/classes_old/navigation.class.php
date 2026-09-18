<?php
class Navigation extends Core
{
    public $_Nav_Dashboard;
    public $_Nav_Blog; // new item
    public $_Nav_HRMS;
    public function __construct()
    {
        $this->_Nav_Dashboard = false;
        $this->_Nav_Blog = false; // initialize new item
        $this->_Nav_HRMS = false;
    }

    public function setNavigation($role)
    {
        $jsonPath = '../navigation/roles_navigation.json';
        if (!file_exists($jsonPath)) $jsonPath = __DIR__ . '/../navigation/roles_navigation.json';
        if (!file_exists($jsonPath)) $jsonPath = dirname(__DIR__) . '/navigation/roles_navigation.json';
        $nav_array = json_decode(file_get_contents($jsonPath), true);
        if (!isset($nav_array[$role])) return; // role not found
        $temp_nav_array = $nav_array[$role];
        if (in_array("_Nav_Dashboard", $temp_nav_array)) $this->_Nav_Dashboard = true;
        if (in_array("_Nav_Blog", $temp_nav_array)) $this->_Nav_Blog = true;
        if (in_array("_Nav_HRMS", $temp_nav_array)) $this->_Nav_HRMS = true;

    }
}
?>
