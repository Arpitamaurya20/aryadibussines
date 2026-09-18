<?php
include('../../controllers/common_controllers.php');
include('../../enterprises/controller/enterprise_controller.php');
$conn = _connectodb();
$search_text = $_POST['search_text'];
$enterprises = getSearchedEnterprises($conn,$search_text)['data'];
if(sizeof($enterprises) == 0)
{
	?>
	<div class="card">
		<div class="card-body">
			<p class="card-title">No company found with your search. Please write your company name manually - <?php echo $search_text; ?> to join novologic</p>
			<button type="button" class="btn btn-info waves-effect waves-themed" onclick="SelectEnterprise('<?php echo $search_text;?>','');">Confirm</button>
		</div>
	</div>
	<?php
}
else
{
?>
<ul class="notification">
<?php 
foreach($enterprises as $enterprise)
{
	
?>
	<li class="unread">
		<a onclick="SelectEnterprise('<?php echo $enterprise['CompanyName'];?>','<?php echo $enterprise['EnterpriseID'];?>')" class="d-flex align-items-center">
			<span class="d-flex flex-column flex-1 ml-1">
				<span class="name"><?php echo $enterprise['CompanyName'];?></span>
				<span class="msg-a fs-sm"><?php echo $enterprise['AddressLine1'];?></span>
				<span class="msg-b fs-xs"><?php echo $enterprise['City'];?></span>
			</span>
		</a>
	</li>
<?php
}
?>
</ul>
<?php
}
?>