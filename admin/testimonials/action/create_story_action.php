<?php
session_start();
## Database configuration
include('../../controllers/common_controllers.php');
include('../controller/story_controller.php');
$UserType = SessionCheck();
if(!($UserType == "Admin"))
{
	?>
		<script type="text/javascript">
		window.location.href = "../authentication/login.php";
		</script>
		<?php
}
$conn = _connectodb();
setTimeZone();
$current_date = date("Y-m-d");
$Name = $_POST['txtname'];
$Title = $_POST['txtTitle'];
$Location = $_POST['txtLocation'];
$Story = $_POST['txtStory'];

$extn=explode('.',$_FILES["txtStoryImage"]["name"]);
   // echo "You have total ".$totalSheet." sheets".
    /* For Loop for all sheets */

	  $sql_story 	= "INSERT into stories (id ,
		Name,
		Title,
		Location,
		Story
		Image
		)
		VALUES ('',
		'$Name',
		'$Title',
		'$Location',
		'$Story',
		'$extn[1]'
		)";
		$result_sql_story	= mysqli_query($conn, $sql_story);

		$last_id = $conn->insert_id;
		$story_Image   = "Story".$last_id.".".$extn[1];

		$upath="../../media/Story/"."Story".$last_id.".".$extn[1];

		move_uploaded_file($_FILES["txtStoryImage"]["tmp_name"],$upath);

		 $update_story="UPDATE stories SET Image='$story_Image' WHERE id ='$last_id' ";
        $result_story 	= mysqli_query($conn, $update_story);


//$response_block_code = CreateStory($conn,$Name,$Title,$Location,$Story);
  header('../view-stories.php');
//echo json_encode($response_block_code);

?>