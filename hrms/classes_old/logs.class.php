<?php 
class Logs extends Core
{
	private $api_log_file;
	function __construct()
	{
		$this->setTimeZone();
	}
	public function SetCurrentAPILogFile()
	{
		$current_month_name = strtolower(date('F'));
		$folderPath = '../logs';
		if (is_dir($folderPath)) {
			
		} else {
			$folderPath = '../../logs';
		}
		
		$file_path = $folderPath."/api_logs_".$current_month_name.".txt";
		if (!file_exists($file_path)) 
		{
		    // Create the file
		    $file_handle = fopen($file_path, "w");
		    if ($file_handle === false) 
		    {
		    } 
		    else 
		    {
		    	$this->api_log_file = $file_path;
		        fclose($file_handle);
		    }
		}
		else
		{
			$this->api_log_file = $file_path;
		}
	}
	public function SetCurrentMailLogFile()
	{
		$current_month_name = strtolower(date('F'));
		$folderPath = '../logs';
		if (is_dir($folderPath)) {
			
		} else {
			$folderPath = '../../logs';
		}
		
		$file_path = $folderPath."/mail_logs_".$current_month_name.".txt";
		if (!file_exists($file_path)) 
		{
		    // Create the file
		    $file_handle = fopen($file_path, "w");
		    if ($file_handle === false) 
		    {
		    } 
		    else 
		    {
		    	$this->api_log_file = $file_path;
		        fclose($file_handle);
		    }
		}
		else
		{
			$this->api_log_file = $file_path;
		}
	}
	public function WriteLog($txt,$file_name,$line_number)
	{
		if (empty($this->api_log_file)) {
			return;
		}
		$logFile = @fopen($this->api_log_file, "a");
		if ($logFile === false) {
			return;
		}
		$information = date("Y-m-d")."_".date("H:i:s")."_".$file_name."_".$line_number;
		fwrite($logFile, "\n". $information);
		fwrite($logFile, "\n". $txt);
		fclose($logFile);
	}
	
}