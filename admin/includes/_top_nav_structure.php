<style type="text/css">.navdata li.active {   color: #fff!important;   background:#584475;   padding:8px;}.navdata li.active a span {    color: #fff!important;}.navdata li {    list-style: none;    margin-right: 15px;}.top_nav #nav_view_users {    padding-top: 12%;}.top_nav {    padding-top: 5%;}

.nav-item span
{
	margin-left:5px;
}
.nav-item .fal
{
	color:#ccc; 
}
</style>
<?php
$current_url = $_SERVER['REQUEST_URI'];
$current_page = substr($current_url, strrpos($current_url, '/') + 1);
// Project pages
if(!isset($E_Role))
{
	$E_Role = "User";
}
if(!isset($P_Role))
{
	$P_Role = "User";
}

switch($current_page)
{
	case 'enterprise_workspace.php':
	case 'enterprise_details.php' :
	//case 'update_enterprise.php':
	//case 'create_project.php':
	case 'view_projects.php':
	case 'enterprise_certifications.php':
	case 'enterprise_data.php':
	case 'enterprise_equipment.php':
	case 'enterprise_finance.php':
	case 'enterprise_documents.php':
	case 'enterprise_hardware_mis.php':
	case 'enterprise_mis.php':
	case 'enterprise_organizations.php':
	case 'enterprise_users.php':
	{
		if($APPA || $E_Role =="EX")
		{
		?>
			<a onclick="URLRouter('../enterprises/update_enterprise.php');" class="text-white ml-4 btn btn-info cursor-pointer btn-icon rounded-circle">
				<i class="fal fa-edit"></i>
			</a>
			<a onclick="URLRouter('../projects/create_project.php');" class="text-white ml-2 btn btn-success btn-icon rounded-circle waves-effect waves-themed cursor-pointer">
				<i class="fal fa-plus"></i>
			</a>
			
		<?php
		}
		else
		{
		?>
			<a href="javascript:void(0);" class="text-white ml-4 btn btn-secondary cursor-auto btn-icon rounded-circle">
				<i class="fal fa-edit"></i>
			</a>
			<a class="text-white ml-2 btn btn-secondary btn-icon rounded-circle waves-effect waves-themed">
				<i class="fal fa-plus"></i>
			</a>
			
		<?php
		}
		
		break;
	}
	case 'project_details.php':
	case 'update_project.php' :
	case 'project_workspace.php':
	case 'project_members.php':
	case 'project_vendors.php':
	case 'project_documents.php':
	case 'project_certifications.php':
	case 'project_data.php':
	case 'project_documents.php':
	case 'project_equipment.php':
	case 'project_finance.php':
	case 'project_hardware_mis.php':
	case 'project_mis.php':
	case 'project_organizations.php':
	{
		
		if($APPA || $E_Role =="EX")
		{
		?>
			<a onclick="BasicURLRouter('../projects/update_project.php');" class="text-white ml-4 btn btn-info cursor-pointer btn-icon rounded-circle">
				<i class="fal fa-edit"></i>
			</a>
			<a onclick="BasicURLRouter('../projects/create_project.php');" class="text-white ml-2 btn btn-success btn-icon rounded-circle waves-effect waves-themed cursor-pointer">
				<i class="fal fa-plus"></i>
			</a>
			
		<?php
		}
		else if($P_Role =="PM" || $P_Role =="PX")
		{
			?>
			<a onclick="BasicURLRouter('../projects/update_project.php');" class="text-white ml-4 btn btn-info cursor-pointer btn-icon rounded-circle">
				<i class="fal fa-edit"></i>
			</a>
			<a class="ml-2 btn btn-secondary btn-icon rounded-circle waves-effect waves-themed text-white">
				<i class="fal fa-plus"></i>
			</a>
			
			<?php
		}
		else
		{
		?>
			<a href="javascript:void(0);" class="ml-4 btn btn-secondary cursor-auto btn-icon rounded-circle text-white">
				<i class="fal fa-edit"></i>
			</a>
			<a class="ml-2 btn btn-secondary btn-icon rounded-circle waves-effect waves-themed text-white">
				<i class="fal fa-plus"></i>
			</a>
			
		<?php
		}
		
		break;
	}
	case 'project_stake_holders.php':
	case 'add_project_stakeholders.php':
	case 'edit_project_stakeholders.php':
	{
		if($APPA || $E_Role =="EX" || $P_Role =="PM" || $P_Role =="PX")
		{
		?>
			<a onclick="BasicURLRouter('../projects/edit_project_stakeholders.php');" class="text-white ml-4 btn btn-info cursor-pointer btn-icon rounded-circle">
				<i class="fal fa-edit"></i>
			</a>
			<a onclick="BasicURLRouter('../projects/add_project_stakeholders.php');" class="text-white ml-2 btn btn-success btn-icon rounded-circle waves-effect waves-themed cursor-pointer">
				<i class="fal fa-plus"></i>
			</a>
			
		<?php
		}
		else if($P_Role =="PM" || $P_Role =="PX")
		{
			?>
			<a onclick="BasicURLRouter('../projects/update_project.php');" class="text-white ml-4 btn btn-info cursor-pointer btn-icon rounded-circle">
				<i class="fal fa-edit"></i>
			</a>
			<a class="ml-2 btn btn-secondary btn-icon rounded-circle waves-effect waves-themed text-white">
				<i class="fal fa-plus"></i>
			</a>
			
			<?php
		}
		else
		{
		?>
			<a href="javascript:void(0);" class="ml-4 btn btn-secondary cursor-auto btn-icon rounded-circle text-white">
				<i class="fal fa-edit"></i>
			</a>
			<a class="ml-2 btn btn-secondary btn-icon rounded-circle waves-effect waves-themed text-white">
				<i class="fal fa-plus"></i>
			</a>
			
		<?php
		}
		
		break;
	}
	case 'view_enterprises.php':
	//case 'create_enterprise.php':
	{

		if($APPA)
		{
		?>
			<a href="javascript:void(0);" class="ml-4 btn btn-secondary cursor-auto btn-icon rounded-circle">
				<i class="fal fa-edit"></i>
			</a>
			<a href="../enterprises/create_enterprise.php" class="ml-2 btn btn-success btn-icon rounded-circle waves-effect waves-themed">
				<i class="fal fa-plus"></i>
			</a>
		<?php
		}
		else
		{
		?>
			<a href="javascript:void(0);" class="ml-4 btn btn-secondary cursor-auto btn-icon rounded-circle">
				<i class="fal fa-edit"></i>
			</a>
			<a href="../enterprises/create_enterprise.php" class="ml-2 btn btn-secondary btn-icon rounded-circle waves-effect waves-themed">
				<i class="fal fa-plus"></i>
			</a>
		<?php
		}
		?>
		
		<?php
		break;
	}
	
	default:
	{
	}
	
}
?>
