<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$QuotationID = -1;
if(isset($_POST['QuotationID']))
{
    $QuotationID = $_POST['QuotationID'];
    $corporateticket_obj = new Corporateticket($conn);
    $quotation_history = $corporateticket_obj->GetQuotationHistory($QuotationID);
    if(sizeof($quotation_history) > 0)
    {
        ?>
        <div class="chat-segment chat-segment-get">
            <?php
            foreach($quotation_history as $q_history)
            {

            ?>
            <div class="chat-message mt-2 w-100">
                <p>
                  <strong>Status - <?php echo $q_history['QuotationStatus'];?></strong>
                  <?php 
                  if($q_history['Remarks'] != "")
                  {
                    echo "<p>".$q_history['Remarks']."</p>";
                  }
                  ?>
                </p>
                <div class="fw-300 text-muted mt-1 fs-xs">
                    <?php
                        echo "Updated By - ".$q_history['CreatedBy']." at ".$q_history['CreatedDate']." ".$q_history['CreatedTime']; 
                    ?>
                </div>
            </div>
            <?php
            }
            ?>
        </div>
        <?php
    }
    
}
else
{
?>
<div class="chat-segment chat-segment-get">
    <div class="chat-message">
        <p>
          No History of Quotation
        </p>
    </div>
</div>
<?php  
}

?>
