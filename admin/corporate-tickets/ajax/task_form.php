<?php
$counter = 1;
if(isset($_POST['counter']))
{
    $counter = $_POST['counter'];
}
?>
<div class="row mt-2" id="task_row_<?php echo $counter;?>">
    <div class="col-3">
        <label>Task Name <span class="text-danger">*</span> </label>
        <textarea class="form-control" name="task_name[]" placeholder="Enter Task Description"></textarea>
    </div>

    
    <div class="col-2">
        <label> Start Date <span class="text-danger">*</span></label>
        <input type="text" class="form-control task_start_date" name="task_start_date[]" placeholder="Enter Task Start Date" />
    </div>

    <div class="col-2">
        <label>Expected End Date <span class="text-danger">*</span></label>
        <input type="text" class="form-control task_end_date" name="task_end_date[]" placeholder="Enter Task End Date" />
    </div>
    <div class="col-2">
        <label>TechXpert Cost </label>
        <input type="text" class="form-control" name="task_techxpert_cost[]" placeholder="Enter Techxpert Cost" />
    </div>
    <div class="col-2">
        <label>Customer Cost </label>
        <input type="text" class="form-control" name="task_customer_cost[]" placeholder="Enter Customer Cost" />
    </div>
    
    <?php 
    if($counter != 1)
    {
    ?>
        <div class="col-1">
            <a onclick="DeleteTask(<?php echo $counter;?>)" class="btn btn-danger mt-4 text-white">
              <i class="fas fa-minus"></i>
            </a>
        </div>
    <?php
    }
    ?>
</div>