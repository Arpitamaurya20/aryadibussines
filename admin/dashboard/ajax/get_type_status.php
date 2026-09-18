<?php
@session_start();
include('../../includes/autoloader.inc.php');
$type_array = array('Projects','R&M','Supply');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$corporate_tickets_obj = new Corporateticket($conn);
$status_array = $corporate_tickets_obj->getCorporateTicketStatusArray('All');
$CorporateID = $_POST['CorporateID'];
?>
<div class="row">
	<div class="col-lg-6 col-xl-6 panel">
		<div class="panel-hdr">
		    <h2>
		        Type
		    </h2>
		</div>
		<div class="panel-container show">
			<div class="panel-content">
				<select class="form-control" name="ticket_type_filter" id="ticket_type_filter" onchange="GenerateBranchAnalytics(<?=$CorporateID;?>)">
                    <option value="">Select Type</option>
					<?php 
					foreach ($type_array as $type) 
					{
						?>
						<!-- <div class="row mt-2">
							<span class="badge badge-info"><?=$type;?></span>
						</div> -->
						<option value="<?=$type; ?>"><?=$type;?></option>
						<?php
					}
					?>
				</select>
			</div>
		</div>
	</div>
	<div class="col-lg-6 col-xl-6 panel">
		<div class="panel-hdr">
		    <h2>
		        Status
		    </h2>
		</div>
		<div class="panel-container show">
			<div class="panel-content">
				<div class="row">
					<select class="form-control" name="ticket_status_filter" id="ticket_status_filter" onchange="GenerateBranchAnalytics(<?=$CorporateID;?>)">
						<option value="">Select Status</option>
						<?php 
						foreach ($status_array as $status) 
						{
							?>
							<!-- <div class="col-lg-6 col-xl-6">
								<span class="badge badge-danger"><?=$status['Status'];?></span>
							</div> -->
							<option value="<?=$status['Status']; ?>"><?=$status['Status']; ?></option>
							<?php
						}
						?>
					</select>
				</div>
			</div>
		</div>
	</div>
</div>