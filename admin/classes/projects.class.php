<?php 
class Projects extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}
	public function SaveProject($data)
	{
		$project_name = $data['project_name'];
		$project_start_date = $data['project_start_date'];
		$project_end_date = $data['project_end_date'];
		$project_manager = $data['project_manager'];
		$CreatedBy = $data['CreatedBy'];
		$TicketReference = "";
		if(isset($data['ticket_reference']))
		{
			$TicketReference = $data['ticket_reference'];
		}
		$CreatedDate = date('Y-m-d');
		$CreatedTime = date('H:i:s');
		$project_status = $data['project_status'];
		if($data['form_action'] == "add")
		{
			
			$insert_project_sql = "INSERT INTO projects(ProjectName,ProjectManager,StartDate,EndDate,TicketNumber,CreatedBy,CreatedDate,CreatedTime,Status) VALUES ('$project_name',$project_manager,'$project_start_date','$project_end_date','$TicketReference','$CreatedBy','$CreatedDate','$CreatedTime','$project_status')";
			$response = $this->_InsertTableRecords($this->conn,$insert_project_sql);

			if($project_manager != -1)
			{
				// check if the current project manager is already associated
				$filter = " where EmployeeID = $project_manager and Role = 'Project Manager'";
				if($this->check_unique_identity_filter($this->conn,'user_roles', $filter))
				{
					$sql_insert_project_manager_role = "INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($project_manager,'Project Manager','$CreatedDate','$CreatedTime')";
					$response = $this->_InsertTableRecords($this->conn, $sql_insert_project_manager_role);
				}
			}
		}
		if($data['form_action'] == "edit")
		{
			if(isset($_POST['ticket_reference_edit']))
			{
				if($_POST['ticket_reference_edit'] != "")
				{
					$TicketReference = $_POST['ticket_reference_edit'];
				}
			}
			$ProjectID = $data['form_id'];
			// get old project manager
			$old_project_details = $this->GetProjectDetails($ProjectID);
			if($old_project_details['ProjectManager'] != $project_manager)
		    {
		    	$old_project_manager = $old_project_details['ProjectManager'];
		    	$filter = " where ProjectManager = $old_project_manager";
		    	// check if the old member is already project manager
		    	// To check the old member already project manager
		    	if($this->_getTotalRows($this->conn,'projects',$filter) > 0)
		    	{
		    		// do nothing for old
		    	}
		    	else
		    	{
		    		// Remove role of the old one
		    		$query_parameter = " where Role='Project Manager' and EmployeeID = $old_project_manager";
		    		$this->delete_identity_filter($this->conn,"user_roles",$query_parameter);
		    	}

		    	// Check if new one is already Account Manager of any other Account
		    	if($project_manager != -1)
		    	{
			    	$filter = " where ProjectManager = $project_manager";
			    	if($this->_getTotalRows($this->conn,'projects',$filter) > 1)
			    	{
			    		// do nothing for new
			    	}
			    	else
			    	{
			    		// Insert new role
			    		$sql_insert_project_manager_role = "INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($project_manager,'Project Manager','$CreatedDate','$CreatedTime')";
						$this->_InsertTableRecords($this->conn, $sql_insert_project_manager_role);
			    	}
		    	}		
		    }
		    $update_project_sql = " ProjectName = '$project_name',ProjectManager = $project_manager,StartDate = '$project_start_date',EndDate = '$project_end_date',Status='$project_status',TicketNumber = '$TicketReference' where ID = $ProjectID";
			$response = $this->_UpdateTableRecords($this->conn,'projects',$update_project_sql);
			
		}

		return $response;
	}
	public function DeleteProject($data)
	{
		$response = array();
		$ProjectID = $data['ProjectID'];
		// Delete all subtasks associated
		$query = " where TaskID IN (Select ID from project_tasks where ProjectID = $ProjectID)";
		$response['no_error'] = $this->delete_identity_filter($this->conn,'project_task_date_progress', $query);

		if($response['no_error'] == true)
		{
			// Delete all tasks associated
			$query = " where ProjectID = $ProjectID";
			$response['no_error'] = $this->delete_identity_filter($this->conn,'project_tasks', $query);
		}
		if($response['no_error'] == true)
		{
			// Delete the project
			$query = " where ID = $ProjectID";
			$response['no_error'] = $this->delete_identity_filter($this->conn,'projects', $query);
		}
		if($response['no_error'] == true)
		{
			$response['message'] = "Project deleted!";
		}
		else
		{
			$response['message'] = "Technical Problem. Please try again";
		}
		return $response;

	}
	public function GetProjectDetails($ProjectID)
	{
		return $this->_getTableDetails($this->conn,'projects',' where ID = '.$ProjectID);
	}
	public function SaveTasks($data)
	{
		$response = array();
		$response['error'] = false;
		$CreatedDate = date("Y-m-d");
		$CreatedTime = date("H:i:s");
		$CreatedBy = $data['CreatedBy'];
		$ProjectID = $data['project_id'];
		$task_name = $data['task_name'];
		$task_start_date = $data['task_start_date'];
		$task_end_date = $data['task_end_date'];
		$task_techxpert_cost = $data['task_techxpert_cost'];
		$task_customer_cost = $data['task_customer_cost'];
		//var_dump($data);
		$no_of_tasks = sizeof($task_name);
		for($i=0;$i<$no_of_tasks;$i++)
		{
			$task_name_i = $this->cleantext($task_name[$i]);
			$task_start_date_i = $task_start_date[$i];
			$task_end_date_i = $task_end_date[$i];
			$task_techxpert_cost = $task_techxpert_cost[$i];
			$task_customer_cost = $task_customer_cost[$i];
			if($task_name_i == ""||$task_start_date_i == ""||$task_end_date_i == "")
			{
				$response['error'] = true;
				$response['message'] = "Parameters can't be empty";
				return $response;
			}
		}

		for($i=0;$i<$no_of_tasks;$i++)
		{
			$task_name_i = $this->cleantext($task_name[$i]);
			$task_start_date_i = $task_start_date[$i];
			$task_end_date_i = $task_end_date[$i];
			$task_techxpert_cost = "";
			$task_customer_cost = "";
			if(isset($task_techxpert_cost[$i]))
				$task_techxpert_cost = $task_techxpert_cost[$i];
			if(isset($task_customer_cost[$i]))
				$task_customer_cost = $task_customer_cost[$i];
			
			$insert_task_sql = "INSERT INTO project_tasks(ProjectID,Description,StartDate,EndDate,InternalCost,ExternalCost,CreatedDate,CreatedTime) VALUES ($ProjectID,'$task_name_i','$task_start_date_i','$task_end_date_i','$task_techxpert_cost','$task_customer_cost','$CreatedDate','$CreatedTime')";
			$response_insert = $this->_InsertTableRecords($this->conn,$insert_task_sql);
			if($response_insert['error'] == true)
			{
				$response['error'] = true;
				$response_message = $response_message."\n Error with Task Name ".$task_name_i;
			}
			else
			{
				$TaskID = $response_insert['last_insert_id'];
				// Insert Daily Progress dates
				$StartDateTime = new DateTime($task_start_date_i);
				$EndDateTime = new DateTime($task_end_date_i);

				$generatedDates = array();

				while($StartDateTime <= $EndDateTime)
				{
					$generatedDates[] = $StartDateTime->format('Y-m-d');
					$StartDateTime->modify('+1 day');
				}

				foreach($generatedDates as $generatedDate)
				{
					$sql_insert_daily_progress = " INSERT INTO project_task_date_progress(TaskID,TaskDate,Remarks,UpdatedBy,UpdatedDate,UpdatedTime) VALUES ($TaskID,'$generatedDate','','$CreatedBy','$CreatedDate','$CreatedTime')";
					$this->_InsertTableRecords($this->conn,$sql_insert_daily_progress);
				}
				
			}
		}
		if($response['error'] == false)
		{
			$response['message'] = "Tasks addded in the Projects";
		}
		else
		{
			$response['message'] = $response_message;
		}
		return $response;
	}
	public function DeleteProjectTask($data)
	{
		$response = array();
		$TaskID = $data['TaskID'];
		// Delete all subtasks associated
		$query = " where TaskID = $TaskID";
		$response['no_error'] = $this->delete_identity_filter($this->conn,'project_task_date_progress', $query);

		if($response['no_error'] == true)
		{
			// Delete all tasks associated
			$query = " where ID = $TaskID";
			$response['no_error'] = $this->delete_identity_filter($this->conn,'project_tasks', $query);
		}
		if($response['no_error'] == true)
		{
			$response['message'] = "Task deleted!";
		}
		else
		{
			$response['message'] = "Technical Problem. Please try again";
		}
		return $response;

	}
	public function GetTasksDailyProgress($data)
	{
		$TaskID = $data['TaskID'];
		$where = " where TaskID = $TaskID ORDER BY TaskDate ASC";
		$daily_progress_for_task_array = $this->_getTableRecords($this->conn,'project_task_date_progress',$where);
		return $daily_progress_for_task_array;
	}

	public function SaveTaskProgress($data)
	{
		$response = array();
		$response['error'] = false;
		$task_data = array();
		$task_data['TaskID'] = $data['task_id'];
		$TaskID = $data['task_id'];
		$daily_progress_for_task_array = $this->GetTasksDailyProgress($task_data);
		$no_of_subtasks = sizeof($daily_progress_for_task_array);
		$flag_task_update = 0;
		foreach($daily_progress_for_task_array as $daily_progress)
		{
			$Status_to_be_updated = 0;
			extract($daily_progress);
			$form_task_date = "task_completion_date_".$TaskDate;
			$form_remarks = "task_remarks_".$TaskDate;
			if(isset($data[$form_task_date]))
			{
				$flag_task_update = 1;
				$Status_to_be_updated = 1;
				$Remarks_to_be_udpated = $data[$form_remarks];
				$sql_update = " Status = $Status_to_be_updated,Remarks='$Remarks_to_be_udpated' where ID=$ID";
				$this->_UpdateTableRecords($this->conn,'project_task_date_progress',$sql_update);
			}
			else
			{
				$Status_to_be_updated = 0;
				$sql_update = " Status = $Status_to_be_updated where ID=$ID";
				$this->_UpdateTableRecords($this->conn,'project_task_date_progress',$sql_update);
			}
		}
		$TaskStatus = $data['project_task_status'];
		if(($TaskStatus == "To Start" || $TaskStatus == "On Hold") && $flag_task_update)
		{
			$TaskStatus = "In Progress";
		}
		if(($TaskStatus == "Completed" || $TaskStatus == "In Progress") && $flag_task_update == 0)
		{
			$TaskStatus = "To Start";
			$Actual_Task_EndDate = "";
		}
		if($TaskStatus == "Completed")
		{
			$Actual_Task_EndDate = date("Y-m-d");
			$sql_update_task = " TaskStatus = '$TaskStatus',ActualEndDate = '$Actual_Task_EndDate' where ID = $TaskID";
		}
		else
		{
			$sql_update_task = " TaskStatus = '$TaskStatus' where ID = $TaskID";
		}
		$this->_UpdateTableRecords($this->conn,'project_tasks',$sql_update_task);
		$response['message'] = "Task Progress has been updated";
		return $response;
	}
	public function GetTaskProgress($TaskID,$TaskStatus)
	{
		if($TaskStatus == "Completed")
		{
			return 100;
		}
		else
		{
			$filter = " where IsActive = 1 and TaskID = $TaskID";
			$total_sub_tasks = $this->_getTotalRows($this->conn,'project_task_date_progress', $filter);

			$filter = " where Status=1 and IsActive = 1 and TaskID = $TaskID";
			$total_completed = $this->_getTotalRows($this->conn,'project_task_date_progress', $filter);

			if($total_completed == 0)
			{
				return 0;
			}
			else
			{
				return intval($total_completed)/intval($total_sub_tasks)*100;
			}
		}

	}

	public function GetProjectDetailsBYTicketID($ProjectID)
	{
		return $this->_getTableDetails($this->conn,'projects',' where TicketID = '.$ProjectID);
	}

	public function SaveTasksNew($data)
{
    $response = array();
    $response['error'] = false;

    $CreatedDate = date("Y-m-d");
    $CreatedTime = date("H:i:s");
    $CreatedBy   = $data['CreatedBy'];
    $TicketID    = $data['ticket_id'];

    $task_name            = $data['task_name'];
    $task_start_date      = $data['task_start_date'];
    $task_end_date        = $data['task_end_date'];
    $task_techxpert_cost  = $data['task_techxpert_cost'];
    $task_customer_cost   = $data['task_customer_cost'];

    $no_of_tasks = sizeof($task_name);

    // ----------- VALIDATION -------------
    for ($i = 0; $i < $no_of_tasks; $i++) {

        $task_name_i       = $this->cleantext($task_name[$i]);
        $task_start_date_i = $task_start_date[$i];
        $task_end_date_i   = $task_end_date[$i];

        if ($task_name_i == "" || $task_start_date_i == "" || $task_end_date_i == "") {
            $response['error'] = true;
            $response['message'] = "Parameters can't be empty";
            return $response;
        }
    }

    // ----------- INSERTING DATA -------------
    for ($i = 0; $i < $no_of_tasks; $i++) {

        $task_name_i       = $this->cleantext($task_name[$i]);
        $task_start_date_i = $task_start_date[$i];
        $task_end_date_i   = $task_end_date[$i];

        // FIXED: DO NOT OVERWRITE THE ARRAY
        $task_techxpert_cost_i = isset($task_techxpert_cost[$i]) ? $task_techxpert_cost[$i] : "";
        $task_customer_cost_i  = isset($task_customer_cost[$i]) ? $task_customer_cost[$i] : "";

        $insert_task_sql = "
            INSERT INTO project_tasks
            (TicketID, Description, StartDate, EndDate, InternalCost, ExternalCost, CreatedDate, CreatedTime)
            VALUES
            ($TicketID, '$task_name_i', '$task_start_date_i', '$task_end_date_i', '$task_techxpert_cost_i', '$task_customer_cost_i', '$CreatedDate', '$CreatedTime')
        ";

        $response_insert = $this->_InsertTableRecords($this->conn, $insert_task_sql);

        if ($response_insert['error'] == true) {

            $response['error'] = true;
            $response_message .= "\n Error with Task Name: " . $task_name_i;

        } else {

            $TaskID = $response_insert['last_insert_id'];

            // Generate date range
            $StartDateTime = new DateTime($task_start_date_i);
            $EndDateTime   = new DateTime($task_end_date_i);

            $generatedDates = array();

            while ($StartDateTime <= $EndDateTime) {
                $generatedDates[] = $StartDateTime->format('Y-m-d');
                $StartDateTime->modify('+1 day');
            }

            // Insert daily progress rows
            foreach ($generatedDates as $generatedDate) {
                $sql_insert_daily_progress = "
                    INSERT INTO project_task_date_progress
                    (TaskID, TaskDate, Remarks, UpdatedBy, UpdatedDate, UpdatedTime)
                    VALUES
                    ($TaskID, '$generatedDate', '', '$CreatedBy', '$CreatedDate', '$CreatedTime')
                ";

                $this->_InsertTableRecords($this->conn, $sql_insert_daily_progress);
            }
        }
    }

    if ($response['error'] == false) {
        $response['message'] = "Tasks addded in the Projects";
    } else {
        $response['message'] = $response_message;
    }

    return $response;
}


		public function GetTaskProgressNew($TaskID)
{
    // Fetch all task progress records for the given TaskID
    $sql = "SELECT Status FROM project_task_date_progress WHERE IsActive = 1 AND TaskID = $TaskID";
    $records = $this->_getSQLRecords($this->conn, $sql);

    if (empty($records)) {
        return 0;
    }

    $total_status = 0;
    $total_tasks = count($records);

    foreach ($records as $row) {
        $total_status += intval($row['Status']);
    }

    // Calculate average percentage
    $progress = ($total_status / (1 * 100)) * 100;
    if($progress>=100)
    {
    	$progress=100;
    }

    return round($progress, 2); // returns value like 25.00
}

public function SaveTaskProgressNew($data)
	{
	    $response = array();
	    $response['error'] = false;

	    $task_data = array();
	    $task_data['TaskID'] = $data['task_id'];
	    $TaskID = $data['task_id'];

	    // Get daily progress records for this task
	    $daily_progress_for_task_array = $this->GetTasksDailyProgress($task_data);
	    $no_of_subtasks = sizeof($daily_progress_for_task_array);
	    $flag_task_update = 0;

	    foreach ($daily_progress_for_task_array as $daily_progress)
	     {
	        extract($daily_progress);
	        $form_task_completion = "task_completion_" . $TaskDate;
	        $form_remarks = "task_remarks_" . $TaskDate;
	        $form_evidence = "task_evidence_" . $TaskDate; // ✅ fixed field name

	        $Status_to_be_updated = isset($data[$form_task_completion]) ? intval($data[$form_task_completion]) : 0;
	        $Remarks_to_be_updated = isset($data[$form_remarks]) ? addslashes($data[$form_remarks]) : '';

	        if ($Status_to_be_updated > 0) {
	            $flag_task_update = 1;
	        }

	        // ✅ Update task progress for each day
	        $sql_update = " Status = $Status_to_be_updated, Remarks = '$Remarks_to_be_updated' WHERE ID = $ID";
	        $this->_UpdateTableRecords($this->conn, 'project_task_date_progress', $sql_update);
        }
	   
	    $total_completion = 0;
	    foreach ($daily_progress_for_task_array as $daily_progress) {
	        $total_completion += intval($data["task_completion_" . $daily_progress['TaskDate']] ?? 0);
	    }

	    $avg_completion = $no_of_subtasks > 0 ? ($total_completion / 1) : 0;

	    // ✅ Determine new task status dynamically
	    if ($avg_completion <= 0) {
	        $TaskStatus = "To Start";
	        $Actual_Task_EndDate = "";
	    } elseif ($avg_completion > 0 && $avg_completion < 100) {
	        $TaskStatus = "In Progress";
	        $Actual_Task_EndDate = "";
	    } else {
	        $TaskStatus = "Completed";
	        $Actual_Task_EndDate = date("Y-m-d");
	    }

	    // ✅ Update main task record
	    if ($TaskStatus == "Completed") {
	        $sql_update_task = " TaskStatus = '$TaskStatus', ActualEndDate = '$Actual_Task_EndDate' WHERE ID = $TaskID";
	    } else {
	        $sql_update_task = " TaskStatus = '$TaskStatus' WHERE ID = $TaskID";
	    }

	    $this->_UpdateTableRecords($this->conn, 'project_tasks', $sql_update_task);

	    // ✅ Return final response
	    $response['message'] = "Task Progress and Evidence have been updated successfully.";
	    $response['average_completion'] = round($avg_completion, 2);
	    $response['task_status'] = $TaskStatus;

	    return $response;
	}

}