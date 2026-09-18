<style type="text/css">

.selectednav{
  
  background-color:#eee;
  padding: 5px 3px;
 
}
#myDIV{
  
  display:flex;
  justify-content: space-between;
}
ol li{
  
  list-style: none;
}


 
</style>
<?php
$current_url = $_SERVER['REQUEST_URI'];
$current_page = substr($current_url, strrpos($current_url, '/') + 1);
switch($current_page)
{
	case 'enterprise_workspace.php':
	case 'enterprise_details.php' :
	case 'update_enterprise.php':
	case 'create_project.php':
	case 'view_projects.php':
	{
	?><ol class="breadcrumb top_nav " id="myDIV">
			<li class="breadcrumb-item" data-breadcrumb-seperator="-">
				<i class="fal fa-clipboard-list"></i> <a onclick="URLRouter('../enterprises/enterprise_workspace.php');">Dashboard</a>
			</li>
			<li class="breadcrumb-item " data-breadcrumb-seperator="-">
				<i class="fal fa-list"></i> <a onclick="URLRouter('../enterprises/enterprise_details.php');">View Details</a>
			</li>
			<li class="breadcrumb-item " data-breadcrumb-seperator="-">
				<i class="fal fa-plus"></i> <a onclick="URLRouter('../projects/create_project.php');">Create Project</a>
			</li>
			<li class="breadcrumb-item " data-breadcrumb-seperator="-">
			<i class="fal fa-list-alt"></i> <a onclick="URLRouter('../projects/view_projects.php');" >View Projects</a>
			</li>
			
		</ol>
	<?php
		break;
	}
	case 'project_details.php':
	case 'update_project.php':
	case 'project_workpace.php':
	{
	?><ol class="breadcrumb top_nav " id="myDIV">
			<li class="breadcrumb-item" data-breadcrumb-seperator="-">
				<i class="fal fa-clipboard-list"></i> <a onclick="URLRouter('../projects/project_workpace.php');">Workspace</a>
			</li>
			<li class="breadcrumb-item " data-breadcrumb-seperator="-">
				<i class="fal fa-list"></i> <a onclick="URLRouter('../projects/project_details.php');">Project Details</a>
			</li>
			<li class="breadcrumb-item " data-breadcrumb-seperator="-">
				<i class="fal fa-plus"></i> <a onclick="URLRouter('../projects/add_project_members.php');">Add Members</a>
			</li>
			
			
		</ol>
	<?php
		break;
	}
	
	default:
	{
	}
}
?>