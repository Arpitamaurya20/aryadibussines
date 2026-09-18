<?php 
/**
 * DB Connect 
 */
class Configuration extends Core
{ 
	public $module_id;
	public $signature_url;
	public $asset_url;
	public $generate_report_url;
	function __construct()
	{
		$this->module_id = 1; 
	}
	public function getClientDB($masterconn,$OrgID,$ModuleID)
	{
		$where = " where OrgID = $OrgID and ModuleID = $ModuleID";
		$org_module_details = $this->_getTableDetails($masterconn,'org_module_mapping',$where);
		return $org_module_details;
	}
	public function getJWTKey()
	{
		return "L2A";
	}
	public function SetMediaURLs()
	{
		if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) 
		{
		    $this->signature_url = "https://localhost/Projects/uhl/uhl-management/service-reports/media/signature";
		    $this->asset_url = "https://localhost/Projects/uhl/uhl-management/service-reports/media/service_image";
		}
		else
		{
			$this->signature_url = "https://unitedhealthlumina.com/uhl-management/service-reports/media/signature";
		    $this->asset_url = "https://unitedhealthlumina.com/uhl-management/service-reports/media/service_image";
		}
		
	}
	public function SetGenerateReportURL()
	{
		if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) 
		{
		    $this->generate_report_url = "http://localhost/Projects/unitedhealthlumina_3.0/uhladmin/uhl-management/service-reports/generate-policy-doc-new.php";
		}
		else
		{
			$this->generate_report_url = "https://unitedhealthlumina.com/uhladmin/uhl-management/service-reports/generate-policy-doc-new.php";
		}
		
	}
}
?>