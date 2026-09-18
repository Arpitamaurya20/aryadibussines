<?php 

class Core
{
	public function getURL()
	{
		if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) 
		{
			$_URL = "http://localhost/Projects/partnership-portal/";
			return $_URL;
		}
	}
	public function setTimeZone()
	{
		date_default_timezone_set('Asia/Kolkata');
	}

	public function SessionCheck()
	{
		@session_start();
		if(isset($_SESSION['pb_username']))
		{
			return $_SESSION['UserType'];
		}
		else
		{
			if(isset($_COOKIE['pb_username']))
			{
				$_SESSION['pb_username'] = $_COOKIE['pb_username'];
				$_SESSION['UserType'] = $_COOKIE['UserType'];
				$roles_array = unserialize($_COOKIE['Roles']);
				$_SESSION['Roles'] = $roles_array;
				return $_SESSION['UserType'];
			}
			else
			{
				if (strpos($_SERVER['REQUEST_URI'],'login') !== false) 
				{
				    
				} 
				else 
				{
				    if(file_exists("../authentication/login.php"))
						header('Location: ../authentication/login.php');
					else
						header('Location:../../authentication/login.php');
				}
					
			}
			return "Error";
		}
	}

	public function _InsertTableRecords($conn, $sql)
	{
		$response = array();
		$result = mysqli_query($conn, $sql);
		if ($result) {
			$response['message'] = "Data Inserted";
			$response['error'] = false;
			$lastId = mysqli_insert_id($conn);
			$response['last_insert_id'] = $lastId;
		} else {
			$response['sql'] = $sql;
			$response['error'] = true;
			$error = mysqli_error($conn);
			$response['message'] = $error;
			//echo $sql;
			//echo $error;
		}
		return $response;
	}

	public function _InsertTableRecords_prepare($conn, $tableName, $data)
	{
		$response = array();
		 // Build the columns and placeholders strings dynamically
	    $columns = implode(", ", array_keys($data));
	    $placeholders = implode(", ", array_fill(0, count($data), '?'));

	    // Prepare the SQL statement
	    $sql = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
	    $stmt = $conn->prepare($sql);

	    if ($stmt === false) {
	        die("Error preparing statement: " . $conn->error);
	    }

	    // Bind the parameters dynamically
	    $types = str_repeat('s', count($data)); // Assuming all parameters are strings; adjust as needed
	    $stmt->bind_param($types, ...array_values($data));

	    // Execute the statement
	    if (!$stmt->execute()) 
	    {
	    	$response['error'] = true;
	    	$response['message'] = $stmt->error;
	    }
	    else
	    {
	    	$response['error'] = false;
	    	$response['message'] = "Data Inserted";
	    	$response['last_insert_id'] = $conn->insert_id;
	    }

	    $stmt->close();
	    return $response;
	}

	public function _UpdateTableRecords_prepare($conn, $tableName, $data, $where)
	{
	    $response = array();

	    // Build the columns and placeholders strings dynamically for SET clause
	    $setParts = [];
	    foreach ($data as $column => $value) {
	        $setParts[] = "$column = ?";
	    }
	    $setClause = implode(", ", $setParts);

	    // Build the WHERE clause dynamically
	    $whereParts = [];
	    foreach ($where as $column => $value) {
	        $whereParts[] = "$column = ?";
	    }
	    $whereClause = implode(" AND ", $whereParts);

	    // Prepare the SQL statement
	    $sql = "UPDATE $tableName SET $setClause WHERE $whereClause";
	    $stmt = $conn->prepare($sql);

	    if ($stmt === false) {
	        die("Error preparing statement: " . $conn->error);
	    }

	    // Bind the parameters dynamically
	    $types = str_repeat('s', count($data) + count($where)); // Assuming all parameters are strings; adjust as needed
	    $params = array_merge(array_values($data), array_values($where));
	    $stmt->bind_param($types, ...$params);

	    // Execute the statement
	    if (!$stmt->execute()) 
	    {
	        $response['error'] = true;
	        $response['message'] = $stmt->error;
	    }
	    else
	    {
	        $response['error'] = false;
	        $response['message'] = "Data Updated";
	        $response['affected_rows'] = $stmt->affected_rows;
	    }

	    $stmt->close();
	    return $response;
	}


	function _InsertPreparedData($conn,$tableName, $columns, $values) 
	{
		$response = array();
		$response['error'] = false;
		$response['message'] = "Data inserted successfully";
		// Prepare a statement with placeholders for column names and values
		$sql = "INSERT INTO $tableName (".implode(", ", $columns).") VALUES (".rtrim(str_repeat("?, ", count($columns)), ", ").")";
		$stmt = $conn->prepare($sql);

		// Bind parameters
		$types = str_repeat("s", count($values));
		$stmt->bind_param($types, ...$values);
		if (!$stmt->bind_param($types, ...$values)) {
			$response['error'] = true;
		    $response['message'] = "Failed to bind parameters: " . $stmt->error;
		    $stmt->close();
		    return $response;
	  	}

		  // Execute statement
		if (!$stmt->execute()) {
			$response['error'] = true;
			$response['message'] = "Failed to execute statement: " . $stmt->error;
			$stmt->close();
			return $response;
		}

		// Close statement and database connection
		$stmt->close();
		return $response;
	}

	public function _UpdateTableRecords($conn, $table_name, $query_parameter)
	{
		$response = array();
	   	$sql = "UPDATE $table_name SET $query_parameter";
		$result = mysqli_query($conn, $sql);
		if ($result) {
			$response['message'] = "Data Updated";
			$response['error'] = false;
		} else {
			$response['sql'] = $sql;
			$response['error'] = true;
			$error = mysqli_error($conn);
			$response['message'] = $error;
			echo $sql;
			echo $error;
		}
		return $response;
	}

	public function delete_identity_filter($conn, $table, $query)
	{
		$sql = "Delete from $table $query";
		$result = mysqli_query($conn, $sql);
		if ($result) {
			return true;
		}
		return false;
	}

	public function delete_identity_filter_disable($conn, $table, $query)
	{
		$sql = "UPDATE $table SET IsActive = 0 $query";
		$result = mysqli_query($conn, $sql);
		if ($result) {
			return true;
		}
		return false;
	}

	public function _getTableRecords($conn, $table_name, $where)
	{
		$response = array();
		$sql = "Select * from $table_name $where";
		$result = mysqli_query($conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row);
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
	}
	public function _debug_getTableRecords($conn, $table_name, $where)
	{
		$response = array();
		$sql = "Select * from $table_name $where";
		echo $sql;
		$result = mysqli_query($conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row);
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
	}


	public function _getTableDetails($conn,$table_name, $where)
	{
		$sql = "Select * from $table_name $where";
		$result=mysqli_query($conn,$sql);
		if($result)
			$row = $result->fetch_assoc();
		else
		{
			$error = mysqli_error($conn);
			echo $sql;
			echo $error;
		}
		return $row;
	}
	public function _getSQLRecords($conn, $sql)
	{
		$response = array();
		$result = mysqli_query($conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row);
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
	}
	public function _getSQLDetails($conn, $sql)
	{
		$response = array();
		$result = mysqli_query($conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				$response = $row = $result->fetch_assoc();
			}
		} else {
			//echo $sql;
		}
		return $response;
	}
	public function _getDistinctTableRecords($conn,$table_name,$column_name,$where)
	{
		$response = array();
		$sql = "Select DISTINCT($column_name) as $column_name from $table_name $where";
		$result = mysqli_query($conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row);
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
	}
	public function _getTableRecordsassoc($conn, $table_name, $where)
	{
		$response = array();
		$sql = "Select * from $table_name $where";
		$result = mysqli_query($conn, $sql);
		if ($result)
		{
			if ($result->num_rows > 0)
			{
				$row = $result->fetch_assoc() ;
				return $row;
			}
		}
		else
		{
			//echo $sql;
		}
		//return $row;
	}
	public function check_unique_identity_filter($conn, $table, $filter)
	{
		$sql = "Select * from $table $filter";
		$result = mysqli_query($conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				return false;
			}
		}
		return true;
	}
	public function _getTotalRows($conn, $table, $filter)
	{
		if ($filter == "") {
			$sql = "Select COUNT(*) as no_count from $table";
		} else {
			$sql = "Select COUNT(*) as no_count from $table $filter";
		}
		//echo $sql;
		$result = mysqli_query($conn, $sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return $row['no_count'];
		} else {
			return 0;
		}
	}

	public function _getMaxIdentityValue($conn, $table, $column)
	{
		$sql = "Select COALESCE(MAX($column),0) as max_value from $table";
		$result = mysqli_query($conn, $sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return $row['max_value'];
		} else {
			return 0;
		}
	}
	public function _getMaxIdentityValue_filter($conn, $table, $column, $where_query)
	{
		$sql = "Select COALESCE(MAX($column),0) as max_value from $table $where_query";
		$result = mysqli_query($conn, $sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return $row['max_value'];
		} else {
			return 0;
		}
	}

	public function getEmployeeDetailsfromID($conn,$EmployeeID)
	{
		$where = " where ID = $EmployeeID";
		return _getTableDetails($conn,'employees',$where);
	}

	public function generateArraywithKey($data_array)
	{
		$array_temp = array();
		foreach($data_array as $data)
		{
			$ID = $data['ID'];
			$array_temp[$ID] = $data;
		}
		return $array_temp;
	}
	public function sendMailRequest($postdata,$url)
	{
		//$url = $this->getURL();
		$postdata = json_encode($postdata);
		$resource = $url;
		$ch = curl_init($resource);
		curl_setopt($ch, CURLOPT_URL, $resource);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_USERAGENT, 'api');
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch,  CURLOPT_RETURNTRANSFER, false);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_exec($ch);
		curl_close($ch);
		
	}

	public function sendCurlRequest($postdata,$url)
	{
		// Initialize cURL
	    $ch = curl_init($url);

	    // Configure cURL options
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));

	    // Execute cURL request and get the response
	    $response = curl_exec($ch);

	    // Check for cURL errors
	    if (curl_errno($ch)) {
	        $error_msg = curl_error($ch);
	    }

	    // Close cURL session
	    curl_close($ch);

	    // Handle error
	    if (isset($error_msg)) {
	        return ['error' => $error_msg];
	    }

	    // Return the response
	    return json_decode($response, true);
		
	}

	public function formatIndianNumber($number) {
	    $decimal = (string)($number - floor($number));
	            $money = floor($number);
	            $length = strlen($money);
	            $delimiter = '';
	            $money = strrev($money);
	 
	            for($i=0;$i<$length;$i++){
	                if(( $i==3 || ($i>3 && ($i-1)%2==0) )&& $i!=$length){
	                    $delimiter .=',';
	                }
	                $delimiter .=$money[$i];
	            }
	 
	            $result = strrev($delimiter);
	            $decimal = preg_replace("/0\./i", ".", $decimal);
	            $decimal = substr($decimal, 0, 3);
	 
	            if( $decimal != '0'){
	                $result = $result.$decimal;
	            }
	 
	            return $result;
	}
	public function convertToIndianCurrency($input) 
	{
	    // Set the rupee symbol
		    $rupeeSymbol = '₹';

		    // Convert the input to a float to handle both string and number inputs
		    $number = floatval($input);

		    // Round the number to two decimal places if it has a fractional part
		    $decimalPart = '';
		    if (strpos($input, '.') !== false) {
		        // Extract and round to two decimal places
		        $decimalPart = number_format($number, 2, '.', '');
		        $decimalPart = substr(strrchr($decimalPart, "."), 0); // Get the decimal part starting from '.'
		    }

		    // Get the integer part of the number
		    $integerPart = floor($number);

		    // Convert the integer part to a string for custom formatting
		    $integerPart = strval($integerPart);

		    // Format the integer part in the Indian currency style (e.g., lakh, crore)
		    $length = strlen($integerPart);
		    if ($length > 3) {
		        // Get the last 3 digits
		        $lastThree = substr($integerPart, -3);
		        // Get the digits before the last 3
		        $rest = substr($integerPart, 0, $length - 3);
		        // Apply Indian number system formatting (commas after every two digits for the rest part)
		        $rest = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest);
		        // Combine the rest and last three parts
		        $formattedInteger = $rest . ',' . $lastThree;
		    } else {
		        // If the number is less than 1000, no need for special formatting
		        $formattedInteger = $integerPart;
		    }

		    // Return the final formatted number with the rupee symbol and decimal part (if exists)
		    return $rupeeSymbol . ' ' . $formattedInteger . $decimalPart;
	}
	public function CheckRole($data,$Role_to_be_checked)
	{
	    if(isset($data['UserType']))
	    {
	        $UserType = $data['UserType'];
	        if($UserType == "Employee")
	        {
	            if(isset($data['Roles']['EmployeeRoles']))
	            {
	                $EmployeeRoles = $data['Roles']['EmployeeRoles'];
	                foreach($EmployeeRoles as $Role)
	                {
	                    if($Role == $Role_to_be_checked)
	                    {
	                        return true;
	                    }
	                }
	            }
	        }
	    }
	    return false;
	}

	public function cleantext($str)
	{
		$str = addslashes($str);
		$str = trim($str);
		return $str;
	} 

	public function numberToWords($number) 
	{
	    $hyphen      = '-';
	    $conjunction = ' and ';
	    $separator   = ', ';
	    $negative    = 'negative ';
	    $decimal     = ' point ';
	    $dictionary  = [
	        0                   => 'zero',
	        1                   => 'one',
	        2                   => 'two',
	        3                   => 'three',
	        4                   => 'four',
	        5                   => 'five',
	        6                   => 'six',
	        7                   => 'seven',
	        8                   => 'eight',
	        9                   => 'nine',
	        10                  => 'ten',
	        11                  => 'eleven',
	        12                  => 'twelve',
	        13                  => 'thirteen',
	        14                  => 'fourteen',
	        15                  => 'fifteen',
	        16                  => 'sixteen',
	        17                  => 'seventeen',
	        18                  => 'eighteen',
	        19                  => 'nineteen',
	        20                  => 'twenty',
	        30                  => 'thirty',
	        40                  => 'forty',
	        50                  => 'fifty',
	        60                  => 'sixty',
	        70                  => 'seventy',
	        80                  => 'eighty',
	        90                  => 'ninety',
	        100                 => 'hundred',
	        1000                => 'thousand',
	        1000000             => 'million',
	        1000000000          => 'billion',
	        1000000000000       => 'trillion',
	        1000000000000000    => 'quadrillion',
	        1000000000000000000 => 'quintillion'
	    ];
	    
	    if (!is_numeric($number)) {
	        return false;
	    }
	    
	    if ($number < 0) {
	        return $negative . $this->numberToWords(abs($number));
	    }
	    
	    $string = $fraction = null;
	    
	    if (strpos($number, '.') !== false) {
	        list($number, $fraction) = explode('.', (string)$number);
	    }
	    
	    switch (true) {
	        case $number < 21:
	            $string = $dictionary[$number];
	            break;
	        case $number < 100:
	            $tens   = ((int) ($number / 10)) * 10;
	            $units  = $number % 10;
	            $string = $dictionary[$tens];
	            if ($units) {
	                $string .= $hyphen . $dictionary[$units];
	            }
	            break;
	        case $number < 1000:
	            $hundreds  = (int) ($number / 100);
	            $remainder = $number % 100;
	            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
	            if ($remainder) {
	                $string .= $conjunction . $this->numberToWords($remainder);
	            }
	            break;
	        default:
	            $baseUnit = pow(1000, floor(log($number, 1000)));
	            $numBaseUnits = (int) ($number / $baseUnit);
	            $remainder = $number % $baseUnit;
	            $string = $this->numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
	            if ($remainder) {
	                $string .= $remainder < 100 ? $conjunction : $separator;
	                $string .= $this->numberToWords($remainder);
	            }
	            break;
	    }
	    
	    if ($fraction !== null && is_numeric($fraction)) {
	        $string .= $decimal;
	        $fraction = rtrim($fraction, '0'); // remove any trailing zeroes
	        foreach (str_split((string) $fraction) as $digit) {
	            $string .= $dictionary[$digit] . ' ';
	        }
	        $string = rtrim($string); // remove any trailing space
	    }
	    
	    return $string;
	}

	function numberToWordsIndian(float $number) 
	{
		$decimal = round($number - ($no = floor($number)), 2) * 100;
	    $decimal = round($decimal); // Ensure decimal is an integer
	    $decimal_part = $decimal;
	    $hundred = null;
	    $digits_length = strlen($no);
	    $str = array();
	    $words = array(
	        0 => '', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 
	        5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
	        10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
	        14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
	        18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
	        40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy',
	        80 => 'eighty', 90 => 'ninety'
	    );
	    $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');

	    $i = 0;
	    while ($i < $digits_length) {
	        $divider = ($i == 2) ? 10 : 100;
	        $number = floor($no % $divider);
	        $no = floor($no / $divider);
	        $i += $divider == 10 ? 1 : 2;

	        if ($number) {
	            $plural = (count($str) && $number > 9) ? 's' : null;
	            $hundred = (count($str) == 1 && $str[0]) ? ' and ' : null;
	            $str[] = ($number < 21) 
	                ? $words[$number] . ' ' . $digits[count($str)] . $plural . ' ' . $hundred
	                : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[count($str)] . $plural . ' ' . $hundred;
	        } else {
	            $str[] = null;
	        }
	    }

	    $Rupees = implode('', array_reverse($str));
	    $paise = ($decimal_part > 0) 
	        ? $words[floor($decimal_part / 10) * 10] . ' ' . $words[$decimal_part % 10] . ' Paise' 
	        : '';

	    return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise;
	}


	public function compressImage($source, $destination, $quality) 
	{
	     	$info = getimagesize($source);

		    if ($info === false) {
		        // If the image is not valid, return null
		        return null;
		    }

		    $image = null;
		    switch ($info['mime']) {
		        case 'image/jpeg':
		            $image = imagecreatefromjpeg($source);
		            break;
		        case 'image/gif':
		            $image = imagecreatefromgif($source);
		            break;
		        case 'image/png':
		            $image = imagecreatefrompng($source);
		            break;
		        default:
		            // Unsupported image type
		            return null;
		    }

		    // If image creation failed, return null
		    if ($image === false) {
		        return null;
		    }

		    // Compress the image
		    imagejpeg($image, $destination, $quality);

		    // Free up memory
		    imagedestroy($image);

		    return true;
	}

	public function getAddress($latitude, $longitude, $apiKey) 
	{
	    // Google Maps Geocoding API URL
	    $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&key=$apiKey";
	    
	    // Send the request
	    $response = file_get_contents($url);
	    $json = json_decode($response, true);
	    
	    // Check if the response is OK
	    if ($json['status'] == 'OK') {
	        // Return the formatted address
	        return $json['results'][0]['formatted_address'];
	    } else {
	        return "Location not found";
	    }
	}

	public function getValueorNotSet($val)
	{
		if($val == "")
		{
			return "Not Set";
		}
		else
		{
			return $val;
		}
	}

	public function calculatePercentage($part,$total)
	{
		if($total == 0) 
		{
        	return 0; // Avoid division by zero
    	}
    	$percentage = ($part / $total) * 100;
    	return round($percentage, 2); // Round to 2 decimal places
	}
}

